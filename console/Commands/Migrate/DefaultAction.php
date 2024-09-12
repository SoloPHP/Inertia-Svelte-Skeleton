<?php

namespace Console\Commands\Migrate;

use Console\AbstractAction;

class DefaultAction extends AbstractAction
{

    protected function action(): string
    {
        $pendingMigrations = $this->getPendingMigrations();
        if (count($pendingMigrations) === 0) return $this->response('All migrations have already been executed.');

        $this->writeToConsole("Executed migrations:");
        $this->writeToConsole("---------------------");
        foreach ($pendingMigrations as $migration) {
            $sqlFile = "$this->migrationsPath/$migration.sql";
            $sqlQuery = file_get_contents($sqlFile);
            if ($this->db->query($sqlQuery)) {
                $this->db->query("INSERT INTO db_migrations SET name = ?s", $migration);
                $this->writeToConsole($migration);
            }
        }
        return $this->response();
    }

    protected function getAppliedMigrations(): array
    {
        $this->db->query('SELECT name FROM db_migrations');
        return array_column($this->db->results(), 'name');
    }

    protected function getAvailableMigrations(): array
    {
        $availableMigrations = [];
        $files = array_diff(scandir($this->migrationsPath), array('..', '.'));
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $migrationName = pathinfo($file, PATHINFO_FILENAME);
                $availableMigrations[] = $migrationName;
            }
        }
        return $availableMigrations;
    }

    protected function getPendingMigrations(): array
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $availableMigrations = $this->getAvailableMigrations();
        return array_diff($availableMigrations, $appliedMigrations);
    }
}