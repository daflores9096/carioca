<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CompraService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CompraController extends BaseController
{
    public function __construct(private readonly CompraService $compras)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->compras->list($request->getQueryParams()));
    }

    public function nextCode(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->compras->nextCode());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->compras->get((int) $args['cod']));
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->compras->create($this->body($request)));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->compras->replace((int) $args['cod'], $this->body($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($args) {
            $this->compras->void((int) $args['cod']);
            return ['ok' => true];
        });
    }
}
