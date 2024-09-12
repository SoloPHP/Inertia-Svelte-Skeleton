<?php declare(strict_types=1);

namespace console;

use Solo\Database;
use Psr\Container\ContainerInterface;

abstract class AbstractAction
{
    protected array $args;
    protected string $message = '';
    protected string $migrationsPath;
    protected string $seedsPath;
    protected Database $db;

    public function __construct(protected readonly ContainerInterface $container)
    {
        $this->db = $this->container->get(Database::class);
        $this->migrationsPath = dirname(__DIR__ ).'/database/migrations';
        $this->seedsPath = dirname(__DIR__ ).'/database/seeds';
        $this->ensureDirectoryExists($this->migrationsPath);
        $this->ensureDirectoryExists($this->seedsPath);
        $this->checkMigrationsTable();
        $this->checkSeedsTable();
    }

    public function __invoke($args): string
    {
        $this->args = $args;
        return $this->action();
    }

    abstract protected function action(): string;

    protected function resolveArg(int $index): string
    {
        if (!isset($this->args[$index])) {
            exit("Could not resolve arg[$index]" . PHP_EOL);
        }
        return $this->args[$index];
    }

    protected function writeToConsole(string $message): void
    {
        $this->message .= $message . PHP_EOL;
    }

    protected function response($message = ''): string
    {
        if (!empty($message)) {
            $this->message .= $message . PHP_EOL;
        }
        return $this->message;
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$path}");
            }
        }
    }

    private function checkMigrationsTable(): void
    {
        $this->db->query("SHOW TABLES LIKE 'db_migrations'");
        if($this->db->rowCount() === 0) {
            $this->db->query("CREATE TABLE `db_migrations` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL DEFAULT '',
                  `executed_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }
    }

    private function checkSeedsTable(): void
    {
        $this->db->query("SHOW TABLES LIKE 'db_seeds'");
        if($this->db->rowCount() === 0) {
            $this->db->query("CREATE TABLE `db_seeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL DEFAULT '',
                  `executed_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }
    }
}