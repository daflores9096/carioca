<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\PasswordHasher;
use App\Auth\SessionAuth;
use App\Infrastructure\Mail\SmtpMailer;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Support\HttpException;
use RuntimeException;

final class AuthService
{
    /**
     * @param array{debug:bool,public_url:string} $appSettings
     */
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionAuth $session,
        private readonly PasswordResetRepository $resets,
        private readonly SmtpMailer $mailer,
        private readonly array $appSettings,
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
     * Solicita recuperación por email. Siempre responde genérico (no revela si existe).
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool,message:string,dev_reset_url?:string}
     */
    public function requestPasswordReset(array $input): array
    {
        $email = trim((string) ($input['usuario_email'] ?? $input['email'] ?? ''));
        $generic = [
            'ok' => true,
            'message' => 'Si el email está registrado, recibirás instrucciones para restablecer tu contraseña.',
        ];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('Indicá un email válido.', 422);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return $generic;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->resets->deleteActiveForUser((int) $user['usuario_id']);
        $this->resets->create((int) $user['usuario_id'], $tokenHash, 3600);

        $resetUrl = rtrim((string) $this->appSettings['public_url'], '/')
            . '/reset-password?token=' . urlencode($token);

        $debug = (bool) ($this->appSettings['debug'] ?? false);

        if ($this->mailer->isConfigured()) {
            $nombre = (string) $user['usuario_nombre'];
            $body = "Hola {$nombre},\n\n"
                . "Recibimos una solicitud para restablecer tu contraseña en CARIOCA.\n\n"
                . "Abrí este enlace (válido por 1 hora):\n"
                . "<{$resetUrl}>\n\n"
                . "Si no solicitaste este cambio, ignorá este mensaje.\n";
            $this->mailer->send(
                (string) $user['usuario_email'],
                'Restablecer contraseña — CARIOCA',
                $body
            );
            return $generic;
        }

        if ($debug) {
            $generic['dev_reset_url'] = $resetUrl;
            $generic['message'] = 'SMTP no configurado (modo debug). Usá el enlace de desarrollo.';
            return $generic;
        }

        throw new HttpException(
            'El envío de correo no está configurado. Contactá al administrador.',
            503
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool}
     */
    public function resetPassword(array $input): array
    {
        // Algunos clientes de correo parten el enlace y meten espacios/saltos en el token.
        $token = preg_replace('/\s+/', '', trim((string) ($input['token'] ?? ''))) ?? '';
        $nueva = (string) ($input['usuario_clave'] ?? $input['clave_nueva'] ?? '');
        $conf = (string) ($input['usuario_clave_conf'] ?? $input['clave_nueva_conf'] ?? '');

        if ($token === '' || strlen($token) < 32) {
            throw new HttpException('Enlace inválido o incompleto.', 422);
        }
        if ($nueva === '') {
            throw new HttpException('La contraseña nueva es obligatoria.', 422);
        }
        if (strlen($nueva) < 6) {
            throw new HttpException('La contraseña debe tener al menos 6 caracteres.', 422);
        }
        if ($nueva !== $conf) {
            throw new HttpException('Las contraseñas no coinciden.', 422);
        }

        $row = $this->resets->findValidByHash(hash('sha256', $token));
        if ($row === null) {
            throw new HttpException('El enlace expiró o ya fue utilizado.', 422);
        }

        $usuarioId = (int) $row['usuario_id'];
        $user = $this->users->findById($usuarioId);
        if ($user === null) {
            throw new HttpException('Usuario no encontrado.', 404);
        }

        $this->users->updatePassword($usuarioId, md5($nueva), $this->hasher->hash($nueva));
        $this->resets->markUsed((int) $row['id']);
        $this->resets->deleteActiveForUser($usuarioId);

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
