<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\SessionMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->add(CorsMiddleware::class);
    $app->add(SessionMiddleware::class);

    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    $app->get('/api/health', [HealthController::class, 'health']);

    $app->group('/api/auth', function (RouteCollectorProxy $group): void {
        $group->post('/login', [AuthController::class, 'login']);
        $group->post('/logout', [AuthController::class, 'logout'])->add(AuthMiddleware::class);
        $group->get('/me', [AuthController::class, 'me'])->add(AuthMiddleware::class);
        $group->post('/password', [AuthController::class, 'changePassword'])->add(AuthMiddleware::class);
    });

    $app->group('/api', function (RouteCollectorProxy $api): void {
        $api->group('/items', function (RouteCollectorProxy $g): void {
            $g->get('', [ItemController::class, 'index']);
            $g->post('', [ItemController::class, 'store'])->add(AdminMiddleware::class);
            $g->get('/detail', [ItemController::class, 'show']);
            $g->put('/detail', [ItemController::class, 'update']);
            $g->delete('/detail', [ItemController::class, 'destroy'])->add(AdminMiddleware::class);
            $g->get('/movimientos', [ItemController::class, 'movements']);
        });

        $api->group('/clientes', function (RouteCollectorProxy $g): void {
            $g->get('', [ClienteController::class, 'index']);
            $g->get('/meta/next-code', [ClienteController::class, 'nextCode']);
            $g->get('/{cod}', [ClienteController::class, 'show']);
            $g->post('', [ClienteController::class, 'store']);
            $g->put('/{cod}', [ClienteController::class, 'update']);
            $g->delete('/{cod}', [ClienteController::class, 'destroy']);
        });

        $api->group('/proveedores', function (RouteCollectorProxy $g): void {
            $g->get('', [ProveedorController::class, 'index']);
            $g->get('/meta/next-code', [ProveedorController::class, 'nextCode']);
            $g->get('/{cod}', [ProveedorController::class, 'show']);
            $g->post('', [ProveedorController::class, 'store']);
            $g->put('/{cod}', [ProveedorController::class, 'update']);
            $g->delete('/{cod}', [ProveedorController::class, 'destroy']);
        })->add(AdminMiddleware::class);

        // Ventas: todos autenticados; anular solo admin
        $api->group('/ventas', function (RouteCollectorProxy $g): void {
            $g->get('', [VentaController::class, 'index']);
            $g->get('/meta/next-code', [VentaController::class, 'nextCode']);
            $g->get('/{cod}', [VentaController::class, 'show']);
            $g->post('', [VentaController::class, 'store']);
            $g->put('/{cod}', [VentaController::class, 'update']);
            $g->delete('/{cod}', [VentaController::class, 'destroy'])->add(AdminMiddleware::class);
        });

        // Compras: solo admin
        $api->group('/compras', function (RouteCollectorProxy $g): void {
            $g->get('', [CompraController::class, 'index']);
            $g->get('/meta/next-code', [CompraController::class, 'nextCode']);
            $g->get('/{cod}', [CompraController::class, 'show']);
            $g->post('', [CompraController::class, 'store']);
            $g->put('/{cod}', [CompraController::class, 'update']);
            $g->delete('/{cod}', [CompraController::class, 'destroy']);
        })->add(AdminMiddleware::class);

        // Inventario físico: solo admin
        $api->group('/inventarios', function (RouteCollectorProxy $g): void {
            $g->get('', [InventarioController::class, 'index']);
            $g->post('', [InventarioController::class, 'store']);
            $g->get('/{id}', [InventarioController::class, 'show']);
            $g->put('/{id}', [InventarioController::class, 'update']);
            $g->post('/{id}/aplicar', [InventarioController::class, 'apply']);
            $g->delete('/{id}', [InventarioController::class, 'destroy']);
        })->add(AdminMiddleware::class);

        // Reportes: solo admin
        $api->group('/reportes', function (RouteCollectorProxy $g): void {
            $g->get('/compras', [ReportController::class, 'compras']);
            $g->get('/ventas', [ReportController::class, 'ventas']);
        })->add(AdminMiddleware::class);

        // Usuarios: solo admin
        $api->group('/usuarios', function (RouteCollectorProxy $g): void {
            $g->get('', [UserController::class, 'index']);
            $g->post('', [UserController::class, 'store']);
            $g->get('/{id}', [UserController::class, 'show']);
            $g->put('/{id}', [UserController::class, 'update']);
            $g->post('/{id}/password', [UserController::class, 'setPassword']);
            $g->delete('/{id}', [UserController::class, 'destroy']);
        })->add(AdminMiddleware::class);

        // Respaldos: solo admin (descarga SQL; sin restaurar/reset destructivo)
        $api->get('/respaldos/download', [BackupController::class, 'download'])
            ->add(AdminMiddleware::class);
    })->add(AuthMiddleware::class);
};

