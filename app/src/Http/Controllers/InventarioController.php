<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\InventarioService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class InventarioController extends BaseController
{
    public function __construct(private readonly InventarioService $inventarios)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->inventarios->list($request->getQueryParams()));
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->inventarios->get((int) $args['id']));
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->inventarios->create($this->body($request)));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->inventarios->update((int) $args['id'], $this->body($request)));
    }

    public function apply(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->inventarios->apply((int) $args['id'], $this->body($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, function () use ($args) {
            $this->inventarios->void((int) $args['id']);
            return ['ok' => true];
        });
    }
}
