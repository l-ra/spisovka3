<?php

// Step 1: Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Step 2: Configure environment
Environment::loadConfig(CLIENT_DIR .'/configs/system.ini');
$user_config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
Environment::setVariable('user_config', $user_config);

// 2c) check if directory /app/temp is writable
Environment::setVariable('tempDir',CLIENT_DIR .'/temp');
if (@file_put_contents(Environment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('tempDir') . "' writable!");
}

// 2d) enable RobotLoader - this allows load all classes automatically
$loader = new RobotLoader();
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
$loader->register();

// 2e) setup sessions
$session = Environment::getSession();
$session->setSavePath(CLIENT_DIR . '/sessions/');

// Step 3: Configure application
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
$application->catchExceptions = Environment::isProduction();

// 3b) Load database
try {
    dibi::connect(Environment::getConfig('database'));
    dibi::addSubst('PREFIX', Environment::getConfig('database')->prefix);
    define('DB_PREFIX', Environment::getConfig('database')->prefix);
} catch (DibiDriverException $e) {
    if ( !Environment::isProduction() ) {
        define('DB_ERROR', $e->getMessage());
    } else {
        define('DB_ERROR', 1);
    }
}

// Step 5: Run the application!
//$application->run();

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
//session_start();
set_time_limit(0);

$User = new UserModel();
$Osoba = new Osoba();

if ( GENEROVAT_ZAMESTNANCE ) {
    if (TRUNCATE) $Osoba->deleteAll();
    if (TRUNCATE) $User->deleteAll();
}

try {
$osoba_id = $Osoba->insert( array(
    'prijmeni' => 'Administrátorský',
    'jmeno' => 'Admin',
    'titul_pred' => '',
    'titul_za' => '',
    'email' => 'admin@admin.admin',
    'pozice' => 'admin',
    'stav' => (int) 1,
    'date_created' => new DateTime()
));
if ( $osoba_id ) {
    $User->pridatUcet($osoba_id, array(
        'username' => ADMIN_LOGIN,
        'heslo' => ADMIN_PASSWORD,
        'role' => 1
    ));
}
} catch ( Exception $e ) {

}

$user_auth = Environment::getUser();
$user_auth->authenticate(ADMIN_LOGIN, ADMIN_PASSWORD);


function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}
mt_srand(make_seed());

debug_head('Info', 2);

debug(Environment::getConfig('database'),'databaze');


/* **************************************************************************
 *
 * Spisove znaky
 *
 ************************************************************************** */
if ( GENEROVAT_SPISOVE_ZNAKY ) {
debug_head('Generování spisových znaků', 2);

$sz_skartacni_znak = array('A','S','V');
$sz_skartacni_lhuta = range(5,100,5);

$SpisZnak = new SpisovyZnak();
if (TRUNCATE) $SpisZnak->deleteAll();

for ( $i =0; $i <= POCET_SPISOVYCH_ZNAKU; $i++ ) {
    try {
        $SpisZnak->vytvorit(array(
            'nazev' => sprintf('%03d',$i),
            'popis' => "Spisový znak č.".$i,
            'skartacni_znak' => $sz_skartacni_znak[array_rand($sz_skartacni_znak)],
            'skartacni_lhuta' => $sz_skartacni_lhuta[array_rand($sz_skartacni_lhuta)],
            'stav' => 1,
            'spousteci_udalost' => 3,
            'date_created' => new DateTime()
        ));

        echo "<br>". sprintf('%03d',$i);

    } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nepřeneseno!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
    }
}
}// GENEROVAT_SPISOVE_ZNAKY
/* **************************************************************************
 *
 * Spisy
 *
 ************************************************************************** */
if ( GENEROVAT_SPISY ) {
debug_head('Generování spisů', 2);

$Spis = new Spis();

if (TRUNCATE) $Spis->deleteAll();

for ( $i =0; $i <= POCET_SPISU; $i++ ) {
    try {

        $nazev = "SPIS_". make_string(4) ."_". $i;

        $Spis->vytvorit( array(
            'nazev' => $nazev,
            'popis' => "Spis č.".$i,
            'typ' => 'S',
            'skartacni_znak' => '',
            'skartacni_lhuta' => '',
            'stav' => 1,
            'spousteci_udalost' => '',
            'date_created' => new DateTime()
        ));

        echo "<br> ". $nazev;

     } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nevytvořeno $i!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
     }
}
} //GENEROVAT_SPISY
/* **************************************************************************
 *
 * Subjekty
 *
 ************************************************************************** */
include APP_DIR .'/test_data.dat';

if ( GENEROVAT_SUBJEKTY ) {
debug_head('Generování subjektů', 2);

$subjekt_type = array('OVM','FO','PO','PFO','FO','FO');

$Subjekt = new Subjekt();

if (TRUNCATE) $Subjekt->deleteAll();

for ( $i = 1; $i <= POCET_SUBJEKTU; $i++ ) {
    try {

        $mesto = mt_rand(0,count($subj)-1);
        $typ = mt_rand(0,count($subjekt_type)-1);
        
        $ulice = mt_rand(0,count($subj_ulice)-1);

        $nazev = ""; $prijmeni = ""; $jmeno = ""; $email = ""; $nazevplus = "";
        if ( $subjekt_type[$typ] == 'OVM' ) {
            $nazev = "Obec ". $subj[$mesto]['obec'];
            $email = "info@". String::webalize($subj[$mesto]['obec']) .".cz";
        } else if ( $subjekt_type[$typ] == 'PO' ) {
            $nazev = "Firma $i";
            $email = "info@". String::webalize($nazev) .".cz";
        } else {
            $aprijmeni = mt_rand(0,count($subj_prijmeni)-1);
            $prijmeni = $subj_prijmeni[ $aprijmeni ];
            $ajmeno = mt_rand(0,count($subj_jmena)-1);
            $jmeno = $subj_jmena[$ajmeno];
            $nazev = "";// $prijmeni ." ". $jmeno;
            $nazevplus = $prijmeni ." ". $jmeno;
            $email = String::webalize($jmeno) ."@". String::webalize($prijmeni) .".cz";
        }

        $Subjekt->insert_basic(array(
                'id' => (int) $i,
                'version' => (int) 1,
                'stav' => (int) 1,
                'type' => $subjekt_type[$typ],
                'ic' => '',
                'dic' => '',
                'nazev_subjektu' => $nazev,
                'prijmeni' => $prijmeni,
                'jmeno' => $jmeno,
                'adresa_mesto' => $subj[$mesto]['obec'],
                'adresa_ulice' => $subj_ulice[$ulice],
                'adresa_cp' => mt_rand(0,5000),
                'adresa_co' => '',
                'adresa_psc' => $subj[$mesto]['psc'],
                'adresa_stat' => 'CZE',
                'email' => $email,
                'telefon' => mt_rand(222222222,999999999),
                'id_isds' => '',
                'poznamka' => '',
                'date_created' => new DateTime()
        ));

        echo "<br> ". $subjekt_type[$typ] ." = ". $nazevplus.$nazev .", ". $subj[$mesto]['obec'];

     } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nevytvořeno $i!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
     }
}
} //GENEROVAT_SUBJEKTY
/* **************************************************************************
 *
 * Migrace organizacni jednotky
 *
 ************************************************************************** */
if ( GENEROVAT_ORGJEDNOTKY ) {
debug_head('Organizační jednotky', 2);

$OrgJednotka = new Orgjednotka();

if (TRUNCATE) $OrgJednotka->deleteAllOrg();

for ( $i =0; $i <= POCET_ORGJEDNOTEK; $i++ ) {
    try {

            // Pridani organizacni jednotky
            $salt = sprintf('%03d',$i) ."_". make_string(5);
            $code = 'ORG_'. $salt ;
            $plny_nazev = "Organizační jednotka $salt";
            $zkraceny_nazev = "Org. jednotka $salt";

            $org_id = null;
            $org_id = $OrgJednotka->insert( array(
                'plny_nazev' => $plny_nazev,
                'zkraceny_nazev' => $zkraceny_nazev,
                'ciselna_rada' => $code,
                'note' => '',
                'stav' => (int) 1,
                'date_created' => new DateTime()
            ));

            if ( $org_id ) {
                // Pridani jednotlivych roli
                $OrgJednotka->pridatOrganizacniStrukturu($org_id, 4);
                $OrgJednotka->pridatOrganizacniStrukturu($org_id, 5);
                $OrgJednotka->pridatOrganizacniStrukturu($org_id, 6);
            }
            //$_SESSION['S3_orgjednotka'] = 1;

            echo "<br> ". $plny_nazev;

     } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nevytvořeno $i!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
     }
}
} //GENEROVAT_ORGJEDNOTKY
/* **************************************************************************
 *
 * Zamestnanci
 *
 *
 ************************************************************************** */
if ( GENEROVAT_ZAMESTNANCE ) {
debug_head('Zaměstnanci', 2);

$Role = new RoleModel();
$role_seznam = $Role->seznam();
$role_select = array();
$role_name = array();
$role_id = 0;
foreach ($role_seznam as $key => $value) {
    if ( $value->fixed == 1 ) continue;
    $role_select[ $role_id ] = $value->id;
    $role_name[ $role_id ] = $value->name;
    $role_id++;
}

echo "<br> Administrátor => ". ADMIN_LOGIN ." / ". ADMIN_PASSWORD ."<br>&nbsp;";

for ( $i =0; $i <= POCET_ZAMESTNANCU; $i++ ) {
    try {

        $aprijmeni = mt_rand(0,count($subj_prijmeni)-1);
        $prijmeni = $subj_prijmeni[ $aprijmeni ];
        $ajmeno = mt_rand(0,count($subj_jmena)-1);
        $jmeno = $subj_jmena[$ajmeno];
        $urad = $user_config->urad->nazev;
        $urad = String::webalize($urad);
        $email = String::webalize($jmeno) .".". String::webalize($prijmeni) ."@". $urad .".cz";

        $username = String::webalize($prijmeni) . $i;
        $role_id = mt_rand(0, count($role_select)-1);


        // Pridani jako osoba
        $osoba_id = $Osoba->insert( array(
                'prijmeni' => $prijmeni,
                'jmeno' => $jmeno,
                'titul_pred' => '',
                'titul_za' => '',
                'email' => $email,
                'pozice' => $role_name[$role_id],
                'stav' => (int) 1,
                'date_created' => new DateTime()
        ));

        if ( $osoba_id ) {
            $User->pridatUcet($osoba_id, array(
                    'username' => $username,
                    'heslo' => $username,
                    'role' => $role_select[$role_id]
                    )
            );
        }

        echo "<br> ". $prijmeni ." ". $jmeno ." => ". $username ." / ". $username;

     } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nevytvořeno $i!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
     }
}

} //GENEROVAT_ZAMESTNANCE

echo "\n\n\n";

/* **************************************************************************
 *
 * Dokumenty
 *
 ************************************************************************** */
if ( GENEROVAT_DOKUMENTY ) {
debug_head('Dokumenty', 2);

$Dokument = new Dokument();
$Workflow = new Workflow();
$LogDokument = new LogModel();
$File = new FileModel();
$CJ = new CisloJednaci();

if (TRUNCATE) $Workflow->deleteAll();
if (TRUNCATE) $Dokument->deleteAll();
if (TRUNCATE) $File->deleteAll();
if (TRUNCATE) $LogDokument->deleteAllDokument();

$typ_dokumentu_a = range(1,5);
$typ_dokumentu_a[] = 1;
$typ_dokumentu_a[] = 1;
$typ_dokumentu_a[] = 1;
$typ_dokumentu_a[] = 2;
$typ_dokumentu_a[] = 2;

for ( $i =0; $i <= POCET_DOKUMENTU; $i++ ) {
    try {

        $cjednaci = $CJ->generuj(1);

        $nazev_id = mt_rand(0, count($dokument_nazev)-1 );
        $nazev = $dokument_nazev[$nazev_id];
        $typ_dokumentu = mt_rand(0, count($typ_dokumentu_a)-1 );
        $datum_pred = mt_rand(0, 20);

        // dynamicke promenne
        if ( strpos($nazev,'%') !== false ) {
            $nazev = str_replace('%datum%', date('j.n.Y'), $nazev);
            
            $mesto = mt_rand(0,count($subj)-1);
            $nazev = str_replace('%subject%', 'OÚ '. $subj[$mesto]['obec'], $nazev);

            $aprijmeni = mt_rand(0,count($subj_prijmeni)-1);
            $ajmeno = mt_rand(0,count($subj_jmena)-1);
            $nazev = str_replace('%jmeno%', $subj_jmena[$ajmeno] ." ". $subj_prijmeni[ $aprijmeni ], $nazev);
        }


        $dok = array(
            "JID" => $cjednaci->app_id.'-ESS-'.$i,
            "nazev" => $nazev,
            "popis" => "",
            "stav" => 1,
            "typ_dokumentu_id" => $typ_dokumentu_a[$typ_dokumentu],
            "cislojednaci_id" => $cjednaci->id,
            "cislo_jednaci" => $cjednaci->cislo_jednaci,
            "podaci_denik" => $cjednaci->podaci_denik,
            "podaci_denik_poradi" => $cjednaci->poradove_cislo,
            "podaci_denik_rok" => $cjednaci->rok,

            "zpusob_doruceni_id" => "",
            "cislo_jednaci_odesilatele" => "",
            "datum_vzniku" => date('Y-m-d H:i:s', time() - ($datum_pred*86400)),
            "lhuta" => "30",
            "poznamka" => "",
            "zmocneni_id" => "0"
        );

        $dokument = $Dokument->ulozit($dok);
        if ( $dokument ) {

            $Workflow->vytvorit($dokument->id,"");
            $LogDokument->logDokument($dokument->id, LogModel::DOK_NOVY);

            // Spis
            $DokumentSpis = new DokumentSpis();
            if ( $user_config->cislo_jednaci->typ_evidence == 'sberny_arch' ) {

                $spis = $Spis->getInfo($dok['cislo_jednaci']);
                if ( !$spis ) {
                    // vytvorime spis
                    $spis_new = array(
                        'nazev' => $dok['cislo_jednaci'],
                        'popis' => $dok['popis'],
                        'typ' => 'S',
                        'stav' => 1
                    );
                    $spis_id = $Spis->vytvorit($spis_new);
                    $spis = $Spis->getInfo($spis_id);
                }

                // pripojime
                if ( $spis ) {
                    $DokumentSpis->pripojit($dokument->id, $spis->id);
                }
            } else {
                // nahodne pridani ke spisu
                $spis_id = mt_rand(1, POCET_SPISU-1 );
                $DokumentSpis->pripojit($dokument->id, $spis_id);
            }

            // Subjekty
            $subjekt_id = mt_rand(1, POCET_SUBJEKTU );
            $subjekt_type_a = array('AO','A','O');
            $subjekt_type_id = mt_rand(0, count($subjekt_type_a)-1 );
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentSubjekt->pripojit($dokument->id, $subjekt_id, $subjekt_type_a[$subjekt_type_id]);

            // Predani
            $user_predani = mt_rand(1, POCET_ZAMESTNANCU-1);
            $UserOrg = $User->getOrg($user_predani);
            $Workflow->priradit($dokument->id, $user_predani, @$UserOrg->id);
            $Workflow->prevzit($dokument->id, $user_predani);

        }

        echo "<br> ". $dok['cislo_jednaci'] ." - ". $nazev;

     } catch ( DibiException $e ) {
        echo "<br>   => <span style='color:red'>nevytvořeno $i!</span>";
        echo "<br>   <span style='color:red'>Chyba: ".htmlspecialchars($e->getMessage())."</span>";
        echo "<br>   <span style='color:red'>SQL: ".htmlspecialchars($e->getSql())."</span>";
     }
}
} //GENEROVAT_DOKUMENTY
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

    function make_string($pass_len = 8)
    {
        $salt = 'abcdefghijklmnopqrstuvwxyz';
        $salt = strtoupper($salt);
        $salt_len = strlen($salt);
        /*function make_seed()
        {
            list($usec, $sec) = explode(' ', microtime());
            return (float) $sec + ((float) $usec * 100000);
        }*/
        mt_srand(make_seed());
        $pass = '';
        for ($i=0; $i<$pass_len; $i++) {
            $pass .= substr($salt, mt_rand() % $salt_len, 1);
        }
        return $pass;
    }