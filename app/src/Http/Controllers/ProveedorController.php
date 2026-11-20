<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ProveedorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ProveedorController extends BaseController
{
    public function __construct(private readonly ProveedorService $proveedores)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->proveedores->list($request->getQueryParams()));
    }

    public function nextCode(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->proveedores->nextCode());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => ['proveedor' => $this->proveedores->get((int) $args['cod'])]);
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return ['proveedor' => $this->proveedores->create($this->body($request))];
        });
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($request, $args) {
            return ['proveedor' => $this->proveedores->update((int) $args['cod'], $this->body($request))];
        });
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($args) {
            $this->proveedores->delete((int) $args['cod']);
            return ['ok' => true];
        });
    }
}
