<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\PasswordHasher;
use App\Auth\SessionAuth;
use App\Repositories\UserRepository;
use App\Support\HttpException;

final class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionAuth $session,
    ) {
    }

    /**
     * @return array{data: list<array<string,mixed>>}
     */
    public function list(): array
    {
        $rows = array_map([$this, 'publicUser'], $this->users->listAll());
        return ['data' => $rows];
    }

    /**
     * @return array{usuario: array<string,mixed>}
     */
    public function get(int $id): array
    {
        $row = $this->users->findPublicById($id);
        if ($row === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }
        return ['usuario' => $this->publicUser($row)];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{usuario: array<string,mixed>}
     */
    public function create(array $input): array
    {
        $nombre = trim((string) ($input['usuario_nombre'] ?? ''));
        $clave = (string) ($input['usuario_clave'] ?? '');
        $clave2 = (string) ($input['usuario_clave_conf'] ?? $input['usuario_clave'] ?? '');
        $email = trim((string) ($input['usuario_email'] ?? ''));
        $nivel = $this->normalizeNivel($input['nivel_acceso'] ?? null);

        if ($nombre === '' || preg_match('/\s/', $nombre)) {
            throw new HttpException('El usuario es obligatorio y no puede tener espacios.', 422);
        }
        if (strlen($nombre) > 15) {
            throw new HttpException('El usuario no puede superar 15 caracteres.', 422);
        }
        if ($clave === '') {
            throw new HttpException('La contraseña es obligatoria.', 422);
        }
        if ($clave !== $clave2) {
            throw new HttpException('Las contraseñas no coinciden.', 422);
        }
        if (!$this->validEmail($email)) {
            throw new HttpException('Email inválido.', 422);
        }
        if ($this->users->usernameExists($nombre)) {
            throw new HttpException('Ese nombre de usuario ya existe.', 409);
        }

        $id = $this->users->insert([
            'usuario_nombre' => $nombre,
            'usuario_clave' => md5($clave),
            'usuario_clave_hash' => $this->hasher->hash($clave),
            'usuario_email' => $email,
            'usuario_freg' => date('Y-m-d H:i:s'),
            'nivel_acceso' => $nivel,
        ]);

        return $this->get($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{usuario: array<string,mixed>}
     */
    public function update(int $id, array $input): array
    {
        $existing = $this->users->findPublicById($id);
        if ($existing === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }

        $email = trim((string) ($input['usuario_email'] ?? $existing['usuario_email']));
        $nivel = array_key_exists('nivel_acceso', $input)
            ? $this->normalizeNivel($input['nivel_acceso'])
            : ($existing['nivel_acceso'] !== null ? (string) $existing['nivel_acceso'] : null);

        if (!$this->validEmail($email)) {
            throw new HttpException('Email inválido.', 422);
        }

        // Evitar quedarse sin admin si se degrada el último admin
        if (($existing['nivel_acceso'] ?? null) === '1' && $nivel !== '1') {
            $admins = array_filter(
                $this->users->listAll(),
                static fn (array $u): bool => ($u['nivel_acceso'] ?? null) === '1'
            );
            if (count($admins) <= 1) {
                throw new HttpException('Debe quedar al menos un administrador.', 422);
            }
        }

        $this->users->updateProfile($id, $email, $nivel);

        // Si edita su propio nivel, refrescar sesión
        $me = $this->session->user();
        if ($me !== null && (int) $me['usuario_id'] === $id) {
            $fresh = $this->users->findById($id);
            if ($fresh !== null) {
                $this->session->login($fresh);
            }
        }

        return $this->get($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool}
     */
    public function setPassword(int $id, array $input): array
    {
        $existing = $this->users->findById($id);
        if ($existing === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }

        $clave = (string) ($input['usuario_clave'] ?? '');
        $clave2 = (string) ($input['usuario_clave_conf'] ?? '');
        if ($clave === '') {
            throw new HttpException('La contraseña es obligatoria.', 422);
        }
        if ($clave !== $clave2) {
            throw new HttpException('Las contraseñas no coinciden.', 422);
        }

        $this->users->updatePassword($id, md5($clave), $this->hasher->hash($clave));
        return ['ok' => true];
    }

    public function delete(int $id): array
    {
        $existing = $this->users->findPublicById($id);
        if ($existing === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }

        $me = $this->session->user();
        if ($me !== null && (int) $me['usuario_id'] === $id) {
            throw new HttpException('No puede eliminar su propio usuario.', 422);
        }

        if (($existing['nivel_acceso'] ?? null) === '1') {
            $admins = array_filter(
                $this->users->listAll(),
                static fn (array $u): bool => ($u['nivel_acceso'] ?? null) === '1'
            );
            if (count($admins) <= 1) {
                throw new HttpException('No se puede eliminar el último administrador.', 422);
            }
        }

        $this->users->delete($id);
        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function publicUser(array $row): array
    {
        $nivel = $row['nivel_acceso'] !== null ? (string) $row['nivel_acceso'] : null;
        return [
            'usuario_id' => (int) $row['usuario_id'],
            'usuario_nombre' => (string) $row['usuario_nombre'],
            'usuario_email' => (string) $row['usuario_email'],
            'nivel_acceso' => $nivel,
            'usuario_freg' => (string) ($row['usuario_freg'] ?? ''),
            'is_admin' => $nivel === '1',
        ];
    }

    private function normalizeNivel(mixed $nivel): ?string
    {
        if ($nivel === true || $nivel === 1 || $nivel === '1' || $nivel === 'admin') {
            return '1';
        }
        return null;
    }

    private function validEmail(string $email): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9-_.+%]+@[A-Za-z0-9-.]+\.[A-Za-z]{2,}$/', $email);
    }
}
