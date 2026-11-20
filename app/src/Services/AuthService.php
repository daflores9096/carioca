<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\PasswordHasher;
use App\Auth\SessionAuth;
use App\Repositories\UserRepository;
use App\Support\HttpException;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionAuth $session,
    ) {
    }

    /**
     * @return array{usuario_id:int,usuario_nombre:string,nivel_acceso:?string,usuario_email:string,is_admin:bool}
     */
    public function login(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            throw new RuntimeException('Usuario y contraseña son obligatorios.', 422);
        }

        $user = $this->users->findByUsername($username);
        if ($user === null) {
            throw new RuntimeException('Usuario o contraseña incorrectos.', 401);
        }

        $ok = $this->hasher->verify(
            $password,
            $user['usuario_clave_hash'] ?? null,
            $user['usuario_clave'] ?? null
        );

        if (!$ok) {
            throw new RuntimeException('Usuario o contraseña incorrectos.', 401);
        }

        if ($this->hasher->needsRehash($user['usuario_clave_hash'] ?? null)) {
            $this->users->updateModernHash((int) $user['usuario_id'], $this->hasher->hash($password));
        }

        $this->session->login($user);

        return $this->publicUser($this->session->user());
    }

    public function logout(): void
    {
        $this->session->logout();
    }

    /**
     * @return array{usuario_id:int,usuario_nombre:string,nivel_acceso:?string,usuario_email:string,is_admin:bool}
     */
    public function me(): array
    {
        $user = $this->session->user();
        if ($user === null) {
            throw new RuntimeException('No autenticado.', 401);
        }

        return $this->publicUser($user);
    }

    public function session(): SessionAuth
    {
        return $this->session;
    }

    /**
     * Cambio de contraseña del usuario autenticado.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool}
     */
    public function changePassword(array $input): array
    {
        $me = $this->session->user();
        if ($me === null) {
            throw new HttpException('No autenticado.', 401);
        }

        $actual = (string) ($input['clave_actual'] ?? '');
        $nueva = (string) ($input['usuario_clave'] ?? $input['clave_nueva'] ?? '');
        $conf = (string) ($input['usuario_clave_conf'] ?? $input['clave_nueva_conf'] ?? '');

        if ($actual === '' || $nueva === '') {
            throw new HttpException('Contraseña actual y nueva son obligatorias.', 422);
        }
        if ($nueva !== $conf) {
            throw new HttpException('Las contraseñas nuevas no coinciden.', 422);
        }

        $row = $this->users->findById((int) $me['usuario_id']);
        if ($row === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }

        $ok = $this->hasher->verify(
            $actual,
            $row['usuario_clave_hash'] ?? null,
            $row['usuario_clave'] ?? null
        );
        if (!$ok) {
            throw new HttpException('La contraseña actual es incorrecta.', 422);
        }

        $this->users->updatePassword(
            (int) $me['usuario_id'],
            md5($nueva),
            $this->hasher->hash($nueva)
        );

        return ['ok' => true];
    }

    /**
     * @param array{usuario_id:int,usuario_nombre:string,nivel_acceso:?string,usuario_email:string} $user
     * @return array{usuario_id:int,usuario_nombre:string,nivel_acceso:?string,usuario_email:string,is_admin:bool}
     */
    private function publicUser(array $user): array
    {
        return [
            'usuario_id' => $user['usuario_id'],
            'usuario_nombre' => $user['usuario_nombre'],
            'nivel_acceso' => $user['nivel_acceso'],
            'usuario_email' => $user['usuario_email'],
            'is_admin' => $user['nivel_acceso'] === '1',
        ];
    }
}
