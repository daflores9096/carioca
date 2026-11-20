<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Services\BackupService;
use App\Support\HttpException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class BackupController extends BaseController
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function download(Request $request, Response $response): Response
    {
        $path = null;
        try {
            $params = $request->getQueryParams();
            $label = isset($params['nombre']) ? (string) $params['nombre'] : null;
            $dump = $this->backups->createDumpFile($label);
            $path = $dump['path'];

            $stream = fopen($path, 'rb');
            if ($stream === false) {
                throw new HttpException('No se pudo leer el respaldo.', 500);
            }

            $body = $response->getBody();
            while (!feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    break;
                }
                $body->write($chunk);
            }
            fclose($stream);
            @unlink($path);
            $path = null;

            return $response
                ->withHeader('Content-Type', 'application/sql; charset=utf-8')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $dump['filename'] . '"')
                ->withHeader('Cache-Control', 'no-store')
                ->withStatus(200);
        } catch (HttpException $e) {
            if ($path !== null) {
                @unlink($path);
            }
            return JsonResponse::error($response, $e->getMessage(), $e->status());
        } catch (Throwable $e) {
            if ($path !== null) {
                @unlink($path);
            }
            return JsonResponse::error($response, 'Error al generar respaldo: ' . $e->getMessage(), 500);
        }
    }
}
