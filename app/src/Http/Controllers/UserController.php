<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UserController extends BaseController
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->users->list());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->users->get((int) $args['id']));
    }

    public function store(Request $request, Response $response): Response
    {
        return $this->handle($response, fn () => $this->users->create($this->body($request)));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->users->update((int) $args['id'], $this->body($request)));
    }

    public function setPassword(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->users->setPassword((int) $args['id'], $this->body($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->handle($response, fn () => $this->users->delete((int) $args['id']));
    }
}
