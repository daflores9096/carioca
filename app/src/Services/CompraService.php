<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CompraRepository;
use App\Repositories\ItemRepository;
use App\Support\DbText;
use App\Support\HttpException;
use App\Support\ListQuery;

final class CompraService
{
    private const SORTABLE = ['cod_fac', 'fecha_fac', 'nom_pro', 'total_fac'];

    public function __construct(
        private readonly CompraRepository $compras,
        private readonly StockService $stock,
        private readonly ItemRepository $items,
    ) {
    }

    public function list(array $params): array
    {
        if (!isset($params['dir'])) {
            $params['dir'] = 'desc';
        }
        $query = new ListQuery($params, self::SORTABLE, 'fecha_fac');
        $result = $this->compras->paginate($query);
        $result['data'] = array_map([$this, 'normalizeHeader'], $result['data']);
        return $result;
    }

    public function get(int $codFac): array
    {
        $header = $this->compras->findHeader($codFac);
        if ($header === null) {
            throw new HttpException('Compra no encontrada.', 404);
        }
        return [
            'compra' => $this->normalizeHeader($header),
            'lines' => array_map([$this, 'normalizeLine'], $this->compras->findLines($codFac)),
        ];
    }

    public function nextCode(): array
    {
        return ['next_cod_fac' => $this->compras->nextCode()];
    }

    public function create(array $input): array
    {
        if (!isset($input['cod_fac']) || (int) $input['cod_fac'] <= 0) {
            $input['cod_fac'] = $this->compras->nextCode();
        }
        $dto = $this->validate($input, true);
        if ($this->compras->exists($dto['header']['cod_fac'])) {
            throw new HttpException('Ya existe una compra con ese número.', 409);
        }
        $this->persistNew($dto);
        return $this->get($dto['header']['cod_fac']);
    }

    public function replace(int $codFac, array $input): array
    {
        if (!$this->compras->exists($codFac)) {
            throw new HttpException('Compra no encontrada.', 404);
        }
        $dto = $this->validate($input, false);
        $dto['header']['cod_fac'] = $codFac;
        $this->reverseStock($codFac);
        $this->compras->deleteLines($codFac);
        $this->stock->deleteMovimientos($codFac, 'C');
        $this->compras->deleteHeader($codFac);
        $this->persistNew($dto);
        return $this->get($codFac);
    }

    public function void(int $codFac): void
    {
        if (!$this->compras->exists($codFac)) {
            throw new HttpException('Compra no encontrada.', 404);
        }
        $this->reverseStock($codFac);
        $this->compras->deleteLines($codFac);
        $this->stock->deleteMovimientos($codFac, 'C');
        $this->compras->deleteHeader($codFac);
    }

    private function reverseStock(int $codFac): void
    {
        foreach ($this->compras->findLines($codFac) as $line) {
            $this->stock->adjustExistencia((string) $line['cod_item'], -(float) $line['cant_fac']);
        }
    }

    /**
     * @param array{header:array, lines:list<array>} $dto
     */
    private function persistNew(array $dto): void
    {
        $h = $dto['header'];
        $this->compras->insertHeader($h);
        foreach ($dto['lines'] as $line) {
            $this->compras->insertLine([
                'cod_fac' => $h['cod_fac'],
                'cod_item' => $line['cod_item'],
                'cant_fac' => $line['cant_fac'],
                'precio_uni' => $line['precio_uni'],
                'precio_ven' => $line['precio_ven'],
                'importe_fac' => $line['importe_fac'],
            ]);
            // Compra: stock + ; movimiento tipo C (corrige bug legacy de add-line con 'V')
            $this->stock->adjustExistencia($line['cod_item'], $line['cant_fac']);
            $this->stock->insertMovimiento([
                'tipo_mov' => 'C',
                'cod_mov' => $h['cod_fac'],
                'cod_item' => $line['cod_item'],
                'fecha_mov' => $h['fecha_fac'],
                'cod_cli_pro' => $h['cod_pro'],
                'nom_cli_pro' => mb_substr($h['nom_pro'], 0, 60),
                'entrada' => $line['cant_fac'],
                'salida' => 0.0,
            ]);
        }
    }

    /**
     * @return array{header:array, lines:list<array>}
     */
    private function validate(array $input, bool $requireCod): array
    {
        $codFac = (int) ($input['cod_fac'] ?? 0);
        if ($requireCod && $codFac <= 0) {
            throw new HttpException('El número de compra (cod_fac) es obligatorio.', 422);
        }

        $fecha = trim((string) ($input['fecha_fac'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new HttpException('fecha_fac inválida (YYYY-MM-DD).', 422);
        }

        $codPro = (int) ($input['cod_pro'] ?? 0);
        $nomPro = DbText::forStorage((string) ($input['nom_pro'] ?? ''));
        if ($nomPro === '') {
            throw new HttpException('El proveedor es obligatorio.', 422);
        }

        $rawLines = $input['lines'] ?? [];
        if (!is_array($rawLines)) {
            throw new HttpException('lines debe ser un arreglo.', 422);
        }

        $lines = [];
        $total = 0.0;
        foreach ($rawLines as $i => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $codItem = trim((string) ($raw['cod_item'] ?? ''));
            if ($codItem === '') {
                throw new HttpException("Línea {$i}: cod_item obligatorio.", 422);
            }
            if ($this->items->find($codItem) === null) {
                throw new HttpException("Línea {$i}: artículo {$codItem} no existe.", 422);
            }
            $cant = $this->num($raw['cant_fac'] ?? 0);
            $precio = $this->num($raw['precio_uni'] ?? 0);
            $precioVen = array_key_exists('precio_ven', $raw)
                ? $this->num($raw['precio_ven'])
                : round($precio * 1.40, 2);
            // Legacy UI: importe = cant * precio_ven
            $importe = array_key_exists('importe_fac', $raw)
                ? $this->num($raw['importe_fac'])
                : round($cant * $precioVen, 2);
            if ($cant <= 0) {
                throw new HttpException("Línea {$i}: cantidad debe ser > 0.", 422);
            }
            $lines[] = [
                'cod_item' => $codItem,
                'cant_fac' => $cant,
                'precio_uni' => $precio,
                'precio_ven' => $precioVen,
                'importe_fac' => $importe,
            ];
            $total += $importe;
        }

        return [
            'header' => [
                'cod_fac' => $codFac,
                'fecha_fac' => $fecha,
                'cod_pro' => $codPro,
                'nom_pro' => mb_substr($nomPro, 0, 50),
                'total_fac' => array_key_exists('total_fac', $input) ? $this->num($input['total_fac']) : round($total, 2),
            ],
            'lines' => $lines,
        ];
    }

    private function num(mixed $v): float
    {
        if (is_string($v)) {
            $v = str_replace(',', '.', $v);
        }
        return round((float) $v, 2);
    }

    private function normalizeHeader(array $row): array
    {
        return [
            'cod_fac' => (int) $row['cod_fac'],
            'fecha_fac' => (string) $row['fecha_fac'],
            'cod_pro' => (int) $row['cod_pro'],
            'nom_pro' => (string) $row['nom_pro'],
            'total_fac' => (float) $row['total_fac'],
        ];
    }

    private function normalizeLine(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'cod_item' => (string) $row['cod_item'],
            'nom_item' => (string) ($row['nom_item'] ?? ''),
            'unid_item' => (string) ($row['unid_item'] ?? ''),
            'caja_item' => (float) ($row['caja_item'] ?? 0),
            'cant_fac' => (float) $row['cant_fac'],
            'precio_uni' => (float) $row['precio_uni'],
            'precio_ven' => (float) $row['precio_ven'],
            'importe_fac' => (float) $row['importe_fac'],
        ];
    }
}
