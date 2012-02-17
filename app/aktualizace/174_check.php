<?php

//dibi::begin();
try {

    // kontrola na existenci sloupce cislo_doporuceneho_dopisu
    dibi::query('SELECT cislo_doporuceneho_dopisu FROM %n', $config['prefix'] ."dokument"," LIMIT 1")->fetchAll();
    
    // sloupec existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
}
