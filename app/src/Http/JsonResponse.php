<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\DbText;
use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    public static function write(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $data = DbText::normalize($data);
        $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
        $payload = json_encode($data, $flags);
        if ($payload === false) {
            $payload = json_encode(['error' => 'No se pudo serializar la respuesta JSON.'], $flags) ?: '{"error":"json_encode failed"}';
            $status = 500;
        }

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    public static function error(ResponseInterface $response, string $message, int $status = 400, array $extra = []): ResponseInterface
    {
        return self::write($response, array_merge(['error' => $message], $extra), $status);
    }
}
