<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ItemRepository;
use App\Repositories\MovimientoRepository;
use App\Support\HttpException;
use App\Support\ListQuery;

final class ItemService
{
    private const SORTABLE = [
        'cod_item', 'nom_item', 'unid_item', 'precio_item', 'existencia', 'exi_min', 'exi_max',
    ];

    private const MOV_SORTABLE = [
        'tipo_mov', 'fecha_mov', 'cod_mov', 'nom_cli_pro', 'entrada', 'salida',
    ];

    public function __construct(
        private readonly ItemRepository $items,
        private readonly MovimientoRepository $movimientos,
    ) {
    }

    public function list(array $params): array
    {
        $query = new ListQuery($params, self::SORTABLE, 'nom_item');
        $result = $this->items->paginate($query);
        $result['data'] = array_map([$this, 'normalize'], $result['data']);
        return $result;
    }

    public function get(string $codItem): array
    {
        $row = $this->items->find($codItem);
        if ($row === null) {
            throw new HttpException('Artículo no encontrado.', 404);
        }
        return $this->normalize($row);
    }

    public function create(array $input): array
    {
        $data = $this->validate($input, true);
        if ($this->items->exists($data['cod_item'])) {
            throw new HttpException('El código de artículo ya existe.', 409);
        }
        $this->items->insert($data);
        return $this->get($data['cod_item']);
    }

    public function update(string $originalCod, array $input): array
    {
        if ($this->items->find($originalCod) === null) {
            throw new HttpException('Artículo no encontrado.', 404);
        }
        $data = $this->validate($input, true);
        if ($data['cod_item'] !== $originalCod && $this->items->exists($data['cod_item'])) {
            throw new HttpException('El nuevo código de artículo ya existe.', 409);
        }
        $this->items->update($originalCod, $data);
        return $this->get($data['cod_item']);
    }

    public function delete(string $codItem): void
    {
        if ($this->items->find($codItem) === null) {
            throw new HttpException('Artículo no encontrado.', 404);
        }
        $this->items->delete($codItem);
    }

    /**
     * @param array<string, mixed> $params
     * @return array{item: array<string, mixed>, data: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function movements(string $codItem, array $params): array
    {
        $item = $this->get($codItem);
        if (!isset($params['sort'])) {
            $params['sort'] = 'fecha_mov';
        }
        if (!isset($params['dir'])) {
            $params['dir'] = 'desc';
        }
        $query = new ListQuery($params, self::MOV_SORTABLE, 'fecha_mov');
        $result = $this->movimientos->paginateByItem($codItem, $query);
        $result['data'] = array_map([$this, 'normalizeMovement'], $result['data']);
        $result['item'] = $item;
        return $result;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeMovement(array $row): array
    {
        $tipo = strtoupper((string) ($row['tipo_mov'] ?? ''));
        return [
            'id_mov' => (int) $row['id_mov'],
            'tipo_mov' => $tipo,
            'cod_mov' => (int) $row['cod_mov'],
            'cod_item' => (string) $row['cod_item'],
            'fecha_mov' => (string) $row['fecha_mov'],
            'cod_cli_pro' => (int) $row['cod_cli_pro'],
            'nom_cli_pro' => (string) $row['nom_cli_pro'],
            'entrada' => (float) $row['entrada'],
            'salida' => (float) $row['salida'],
            'nota_path' => $tipo === 'V'
                ? '/ventas/' . (int) $row['cod_mov']
                : ($tipo === 'C' ? '/compras/' . (int) $row['cod_mov'] : null),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function validate(array $input, bool $requireCode): array
    {
        $cod = trim((string) ($input['cod_item'] ?? ''));
        $nom = trim((string) ($input['nom_item'] ?? ''));
        if ($requireCode && $cod === '') {
            throw new HttpException('El código es obligatorio.', 422);
        }
        if ($nom === '') {
            throw new HttpException('El nombre es obligatorio.', 422);
        }
        if (strlen($cod) > 10) {
            throw new HttpException('El código no puede superar 10 caracteres.', 422);
        }

        return [
            'cod_item' => $cod,
            'nom_item' => mb_substr($nom, 0, 50),
            'unid_item' => mb_substr(trim((string) ($input['unid_item'] ?? '')), 0, 5),
            'precio_item' => $this->float($input['precio_item'] ?? 0),
            'caja_item' => $this->float($input['caja_item'] ?? 0),
            'exi_max' => $this->float($input['exi_max'] ?? 0),
            'existencia' => $this->float($input['existencia'] ?? 0),
            'exi_min' => $this->float($input['exi_min'] ?? 0),
            'deta_item' => mb_substr(trim((string) ($input['deta_item'] ?? '')), 0, 100),
        ];
    }

    private function float(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        return round((float) $value, 2);
    }

    private function normalize(array $row): array
    {
        return [
            'cod_item' => (string) $row['cod_item'],
            'nom_item' => (string) $row['nom_item'],
            'unid_item' => (string) $row['unid_item'],
            'precio_item' => (float) $row['precio_item'],
            'caja_item' => (float) $row['caja_item'],
            'exi_max' => (float) $row['exi_max'],
            'existencia' => (float) $row['existencia'],
            'exi_min' => (float) $row['exi_min'],
            'deta_item' => (string) $row['deta_item'],
        ];
    }
}
