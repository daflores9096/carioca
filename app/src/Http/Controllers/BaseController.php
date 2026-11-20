<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Support\HttpException;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

abstract class BaseController
{
    protected function handle(Response $response, callable $callback): Response
    {
        try {
            $data = $callback();
            return JsonResponse::write($response, $data);
        } catch (HttpException $e) {
            return JsonResponse::error($response, $e->getMessage(), $e->status());
        } catch (Throwable $e) {
            return JsonResponse::error($response, 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function body($request): array
    {
        $parsed = $request->getParsedBody();
        return is_array($parsed) ? $parsed : [];
    }
}
