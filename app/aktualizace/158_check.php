<?php

//dibi::begin();
try {

    // kontrola na existenci tabulky dokument_historie
    dibi::query('SELECT id FROM %n', $config['prefix'] ."dokument_historie"," LIMIT 1")->fetchAll();
    
    // tabulka dokument_historie existuje, revize se neprovede
    $continue = 1;
    
    /* Prejit na revizi 309 */
    /* Revize mezi 158 a 309 jsou jiz provedeny v revizi 158 */
    //$revision = 309;
    
} catch (DibiException $e) {
    // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
}
