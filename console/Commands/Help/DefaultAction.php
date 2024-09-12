<?php

namespace Console\Commands\Help;

use Console\AbstractAction;

class DefaultAction extends AbstractAction
{

    protected function action(): string
    {
        $this->writeToConsole('CLI commands:');
        $this->writeToConsole('------------');
        $this->writeToConsole('php cli help - help for available commands');
        $this->writeToConsole('php cli migrate - execute migrations');
        $this->writeToConsole('php cli migrate:drop - drop all tables');
        $this->writeToConsole('php cli migrate:status - migration status');
        $this->writeToConsole('php cli make:migration {name} - create a new migration');
        $this->writeToConsole('php cli seed - run all seeders');
        $this->writeToConsole('php cli seed:status - seeder status');
        $this->writeToConsole('php cli make:seed {name} - create a new seeder');
        return $this->response();
    }
}