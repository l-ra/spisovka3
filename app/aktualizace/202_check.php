<?php

//dibi::begin();
try {

    // kontrola na existenci tabulky druh_zasilky
    dibi::query('SELECT id FROM %n', $config['prefix'] ."druh_zasilky"," LIMIT 1")->fetchAll();
    
    // tabulka existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
}
