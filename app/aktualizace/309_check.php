<?php

//dibi::begin();
try {

    // kontrola nastaveni sloupce popis
    $rows = dibi::query('SHOW COLUMNS FROM %n', $config['prefix'] ."dokument")->fetchAssoc('Field');
    
    if ( isset($rows['popis']) && $rows['popis']->Type == "text" ) {
        // sloupec je jiz nastaven
        $continue = 1;
    }
    
} catch (DibiException $e) {
    // vyvolana chyba
}
