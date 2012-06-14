<?php

//dibi::begin();
try {

    // kontrola na existenci tabulky stat
    dibi::query('SELECT id FROM %n', $config['prefix'] ."stat"," LIMIT 1")->fetchAll();
    
    // tabulka stat existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
}
