<?php

// Vraci boolean, jestli je revize jiz nainstalovana

function is_installed_171()
{
    try {
        // kontrola na existenci sloupce spisovy_znak_id
        dibi::query('SELECT spisovy_znak_id FROM [:PREFIX:spis] LIMIT 1')->fetchAll();
        
        // sloupec existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_174()
{
    try {
        // kontrola na existenci sloupce cislo_doporuceneho_dopisu
        dibi::query('SELECT cislo_doporuceneho_dopisu FROM [:PREFIX:dokument] LIMIT 1')->fetchAll();
        
        // sloupec existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_202()
{     
    //dibi::begin();
    try {
        // kontrola na existenci tabulky druh_zasilky
        dibi::query('SELECT id FROM [:PREFIX:druh_zasilky] LIMIT 1')->fetchAll();
        
        // tabulka existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_305()
{
    try {
        // kontrola na existenci sloupce orgjednotka_id
        dibi::query('SELECT orgjednotka_id FROM [:PREFIX:spis] LIMIT 1')->fetchAll();
        
        // sloupec existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze sloupec jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_309()
{
    try {
        // kontrola nastaveni sloupce popis
        $rows = dibi::query('SHOW COLUMNS FROM [:PREFIX:dokument]')->fetchAssoc('Field');
        
        if ( isset($rows['popis']) && $rows['popis']->Type == "text" ) {
            // sloupec je jiz nastaven
            return true;
        }
        
    } catch (DibiException $e) {
        // vyvolana chyba
        return false;
    }
}

function is_installed_321()
{
    try {
        // kontrola nastaveni sloupce popis
        $rows = dibi::query('SHOW COLUMNS FROM [:PREFIX:spisovy_znak]')->fetchAssoc('Field');
        
        if ( isset($rows['selected']) ) {
            // sloupec existuje
            return true;
        }
        
    } catch (DibiException $e) {
        // vyvolana chyba
        return false;
    }
}

function is_installed_450()
{
    try {
        // kontrola na existenci tabulky stat
        dibi::query('SELECT id FROM [:PREFIX:stat] LIMIT 1')->fetchAll();
        
        // tabulka stat existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_458()
{
    try {
        // kontrola na existenci tabulky stat
        dibi::query('SELECT id FROM [:PREFIX:zprava] LIMIT 1')->fetchAll();
        
        // tabulka zprava existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_500()
{
    // kontrola na existenci zaznamu
    $result = dibi::query('SELECT id FROM [:PREFIX:user_resource]', " WHERE code = 'DatovaSchranka' ")->fetchAll();
    
    // zaznam existuje, revize se neprovede
    if ($result)
        return true;
    else
        return false;
}

function is_installed_510()
{
    // kontrola na existenci zaznamu
    $result = dibi::query('SELECT id FROM [:PREFIX:user_resource]', " WHERE name = 'Administrace - spisy' ")->fetchAll();
    
    // zaznam existuje, revize se neprovede
    if ($result)
        return true;
    else
        return false;
}

function is_installed_520()
{
    try {
        // kontrola na existenci tabulky
        dibi::query('SELECT id FROM [:PREFIX:user_settings]')->fetchAll();
        
        // tabulka existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
        return false;
    }
}

function is_installed_530()
{
    try {
        // kontrola na existenci tabulky
        dibi::query('SELECT name FROM [:PREFIX:settings]')->fetchAll();
        
        // tabulka existuje, revize se neprovede
        return true;
        
    } catch (DibiException $e) {
        // vyvolana chyba, to znamena, ze tabulka jeste neexistuje, tudiz revize se provede.
        return false;
    }
}
