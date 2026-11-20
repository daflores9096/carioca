<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ClienteService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ClienteController extends BaseController
{
    public function __construct(private readonly ClienteService $clientes)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->clientes->list($request->getQueryParams()));
    }

    public function nextCode(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->clientes->nextCode());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => ['cliente' => $this->clientes->get((int) $args['cod'])]);
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return ['cliente' => $this->clientes->create($this->body($request))];
        });
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($request, $args) {
            return ['cliente' => $this->clientes->update((int) $args['cod'], $this->body($request))];
        });
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($args) {
            $this->clientes->delete((int) $args['cod']);
            return ['ok' => true];
        });
    }
}
