<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\VentaService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VentaController extends BaseController
{
    public function __construct(private readonly VentaService $ventas)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->ventas->list($request->getQueryParams()));
    }

    public function nextCode(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->ventas->nextCode());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->ventas->get((int) $args['cod']));
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->ventas->create($this->body($request)));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->ventas->replace((int) $args['cod'], $this->body($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($args) {
            $this->ventas->void((int) $args['cod']);
            return ['ok' => true];
        });
    }
}
