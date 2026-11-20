<?php

declare(strict_types=1);

$corsOrigin = $_ENV['CORS_ORIGIN'] ?? 'http://localhost:5173';
$sessionSecureEnv = $_ENV['SESSION_COOKIE_SECURE'] ?? null;
$sessionSecure = ($sessionSecureEnv === null || $sessionSecureEnv === '')
    ? str_starts_with($corsOrigin, 'https://')
    : filter_var($sessionSecureEnv, FILTER_VALIDATE_BOOLEAN);

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'cors_origin' => $corsOrigin,
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
];
