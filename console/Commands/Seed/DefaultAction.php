<?php

namespace Console\Commands\Seed;

use Console\AbstractAction;

class DefaultAction extends AbstractAction
{

    protected function action(): string
    {
        $pendingSeeds = $this->getPendingSeeds();
        if (count($pendingSeeds) === 0) return $this->response('All seeders have already been executed.');

        $this->writeToConsole("Executed seeders:");
        $this->writeToConsole("-----------------");
        foreach ($pendingSeeds as $seed) {
            $sqlFile = "$this->seedsPath/$seed.sql";
            $sqlQuery = file_get_contents($sqlFile);
            if($this->db->query($sqlQuery)) {
                $this->db->query("INSERT INTO db_seeds SET name = ?s", $seed);
                $this->writeToConsole($seed);
            }
        }
        return $this->response();
    }

    protected function getAppliedSeeds(): array
    {
        $this->db->query('SELECT name FROM db_seeds');
        return array_column($this->db->results(), 'name');
    }

    protected function getAvailableSeeds(): array
    {
        $availableSeeds = [];
        $files = array_diff(scandir($this->seedsPath), array('..', '.'));
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $seedName = pathinfo($file, PATHINFO_FILENAME);
                $availableSeeds[] = $seedName;
            }
        }
        return $availableSeeds;
    }

    protected function getPendingSeeds(): array
    {
        $appliedSeeds = $this->getAppliedSeeds();
        $availableSeeds = $this->getAvailableSeeds();
        return array_diff($availableSeeds, $appliedSeeds);
    }
}