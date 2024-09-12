<?php

namespace Console\Commands\Make;

use Console\AbstractAction;

class SeedAction extends AbstractAction
{

    protected function action(): string
    {
        $seedName = $this->resolveArg(0);
        $timestamp = date('Ymd_His');
        $filename = $timestamp . '_' . $seedName . '.sql';
        $filepath = $this->seedsPath . '/' . $filename;
        $template = "-- Seed: $seedName\n\n";
        file_put_contents($filepath, $template);
        $this->writeToConsole("A new seeder file has been created:");
        $this->writeToConsole("-----------------------");
        return $this->response($filename);
    }
}