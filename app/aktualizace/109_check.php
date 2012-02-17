<?php

try {

    // kontrola na existenci sloupce identifikator
    dibi::query('SELECT identifikator FROM %n', $config['prefix'] ."epodatelna"," LIMIT 1")->fetchAll();
    
    // identifikator existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
}
