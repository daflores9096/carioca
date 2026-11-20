<?php

declare(strict_types=1);

use App\Auth\PasswordHasher;
use App\Auth\SessionAuth;
use App\Infrastructure\Database\ConnectionFactory;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\BackupService;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $builder): void {
    $builder->addDefinitions([
        'settings' => require __DIR__ . '/settings.php',

        PDO::class => function (ContainerInterface $c): PDO {
            /** @var array $settings */
            $settings = $c->get('settings');
            return ConnectionFactory::create($settings['db']);
        },

        PasswordHasher::class => fn () => new PasswordHasher(),

        UserRepository::class => fn (ContainerInterface $c) => new UserRepository($c->get(PDO::class)),

        SessionAuth::class => function (ContainerInterface $c): SessionAuth {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new SessionAuth($settings['app']['session_name']);
        },

        AuthService::class => function (ContainerInterface $c): AuthService {
            return new AuthService(
                $c->get(UserRepository::class),
                $c->get(PasswordHasher::class),
                $c->get(SessionAuth::class),
            );
        },

        BackupService::class => function (ContainerInterface $c): BackupService {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new BackupService($c->get(PDO::class), $settings['db']);
        },
    ]);
};
