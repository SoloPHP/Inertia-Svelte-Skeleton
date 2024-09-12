<?php

namespace Console\Commands\Migrate;

class DropAction extends DefaultAction
{
    protected function action(): string
    {
        $this->db->query("
            SET FOREIGN_KEY_CHECKS = 0;
            SET GROUP_CONCAT_MAX_LEN=32768;
            SET @tables = NULL;
            SELECT GROUP_CONCAT(table_name) INTO @tables
              FROM information_schema.tables
              WHERE table_schema = (SELECT DATABASE());
            SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
            PREPARE stmt FROM @tables;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SET FOREIGN_KEY_CHECKS = 1;
        ");

        return $this->response("All tables have been deleted.");
    }
}
