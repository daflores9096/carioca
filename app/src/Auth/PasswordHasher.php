<?php

declare(strict_types=1);

namespace App\Auth;

final class PasswordHasher
{
    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verify(string $plain, ?string $modernHash, ?string $legacyMd5): bool
    {
        if ($modernHash !== null && $modernHash !== '') {
            return password_verify($plain, $modernHash);
        }

        if ($legacyMd5 === null || $legacyMd5 === '') {
            return false;
        }

        return hash_equals(strtolower($legacyMd5), md5($plain));
    }

    public function needsRehash(?string $modernHash): bool
    {
        if ($modernHash === null || $modernHash === '') {
            return true;
        }

        return password_needs_rehash($modernHash, PASSWORD_DEFAULT);
    }
}
