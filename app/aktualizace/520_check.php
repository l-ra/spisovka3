<?php

try {
    // kontrola na existenci tabulky stat
    dibi::query('SELECT id FROM %n', $config['prefix'] ."user_settings")->fetchAll();
    
    // tabulka zprava existuje, revize se neprovede
    $continue = 1;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
}
    
?>