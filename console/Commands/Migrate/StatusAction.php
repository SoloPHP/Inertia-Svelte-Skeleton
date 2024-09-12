<?php

namespace Console\Commands\Migrate;

class StatusAction extends DefaultAction
{

    protected function action(): string
    {
        $pendingMigrations = $this->getPendingMigrations();
        $countMigrations = count($pendingMigrations);
        if ($countMigrations === 0) {
            return $this->response("All migrations have been executed");
        }

        $this->writeToConsole("The following migrations are pending execution:");
        $this->writeToConsole("----------------------------------------");
        foreach ($pendingMigrations as $migration) {
            $this->writeToConsole($migration);
        }
        return $this->response();
    }
}