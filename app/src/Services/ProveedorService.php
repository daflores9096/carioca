<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProveedorRepository;
use App\Support\HttpException;
use App\Support\ListQuery;

final class ProveedorService
{
    private const SORTABLE = [
        'cod_pro', 'nom_pro', 'ciudad_pro', 'tel_pro', 'email_pro',
    ];

    public function __construct(private readonly ProveedorRepository $proveedores)
    {
    }

    public function list(array $params): array
    {
        $query = new ListQuery($params, self::SORTABLE, 'nom_pro');
        $result = $this->proveedores->paginate($query);
        $result['data'] = array_map([$this, 'normalize'], $result['data']);
        return $result;
    }

    public function get(int $codPro): array
    {
        $row = $this->proveedores->find($codPro);
        if ($row === null) {
            throw new HttpException('Proveedor no encontrado.', 404);
        }
        return $this->normalize($row);
    }

    public function nextCode(): array
    {
        return ['next_cod_pro' => $this->proveedores->nextCode()];
    }

    public function create(array $input): array
    {
        $data = $this->validate($input);
        $id = $this->proveedores->insert($data);
        return $this->get($id);
    }

    public function update(int $codPro, array $input): array
    {
        if ($this->proveedores->find($codPro) === null) {
            throw new HttpException('Proveedor no encontrado.', 404);
        }
        $data = $this->validate($input);
        $this->proveedores->update($codPro, $data);
        return $this->get($codPro);
    }

    public function delete(int $codPro): void
    {
        if ($this->proveedores->find($codPro) === null) {
            throw new HttpException('Proveedor no encontrado.', 404);
        }
        $this->proveedores->delete($codPro);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $nom = trim((string) ($input['nom_pro'] ?? ''));
        if ($nom === '') {
            throw new HttpException('El nombre del proveedor es obligatorio.', 422);
        }

        return [
            'nom_pro' => mb_substr($nom, 0, 40),
            'contacto_sec' => mb_substr(trim((string) ($input['contacto_sec'] ?? '')), 0, 40),
            'dire_pro' => mb_substr(trim((string) ($input['dire_pro'] ?? '')), 0, 50),
            'ciudad_pro' => mb_substr(trim((string) ($input['ciudad_pro'] ?? '')), 0, 30),
            'tel_pro' => mb_substr(trim((string) ($input['tel_pro'] ?? '0')), 0, 15) ?: '0',
            'tel_sec' => mb_substr(trim((string) ($input['tel_sec'] ?? '0')), 0, 15) ?: '0',
            'email_pro' => mb_substr(trim((string) ($input['email_pro'] ?? '')), 0, 30),
            'desc_pro' => mb_substr(trim((string) ($input['desc_pro'] ?? '')), 0, 80),
        ];
    }

    private function normalize(array $row): array
    {
        return [
            'cod_pro' => (int) $row['cod_pro'],
            'nom_pro' => (string) $row['nom_pro'],
            'contacto_sec' => (string) $row['contacto_sec'],
            'dire_pro' => (string) $row['dire_pro'],
            'ciudad_pro' => (string) $row['ciudad_pro'],
            'tel_pro' => (string) $row['tel_pro'],
            'tel_sec' => (string) $row['tel_sec'],
            'email_pro' => (string) $row['email_pro'],
            'desc_pro' => (string) $row['desc_pro'],
        ];
    }
}
