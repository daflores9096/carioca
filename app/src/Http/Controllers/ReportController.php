<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ReportController extends BaseController
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function compras(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->reports->compras($request->getQueryParams()));
    }

    public function ventas(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->reports->ventas($request->getQueryParams()));
    }
}
