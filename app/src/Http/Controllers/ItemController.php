<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ItemService;
use App\Support\HttpException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ItemController extends BaseController
{
    public function __construct(private readonly ItemService $items)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->items->list($request->getQueryParams()));
    }

    public function show(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return ['item' => $this->items->get($this->codFromRequest($request))];
        });
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return ['item' => $this->items->create($this->body($request))];
        });
    }

    public function update(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return ['item' => $this->items->update($this->codFromRequest($request), $this->body($request))];
        });
    }

    public function destroy(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            $this->items->delete($this->codFromRequest($request));
            return ['ok' => true];
        });
    }

    public function movements(Request $request, Response $response): Response
    {
        return $this->handle($response, function () use ($request) {
            return $this->items->movements($this->codFromRequest($request), $request->getQueryParams());
        });
    }

    private function codFromRequest(Request $request): string
    {
        $cod = trim((string) ($request->getQueryParams()['cod'] ?? ''));
        if ($cod === '') {
            throw new HttpException('Parámetro cod es obligatorio.', 422);
        }
        return $cod;
    }
}
