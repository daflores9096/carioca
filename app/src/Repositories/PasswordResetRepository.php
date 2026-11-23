<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PasswordResetRepository
{
    private bool $ensured = false;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        if ($this->ensured) {
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL DEFAULT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_password_resets_token (token_hash),
                KEY idx_password_resets_usuario (usuario_id)
             ) ENGINE=InnoDB DEFAULT CHARSET=latin1'
        );
        $this->ensured = true;
    }

    public function deleteActiveForUser(int $usuarioId): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'DELETE FROM password_resets
             WHERE usuario_id = :uid AND used_at IS NULL'
        );
        $stmt->execute(['uid' => $usuarioId]);
    }

    public function create(int $usuarioId, string $tokenHash, string $expiresAt): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (usuario_id, token_hash, expires_at, created_at)
             VALUES (:uid, :hash, :expires, NOW())'
        );
        $stmt->execute([
            'uid' => $usuarioId,
            'hash' => $tokenHash,
            'expires' => $expiresAt,
        ]);
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario_id, token_hash, expires_at, used_at, created_at
             FROM password_resets
             WHERE token_hash = :hash
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function markUsed(int $id): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
