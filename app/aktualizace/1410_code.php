<?php

function revision_1410_before()
{
    $db_name = dibi::getConnection()->getConfig('database');

    $res = dibi::query('SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = %s AND REFERENCED_TABLE_NAME IS NOT NULL',
                    $db_name)->fetchAll();
    foreach ($res as $row) {
        $table = $row->TABLE_NAME;
        $constraint = $row->CONSTRAINT_NAME;
        dibi::query('ALTER TABLE %n DROP FOREIGN KEY %n', $table, $constraint);
    }

    $res = dibi::query('SELECT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = %s AND INDEX_NAME LIKE %s',
                    $db_name, 'fk_%')->fetchAll();
    foreach ($res as $row) {
        $table = $row->TABLE_NAME;
        $index = $row->INDEX_NAME;
        dibi::query('ALTER TABLE %n DROP KEY %n', $table, $index);
    }

    echo "Integritní omezení byla všechna úspěšně smazána.";
}
