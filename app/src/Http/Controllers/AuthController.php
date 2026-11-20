<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $username = (string) ($body['usuario_nombre'] ?? $body['username'] ?? '');
        $password = (string) ($body['usuario_clave'] ?? $body['password'] ?? '');

        try {
            $user = $this->auth->login($username, $password);
            return JsonResponse::write($response, ['user' => $user]);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;
            return JsonResponse::error($response, $e->getMessage(), $status);
        } catch (Throwable $e) {
            return JsonResponse::error($response, 'Error interno al iniciar sesión.', 500);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return JsonResponse::write($response, ['ok' => true]);
    }

    public function me(Request $request, Response $response): Response
    {
        try {
            return JsonResponse::write($response, ['user' => $this->auth->me()]);
        } catch (RuntimeException $e) {
            return JsonResponse::error($response, $e->getMessage(), 401);
        }
    }

    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $data = $this->auth->changePassword((array) $request->getParsedBody());
            return JsonResponse::write($response, $data);
        } catch (\App\Support\HttpException $e) {
            return JsonResponse::error($response, $e->getMessage(), $e->status());
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;
            return JsonResponse::error($response, $e->getMessage(), $status);
        } catch (Throwable $e) {
            return JsonResponse::error($response, 'Error al cambiar contraseña.', 500);
        }
    }
}
