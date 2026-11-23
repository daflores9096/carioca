<?php

declare(strict_types=1);

$corsOrigin = $_ENV['CORS_ORIGIN'] ?? 'http://localhost:5173';
$appUrl = trim((string) ($_ENV['APP_URL'] ?? ''));
$sessionSecureEnv = $_ENV['SESSION_COOKIE_SECURE'] ?? null;
$sessionSecure = ($sessionSecureEnv === null || $sessionSecureEnv === '')
    ? str_starts_with($corsOrigin, 'https://')
    : filter_var($sessionSecureEnv, FILTER_VALIDATE_BOOLEAN);

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'cors_origin' => $corsOrigin,
        // URL pública de la app (enlaces de email). Por defecto = CORS_ORIGIN.
        'public_url' => rtrim($appUrl !== '' ? $appUrl : $corsOrigin, '/'),
        'session_name' => $_ENV['SESSION_NAME'] ?? 'CARIOCASESSID',
        'session_secure' => $sessionSecure,
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'name' => $_ENV['DB_NAME'] ?? 'carioca',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        // utf8mb4: MariaDB convierte columnas latin1 → UTF-8 al leer (JSON del frontend)
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'host' => (string) ($_ENV['SMTP_HOST'] ?? ''),
        'port' => (int) ($_ENV['SMTP_PORT'] ?? 587),
        'username' => (string) ($_ENV['SMTP_USER'] ?? ''),
        'password' => (string) ($_ENV['SMTP_PASS'] ?? ''),
        'encryption' => strtolower((string) ($_ENV['SMTP_ENCRYPTION'] ?? 'tls')),
        'from_address' => (string) ($_ENV['MAIL_FROM'] ?? ''),
        'from_name' => (string) ($_ENV['MAIL_FROM_NAME'] ?? 'CARIOCA'),
    ],
];
