<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InventarioRepository;
use App\Repositories\ItemRepository;
use App\Support\HttpException;
use App\Support\ListQuery;

final class InventarioService
{
    private const SORTABLE = ['id_inv', 'fecha_lev', 'fecha_ap', 'estado', 'descripcion'];
    private const ESTADO_PENDIENTE = 'Pendiente';
    private const ESTADO_APLICADO = 'Aplicado';

    public function __construct(
        private readonly InventarioRepository $inventarios,
        private readonly ItemRepository $items,
    ) {
    }

    public function list(array $params): array
    {
        if (!isset($params['dir'])) {
            $params['dir'] = 'desc';
        }
        $query = new ListQuery($params, self::SORTABLE, 'id_inv');
        $result = $this->inventarios->paginate($query);
        $result['data'] = array_map([$this, 'normalizeHeader'], $result['data']);
        return $result;
    }

    public function get(int $idInv): array
    {
        $header = $this->inventarios->findHeader($idInv);
        if ($header === null) {
            throw new HttpException('Inventario no encontrado.', 404);
        }
        return [
            'inventario' => $this->normalizeHeader($header),
            'lines' => array_map([$this, 'normalizeLine'], $this->inventarios->findLines($idInv)),
        ];
    }

    public function create(array $input): array
    {
        $dto = $this->validateCreateOrUpdate($input, true);
        $id = $this->inventarios->insertHeader([
            'fecha_lev' => $dto['fecha_lev'],
            'descripcion' => $dto['descripcion'],
            'fecha_ap' => '0000-00-00',
            'estado' => self::ESTADO_PENDIENTE,
        ]);
        foreach ($dto['lines'] as $line) {
            $this->inventarios->insertLine([
                'id_inv' => $id,
                'cod_item' => $line['cod_item'],
                'existencia_inv' => $line['existencia_inv'],
                'existencia_sis' => $line['existencia_sis'],
                'diferencia' => $line['diferencia'],
            ]);
        }
        return $this->get($id);
    }

    public function update(int $idInv, array $input): array
    {
        $header = $this->requireHeader($idInv);
        if ((string) $header['estado'] !== self::ESTADO_PENDIENTE) {
            throw new HttpException('Solo se pueden editar inventarios Pendientes.', 409);
        }
        $dto = $this->validateCreateOrUpdate($input, false);
        $this->inventarios->updateHeader($idInv, [
            'fecha_lev' => $dto['fecha_lev'],
            'descripcion' => $dto['descripcion'],
        ]);
        $this->inventarios->deleteLines($idInv);
        foreach ($dto['lines'] as $line) {
            $this->inventarios->insertLine([
                'id_inv' => $idInv,
                'cod_item' => $line['cod_item'],
                'existencia_inv' => $line['existencia_inv'],
                'existencia_sis' => $line['existencia_sis'],
                'diferencia' => $line['diferencia'],
            ]);
        }
        return $this->get($idInv);
    }

    public function apply(int $idInv, array $input): array
    {
        $header = $this->requireHeader($idInv);
        if ((string) $header['estado'] !== self::ESTADO_PENDIENTE) {
            throw new HttpException('El inventario ya fue aplicado o no está pendiente.', 409);
        }

        $fechaAp = trim((string) ($input['fecha_ap'] ?? ''));
        if ($fechaAp === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaAp)) {
            $fechaAp = date('Y-m-d');
        }

        $lines = $this->inventarios->findLines($idInv);
        if ($lines === []) {
            throw new HttpException('El inventario no tiene líneas para aplicar.', 422);
        }

        // Apply: item.existencia := existencia_inv (conteo físico). No escribe movimiento.
        foreach ($lines as $line) {
            $this->inventarios->setItemExistencia(
                (string) $line['cod_item'],
                (float) $line['existencia_inv']
            );
        }
        $this->inventarios->markApplied($idInv, $fechaAp);
        return $this->get($idInv);
    }

    public function void(int $idInv): void
    {
        $header = $this->requireHeader($idInv);
        $estado = (string) $header['estado'];
        $lines = $this->inventarios->findLines($idInv);

        if ($estado === self::ESTADO_APLICADO) {
            // Restaurar snapshot existencia_sis
            foreach ($lines as $line) {
                $this->inventarios->setItemExistencia(
                    (string) $line['cod_item'],
                    (float) $line['existencia_sis']
                );
            }
        }

        $this->inventarios->deleteLines($idInv);
        $this->inventarios->deleteHeader($idInv);
    }

    private function requireHeader(int $idInv): array
    {
        $header = $this->inventarios->findHeader($idInv);
        if ($header === null) {
            throw new HttpException('Inventario no encontrado.', 404);
        }
        return $header;
    }

    /**
     * @return array{fecha_lev:string, descripcion:string, lines:list<array>}
     */
    private function validateCreateOrUpdate(array $input, bool $requireLines): array
    {
        $fecha = trim((string) ($input['fecha_lev'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new HttpException('fecha_lev inválida (YYYY-MM-DD).', 422);
        }
        $desc = mb_substr(trim((string) ($input['descripcion'] ?? '')), 0, 60);

        $rawLines = $input['lines'] ?? [];
        if (!is_array($rawLines)) {
            throw new HttpException('lines debe ser un arreglo.', 422);
        }
        if ($requireLines && $rawLines === []) {
            throw new HttpException('Debe incluir al menos un artículo.', 422);
        }

        $lines = [];
        $seen = [];
        foreach ($rawLines as $i => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $codItem = trim((string) ($raw['cod_item'] ?? ''));
            if ($codItem === '') {
                throw new HttpException("Línea {$i}: cod_item obligatorio.", 422);
            }
            if (isset($seen[$codItem])) {
                throw new HttpException("Artículo duplicado: {$codItem}.", 422);
            }
            $seen[$codItem] = true;

            $item = $this->items->find($codItem);
            if ($item === null) {
                throw new HttpException("Artículo no encontrado: {$codItem}.", 422);
            }

            $inv = $this->num($raw['existencia_inv'] ?? 0);
            // Snapshot fresco del sistema al guardar
            $sis = $this->num($item['existencia']);
            $dif = round($sis - $inv, 2);

            $lines[] = [
                'cod_item' => $codItem,
                'existencia_inv' => $inv,
                'existencia_sis' => $sis,
                'diferencia' => $dif,
            ];
        }

        if ($requireLines && $lines === []) {
            throw new HttpException('Debe incluir al menos un artículo.', 422);
        }

        return [
            'fecha_lev' => $fecha,
            'descripcion' => $desc,
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
        $fechaAp = (string) ($row['fecha_ap'] ?? '');
        if ($fechaAp === '0000-00-00') {
            $fechaAp = null;
        }
        return [
            'id_inv' => (int) $row['id_inv'],
            'fecha_lev' => (string) $row['fecha_lev'],
            'descripcion' => (string) $row['descripcion'],
            'fecha_ap' => $fechaAp,
            'estado' => (string) $row['estado'],
            'is_pendiente' => (string) $row['estado'] === self::ESTADO_PENDIENTE,
            'is_aplicado' => (string) $row['estado'] === self::ESTADO_APLICADO,
        ];
    }

    private function normalizeLine(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'cod_item' => (string) $row['cod_item'],
            'nom_item' => (string) ($row['nom_item'] ?? ''),
            'unid_item' => (string) ($row['unid_item'] ?? ''),
            'existencia_inv' => (float) $row['existencia_inv'],
            'existencia_sis' => (float) $row['existencia_sis'],
            'diferencia' => (float) $row['diferencia'],
        ];
    }
}
