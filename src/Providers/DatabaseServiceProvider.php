<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\Migrator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Solo\Application\Config;
use Solo\Contracts\Container\WritableContainerInterface;
use Solo\Dotenv\Dotenv;

/**
 * One Doctrine DBAL connection, shared by every repository, and the migrator.
 * SQLite by default, so a new project runs with no database server; set
 * DB_DRIVER and the DB_* variables in .env to move to MySQL or PostgreSQL.
 */
final class DatabaseServiceProvider
{
    public function register(WritableContainerInterface $container): void
    {
        $container->set(Connection::class, function (WritableContainerInterface $c): Connection {
            $basePath = $c->get(Config::class)->basePath;

            return match ($driver = Dotenv::string('DB_DRIVER', 'pdo_sqlite')) {
                'pdo_sqlite' => DriverManager::getConnection(self::sqlite($basePath)),
                'pdo_mysql', 'pdo_pgsql' => DriverManager::getConnection([
                    'driver' => $driver,
                    'host' => Dotenv::string('DB_HOST', '127.0.0.1'),
                    'port' => Dotenv::int('DB_PORT', $driver === 'pdo_mysql' ? 3306 : 5432),
                    'dbname' => Dotenv::required('DB_NAME'),
                    'user' => Dotenv::string('DB_USER'),
                    'password' => Dotenv::string('DB_PASSWORD'),
                    'charset' => $driver === 'pdo_mysql' ? 'utf8mb4' : 'utf8',
                ]),
                default => throw new \InvalidArgumentException(
                    "Unsupported DB_DRIVER '$driver': use pdo_sqlite, pdo_mysql or pdo_pgsql"
                ),
            };
        });

        $container->set(Migrator::class, fn(WritableContainerInterface $c) => new Migrator(
            $c->get(Connection::class),
            $c->get(Config::class)->basePath . '/database/migrations',
        ));
    }

    /** @return array{driver: 'pdo_sqlite', memory?: true, path?: string} */
    private static function sqlite(string $basePath): array
    {
        $path = Dotenv::string('DB_PATH', 'storage/database/app.sqlite');

        if ($path === ':memory:') {
            return ['driver' => 'pdo_sqlite', 'memory' => true];
        }

        return ['driver' => 'pdo_sqlite', 'path' => str_starts_with($path, '/') ? $path : $basePath . '/' . $path];
    }
}
