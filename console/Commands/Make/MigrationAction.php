<?php

namespace Console\Commands\Make;

use Console\AbstractAction;

class MigrationAction extends AbstractAction
{
    protected function action(): string
    {
        $migrationName = $this->resolveArg(0);
        $timestamp = date('Ymd_His');
        $filename = $timestamp . '_' . $migrationName . '.sql';
        $filepath = $this->migrationsPath . '/' . $filename;
        $template = "-- Migration: $migrationName\n\n";
        file_put_contents($filepath, $template);
        $this->writeToConsole("A new migration file has been created:");
        $this->writeToConsole("---------------------------");
        return $this->response($filename);
    }
}