<?php

//dibi::begin();
try {

    // kontrola na existenci sloupce datum_spousteci_udalosti
    dibi::query('SELECT datum_spousteci_udalosti FROM %n', $config['prefix'] ."dokument"," LIMIT 1")->fetchAll();
    
    // sloupec existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
}
