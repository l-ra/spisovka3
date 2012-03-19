<?php

//dibi::begin();
try {

    // kontrola nastaveni sloupce popis
    $rows = dibi::query('SHOW COLUMNS FROM %n', $config['prefix'] ."spisovy_znak")->fetchAssoc('Field');
    
    if ( isset($rows['selected']) ) {
        // sloupec existuje
        $continue = 1;
    }
    
} catch (DibiException $e) {
    // vyvolana chyba
}
