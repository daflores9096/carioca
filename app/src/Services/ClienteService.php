<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Support\HttpException;
use App\Support\ListQuery;

final class ClienteService
{
    private const SORTABLE = [
        'cod_cli', 'nom_cli', 'ciudad_cli', 'tel_cli', 'email_cli',
    ];

    public function __construct(private readonly ClienteRepository $clientes)
    {
    }

    public function list(array $params): array
    {
        $query = new ListQuery($params, self::SORTABLE, 'nom_cli');
        $result = $this->clientes->paginate($query);
        $result['data'] = array_map([$this, 'normalize'], $result['data']);
        return $result;
    }

    public function get(int $codCli): array
    {
        $row = $this->clientes->find($codCli);
        if ($row === null) {
            throw new HttpException('Cliente no encontrado.', 404);
        }
        return $this->normalize($row);
    }

    public function nextCode(): array
    {
        return ['next_cod_cli' => $this->clientes->nextCode()];
    }

    public function create(array $input): array
    {
        $data = $this->validate($input);
        $id = $this->clientes->insert($data);
        return $this->get($id);
    }

    public function update(int $codCli, array $input): array
    {
        if ($this->clientes->find($codCli) === null) {
            throw new HttpException('Cliente no encontrado.', 404);
        }
        $data = $this->validate($input);
        $this->clientes->update($codCli, $data);
        return $this->get($codCli);
    }

    public function delete(int $codCli): void
    {
        if ($this->clientes->find($codCli) === null) {
            throw new HttpException('Cliente no encontrado.', 404);
        }
        $this->clientes->delete($codCli);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $nom = trim((string) ($input['nom_cli'] ?? ''));
        if ($nom === '') {
            throw new HttpException('El nombre del cliente es obligatorio.', 422);
        }

        return [
            'nom_cli' => mb_substr($nom, 0, 40),
            'contacto_sec' => mb_substr(trim((string) ($input['contacto_sec'] ?? '')), 0, 40),
            'dire_cli' => mb_substr(trim((string) ($input['dire_cli'] ?? '')), 0, 50),
            'dire_sec' => mb_substr(trim((string) ($input['dire_sec'] ?? '')), 0, 50),
            'ciudad_cli' => mb_substr(trim((string) ($input['ciudad_cli'] ?? '')), 0, 30),
            'tel_cli' => mb_substr(trim((string) ($input['tel_cli'] ?? '0')), 0, 15) ?: '0',
            'tel_sec' => mb_substr(trim((string) ($input['tel_sec'] ?? '0')), 0, 15) ?: '0',
            'email_cli' => mb_substr(trim((string) ($input['email_cli'] ?? '')), 0, 30),
            'desc_cli' => mb_substr(trim((string) ($input['desc_cli'] ?? '')), 0, 80),
        ];
    }

    private function normalize(array $row): array
    {
        return [
            'cod_cli' => (int) $row['cod_cli'],
            'nom_cli' => (string) $row['nom_cli'],
            'contacto_sec' => (string) $row['contacto_sec'],
            'dire_cli' => (string) $row['dire_cli'],
            'dire_sec' => (string) $row['dire_sec'],
            'ciudad_cli' => (string) $row['ciudad_cli'],
            'tel_cli' => (string) $row['tel_cli'],
            'tel_sec' => (string) $row['tel_sec'],
            'email_cli' => (string) $row['email_cli'],
            'desc_cli' => (string) $row['desc_cli'],
        ];
    }
}
