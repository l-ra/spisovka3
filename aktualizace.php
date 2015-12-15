<?php
/**
 *  Aktualizacni skript OSS Spisove sluzby
 * 
 *  - provadi aktualizaci databaze na aktualni verzi
 *  - platny pouze pro verzi 3 (upgrade z 3.x.x na 3.x.x)
 *  - pred provedenim se doporucuje provest zalohu databaze
 */
    
    define("APP_DIR", __DIR__ . "/app");
    define("LIBS_DIR", __DIR__ . "/libs");
    define("PUBLIC_URI", "public/");
        
    include APP_DIR . "/aktualizace/aktualizace.php";
    
/* ************************************************************************** */    