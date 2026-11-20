<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HealthController
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function health(Request $request, Response $response): Response
    {
        $dbOk = false;
        try {
            $this->pdo->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        return JsonResponse::write($response, [
            'status' => $dbOk ? 'ok' : 'degraded',
            'php' => PHP_VERSION,
            'database' => $dbOk,
        ], $dbOk ? 200 : 503);
    }
}
