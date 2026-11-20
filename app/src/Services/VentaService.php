<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ItemRepository;
use App\Repositories\VentaRepository;
use App\Support\DbText;
use App\Support\HttpException;
use App\Support\ListQuery;

final class VentaService
{
    private const SORTABLE = ['cod_fac', 'fecha_fac', 'nom_cli', 'total_fac', 'tot_bul'];

    public function __construct(
        private readonly VentaRepository $ventas,
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
        $result = $this->ventas->paginate($query);
        $result['data'] = array_map([$this, 'normalizeHeader'], $result['data']);
        return $result;
    }

    public function get(int $codFac): array
    {
        $header = $this->ventas->findHeader($codFac);
        if ($header === null) {
            throw new HttpException('Venta no encontrada.', 404);
        }
        return [
            'venta' => $this->normalizeHeader($header),
            'lines' => array_map([$this, 'normalizeLine'], $this->ventas->findLines($codFac)),
        ];
    }

    public function nextCode(): array
    {
        return ['next_cod_fac' => $this->ventas->nextCode()];
    }

    public function create(array $input): array
    {
        if (!isset($input['cod_fac']) || (int) $input['cod_fac'] <= 0) {
            $input['cod_fac'] = $this->ventas->nextCode();
        }
        $dto = $this->validate($input, true);
        if ($this->ventas->exists($dto['header']['cod_fac'])) {
            throw new HttpException('Ya existe una venta con ese número.', 409);
        }
        $this->persistNew($dto);
        return $this->get($dto['header']['cod_fac']);
    }

    public function replace(int $codFac, array $input): array
    {
        if (!$this->ventas->exists($codFac)) {
            throw new HttpException('Venta no encontrada.', 404);
        }
        $dto = $this->validate($input, false);
        $dto['header']['cod_fac'] = $codFac;
        $this->reverseStock($codFac);
        $this->ventas->deleteLines($codFac);
        $this->stock->deleteMovimientos($codFac, 'V');
        $this->ventas->deleteHeader($codFac);
        $this->persistNew($dto);
        return $this->get($codFac);
    }

    public function void(int $codFac): void
    {
        if (!$this->ventas->exists($codFac)) {
            throw new HttpException('Venta no encontrada.', 404);
        }
        $this->reverseStock($codFac);
        $this->ventas->deleteLines($codFac);
        $this->stock->deleteMovimientos($codFac, 'V');
        $this->ventas->deleteHeader($codFac);
    }

    private function reverseStock(int $codFac): void
    {
        foreach ($this->ventas->findLines($codFac) as $line) {
            $this->stock->adjustExistencia((string) $line['cod_item'], (float) $line['cant_fac']);
        }
    }

    /**
     * @param array{header:array, lines:list<array>} $dto
     */
    private function persistNew(array $dto): void
    {
        $h = $dto['header'];
        $this->ventas->insertHeader($h);
        foreach ($dto['lines'] as $line) {
            $this->ventas->insertLine([
                'cod_fac' => $h['cod_fac'],
                'cod_item' => $line['cod_item'],
                'bultos' => $line['bultos'],
                'cant_fac' => $line['cant_fac'],
                'precio_uni' => $line['precio_uni'],
                'importe_fac' => $line['importe_fac'],
            ]);
            $this->stock->adjustExistencia($line['cod_item'], -$line['cant_fac']);
            $this->stock->insertMovimiento([
                'tipo_mov' => 'V',
                'cod_mov' => $h['cod_fac'],
                'cod_item' => $line['cod_item'],
                'fecha_mov' => $h['fecha_fac'],
                'cod_cli_pro' => $h['cod_cli'],
                'nom_cli_pro' => mb_substr($h['nom_cli'], 0, 60),
                'entrada' => 0.0,
                'salida' => $line['cant_fac'],
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
            throw new HttpException('El número de venta (cod_fac) es obligatorio.', 422);
        }

        $fecha = trim((string) ($input['fecha_fac'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new HttpException('fecha_fac inválida (YYYY-MM-DD).', 422);
        }

        $codCli = (int) ($input['cod_cli'] ?? 0);
        $nomCli = DbText::forStorage((string) ($input['nom_cli'] ?? ''));
        if ($nomCli === '') {
            throw new HttpException('El cliente es obligatorio.', 422);
        }

        $rawLines = $input['lines'] ?? [];
        if (!is_array($rawLines)) {
            throw new HttpException('lines debe ser un arreglo.', 422);
        }

        $lines = [];
        $total = 0.0;
        $totBul = 0.0;
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
            $bultos = $this->num($raw['bultos'] ?? 0);
            $importe = array_key_exists('importe_fac', $raw)
                ? $this->num($raw['importe_fac'])
                : round($cant * $precio, 2);
            if ($cant <= 0) {
                throw new HttpException("Línea {$i}: cantidad debe ser > 0.", 422);
            }
            $lines[] = [
                'cod_item' => $codItem,
                'bultos' => $bultos,
                'cant_fac' => $cant,
                'precio_uni' => $precio,
                'importe_fac' => $importe,
            ];
            $total += $importe;
            $totBul += $bultos;
        }

        return [
            'header' => [
                'cod_fac' => $codFac,
                'fecha_fac' => $fecha,
                'cod_cli' => $codCli,
                'nom_cli' => mb_substr($nomCli, 0, 50),
                'dire_cli' => mb_substr(DbText::forStorage((string) ($input['dire_cli'] ?? '')), 0, 100),
                'traspaso' => mb_substr(DbText::forStorage((string) ($input['traspaso'] ?? '')), 0, 50),
                'total_fac' => array_key_exists('total_fac', $input) ? $this->num($input['total_fac']) : round($total, 2),
                'tot_bul' => array_key_exists('tot_bul', $input) ? $this->num($input['tot_bul']) : round($totBul, 2),
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
            'cod_cli' => (int) $row['cod_cli'],
            'nom_cli' => (string) $row['nom_cli'],
            'dire_cli' => (string) $row['dire_cli'],
            'traspaso' => (string) $row['traspaso'],
            'total_fac' => (float) $row['total_fac'],
            'tot_bul' => (float) $row['tot_bul'],
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
            'precio_item' => (float) ($row['precio_item'] ?? 0),
            'bultos' => (float) $row['bultos'],
            'cant_fac' => (float) $row['cant_fac'],
            'precio_uni' => (float) $row['precio_uni'],
            'importe_fac' => (float) $row['importe_fac'],
        ];
    }
}
