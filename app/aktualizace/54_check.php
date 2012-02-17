<?php

//dibi::begin();
try {

    // kontrola na existenci sloupce id
    dibi::query('SELECT id FROM %n', $config['prefix'] ."dokument"," LIMIT 1")->fetchAll();
    
    // id existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
}
