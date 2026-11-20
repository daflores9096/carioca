<?php

declare(strict_types=1);

namespace App\Auth;

final class SessionAuth
{
    public function __construct(private readonly string $sessionName)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->sessionName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function login(array $user): void
    {
        $this->start();
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $user['usuario_id'];
        $_SESSION['usuario_nombre'] = (string) $user['usuario_nombre'];
        $_SESSION['nivel_acceso'] = $user['nivel_acceso'] !== null
            ? (string) $user['nivel_acceso']
            : null;
        $_SESSION['usuario_email'] = (string) ($user['usuario_email'] ?? '');
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public function check(): bool
    {
        $this->start();
        return isset($_SESSION['usuario_id']);
    }

    /**
     * @return array{usuario_id:int,usuario_nombre:string,nivel_acceso:?string,usuario_email:string}|null
     */
    public function user(): ?array
    {
        $this->start();
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return [
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => (string) $_SESSION['usuario_nombre'],
            'nivel_acceso' => $_SESSION['nivel_acceso'] !== null
                ? (string) $_SESSION['nivel_acceso']
                : null,
            'usuario_email' => (string) ($_SESSION['usuario_email'] ?? ''),
        ];
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && $user['nivel_acceso'] === '1';
    }
}
