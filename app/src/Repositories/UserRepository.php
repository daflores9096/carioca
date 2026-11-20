<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT usuario_id, usuario_nombre, usuario_clave, usuario_clave_hash,
                    usuario_email, nivel_acceso, usuario_freg
             FROM usuarios
             WHERE usuario_nombre = :nombre
             LIMIT 1'
        );
        $stmt->execute(['nombre' => $username]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT usuario_id, usuario_nombre, usuario_clave, usuario_clave_hash,
                    usuario_email, nivel_acceso, usuario_freg
             FROM usuarios
             WHERE usuario_id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublicById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT usuario_id, usuario_nombre, usuario_email, nivel_acceso, usuario_freg
             FROM usuarios
             WHERE usuario_id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT usuario_id, usuario_nombre, usuario_email, nivel_acceso, usuario_freg
             FROM usuarios
             ORDER BY usuario_nombre ASC'
        );
        return $stmt === false ? [] : $stmt->fetchAll();
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM usuarios WHERE usuario_nombre = :n LIMIT 1'
            );
            $stmt->execute(['n' => $username]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM usuarios WHERE usuario_nombre = :n AND usuario_id <> :id LIMIT 1'
            );
            $stmt->execute(['n' => $username, 'id' => $exceptId]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (usuario_nombre, usuario_clave, usuario_clave_hash, usuario_email, usuario_freg, nivel_acceso)
             VALUES (:usuario_nombre, :usuario_clave, :usuario_clave_hash, :usuario_email, :usuario_freg, :nivel_acceso)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, string $email, ?string $nivelAcceso): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios
             SET usuario_email = :email, nivel_acceso = :nivel
             WHERE usuario_id = :id'
        );
        $stmt->execute([
            'email' => $email,
            'nivel' => $nivelAcceso,
            'id' => $id,
        ]);
    }

    public function updatePassword(int $id, string $legacyMd5, string $modernHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios
             SET usuario_clave = :md5, usuario_clave_hash = :hash
             WHERE usuario_id = :id'
        );
        $stmt->execute([
            'md5' => $legacyMd5,
            'hash' => $modernHash,
            'id' => $id,
        ]);
    }

    public function updateModernHash(int $userId, string $hash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET usuario_clave_hash = :hash WHERE usuario_id = :id'
        );
        $stmt->execute([
            'hash' => $hash,
            'id' => $userId,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE usuario_id = :id');
        $stmt->execute(['id' => $id]);
    }
}
