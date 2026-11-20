<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'cors_origin' => $_ENV['CORS_ORIGIN'] ?? 'http://localhost:5173',
        'session_name' => $_ENV['SESSION_NAME'] ?? 'CARIOCASESSID',
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
