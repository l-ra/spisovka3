<?php

/**
 * 
 *  Migracni skript OSS Spisove sluzby
 * 
 *  - provadi migraci dat z verze 2.1 na verzi 3 (aktualni)
 *
 *
 *  Nastaveni:
 *
 *  - nastaveni cesty pro S3
 *    Pouzijte stejne nastaveni jako v pripade index.php.
 *    Pripadne ponechte beze zmen. 
 * 
 *    WWW_DIR - cesta k tomuto souboru (nemenit)
 *    APP_DIR - cesta k adresari /app z pohledu tohoto souboru
 *    LIBS_DIR - cesta k adresari /libs z pohledu tohoto souboru
 *    CLIENT_DIR - cesta k uzivatelskemu adresari /client z pohledu tohoto souboru 
 *
 *  - nastaveni cesty pro S2
 *    
 *    S2_DIR - nastavte cestu k root adresari, kde se nachazi Spisova sluzba verze 2
 *
 *
 *  Pouziti:
 * 
 *  - pro spravnou funkcnost je potreba spravne nastavit cesty (viz nastaveni)
 *  - spustte tento skript v prohlizeci zadanim http://<adresa>/migrace.php
 *  - pokud je vse v poradku, muzete kliknout na odkaz "spustit migraci dat"
 * 
 *  POZOR! Proces migrace maze vsechny data ve Spisove sluzbe verzi 3
 *  
 *  V zavislosti na mnozstvi dat muze migrace trvat i nekolik desitek minut.
 *  Behem migraci neprerusujte akci!
 * 
 */

// Nastaveni cesty k aplikaci S3 (identicke s index.php - pro nacteni pomocnych funkci)
define('WWW_DIR', dirname(__FILE__) );
define('APP_DIR', WWW_DIR . '/app');
define('LIBS_DIR', WWW_DIR . '/libs');

// Nastaveni cesty pro S3 (klientske nastaveni verze 3)
define('CLIENT_DIR', WWW_DIR . '/client');

// Nastaveni cesty pro S2 (absolutni cesta k root aplikaci verze 2)
define('S2_DIR', '/cesta/ke/spisovce_2');

// Skartační lhůta pro migrované spisy v rocích
define('SKARTACNI_LHUTA_SPISU', 5);



/* **************************************************************************
 *
 *  Zde konci uzivatelska cast.
 *  Nasledujici radky jiz nemenit!
 *
 ************************************************************************** */
/************************************************************************** */
/* **************************************************************************
 *
 *  Databaze S2 (automaticky z cesty nebo vyplnit rucne)
 *
 ************************************************************************** */

header('Content-type: text/html; charset="utf-8"',true);
 
if (!file_exists(S2_DIR .'/system/db_nastaveni.php') ) {
    echo "<pre>";
    echo "Nelze načíst nastavení ze Spisovky 2! Migraci nelze provést.<br/>Zkontrolujte nastavení cesty.";
    echo "</pre>";
    exit;
}

if (!file_exists(CLIENT_DIR ."/configs/system.ini") ) {
    echo "<pre>";
    echo "Nelze načíst nastavení ze Spisovky 3! Migraci nelze provést.<br/>Zkontrolujte nastavení cesty.";
    echo "</pre>";
    exit;
}

include S2_DIR .'/system/db_nastaveni.php';
//define("SPIS_DB_SERVER"  ,"localhost");
//define("SPIS_DB_USER"    ,"");
//define("SPIS_DB_PASS"    ,"");
//define("SPIS_DB_DATABASE","");
//define("SPIS_DB_PREFIX"  ,"");

$S2_DB_CONFIG = array(
    "driver"   => "mysql",
    "host"     => SPIS_DB_SERVER,
    "username" => SPIS_DB_USER,
    "password" => SPIS_DB_PASS,
    "database" => SPIS_DB_DATABASE,
    "charset"  => "utf8", /* potreba zkontrolovat skutecne kodovani v DB * starsi verze nemely nastavene set names, tam je pak potreba nastavit "latin1" */
    "prefix"   => SPIS_DB_PREFIX,
);

/* **************************************************************************
 *
 *  Databaze S3 (automaticky z nastaveni nebo rucne)
 *
 ************************************************************************** */

$S3_DB_INI = parse_ini_file(CLIENT_DIR ."/configs/system.ini",true);
$S3_DB_CONFIG = array(
    "driver"   => $S3_DB_INI['common']['database.driver'],
    "host"     => $S3_DB_INI['common']['database.host'],
    "username" => $S3_DB_INI['common']['database.username'],
    "password" => $S3_DB_INI['common']['database.password'],
    "database" => $S3_DB_INI['common']['database.database'],
    "charset"  => $S3_DB_INI['common']['database.charset'],
    "prefix"   => $S3_DB_INI['common']['database.prefix'],
);


/* **************************************************************************
 *
 *  Pripojeni DB
 * 
 ************************************************************************** */

session_start();
set_time_limit(0); // dle poctu klidne i 30 minut
ini_set('memory_limit', '300M');
ini_set('display_errors', 1);

try {

include LIBS_DIR .'/dibi/dibi.php';

include LIBS_DIR .'/Nette/Object.php';
include APP_DIR .'/models/BaseModel.php';
include APP_DIR .'/models/FileModel.php';
include APP_DIR .'/components/UUID.php';

if ( isset($_POST['go']) ) {
    define('MIGRACE',1); // spusti migraci, 0 = test / pouze vystup
    define('TEST_KONTROLA',0); // spusti jen pocatecni kontrolu
} else {
    define('MIGRACE',0); // spusti migraci, 0 = test / pouze vystup
    define('TEST_KONTROLA',1); // spusti jen pocatecni kontrolu
}

if (MIGRACE) debug_head('Konfigurace databáze');
$S2_DB_CONFIG_temp = $S2_DB_CONFIG;
$S2_DB_CONFIG_temp['password'] = "***";
$S3_DB_CONFIG_temp = $S3_DB_CONFIG;
$S3_DB_CONFIG_temp['password'] = "***";
if (MIGRACE) debug($S2_DB_CONFIG_temp,'DB Config S2');
if (MIGRACE) debug($S3_DB_CONFIG_temp,'DB Config S3');
unset($S2_DB_CONFIG_temp,$S3_DB_CONFIG_temp);

$S2 = new DibiConnection($S2_DB_CONFIG);
dibi::addSubst('S2', $S2_DB_CONFIG['prefix']);
define('S2_', $S2_DB_CONFIG['prefix']);
$S3 = new DibiConnection($S3_DB_CONFIG);
dibi::addSubst('S3', $S3_DB_CONFIG['prefix']);
define('S3_', $S3_DB_CONFIG['prefix']);

} catch (Exception $e) {
    echo "<pre>";
    echo "Chyba při načítání migračního skriptu! Migraci nelze provést.";
    echo "<br/><br />Chyba: ". $e->getMessage();
    echo "<br /><br/>Zkontrolujte správnost nastavení konfiguračních souborů spisovek, případně dostupnost daných služeb.";
    echo "</pre>";
    exit;
}

echo "<pre>";
//exit;
$ERROR_LOG = array();

/* **************************************************************************
 *
 * Migrace nastaveni
 *
 ************************************************************************** */
if (MIGRACE) debug_head('Migrace nastavení',2);

include S2_DIR .'/system/system_nastaveni.php';
$S3_nastaveni_klient = parse_ini_file(CLIENT_DIR .'/configs/klient.ini',TRUE);

$S3_nastaveni_klient['urad']['nazev'] = SPIS_URAD_NAZEV;
$S3_nastaveni_klient['urad']['plny_nazev'] = SPIS_URAD_NAZEV;
$S3_nastaveni_klient['urad']['zkratka'] = SPIS_URAD_ZKRATKA;

$S3_CJ = parseCJ(SPIS_MASKA_JEDNACIHO_CISLA);
$S3_nastaveni_klient['cislo_jednaci']['maska'] = $S3_CJ['maska'];
$S3_nastaveni_klient['cislo_jednaci']['typ_evidence'] = 'priorace';

if (MIGRACE) write_ini_file(CLIENT_DIR .'/configs/klient.ini', $S3_nastaveni_klient);
if (MIGRACE) debug($S3_nastaveni_klient);
if (MIGRACE) echo "\n   => <span style='color:green'>nastavení klienta přeneseno</span>";


$S3_nastaveni_epod = parse_ini_file(CLIENT_DIR .'/configs/epodatelna.ini',TRUE);

include S2_DIR .'/email/nastaveni.php';
$S3_nastaveni_epod['email']['0.ucet'] = "Centrální podatelna";
$S3_nastaveni_epod['email']['0.aktivni'] = EMAIL_ENABLE;
$S3_nastaveni_epod['email']['0.typ'] = EMAIL_TYP;
$S3_nastaveni_epod['email']['0.server'] = EMAIL_SERVER;
$S3_nastaveni_epod['email']['0.port'] = EMAIL_PORT;
$S3_nastaveni_epod['email']['0.inbox'] = EMAIL_INBOX;
$S3_nastaveni_epod['email']['0.login'] = EMAIL_LOGIN;
$S3_nastaveni_epod['email']['0.password'] = EMAIL_PASS;
$S3_nastaveni_epod['email']['0.podatelna'] = EMAIL_PODATELNA;
$S3_nastaveni_epod['email']['0.only_signature'] = EMAIL_SIGNATURE;
$S3_nastaveni_epod['email']['0.qual_signature'] = EMAIL_QSIGNATURE;

$S3_nastaveni_epod['odeslani']['0.ucet'] = "Primární email";
$S2_email = EMAIL_ADDRESS;
$S3_nastaveni_epod['odeslani']['0.aktivni'] = empty($S2_email)?0:1;
$S3_nastaveni_epod['odeslani']['0.typ_odeslani'] = EMAIL_TYPE_SEND;
$S3_nastaveni_epod['odeslani']['0.jmeno'] = "";
$S3_nastaveni_epod['odeslani']['0.email'] = EMAIL_ADDRESS;
if ( EMAIL_TYPE_SEND == 1 ) {
    $S2cert = S2_DIR ."/". EMAIL_CERT;
    $S3cert = CLIENT_DIR ."/configs/files/certifikat_email_0.crt";
    $S3_nastaveni_epod['odeslani']['0.cert'] = $S3cert;
    $S3_nastaveni_epod['odeslani']['0.cert_key'] = "";
    $S3_nastaveni_epod['odeslani']['0.cert_pass'] =EMAIL_CERTPASSPHRASE;
} else {
    $S3_nastaveni_epod['odeslani']['0.cert'] = "";
    $S3_nastaveni_epod['odeslani']['0.cert_key'] = "";
    $S3_nastaveni_epod['odeslani']['0.cert_pass'] = "";
}

include S2_DIR .'/isds/nastaveni.php';
$S3_nastaveni_epod['isds']['0.ucet'] = "Centrální podatelna";
$S3_nastaveni_epod['isds']['0.aktivni'] = SPIS_ISDS;
$S3_nastaveni_epod['isds']['0.idbox'] = ISDS_IDBOX;
$S3_nastaveni_epod['isds']['0.login'] = ISDS_LOGIN;
$S3_nastaveni_epod['isds']['0.password'] = ISDS_PASS;
$S3_nastaveni_epod['isds']['0.podatelna'] = ISDS_PODATELNA;
$S3_nastaveni_epod['isds']['0.test'] = ISDS_TEST;
$S3_nastaveni_epod['isds']['0.typ_pripojeni'] = 0;

if (MIGRACE) write_ini_file(CLIENT_DIR .'/configs/epodatelna.ini', $S3_nastaveni_epod);
if (MIGRACE) debug($S3_nastaveni_epod);
if (MIGRACE) echo "\n   => <span style='color:green'>nastavení e-podatelny přeneseno</span>";

if (TEST_KONTROLA==1) {
    
    echo "****************************************************************************************************\n";
    echo "****************************************************************************************************\n";
    echo "\n";
    echo "  Migrační skript OSS Spisové služby \n";
    echo "\n";
    echo "****************************************************************************************************\n";
    echo "****************************************************************************************************\n";
    echo "\n";
    echo "  Tento skript provádí migraci dat z verze 2.1 na verzi 3.\n";
    echo "\n";
    echo "  Vidíte-li tuto stránku, pak je nastavení v pořádku. Můžete provést\n";
    echo "  migraci dat kliknutím na odkaz 'spustit migraci dat'.\n";
    echo "  V závislosti na množství dat může migrace trvat i půl hodiny.\n";
    echo "  Během procesu nepřerušujte stránku, nezavírejte prohlížeč ani nevypínejte počítač.\n";
    echo "  Pokud k tomu dojde, bude migrace neúplná. Pak je potřeba migraci provést znovu.\n";
    echo "\n";
    echo "  POZOR! Migrace dat provádí odstranění veškerých dat ze Spisové služby verze 3.\n";
    echo "  Migraci lze provést pouze na čerstvě nainstalovaném aplikaci.\n";
    echo "\n";
    echo "\n";
    echo "\n";
    
    echo "\n Počáteční informace o procesu migrace: \n\n";
    
    $cas = 0;
    
    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:spis_znak]')->fetchSingle();
    echo "  Migrace spisových znaků\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.005;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";
    
    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:zpusob_vyrizeni]')->fetchSingle();
    echo "  Migrace způsobu vyřízení\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.005;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";

    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:odesilatele]')->fetchSingle();
    echo "  Migrace subjektů\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.005;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";

    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:orgjednotky]')->fetchSingle();
    echo "  Migrace organizačních jednotek\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.04;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";

    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:zamestnanci]')->fetchSingle();
    echo "  Migrace zamestnanců a jejich uživatelských účtů\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.0252;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";

    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:spisy]')->fetchSingle();
    echo "  Migrace spisů\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.005;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";
    
    $pocet = 0; $zpr = 0;
    $pocet = $S2->query('SELECT count(*) FROM [:S2:dokumenty]')->fetchSingle();
    echo "  Migrace dokumentů včetně jejich workflow\n";
    echo "     - počet: $pocet \n";
    $zpr = $pocet * 0.0911;
    $cas += $zpr;
    echo "     - odhadovaný čas zpracování: ". number_format($zpr,3,","," ") ." sekund \n";
    
    echo "\n\n";
    echo "  Celkový odhadovaný čas trvání migrace: <strong>". number_format($cas,3,","," ") ." sekund (". number_format($cas/60,0) ." minut a ". number_format($cas%60,0) ." sekund)</strong>.\n";
    echo "  (* celkový čas je pouze hrubý odhad. Může se lišit v závislosti na výkonu serveru a zatížení. Rozpětí přibližně +- 50%)\n";
    
    
    
    
    
    
    echo "\n";
    echo "\n";
    echo "\n";
    echo '<h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<form action="migrace.php" method="post">
<input name="go" type="submit" value="Spustit migraci dat"></input>
</form></h3>';
    echo "\n";
    echo "\n";
    
 //*  Migracni skript OSS Spisove sluzby
 //* 
 //*  - provadi migraci dat z verze 2.1 na verzi 3 (aktualni)    
    
    exit;
}

$S3->query("SET FOREIGN_KEY_CHECKS=0;");
$S3->query("SET SQL_MODE='';");

/* **************************************************************************
 *
 * Migrace spisovych znaku
 *
 ************************************************************************** */
debug_head('Migrace spisových znaků', 2);

$S2_spisove_znaky = $S2->query('SELECT * FROM [:S2:spis_znak]')->fetchAll();
if ( count($S2_spisove_znaky)>0 ) {
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'spisovy_znak');
    foreach ( $S2_spisove_znaky as $S2_sz ) {
        echo "\n>> ".htmlspecialchars($S2_sz->id_spisznak)." = ".htmlspecialchars($S2_sz->znak)." (".htmlspecialchars($S2_sz->popis).") - ".htmlspecialchars($S2_sz->skartacni_znak)."/".htmlspecialchars($S2_sz->skartacni_lhuta);
        if (MIGRACE):
        try {
            
            $nazev = (empty($S2_sz->znak))?sprintf("%04d",$S2_sz->id_spisznak):$S2_sz->znak;
            $sekvence = $nazev .".". $S2_sz->id_spisznak;
            
            $S3->insert(S3_.'spisovy_znak', array(
                'id' => (int) $S2_sz->id_spisznak,
                'parent_id' => null,
                'nazev' => $nazev,
                'popis' => (string) $S2_sz->popis,
                'skartacni_znak' => !empty($S2_sz->skartacni_znak[0])?$S2_sz->skartacni_znak[0]:null,
                'skartacni_lhuta' => intval($S2_sz->skartacni_lhuta),
                'sekvence' => (int) $S2_sz->id_spisznak,
                'sekvence_string' => $sekvence,
                'stav' => (int) $S2_sz->stav,
                'spousteci_udalost_id' => 3,
                'date_created' => new DateTime($S2_sz->vytvoreno),
                'user_created' => 1
            ))->execute();
            echo "\n   => <span style='color:green'>přeneseno</span>";
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
}
unset($S2_spisove_znaky, $nazev, $sekvence);
/* **************************************************************************
 *
 * Migrace způsob vyřízení
 *
 ************************************************************************** */
debug_head('Migrace způsob vyřízení', 2);

if ( isset($_SESSION['S3_zpvyrizeni']) ) {
    $S3_zpvyrizeni = $_SESSION['S3_zpvyrizeni'];
} else {
$S2_vyriz = $S2->query('SELECT * FROM [:S2:zpusob_vyrizeni]')->fetchAll();
$S3_zpvyrizeni = array();

if ( count($S2_vyriz)>0 ) {
    if (MIGRACE) $S3->query('DELETE FROM '.S3_.'zpusob_vyrizeni WHERE stav=2');
    foreach ( $S2_vyriz as $S2_vy ) {
        echo "\n>> ".htmlspecialchars($S2_vy->id_zpvyrizeni)." = ".htmlspecialchars($S2_vy->nazev);
        if (MIGRACE):
        try {
            $zpusob_vyrizeni = $S3->insert(S3_.'zpusob_vyrizeni', array(
                'nazev' => $S2_vy->nazev,
                'stav' => 0,
            ))->execute(dibi::IDENTIFIER);

            $S3_zpvyrizeni[ $S2_vy->id_zpvyrizeni ] = $zpusob_vyrizeni;

            echo "\n   => <span style='color:green'>přeneseno</span>";
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
    if (MIGRACE) $_SESSION['S3_zpvyrizeni'] = $S3_zpvyrizeni;
}
} // if session
unset($S2_vyriz);

/* **************************************************************************
 *
 * Migrace spisu
 *
 ************************************************************************** */
debug_head('Migrace spisů', 2);
$S2_spisy = $S2->query('SELECT * FROM [:S2:spisy] ORDER BY id_spis')->fetchAll();

if ( count($S2_spisy)>0 ) {
    if (MIGRACE) {
        $S3->query('TRUNCATE TABLE '.S3_.'spis');
        $S3->insert(S3_.'spis', array(
                'id' => 1,
                'parent_id' => null,
                'spousteci_udalost_id' => 3,
                'spisovy_znak_id' => null,
                'nazev' => 'SPISY',
                'popis' => '',
                'typ' => 'F',
                'sekvence' => 1,
                'sekvence_string' => 'SPISY.1',
                'skartacni_znak' => 'V',
                'skartacni_lhuta' => SKARTACNI_LHUTA_SPISU,
                'stav' => 1,
                'datum_otevreni' => new DateTime(),
                'date_created' => new DateTime(),
                'user_created' => 1,
        ))->execute();        
    }
    foreach ( $S2_spisy as $S2_s ) {
        
        if ($S2_s->id_spis == 1) continue;
        
        echo "\n>> ".htmlspecialchars($S2_s->id_spis+1)." = ".htmlspecialchars($S2_s->cislo_spisu);
        if (MIGRACE):
        try {
            $nazev = (empty($S2_s->cislo_spisu))?"Spis č.".($S2_s->id_spis+1):$S2_s->cislo_spisu;
            $sekvence = $nazev .".". ($S2_s->id_spis+1);
            $S3->insert(S3_.'spis', array(
                'id' => (int) ($S2_s->id_spis+1),
                'parent_id' => 1,
                'spousteci_udalost_id' => 3,
                'spisovy_znak_id' => null,
                'nazev' => $nazev,
                'popis' => (string) $S2_s->poznamka,
                'typ' => 'S',
                'sekvence' => '1.'. ($S2_s->id_spis+1),
                'sekvence_string' => "SPISY.1#". $sekvence,
                'skartacni_znak' => 'V',
                'skartacni_lhuta' => 1000,
                'stav' => (int) $S2_s->stav,
                'datum_otevreni' => new DateTime($S2_s->vytvoreno),
                'date_created' => new DateTime($S2_s->vytvoreno),
                'user_created' => 1,
            ))->execute();
            echo "\n   => <span style='color:green'>přeneseno</span>";
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
}
unset($S2_spisy,$nazev,$sekvence);

/* **************************************************************************
 *
 * Migrace subjektu
 *
 ************************************************************************** */
debug_head('Migrace subjektů', 2);
$S2_sub = $S2->query('SELECT * FROM [:S2:odesilatele]')->fetchAll();

if ( count($S2_sub)>0 ) {
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'subjekt');
    foreach ( $S2_sub as $S2_s ) {
        echo "\n>> ".htmlspecialchars($S2_s->id_odesilatel ." = ". $S2_s->nazev);
        if (MIGRACE):
        
        $nazev = (empty($S2_s->nazev))?"Subjekt č.".$S2_s->id_odesilatel:$S2_s->nazev;
        
        if ( $S2_s->typ == "FO" || $S2_s->typ == "PFO" ) {
            $prijmeni = $nazev;
        } else {
            $prijmeni = "";
        }

        try {
            $S3->insert(S3_.'subjekt', array(
                'id' => (int) $S2_s->id_odesilatel,
                'stav' => (int) $S2_s->stav,
                'type' => (empty($S2_s->typ))?"PO":$S2_s->typ,
                'ic' => '',
                'dic' => '',
                'nazev_subjektu' => $nazev,
                'prijmeni' => $prijmeni,
                'jmeno' => '',
                'adresa_mesto' => $S2_s->mesto,
                'adresa_ulice' => $S2_s->ulice,
                'adresa_cp' => $S2_s->cislo_popisne,
                'adresa_co' => $S2_s->cislo_orientacni,
                'adresa_psc' => $S2_s->psc,
                'adresa_stat' => 'CZE',
                'email' => $S2_s->email,
                'telefon' => '',
                'id_isds' => $S2_s->isds_idbox,
                'poznamka' => '',
                'date_created' => new DateTime($S2_s->vytvoreno),
                'user_created' => 1
            ))->execute();
            echo "\n   => <span style='color:green'>přeneseno</span>";
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
}
unset($S2_sub,$nazev,$prijmeni);

/* **************************************************************************
 *
 * Migrace organizacni jednotky
 *
 ************************************************************************** */
debug_head('Migrace organizačních jednotek', 2);

if ( !isset($_SESSION['S3_orgjednotka']) ) {

$S2_org = $S2->query('SELECT * FROM [:S2:orgjednotky]')->fetchAll();

if ( count($S2_org)>0 ) {

    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'orgjednotka');
    if (MIGRACE) $S3->query('DELETE FROM '.S3_."user_rule WHERE privilege LIKE 'orgjednotka_%';");
    if (MIGRACE) $S3->query('DELETE FROM '.S3_."user_role WHERE fixed=0;");
    if (MIGRACE) $S3->query('DELETE FROM '.S3_."user_acl WHERE role_id > 8;"); // cokoli nad id 8, pod jsou fixni role

    foreach ( $S2_org as $S2_o ) {
        echo "\n>> ".htmlspecialchars($S2_o->id_orgjednotka ." = ". $S2_o->zkratka ." - ". $S2_o->nazev);
        if (MIGRACE):
        try {
            // Pridani organizacni jednotky
            $code = (empty($S2_o->zkratka))?"OJ".$S2_o->id_orgjednotka:$S2_o->zkratka;
            $plny_nazev = (empty($S2_o->nazev))?"Organizační jednotka č.".$S2_o->id_orgjednotka:$S2_o->nazev;
            $zkraceny_nazev = (empty($S2_o->nazev))?"Org. jednotka č.".$S2_o->id_orgjednotka:$S2_o->nazev;
            $sekvence = $zkraceny_nazev .".". $S2_o->id_orgjednotka;

            $org_id = null;
            $org_id = $S3->insert(S3_.'orgjednotka', array(
                'id' => (int) $S2_o->id_orgjednotka,
                'parent_id' => null,
                'plny_nazev' => $plny_nazev,
                'zkraceny_nazev' => $zkraceny_nazev,
                'ciselna_rada' => $code,
                'note' => '',
                'stav' => (int) $S2_o->stav,
                'date_created' => new DateTime($S2_o->vytvoreno),
                'user_created' => 1,
                'date_modified' => new DateTime($S2_o->vytvoreno),
                'user_modified' => 1,
                'sekvence' => (int) $S2_o->id_orgjednotka,
                'sekvence_string' => $sekvence
            ))->execute(dibi::IDENTIFIER);
            echo "\n   => <span style='color:green'>přeneseno (id $org_id)</span>";

            if ( $org_id ) {
                // Pridani jednotlivych roli

                // Pravidlo pro organizacni jednotku
                $rule_id = null;
                $rule_id = $S3->insert(S3_.'user_rule', array(
                    'name' => "Oprávnění pro org. jednotku ". $zkraceny_nazev,
                    'privilege' => "orgjednotka_". $org_id,
                ))->execute(dibi::IDENTIFIER);
                echo "\n   => <span style='color:green'>vytvořeno pravidlo pro organizační jednotku $code (id $rule_id)</span>";

                // Referent
                $role_id = null;
                $role_id = $S3->insert(S3_.'user_role', array(
                    'parent_id' => 4,
                    'code' => "referent_". $org_id,
                    'name' => "referent ". $code,
                    'active' => 1,
                    'date_created' => new DateTime(),
                    'orgjednotka_id' => $org_id,
                    'fixed' => 0,
                    'order' => 10
                ))->execute(dibi::IDENTIFIER);
                if ( $role_id ) {
                    $S3->insert(S3_.'user_acl', array(
                        'role_id' => $role_id,
                        'rule_id' => $rule_id,
                        'allowed' => 'Y',
                    ))->execute();
                    echo "\n   => <span style='color:green'>vytvořena role referent pro org. jednotku $code (id $role_id)</span>";
                }

                // Vedouci
                $role_id = null;
                $role_id = $S3->insert(S3_.'user_role', array(
                    'parent_id' => 5,
                    'code' => "vedouci_". $org_id,
                    'name' => "vedoucí ". $code,
                    'active' => 1,
                    'date_created' => new DateTime(),
                    'orgjednotka_id' => $org_id,
                    'fixed' => 0,
                    'order' => 30
                ))->execute(dibi::IDENTIFIER);
                if ( $role_id ) {
                    $S3->insert(S3_.'user_acl', array(
                        'role_id' => $role_id,
                        'rule_id' => $rule_id,
                        'allowed' => 'Y',
                    ))->execute();
                    echo "\n   => <span style='color:green'>vytvořena role vedoucí pro org. jednotku $code (id $role_id)</span>";
                }

                // Podatelna
                $role_id = null;
                $role_id = $S3->insert(S3_.'user_role', array(
                    'parent_id' => 6,
                    'code' => "podatelna_". $org_id,
                    'name' => "podatelna ". $code,
                    'active' => 1,
                    'date_created' => new DateTime(),
                    'orgjednotka_id' => $org_id,
                    'fixed' => 0,
                    'order' => 20
                ))->execute(dibi::IDENTIFIER);
                if ( $role_id ) {
                    $S3->insert(S3_.'user_acl', array(
                        'role_id' => $role_id,
                        'rule_id' => $rule_id,
                        'allowed' => 'Y',
                    ))->execute();
                    echo "\n   => <span style='color:green'>vytvořena role podatelna pro org. jednotku $code (id $role_id)</span>";
                }

            }
            $_SESSION['S3_orgjednotka'] = 1;
            
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
}

} // if session
unset($S2_org,$code,$plny_nazev,$zkraceny_nazev,$sekvence);

/* **************************************************************************
 *
 * Migrace zamestnancu
 *
 ************************************************************************** */
debug_head('Migrace zamestnanců a jejich uživatelských účtů', 2);
$S2_usr = $S2->query('SELECT * FROM [:S2:zamestnanci]')->fetchAll();

if ( count($S2_usr)>0 ) {
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'user');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'osoba');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'osoba_to_user');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'user_to_role');

    $S3_role = $S3->query('SELECT r.id, r.code, r.orgjednotka_id, r1.code AS pcode FROM [:S3:user_role] AS r LEFT JOIN [:S3:user_role] AS r1 ON r1.id=r.parent_id')->fetchAll();
    $role = array();
    if ( count($S3_role)>0 ) {
        foreach ( $S3_role as $r ) {
            $code = ( !empty($r->orgjednotka_id) && !empty($r->pcode) )?$r->pcode:$r->code;
            $role[ (string)$r->orgjednotka_id ][ (string) $code ] = $r->id;
        }
    }

    foreach ( $S2_usr as $S2_u ) {
        echo "\n>> ".htmlspecialchars($S2_u->id_zamestnanec ." = ". $S2_u->prijmeni ." ". $S2_u->jmeno);
        if (MIGRACE):
        try {

            // Pridani jako osoba
            if ( empty($S2_u->prijmeni) && empty($S2_u->jmeno) ) {
                $prijmeni = "Uživatel ". $S2_u->id_zamestnanec;
            } else {
                $prijmeni = $S2_u->prijmeni;
            }
            
            $stav = ($S2_u->stav==1)?0:1;
            $aktivni = (int) $S2_u->stav;
            if ( $S2_u->login == "isds" ) {
                $stav = 1;
                $aktivni = 0;
            }
            
            $osoba_id = $S3->insert(S3_.'osoba', array(
                'prijmeni' => $prijmeni,
                'jmeno' => (empty($S2_u->jmeno))?"":$S2_u->jmeno,
                'titul_pred' => $S2_u->titul_pred,
                'titul_za' => $S2_u->titul_za,
                'email' => $S2_u->email,
                'pozice' => $S2_u->pozice,
                'stav' => $stav,
                'date_created' => new DateTime($S2_u->vytvoreno),
                'user_created' => 1
            ))->execute(dibi::IDENTIFIER);

            if ( $osoba_id ) {
                echo "\n   => <span style='color:green'>osoba přidána - ".htmlspecialchars($prijmeni)."</span>";

                // Pridani jako uzivatele
                $user_id = $S3->insert(S3_.'user', array(
                    'id' => $S2_u->id_zamestnanec,
                    'username' => $S2_u->login,
                    'password' => $S2_u->heslo,
                    'active' => $aktivni,
                    'date_created' => new DateTime($S2_u->vytvoreno)
                ))->execute(dibi::IDENTIFIER);

                if ( $user_id ) {
                    
                    echo "\n   => <span style='color:green'>uživatel přidán - ". htmlspecialchars($S2_u->login) ."</span>";

                    // Pripojeni uzivatele k osobe
                    if ( $S3->insert(S3_.'osoba_to_user', array(
                        'osoba_id' => $osoba_id,
                        'user_id' => $user_id,
                        'active' => $aktivni,
                        'date_added' => new DateTime()
                    ))->execute() ) {
                        echo "\n   => <span style='color:green'>Uživatel připojen k osobě</span>";
                    } else {
                        echo "\n   => <span style='color:red'>uživatel nepřipojen k osobě</span>";
                        echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
                        echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
                        $ERROR_LOG[] = $e->getMessage();
                    }

                    // Nastaveni role dle puvodniho
                    if ( $S2_u->role == "spravce" ) {
                        $S2_role = "admin";
                        $S2_org = "";
                    } else if ( $S2_u->role == "administrator" ) {
                        $S2_role = "superadmin";
                        $S2_org = "";
                    } else {
                        $S2_role = $S2_u->role;
                        $S2_org = $S2_u->id_orgjednotka;
                    }

                    if ( $S3->insert(S3_.'user_to_role', array(
                        'role_id' => isset($role[ (string) $S2_org ][ (string) $S2_role ])?$role[ (string) $S2_org ][ (string) $S2_role ]:4,
                        'user_id' => $user_id,
                        'date_added' => new DateTime()
                    ))->execute() ) {
                        echo "\n   => <span style='color:green'>uživateli připojena role - ". $S2_role ."</span>";
                    } else {
                        echo "\n   => <span style='color:red'>uživateli nepřipojena role</span>";
                        echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
                        echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
                        $ERROR_LOG[] = $e->getMessage();
                    }

                } else {
                    echo "\n   => <span style='color:red'>uživatel nepřidán - ". tmlspecialchars($S2_u->login) ."</span>";
                    echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
                    echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
                    $ERROR_LOG[] = $e->getMessage();
                }
            } else {
                echo "\n   => <span style='color:red'>osoba nepřidána - ".tmlspecialchars($prijmeni)."</span>";
                echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
                echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
                $ERROR_LOG[] = $e->getMessage();
            }
        } catch ( DibiException $e ) {
            echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
        endif;
    }
}
unset($S2_usr, $S3_role, $S2_org, $S2_role, $prijmeni);

echo "\n\n\n";
/* **************************************************************************
 *
 * Migrace dokumentu
 *
 ************************************************************************** */
debug_head('Migrace dokumentů včetně jejich workflow', 2);

$S2_dok = $S2->query('SELECT * FROM [:S2:dokumenty]')->fetchAll();


echo "<div style='color:blue'>";
debug(count($S2_dok),"Celkový počet dokumentů");
$posledni = end($S2_dok);
debug($posledni->id_dokument,"Poslední ID dokumentu");
echo "</div>";

if ( count($S2_dok)>0 ) {
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'epodatelna');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'dokument');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'dokument_to_subjekt');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'dokument_to_spis');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'dokument_to_file');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'file');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'workflow');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'log_dokument');
    if (MIGRACE) $S3->query('TRUNCATE TABLE '.S3_.'log_spis');
    

    $typ_odesiletele = array(''=>'AO','0'=>'A','1'=>'O','2'=>'AO');
    $stav_dokumentu  = array(0=>1, 1=>1, 2=>2, 3=>5);
    $stav_dokumentu_work  = array(0=>1, 1=>1, 2=>2, 3=>5);

    $historie_work = array (
        'Dokument vytvořen' => 11,
        'Dokument předán' => 16,
        'Dokument předán podatelně' => 14,
        'Dokument vyřízen' => 17,
    );

    $historie = array (
        'Příprava nového dokumentu' => 10,
        'Dokument vytvořen' => 11,
        'Dokument změněn' => 12,
        'Dokument předán' => 14,
        'Dokument předán podatelně' => 14,
        'Dokument vyřízen' => 17,

        'Dokument odeslán do ISDS' => 19,
        'Dokument byl elektronicky podepsán a odeslán emailem' => 19,
        'Dokument nebyl odeslán emailem' => 19,
        'Dokument odeslán emailem' => 19,

        'Přidána příloha. Soubor' => 34,
        'Připojena příloha' => 34,
        'Příloha smazána. Soubor' => 35,

        'Připojen odesilatel/adresát' => 24,
        'Odstraněn odesilatel' => 25,
    );

    // Centralni podatelna
    $S2_centpod = $S2->query('SELECT id_orgjednotka FROM [:S2:orgjednotky] WHERE podatelna=2 LIMIT 1')->fetchSingle();
    if ( !$S2_centpod ) {
        $S2_centpod = $S2->query('SELECT id_orgjednotka FROM [:S2:orgjednotky] WHERE podatelna=1 LIMIT 1')->fetchSingle();
    }
    $S2_cjednaci = null;
    $epodatelna_poradi = array();

    // casovac
    $progress_max = $posledni->id_dokument;
    $progress_aktual = 0;
    $progress_time_start = microtime_float();
    
    foreach ( $S2_dok as $S2_d ) {

        if ( strpos($S2_d->cislo_jednaci, '000TMP') !== false ) {
            // rozepsany neulozeny dokument
            continue;
        }
        
        if ( strpos($S2_d->cislo_jednaci, '000EVIDENCE') !== false ) {
            if ( !empty($S2_d->id_isds) || !empty($S2_d->id_email) ) {
                $podaci_rok = 0;
                $datum_vzniku = substr($S2_d->datum_vzniku,0,10) .' '. $S2_d->epodatelna_cas;
                if (preg_match("/20(08|09|10|11|12)|^".$S2_d->poradove_cislo."/", $S2_d->cislo_jednaci, $matches) ) {
                    $podaci_rok = $matches[0];
                }                
                if ( isset($epodatelna_poradi[$podaci_rok]) ) {
                    $epodatelna_poradi[$podaci_rok]++;
                } else {
                    $epodatelna_poradi[$podaci_rok] = 1;
                }
                // Pripojeni subjektu do dokumentu
                $S2_odesilatele = $S2->query('SELECT do.*,o.nazev,o.ulice,o.mesto,o.psc,o.isds_idbox,o.cislo_popisne,o.cislo_orientacni,o.email FROM [:S2:dokumenty_a_odesilatele] AS do LEFT JOIN [:S2:odesilatele] AS o ON o.id_odesilatel=do.id_odesilatel WHERE do.id_dokument=%i',$S2_d->id_dokument)->fetchAll();
                //debug($S2_odesilatele);
                $epodatelna_odesilatel = " ";
                $epodatelna_odesilatel_id = null;
                if ( count($S2_odesilatele)>0 ) {
                    foreach ( $S2_odesilatele as $S2_do ) {
                        if ( $S2_do->prichozi != 1 ) continue;
                        if ( !empty($S2_d->id_isds) ) {
                            $epodatelna_odesilatel = $S2_do->nazev ." (".$S2_do->isds_idbox.")";
                        } else if ( !empty($S2_d->id_email) ) {
                            $epodatelna_odesilatel = $S2_do->nazev ." (".$S2_do->email.")";
                        } else {
                            $epodatelna_odesilatel = $S2_do->nazev;
                        }
                        $epodatelna_odesilatel_id = $S2_do->id_odesilatel;                           
                    }
                }
                
                if (MIGRACE):
                $S3->insert(S3_.'epodatelna', array(
                        'epodatelna_typ' => 0,
                        'poradi' => $epodatelna_poradi[$podaci_rok],
                        'rok' => $podaci_rok,
                        'email_id' => !empty($S2_d->id_email)?$S2_d->id_email:null,
                        'isds_id' => !empty($S2_d->id_isds)?$S2_d->id_isds:null,
                        'identifikator' => null,
                        'predmet' => (empty($S2_d->strucny_obsah))?"Dokument ".$S2_d->cislo_jednaci:$S2_d->strucny_obsah,
                        'popis' => ''.$S2_d->poznamka,
                        'odesilatel' => $epodatelna_odesilatel,
                        'odesilatel_id' => $epodatelna_odesilatel_id,
                        'adresat' => 'Centrální podatelna',
                        'prijato_dne' => $datum_vzniku,
                        'doruceno_dne' => $datum_vzniku,
                        'prijal_kdo' => null,
                        'prijal_info' => null,
                        'sha1_hash' => sha1($S2_d->id_isds.$S2_d->id_email),
                        'prilohy' => '',
                        'evidence' => !empty($S2_d->evidence)?$S2_d->evidence:"",
                        'dokument_id' => null,
                        'stav' => '11',
                        'stav_info' => 'Zpráva zaevidována v evidenci '.$S2_d->evidence,
                        'file_id' => null,                        
                ))->execute();
                endif;
                echo "\n>> JINA EVIDENCE ".htmlspecialchars($S2_d->id_dokument ." = ". $S2_d->cislo_jednaci ." - ". $S2_d->strucny_obsah);
                echo "\n   => <span style='color:green'>Dokument přijat elektronicky. Záznam v epodatelně - ". $epodatelna_odesilatel ."</span>";
                
            }
            continue;
        }

        // progress bar
        $progress_aktual_id = (int) $S2_d->id_dokument;
        $progress_aktual = ($progress_aktual_id*100)/$progress_max;
        $progress_aktual_per = sprintf("%3.1d",$progress_aktual,1) ."%";
        $progress_time_aktual = microtime_float();
        //$progress_time_aktual = str_replace(",","",$progress_time_aktual);
        $progress_time_diff = $progress_time_aktual - $progress_time_start;
        $progress_time_diff_f = number_format($progress_time_diff,2,"."," ") ."s";
        $progress_time_end = ($progress_time_diff*100)/$progress_aktual;
        $progress_time_end_f = number_format($progress_time_end,2,"."," ") ."s";
        
        echo "\n<span style='color:blue;'>>> PRŮBĚH [zpracováno: ". $progress_aktual_per ."] v činnosti ". $progress_time_diff_f ." - odhadovaný čas konce: ". $progress_time_end_f ." (odhadem zbývá: ". number_format($progress_time_end-$progress_time_diff,2,'.'," ") ."s)</span>";
        echo "\n>> ".htmlspecialchars($S2_d->id_dokument ." = ". $S2_d->cislo_jednaci ." - ". $S2_d->strucny_obsah);

        // Podaci denik
        $podaci_rok = 0;
        if (preg_match("/20(08|09|10|11|12|13|14|15)|^".$S2_d->poradove_cislo."/", $S2_d->cislo_jednaci, $matches) ) {
            $podaci_rok = $matches[0];
        }
        
        // Datum vzniku
        if ( $S2_d->epodatelna == "1" ) {
            // epodatelna
            $datum_vzniku = substr($S2_d->datum_vzniku,0,10) .' '. $S2_d->epodatelna_cas;
        } else {
            // listina
            $datum_vzniku = $S2_d->datum_vzniku;
        }

        // Typ dokumentu
        $typ_dokumentu = 1;
        if ( $S2_d->vlastni == 1 ) {
            $typ_dokumentu = 2;
        } else if ( !empty( $S2_d->id_email ) ) {
            $typ_dokumentu = 1;
        } else if ( !empty( $S2_d->id_isds ) ) {
            $typ_dokumentu = 1;
        } else if ( $S2_d->vlastni == 1 ) {
            $typ_dokumentu = 2;
        }

        $stav = $stav_dokumentu[ $S2_d->stav ];
        $zpusob_doruceni = null;

        $zpusob_vyrizeni = null;
        if (MIGRACE):
        if ( $S2_d->id_zpvyrizeni != 0 ) {
            $zpusob_vyrizeni = $S3_zpvyrizeni[ $S2_d->id_zpvyrizeni ];
            $spoust = 3;
            $spoust_datum = $S2_d->vyriz_datum;
        } else {
            $spoust = null;
            $spoust_datum = null;
        }
        else:
            $spoust = null;
            $spoust_datum = null;
        endif;

        

        try {
            $dokument = array(
                'id' => (int) $S2_d->id_dokument,
                'jid' => $S2_d->jid_dokument .".". $S2_d->id_dokument,
                'nazev' => (empty($S2_d->strucny_obsah))?"Dokument ".$S2_d->cislo_jednaci:$S2_d->strucny_obsah,
                'popis' => $S2_d->poznamka,
                'cislo_jednaci_id' => null,
                'cislo_jednaci' => $S2_d->cislo_jednaci,
                'poradi' => $S2_d->cislo_jednaci_poradi,
                'cislo_jednaci_odesilatele' => $S2_d->cislo_jednaci_odes,
                'podaci_denik' => 'denik',
                'podaci_denik_poradi' => $S2_d->poradove_cislo,
                'podaci_denik_rok' => $podaci_rok,
                'dokument_typ_id' => $typ_dokumentu,
                'spisovy_znak_id' => $S2_d->id_spisznak,
                'skartacni_znak' => empty($S2_d->skartacni_znak)?null:$S2_d->skartacni_znak,
                'skartacni_lhuta' => empty($S2_d->skartacni_lhuta)?null:$S2_d->skartacni_lhuta,
                'poznamka' => '',
                'lhuta' => (int) $S2_d->lhuta,
                'epodatelna_id' => null,
                'stav' => 1,
                'date_created' => new DateTime($S2_d->vytvoreno),
                'datum_vzniku' => $datum_vzniku,
                'pocet_listu' => $S2_d->pocet_listu,
                'pocet_priloh' => $S2_d->pocet_listu_priloh,
                'zpusob_doruceni_id' => $zpusob_doruceni,
                'zpusob_vyrizeni_id' => $zpusob_vyrizeni,
                'vyrizeni_pocet_listu' => $S2_d->vyriz_pocet_listu,
                'vyrizeni_pocet_priloh' => $S2_d->vyriz_pocet_listu_priloh,
                'ulozeni_dokumentu' => $S2_d->ulozeni_originalu,
                'datum_vyrizeni' => $S2_d->vyriz_datum,
                'poznamka_vyrizeni' => $S2_d->vyriz_poznamka,
                'spousteci_udalost_id' => $spoust,
                'datum_spousteci_udalosti' => $spoust_datum,

            );

            $S2_cjednaci = $S2_d->cislo_jednaci;

            if (MIGRACE) $S3->insert(S3_.'dokument', $dokument)->execute();
            if (MIGRACE) $dokument_id = (int) $S2_d->id_dokument;
            if (!MIGRACE) $dokument_id = 1;

            if ( $dokument_id ) {
                echo "\n   => <span style='color:green'>Dokument vytvořen</span>";

                // Pripojeni subjektu do dokumentu
                $S2_odesilatele = $S2->query('SELECT do.*,o.nazev,o.ulice,o.mesto,o.psc,o.isds_idbox,o.cislo_popisne,o.cislo_orientacni,o.email FROM [:S2:dokumenty_a_odesilatele] AS do LEFT JOIN [:S2:odesilatele] AS o ON o.id_odesilatel=do.id_odesilatel WHERE do.id_dokument=%i',$S2_d->id_dokument)->fetchAll();
                //debug($S2_odesilatele);
                $epodatelna_odesilatel = null;
                $epodatelna_odesilatel_id = null;
                if ( count($S2_odesilatele)>0 ) {
                    foreach ( $S2_odesilatele as $S2_do ) {
                        if (MIGRACE):
                        $S3->insert(S3_.'dokument_to_subjekt', array(
                            'dokument_id'=>$dokument_id,
                            'subjekt_id'=>$S2_do->id_odesilatel,
                            'typ'=> $typ_odesiletele[ $S2_do->prichozi ],
                            'user_id' => 1,
                            'date_added' => new DateTime(),
                        ))->execute();
                        endif;
                        if ( !empty($S2_d->id_isds) ) {
                            $epodatelna_odesilatel = $S2_do->nazev ." (".$S2_do->isds_idbox.")";
                        } else if ( !empty($S2_d->id_email) ) {
                            $epodatelna_odesilatel = $S2_do->nazev ." (".$S2_do->email.")";
                        } else {
                            $epodatelna_odesilatel = $S2_do->nazev;
                        }
                        $epodatelna_odesilatel_id = $S2_do->id_odesilatel;                           
                        echo "\n   => <span style='color:green'>připojen subjekt ". $S2_do->id_odesilatel ."</span>";
                    }
                }
                unset($S2_odesilatele);

                // Sestaveni prubehu stavu dokumentu (historie)
                $S2_zaznamy = $S2->query('SELECT * FROM [:S2:zaznamy] WHERE id_dokument=%i ORDER BY datum',$S2_d->id_dokument)->fetchAll();
                if ( count($S2_zaznamy)>0 ) {
                    foreach ( $S2_zaznamy as &$S2_z ) {
                        // Predpriprava - nezaznamenava se
                        //if ( $S2_z->dok_stav == 0 ) continue;

                        // Rizeni toku - vytvoreni, predani, vyrizeni
                        $historie_stav = explode(":",$S2_z->cinnost);

                        if ( $historie_stav[0] == "Dokument změněn" ) continue;

                        //print_r($historie_stav);
                        //echo "\n". $historie_work[ $historie_stav[0] ] ."\n";
                        if ( isset( $historie_work[ $historie_stav[0] ]  ) ) {

                            $prideleno = null;
                            $orgjednotka = null;
                            $user = 1;

                            // detekce typu
                            if ( empty($S2_z->id_kam_typ) ) {
                                if ( $S2_z->id_zamestnanec > 0 ) {
                                    $S2_z->id_kam_typ = 'N';
                                } else {
                                    $S2_z->id_kam_typ = 'P';
                                }
                            }

                            if ( $S2_z->id_kam_typ == 'Z' ) {
                                $prideleno = $S2_z->id_kam;
                                $user = $S2_z->id_zamestnanec;
                            } else if ( $S2_z->id_kam_typ == 'O' ) {
                                $orgjednotka = $S2_z->id_kam;
                                $user = $S2_z->id_zamestnanec;
                            } else if ( $S2_z->id_kam_typ == 'P' ) {
                                $orgjednotka = (empty( $S2_z->id_kam ))?$S2_centpod:$S2_z->id_kam;
                                $user = $S2_z->id_zamestnanec;
                            } else if ( $S2_z->id_kam_typ == 'N' ) {

                                if ( $S2_z->id_kam == 0 ) {
                                    // Podatelna
                                    $orgjednotka = $S2_centpod;
                                    $user = 1;
                                } else {
                                    $prideleno = $S2_z->id_zamestnanec;
                                    $user = $S2_z->id_zamestnanec;
                                }
                            }

                            if (MIGRACE):
                            $workflow_id = $S3->insert(S3_.'workflow', array(
                                'dokument_id'=>$dokument_id,
                                'stav_dokumentu'=> $stav_dokumentu_work[ $S2_z->dok_stav ],
                                'prideleno_id'=> $prideleno,
                                'orgjednotka_id'=> $orgjednotka,
                                'stav_osoby'=> 2,
                                'date'=> ( empty($S2_z->datum)?new DateTime():$S2_z->datum ),
                                'user_id'=> $user,
                                'poznamka'=> $S2_d->pred_poznamka,
                                'date_predani'=> $S2_d->pred_dne,
                                'aktivni'=> 0,
                            ))->execute(dibi::IDENTIFIER);
                            endif;
                            echo "\n   => <span style='color:green'>připojen záznam - ". $historie_stav[0] ."</span>";
                            //debug($S2_z);
                        }
                        //debug($S2_z);
                        //debug($historie_stav[0],'Stav Dokumentu');
                        //debug($historie[$historie_stav[0]],'Typ logu');

                        if (MIGRACE) logDokument($S3, $dokument_id, @$historie[$historie_stav[0]], $S2_z->id_zamestnanec, $S2_z->datum, $S2_z->cinnost .", ". $S2_z->poznamka);
                    }
                        if (!MIGRACE) $workflow_id = 0;
                        if ( isset($workflow_id) && $workflow_id > 0 ) {
                            $S3->update(S3_.'workflow', array(
                                'stav_osoby'=> 1,
                                'aktivni'=> 1,
                            ))->where( array(array('id=%i',$workflow_id)) )->execute();
                        }

                   


                }
                unset($S2_zaznamy);

                // Pripojeni spisu do dokumentu
                if ( !empty( $S2_d->id_spis ) && $S2_d->id_spis > 1 ) {
                    $S3_spis_count = $S3->query('SELECT poradi FROM [:S3:dokument_to_spis] WHERE spis_id=%i ORDER BY poradi DESC LIMIT 1;',$S2_d->id_spis+1)->fetchSingle();
                    if (MIGRACE):
                        
                    if ( isset($orgjednotka) ) {
                        // prideleni vlastnictvi spisu dle organizacni jednotky - z workflow
                        $S3->update(S3_.'spis',array('orgjednotka_id'=>$orgjednotka))->where(array(array('id=%i',$S2_d->id_spis+1)))->execute();
                    }
                        
                    $S3->insert(S3_.'dokument_to_spis', array(
                        'dokument_id'=>$dokument_id,
                        'spis_id'=>($S2_d->id_spis+1),
                        'poradi'=> ($S3_spis_count + 1),
                        'stav'=> 1,
                        'date_added' => new DateTime(),
                        'user_id' => 1
                    ))->execute();
                    endif;
                    echo "\n   => <span style='color:green'>připojen spis ". ($S2_d->id_spis+1) ."</span>";
                }
                unset($S3_spis_count);                
                
                // Pripojeni priloh do dokumentu
                $S2_prilohy = $S2->query('SELECT * FROM [:S2:prilohy] WHERE id_dokument=%i',$S2_d->id_dokument)->fetchAll();
                if ( count($S2_prilohy)>0 ) {
                    foreach ( $S2_prilohy as $S2_pr ) {

                        $typ = 1;
                        if ( $S2_pr->stav == 2 ) {
                            $typ = 5;
                        }
                        $real_path = str_replace("prilohy/","/files/dokumenty/", $S2_pr->umisteni);
                            
                        $quid = UUID::v4();
                        $mime_type = FileModel::mimeType($real_path);

                        if ( file_exists(CLIENT_DIR . $real_path) ) {
                            $size = filesize(CLIENT_DIR . $real_path);
                            $md5_hash = md5_file(CLIENT_DIR . $real_path);
                        } else {
                            $size = $S2_pr->velikost;
                            $md5_hash = '';
                        }

                        if (MIGRACE):
                            // Pridani zaznamu prilohy
                            $S3->insert(S3_.'file', array(
                                'id'=> (int) $S2_pr->id_priloha,
                                'typ'=> $typ,
                                'stav'=> 1,
                                'nazev'=> $S2_pr->nazev,
                                'popis'=> $S2_pr->poznamka,
                                'mime_type'=> $mime_type,
                                'real_name'=> $S2_pr->nazev,
                                'real_path'=> $real_path,
                                'real_type'=> 'UploadFile_Basic',
                                'date_created'=> empty($S2_pr->vlozeno)?new DateTime():$S2_pr->vlozeno,
                                'user_created'=> empty($S2_pr->vlozil)?1:$S2_pr->vlozil,
                                'guid'=> $quid,
                                'md5_hash'=> $md5_hash,
                                'size'=> $size,
                            ))->execute();
                        endif;

                        $priloha_id = (int) $S2_pr->id_priloha;

                        if ( $priloha_id ) {
                            echo "\n   => <span style='color:green'>Příloha {$S2_pr->nazev} vytvořena</span>";
                            if (MIGRACE) :
                                if ( $S3->insert(S3_.'dokument_to_file', array(
                                    'dokument_id'=> $dokument_id,
                                    'file_id'=> $priloha_id,
                                    'active'=> 1,
                                    'date_added' => empty($S2_pr->vlozeno)?new DateTime():$S2_pr->vlozeno,
                                    'user_id' => empty($S2_pr->vlozil)?1:$S2_pr->vlozil
                                ))->execute() ) {
                                    echo "\n   => <span style='color:green'>připojena příloha ". $S2_pr->nazev ."</span>";
                                }
                            endif;

                        } else {
                            echo "\n   => <span style='color:red'>příloha {$S2_pr->nazev} nepřenesena!</span>";
                        }
                    }
                }
                unset($S2_prilohy);
                
                    
                // Detekce elektronicke zpravy a zaevidovani do epodatelny
                if ( !empty($S2_d->id_isds) || !empty($S2_d->id_email) ) {
                    if (MIGRACE):

                        if ( isset($epodatelna_poradi[$podaci_rok]) ) {
                            $epodatelna_poradi[$podaci_rok]++;
                        } else {
                            $epodatelna_poradi[$podaci_rok] = 1;
                        }
                        $S3->insert(S3_.'epodatelna', array(
                            'epodatelna_typ' => 0,
                            'poradi' => $epodatelna_poradi[$podaci_rok],
                            'rok' => $podaci_rok,
                            'email_id' => !empty($S2_d->id_email)?$S2_d->id_email:null,
                            'isds_id' => !empty($S2_d->id_isds)?$S2_d->id_isds:null,
                            'identifikator' => null,
                            'predmet' => (empty($S2_d->strucny_obsah))?"Dokument ".$S2_d->cislo_jednaci:$S2_d->strucny_obsah,
                            'popis' => ''.$S2_d->poznamka,
                            'odesilatel' => !empty($epodatelna_odesilatel)?$epodatelna_odesilatel:"(nezjišttěno)",
                            'odesilatel_id' => $epodatelna_odesilatel_id,
                            'adresat' => 'Centrální podatelna',
                            'prijato_dne' => $datum_vzniku,
                            'doruceno_dne' => $datum_vzniku,
                            'prijal_kdo' => null,
                            'prijal_info' => null,
                            'sha1_hash' => sha1($S2_d->id_isds.$S2_d->id_email),
                            'prilohy' => '',
                            'evidence' => !empty($S2_d->evidence)?$S2_d->evidence:"",
                            'dokument_id' => $dokument_id,
                            'stav' => '10',
                            'stav_info' => 'Zpráva přidána do spisové služby jako '.$S2_d->jid_dokument .".". $S2_d->id_dokument,
                            'file_id' => null,                        
                        ))->execute();
                        
                    endif;
                    echo "\n   => <span style='color:green'>Dokument přijat elektronicky. Záznam v epodatelně - ". $epodatelna_odesilatel ."</span>";
                }                    

            } else {
                echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            }
        } catch ( DibiException $e ) {
            //echo "\n   => <span style='color:red'>nepřeneseno!</span>";
            echo "\n   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
            echo "\n   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
            $ERROR_LOG[] = $e->getMessage();
        }
    }
}

/* **************************************************************************
 *
 * Nastaveni cisla jednaciho dle posledniho dokumentu
 *
 ************************************************************************** */
debug_head('Nastaveni cisla jednaciho dle posledniho dokumentu', 2);

$S2_cj = $S2->query("SELECT * FROM [:S2:dokumenty] WHERE cislo_jednaci NOT LIKE '000TMP%' ORDER BY datum_vzniku DESC LIMIT 1;")->fetch();
debug($S2_cj->cislo_jednaci,"S2 cislo jednaci 1");
debug($S2_cjednaci,"S2 cislo jednaci 2");

$S3->query('TRUNCATE TABLE '.S3_.'cislo_jednaci');
$S3_cjednaci = parseCJ(SPIS_MASKA_JEDNACIHO_CISLA, $S2_cj->cislo_jednaci, $S3_nastaveni_klient);
if ( $S3_cjednaci ) {
    unset($S3_cjednaci['maska']);
    if (MIGRACE) $S3->insert(S3_.'cislo_jednaci', $S3_cjednaci)->execute();
    echo "\n   => <span style='color:green'>zaznamenáno číslo jednací - ". $S2_cj->cislo_jednaci ."</span>";

}
$S3_cjednaci = parseCJ(SPIS_MASKA_JEDNACIHO_CISLA, $S2_cjednaci, $S3_nastaveni_klient);
if ( $S3_cjednaci ) {
    unset($S3_cjednaci['maska']);
    if (MIGRACE) $S3->insert(S3_.'cislo_jednaci', $S3_cjednaci)->execute();
    echo "\n   => <span style='color:green'>zaznamenáno číslo jednací - $S2_cjednaci</span>";

}

if (MIGRACE) {
if ( count($ERROR_LOG)>0 ) {
    echo "\n\n\n<span style='color:red'>************************************************************\n";
    echo "Během migrace bylo zjištěno ". count($ERROR_LOG) ." chyb! \n\n"; 
    foreach ( $ERROR_LOG as $elog ) {
        echo $elog ."\n\n";
    }
    echo "************************************************************</span>";
} else {
    echo "\n\n\n<span style='color:green'>************************************************************\n";
    echo "Migrace proběhla v pořádku. Nebyly zjištěny žádné chyby."; 
    echo "\n************************************************************</span>";
}
} 

//$po = memory_get_usage();
//echo "\n\n\n===========================\n";
//echo "Vyuzita pamet\n\n";
//echo "Pocatek: ". $pred ."\n";
//echo "Konec  : ". $po ."\n";
//echo "Rozdil : ". ($po-$pred) ."\n";


echo "</pre>";
/* ***************************************************************************
 * ***************************************************************************
 *************************************************************************** */
function debug($var, $nadpis = null)
{
    echo "<pre style='text-align:left;'>";
    if ( !is_null($nadpis) ) echo $nadpis .": ";
    print_r($var);
    echo "</pre>";
}

function debug_head($nadpis = null, $uroven = 2)
{
    echo "<pre style='text-align:left;'>";
    for ($u=0; $u < $uroven; $u++) {
        echo "********************************************************************************\n";
    }
    echo "*   ". $nadpis ."\n";
    for ($u=0; $u < $uroven; $u++) {
        echo "********************************************************************************\n";
    }
    echo "</pre>";
}

function logDokument($S3, $dokument_id, $typ, $user = 1, $date = null, $poznamka = "") {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['typ'] = empty($typ)?'00':$typ;
        $row['poznamka'] = $poznamka;

        if ( is_object($user) ) {
            $user_id = $user->id;
        } else {
            $user_id = $user;
        }

        if ( empty($date) ) {
            $date = new DateTime();
        }

        $row['user_id'] = $user_id;
        $row['date'] = $date;

        return $S3->insert(S3_.'log_dokument', $row)
            ->execute(dibi::IDENTIFIER);

    }

function write_ini_file($file, array $options){
    $tmp = '; generated by S2 to S3 migration script'."\n\n";
    foreach($options as $section => $values){
        $tmp .= "[$section]\n";
        foreach($values as $key => $val){
            if(is_array($val)){
                foreach($val as $k =>$v){
                    $tmp .= "{$key}[$k] = \"$v\"\n";
                }
            }
            else
                $tmp .= "$key = \"$val\"\n";
        }
        $tmp .= "\n";
    }
    file_put_contents($file, $tmp);
    unset($tmp);
}

function parseCJ($maska, $cislo_jednaci = null, $nastaveni = null) {

    $s2 = array("{rok}","{urad}","{login}","{role}","{utvar}","{osoba}","{poradcislo}");
    $s3 = array("{rok}","{urad}","{user}","{user}","{org}","{prijmeni}","{poradove_cislo}");

    $info = array();
    $info['maska'] = str_replace($s2, $s3, $maska);

    if ( !empty($cislo_jednaci) ) {

        //debug($cislo_jednaci,'CJ');
        //debug($maska,'M1');

        $maska_orig = $maska;
        $maska = preg_replace('/{.*?}/', '(.*?)', $maska);
        $maska_ex = explode('(.*',$maska);
        $maska_ex[ count($maska_ex)-1 ] = substr($maska_ex[ count($maska_ex)-1 ],1);
        $maska = implode('(.*',$maska_ex);

        //debug($maska,'M2');
        $maska = '$'.$maska.'$';
        preg_match($maska, $maska_orig, $matches_keys);
        preg_match($maska, $cislo_jednaci, $matches_value);
        //debug($matches_keys);
        //debug($matches_value);

        $matches = array_combine($matches_keys, $matches_value);
        //debug($matches);

        $info['podaci_denik'] = 'denik';
        $info['rok'] = (isset($matches['{rok}']))?$matches['{rok}']:date("Y");
        $info['poradove_cislo'] = (isset($matches['{poradcislo}']))?$matches['{poradcislo}']:null;

        $info['urad_zkratka'] = (isset($matches['{urad}']))?$matches['{urad}']:$nastaveni['urad']['zkratka'];
        $info['urad_poradi'] = (isset($matches['{poradcislo}']))?$matches['{poradcislo}']:null;

        $info['orgjednotka_id'] = null;
        //$info['org'] = (isset($matches['{utvar}']))?$matches['{utvar}']:null;
        $info['org_poradi'] = null;

        $info['user_id'] = null;
        //$info['user'] = (isset($matches['{login}']))?$matches['{login}']:null;
        //$info['prijmeni'] = (isset($matches['{osoba}']))?$matches['{osoba}']:null;
        $info['user_poradi'] = null;

    }

    return $info;

}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}