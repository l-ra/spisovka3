<?php
/**
 * 
 *  Aktualizacni skript OSS Spisove sluzby
 * 
 *  - provadi aktualizaci z nizsi verze na vyssi (aktualni)
 *  - platna pouze pro verzi 3 (upgrade z 3.x.x na 3.x.x)
 *  - pred provedenim se doporucuje provest zalohu souboru a databaze
 * 
 * 
 *
 * 
 *
 *  Nastaveni:
 * 
 *  MULTISITE - nastavuje rezim aktualizace
 *    0 = standalone - provede zakladni aktualizaci (vychozi stav - /client)
 *    1 = multisite - provede aktualizaci na vsech podsiti (/clients/*)
 * 
 *  Nevite-li v jakem rezimu jste, pak zvolte MULTISITE = 0
 * 
 */

    define('MULTISITE',0); // 0 - standalone, 1 - multisite
    
    
/* ************************************************************************** */
/* ***  V NASLEDUJICICH RADCICH JIZ NEZASAHOVAT !!!  ************************ */
/* ************************************************************************** */

    if ( strpos(__FILE__,"public/aktualizace.php") !== false ) {
        define("WWW_DIR", dirname(__FILE__) . "/..");
        define("PUBLIC_DIR", "");
    } else {
        define("WWW_DIR", dirname(__FILE__) );
        define("PUBLIC_DIR", "public/");
    }
    
/* ************************************************************************** */    
    
    include WWW_DIR . "/app/aktualizace/aktualizace.php";
    
/* ************************************************************************** */    