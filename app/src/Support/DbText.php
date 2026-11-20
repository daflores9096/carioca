<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normaliza texto leído de columnas latin1 que a veces contienen
 * bytes UTF-8 crudos (p. ej. NBSP C2 A0 en venta.nom_cli).
 */
final class DbText
{
    public static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::normalize($v);
            }
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        return self::normalizeString($value);
    }

    /** Texto de entrada (alta/edición): limpia espacios raros antes de guardar. */
    public static function forStorage(string $s): string
    {
        $s = self::normalizeString($s);
        $s = preg_replace('/[ \t]+/u', ' ', $s) ?? $s;
        return trim($s);
    }

    public static function normalizeString(string $s): string
    {
        // Recupera UTF-8 guardado como bytes crudos en latin1, luego
        // re-leído con SET NAMES utf8mb4 (latin1 → utf8 “mojibake”).
        if (self::looksLikeUtf8Mojibake($s)) {
            $recovered = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if (is_string($recovered)
                && $recovered !== ''
                && mb_check_encoding($recovered, 'UTF-8')
                && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $recovered)
            ) {
                $s = $recovered;
            }
        }

        // Espacios no separables / unicode → espacio ASCII
        $s = preg_replace('/[\x{00A0}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $s) ?? $s;

        return $s;
    }

    private static function looksLikeUtf8Mojibake(string $s): bool
    {
        // Patrones típicos tras leer UTF-8-en-latin1 como latin1→utf8:
        // Â (U+00C2) delante de chars continuation, Ã delante de acentos, etc.
        return (bool) preg_match('/\x{00C2}[\x{00A0}-\x{00BF}]|\x{00C3}[\x{0080}-\x{00BF}]/u', $s);
    }
}
