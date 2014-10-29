<?php
/**
 * 
 *  Aktualizacni skript OSS Spisove sluzby
 * 
 *  - provadi aktualizaci databaze na aktualni verzi
 *  - platny pouze pro verzi 3 (upgrade z 3.x.x na 3.x.x)
 *  - pred provedenim se doporucuje provest zalohu databaze
 * 
 * 
 *  Nastaveni:
 * 
 *  MULTISITE - nastavuje rezim aktualizace
 *    0 = standalone - standardni instalace pro jednoho klienta
 *    1 = multisite  - instalace pro vice klientu (hosting)
 * 
 */

    define('MULTISITE', 0); // 0 - standalone, 1 - multisite
    
    
    define("APP_DIR", dirname(__FILE__) . "/app");
    define("LIBS_DIR", dirname(__FILE__) . "/libs");
    define("PUBLIC_URI", "public/");
    
/* ************************************************************************** */    
    
    include APP_DIR . "/aktualizace/aktualizace.php";
    
/* ************************************************************************** */    