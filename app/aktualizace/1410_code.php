<?php

function revision_1410_before()
{
    global $db_config;
    
    $res = dibi::query('SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = %s AND REFERENCED_TABLE_NAME IS NOT NULL',
                    $db_config['database'])->fetchAll();
    foreach ($res as $row) {
        $table = $row->TABLE_NAME;
        $constraint = $row->CONSTRAINT_NAME;
        dibi::query('ALTER TABLE %n DROP FOREIGN KEY %n', $table, $constraint);
    }
    
    echo "Integritní omezení byla všechna úspěšně smazána.";
}
