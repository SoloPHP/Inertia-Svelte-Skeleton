<?php

namespace Console\Commands\Seed;

class StatusAction extends DefaultAction
{

    protected function action(): string
    {
        $pendingSeeds = $this->getPendingSeeds();
        $countSeeds = count($pendingSeeds);
        if ($countSeeds === 0) {
            return $this->response("All seeders have been executed");
        }

        $this->writeToConsole("The following seeders are pending execution:");
        $this->writeToConsole("-------------------------------------");
        foreach ($pendingSeeds as $migration) {
            $this->writeToConsole($migration);
        }
        return $this->response();
    }
}