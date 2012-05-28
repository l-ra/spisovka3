<?php

//dibi::begin();
try {

    // kontrola na existenci sloupce id
    dibi::query('SELECT dokument_id FROM %n', $config['prefix'] ."dokument"," LIMIT 1")->fetchAll();
    
    // id neexistuje, revize se provede
    $continue = 0;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze sloupec jeste neexistuje nebo jina chyba, tudiz revize se provede.
    $continue = 1;
}
