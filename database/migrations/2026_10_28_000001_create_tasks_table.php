<?php

declare(strict_types=1);

/*
 * Built with the DBAL schema API rather than raw SQL, so the same migration
 * runs on SQLite, MySQL and PostgreSQL.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

return static function (Connection $connection): void {
    $connection->createSchemaManager()->createTable(
        Table::editor()
            ->setUnquotedName('tasks')
            ->setColumns(
                Column::editor()
                    ->setUnquotedName('id')
                    ->setTypeName(Types::INTEGER)
                    ->setAutoincrement(true)
                    ->create(),
                Column::editor()
                    ->setUnquotedName('title')
                    ->setTypeName(Types::STRING)
                    ->setLength(255)
                    ->create(),
                Column::editor()
                    ->setUnquotedName('done')
                    ->setTypeName(Types::BOOLEAN)
                    ->setDefaultValue(false)
                    ->create(),
                Column::editor()
                    ->setUnquotedName('created_at')
                    ->setTypeName(Types::DATETIME_IMMUTABLE)
                    ->create(),
                Column::editor()
                    ->setUnquotedName('updated_at')
                    ->setTypeName(Types::DATETIME_IMMUTABLE)
                    ->create(),
            )
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create())
            ->setIndexes(Index::editor()->setUnquotedName('tasks_done_index')->setUnquotedColumnNames('done')->create())
            ->create(),
    );
};
