<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use UnexpectedValueException;

/**
 * Forward-only migrations: each PHP file in the directory returns a callable
 * that receives the connection, and runs once, in file name order. The names
 * of the applied ones are kept in the `migrations` table.
 */
final readonly class Migrator
{
    private const string TABLE = 'migrations';

    public function __construct(
        private Connection $connection,
        private string $directory,
    ) {
    }

    /** @return list<string> The migrations applied by this call */
    public function run(): array
    {
        $this->createTableIfMissing();

        $applied = [];
        foreach ($this->pending() as $name => $file) {
            $migration = require $file;
            if (!is_callable($migration)) {
                throw new UnexpectedValueException("Migration $name must return a callable");
            }

            $migration($this->connection);
            $this->connection->insert(self::TABLE, ['name' => $name, 'applied_at' => gmdate('Y-m-d H:i:s')]);
            $applied[] = $name;
        }

        return $applied;
    }

    /** @return array<string, string> Name => file of each migration not applied yet */
    public function pending(): array
    {
        $this->createTableIfMissing();

        $applied = $this->connection->fetchFirstColumn('SELECT name FROM ' . self::TABLE);

        $pending = [];
        foreach (glob($this->directory . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $applied, true)) {
                $pending[$name] = $file;
            }
        }
        ksort($pending, SORT_STRING);

        return $pending;
    }

    private function createTableIfMissing(): void
    {
        $schema = $this->connection->createSchemaManager();
        if ($schema->tableExists(self::TABLE)) {
            return;
        }

        $schema->createTable(
            Table::editor()
                ->setUnquotedName(self::TABLE)
                ->setColumns(self::stringColumn('name', 255), self::stringColumn('applied_at', 19))
                ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('name')->create())
                ->create(),
        );
    }

    /** @param non-empty-string $name */
    private static function stringColumn(string $name, int $length): Column
    {
        return Column::editor()->setUnquotedName($name)->setTypeName(Types::STRING)->setLength($length)->create();
    }
}
