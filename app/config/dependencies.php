<?php

declare(strict_types=1);

use App\Auth\PasswordHasher;
use App\Auth\SessionAuth;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Mail\SmtpMailer;
use App\Repositories\PasswordResetRepository;
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

        PasswordResetRepository::class => fn (ContainerInterface $c) => new PasswordResetRepository($c->get(PDO::class)),

        SmtpMailer::class => function (ContainerInterface $c): SmtpMailer {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new SmtpMailer($settings['mail']);
        },

        SessionAuth::class => function (ContainerInterface $c): SessionAuth {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new SessionAuth(
                $settings['app']['session_name'],
                (bool) ($settings['app']['session_secure'] ?? false),
            );
        },

        AuthService::class => function (ContainerInterface $c): AuthService {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new AuthService(
                $c->get(UserRepository::class),
                $c->get(PasswordHasher::class),
                $c->get(SessionAuth::class),
                $c->get(PasswordResetRepository::class),
                $c->get(SmtpMailer::class),
                [
                    'debug' => (bool) ($settings['app']['debug'] ?? false),
                    'public_url' => (string) ($settings['app']['public_url'] ?? ''),
                ],
            );
        },

        BackupService::class => function (ContainerInterface $c): BackupService {
            /** @var array $settings */
            $settings = $c->get('settings');
            return new BackupService($c->get(PDO::class), $settings['db']);
        },
    ]);
};
