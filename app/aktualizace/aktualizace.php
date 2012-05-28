<?php

/* Mozna vytvorit mechanismus proti nechtene aktualizaci
 * 
 * if ( PUBLIC_DIR == "" ) {
    if (!file_exists(WWW_DIR ."public/aktualizace.php") ) {
        header("Status: 404 Not Found");
        echo "Nelze provést aktualizaci";
        exit;
    }
} else {
    if (!file_exists(WWW_DIR ."aktualizace.php") ) {
        header("Status: 404 Not Found");
        exit;
    }    
}*/
    
    set_time_limit(0); // zpracovani muze byt delsi dle mnozstvi dat

    include WWW_DIR ."libs/dibi/dibi.php";
    
    define('UPDATE_DIR',WWW_DIR.'app/aktualizace/');
    $alter = array();
    $rev_a = array();

    // Nacteni obsahu adresare
    $adir = opendir(UPDATE_DIR);
    while (($af = readdir($adir)) !== false) {
        
        $rev_part = explode("_",$af);
        if ( is_numeric($rev_part[0][0]) ) {
            if ( strlen($rev_part[0]) == 7 ) {
                if ( preg_match('#(\d)(\d{3})(\d{3})#', $rev_part[0], $matches) ) {
                    $rev_a[ $rev_part[0] ] = (int)$matches[1] .".". (int)$matches[2] .".". (int)$matches[3];
                } else {
                    $rev_a[ $rev_part[0] ] = $rev_part[0];
                }
            } else {
                $rev_a[ $rev_part[0] ] = $rev_part[0];
            }
            
            
            if ( strpos($af,"_alter.sql") !== false ) {
                $sql_source = file_get_contents(UPDATE_DIR . $af);
                $sql_query = explode(";",$sql_source);
                unset($sql_query[0]);
                $alter[ $rev_part[0] ] = $sql_query;
                unset($sql_source, $sql_query);
            }                
            
        }
    }
    closedir($adir);
    ksort( $rev_a, SORT_NUMERIC ); //setridit pole, aby se alter skripty spoustely ve spravnem poradi

    $sites = array();
    if ( MULTISITE == 1 ) {
        $odir = opendir(WWW_DIR ."clients");
        while (($file = readdir($odir)) !== false) {
            if ( $file == "." || $file == "..") {
                continue;
            } elseif ( is_dir(WWW_DIR ."clients/".$file)) {        
                $sites[ WWW_DIR ."clients/".$file ] = $file ."(".WWW_DIR ."clients/".$file.")";
            }
        }
    } else {
        $sites[ WWW_DIR ."client" ] = "STANDALONE (".WWW_DIR ."client)";
    }

?>    
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Spisová služba - Aktualizace</title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo PUBLIC_DIR; ?>css/site.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo PUBLIC_DIR; ?>css/install_site.css" />
    <link rel="shortcut icon" href="<?php echo PUBLIC_DIR; ?>favicon.ico" type="image/x-icon" />
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1>Spisová služba<span id="top_urad">Aktualizace</span></h1>
        <div id="top_menu">
            &nbsp;
        </div>
        <div id="top_jmeno">
            &nbsp;
        </div>
    </div>
</div>
<div id="layout">
    <div id="menu">
    &nbsp;
    <a href="aktualizace.php?go=1" onclick="return confirm('Opravdu chcete provést aktualizaci spisové služby?');">Spustit aktualizaci</a>
    </div>
    <div id="content"> 
        
<?php    
    
    foreach ( $sites as $site_path => $site_name ) {
        
        echo "<div class='update_site'>";
        echo "<h1>$site_name</h1>";
        
        $ini = parse_ini_file($site_path."/configs/system.ini",true);
        $config = array(
            "driver"=>$ini['common']['database.driver'],
            "host"=>$ini['common']['database.host'],
            "username"=>$ini['common']['database.username'],
            "password"=>$ini['common']['database.password'],
            "database"=>$ini['common']['database.database'],
            "charset"=>$ini['common']['database.charset'],
            "prefix"=>$ini['common']['database.prefix'],
            "profiler"=>FALSE
        );

        echo '<div class="dokument_blok">';
        echo '<dl class="detail_item">';
        echo '    <dt>Databáze:</dt>';
        echo '    <dd>'. $config['driver'] .'://'. $config['username'] .'@'. $config['host'] .'/'. $config['database'] .'&nbsp;</dd>';
        echo '</dl>';        
        //echo "Databaze: "; print_r($config);

        dibi::connect($config);

        if (file_exists($site_path."/configs/_aktualizace") ) {
            $revision = trim(file_get_contents($site_path."/configs/_aktualizace"));
        } else {
            $revision = 0;
        }

        echo '<dl class="detail_item">';
        echo '    <dt>Poslední zjištěná revize klienta:</dt>';
        echo '    <dd>'. $revision .'&nbsp;</dd>';
        echo '</dl>';        
                    
        echo '</div>';
        echo '<br />';

        $apply_rev = 0; $rev_error = 0;
        if ( count($rev_a)>0 ) {
            
            foreach( $rev_a as $arev => $arevs ) {
                if ( $revision < $arev ) {
                    
                    try {

                    // Control source
                    $continue = 0;    
                    if (file_exists(UPDATE_DIR . $arev .'_check.php') ) {
                        include_once UPDATE_DIR . $arev .'_check.php';
                    }                     
                    if ( $continue == 1 ) { $continue = 0; continue; }
                    $apply_rev++;
                    
                    echo "<div class='update_rev'>";

                    if ( isset($_GET['go']) ) dibi::begin();
                    
                    // Info
                    if (file_exists(UPDATE_DIR . $arev .'_info.php') ) {
                        echo "<div class='update_title'>Informace o této aktualizaci:</div>";
                        echo "<div class='update_info'>". file_get_contents(UPDATE_DIR . $arev .'_info.php') ."</div>";
                    } else {
                        echo "<div class='update_title'>Informace o této aktualizaci:</div>";
                        if ( $arevs[1] == "." ) {
                            echo "<div class='update_info'><strong>Verze ". $arevs ."</strong></div>";
                        } else {
                            echo "<div class='update_info'><strong>Revize #". $arevs ."</strong></div>";
                        }                        
                    }
                    
                    // PRE script
                    if (file_exists(UPDATE_DIR . $arev .'_script_prev.php') ) {
                        if ( isset($_GET['go']) ) {
                            echo "<div class='update_title'>Provedení PHP skriptu (před aktualizaci databáze)</div>";
                            echo "<pre>";
                            include_once UPDATE_DIR . $arev .'_script_prev.php';
                            echo "</pre>";
                            
                        } else {
                            echo "<div class='update_title'>Bude proveden PHP skript (před aktualizaci databáze)</div>";
                        }
                    }
                    
                    // SQL
                    if ( isset($alter[$arev]) && count($alter[$arev])>0 ) {
                        
                        if ( isset($_GET['go']) ) {
                            echo "<div class='update_title'>Aktualizace databáze:</div>";
                        } else {
                            echo "<div class='update_title'>Bude provedena aktualizace databáze s následujícími SQL příkazy:</div>";
                        }
                        
                        echo "<pre>";
                        foreach ( $alter[$arev] as $query ) {
                            $query = str_replace("\r", "", $query);
                            $query = str_replace("\n", "", $query);
                            $query = str_replace("\t", " ", $query);
                            $query = str_replace("{tbls3}", $config['prefix'], $query);
                            $query = trim($query);
                            if ( empty($query) ) continue;
                            if ( $query[0] == "-" ) continue;

                            if ( isset($_GET['go']) ) {
                                try {
                                    dibi::query($query);
                                    echo "<span style='color:green'> >> ". $query ."</span>\n";
                                } catch ( DibiException $e ) {
                                    echo "<span style='color:red'> >> ". $query ."</span>\n";
                                    echo "<span style='color:red'> >> Chyba: ". $e->getMessage() ."</span>\n";
                                    throw new DibiException($e->getMessage(),$e->getCode(),$e->getSql());
                                }
                            } else {
                                echo "". $query .";\n";
                            }
                        }
                        echo "</pre>";
                    }
                    
                    // AFTER source
                    if (file_exists(UPDATE_DIR . $arev .'_script_after.php') ) {
                        if ( isset($_GET['go']) ) {
                            echo "<div class='update_title'>Provedení PHP skriptu (po aktualizaci databáze)</div>";
                            echo "<pre>";
                            include_once UPDATE_DIR . $arev .'_script_after.php';
                            echo "</pre>";
                        } else {
                            echo "<div class='update_title'>Bude proveden PHP skript (po aktualizaci databáze)</div>";
                        }
                    } 
                    
                        if ( isset($_GET['go']) ) dibi::commit();
                    
                    } catch (DibiException $e) {
                        if ( isset($_GET['go']) ) dibi::rollback();
                        $rev_error = 1;
                        break;
                    }
                    
                    echo "</div>";
                }
            }
        }
        
        if ($apply_rev == 0) {
            echo "<div class='update_no'>Nebyla zjištěna žádná revize. Spisová služba je aktuální.</div>";
            if ( $rev_error != 1 ) file_put_contents($site_path."/configs/_aktualizace",$arev);
        }
        unset($apply_rev);
        
        if ( isset($_GET['go']) ) {
            if ( $rev_error != 1 ) file_put_contents($site_path."/configs/_aktualizace",$arev);
        }

        dibi::disconnect();

        echo "</div>\n\n";

        
    }

?>
    </div>
</div>
<div id="layout_bottom">
    <div id="bottom">
        <strong>OSS Spisová služba</strong><br/>
        Na toto dílo se vztahuje <a href="http://www.osor.eu/eupl/european-union-public-licence-eupl-v.1.1">licence EUPL V.1.1</a>
    </div>
</div>


</body>
</html>