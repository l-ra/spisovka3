<?php

    //header("Content-type: text/plain");

    define("WWW_DIR","");
    define('MULTISITE',0); // 0 - standalone, 1 - multisite

    include WWW_DIR ."libs/dibi/dibi.php";

    $adir = opendir(WWW_DIR .'app/InstallModule/');
    $alter = array();
    while (($af = readdir($adir)) !== false) {
        if ( $af[0] == "a" ) {
            $sql_source = file_get_contents(WWW_DIR .'app/InstallModule/'. $af);
            $sql_query = explode(";",$sql_source);
            $sql_revision = trim(str_replace(".sql", "", str_replace("alter_", "", $af)));
            unset($sql_query[0]);
            $alter[$sql_revision] = $sql_query;
            unset($sql_source, $sql_query);
        }
    }
    closedir($adir);

    echo "<pre>";

    if ( MULTISITE == 1 ) {

    $odir = opendir(WWW_DIR ."clients");
    while (($file = readdir($odir)) !== false) {
        if ( $file == "." || $file == "..") {
            continue;
        } elseif ( is_dir(WWW_DIR ."clients/".$file)) {

            echo "==========================================\n";
            echo "=== ". $file ." ===\n";
            echo "\n";

            $ini = parse_ini_file(WWW_DIR ."clients/".$file."/configs/system.ini",true);
            $config = array(
                "driver"=>$ini['common']['database.driver'],
                "host"=>$ini['common']['database.host'],
                "username"=>$ini['common']['database.username'],
                "password"=>$ini['common']['database.password'],
                "database"=>$ini['common']['database.database'],
                "charset"=>$ini['common']['database.charset'],
                "prefix"=>$ini['common']['database.prefix'],
            );
            echo "Databaze: "; print_r($config);

            dibi::connect($config);

            if (file_exists(WWW_DIR ."clients/".$file."/configs/_aktualizace") ) {
                $revision = trim(file_get_contents(WWW_DIR ."clients/".$file."/configs/_aktualizace"));
            } else {
                $revision = 0;
            }

            echo "\nPosledni revize klienta: ". $revision ."\n\n";

            if ( count($alter)>0 ) {
                foreach( $alter as $arev => $asql ) {
                    if ( $revision < $arev ) {
                        echo "\nAplikace revize #". $arev ."\n\n";
                        if ( count($asql)>0 ) {
                            foreach ( $asql as $query ) {
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
                                    }
                                } else {
                                    echo "". $query .";\n";
                                }


                            }
                        }
                    }
                }
            }

            if ( isset($_GET['go']) ) {
                file_put_contents(WWW_DIR ."clients/".$file."/configs/_aktualizace",$arev);
            }

            dibi::disconnect();

            echo "\n\n";

        }
    }
    closedir($odir);

    } else {

            echo "==========================================\n";
            echo "=== Standalone client ===\n";
            echo "\n";

            $ini = parse_ini_file(WWW_DIR ."client/configs/system.ini",true);
            $config = array(
                "driver"=>$ini['common']['database.driver'],
                "host"=>$ini['common']['database.host'],
                "username"=>$ini['common']['database.username'],
                "password"=>$ini['common']['database.password'],
                "database"=>$ini['common']['database.database'],
                "charset"=>$ini['common']['database.charset'],
                "prefix"=>$ini['common']['database.prefix'],
            );
            echo "Databaze: "; print_r($config);

            dibi::connect($config);

            if (file_exists(WWW_DIR ."client/configs/_aktualizace") ) {
                $revision = trim(file_get_contents(WWW_DIR ."client/configs/_aktualizace"));
            } else {
                $revision = 0;
            }

            echo "\nPosledni revize klienta: ". $revision ."\n\n";

            if ( count($alter)>0 ) {

		// setridit pole, aby se alter skripty spoustely ve spravnem poradi
		asort( $alter, SORT_NUMERIC );

                foreach( $alter as $arev => $asql ) {
                    if ( $revision < $arev ) {
                        echo "\nAplikace revize #". $arev ."\n\n";
                        if ( count($asql)>0 ) {
                            foreach ( $asql as $query ) {
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
                                    }
                                } else {
                                    echo "". $query .";\n";
                                }


                            }
                        }
                    }
                }
            }

            if ( isset($_GET['go']) ) {
                file_put_contents(WWW_DIR ."client/configs/_aktualizace",$arev);
            }
            dibi::disconnect();

            echo "\n\n";

    }
