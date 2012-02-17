<?php

//dibi::begin();
try {

    // kontrola na existenci sloupce orgjednotka_id
    dibi::query('SELECT orgjednotka_id FROM %n', $config['prefix'] ."spis"," LIMIT 1")->fetchAll();
    
    // sloupec existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
}
