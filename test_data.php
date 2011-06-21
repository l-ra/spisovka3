<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

// client identificator
define('KLIENT', 'brno');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/client');

// setting

// smazat predchozi data?
define('TRUNCATE', false);

// generovat data 1=ANO 0=NE

define('GENEROVAT_SPISOVE_ZNAKY', 0);
define('GENEROVAT_SPISY', 0);
define('GENEROVAT_SUBJEKTY', 0);
define('GENEROVAT_ORGJEDNOTKY', 0);
define('GENEROVAT_ZAMESTNANCE', 0);
define('GENEROVAT_DOKUMENTY', 1);
define('GENEROVAT_PRILOHY', 0);

// pocet zaznamu
define('POCET_SPISOVYCH_ZNAKU', 10);
define('POCET_SPISU', 10);
define('POCET_SUBJEKTU', 300);
define('POCET_ORGJEDNOTEK', 100);
define('POCET_ZAMESTNANCU', 91);
define('POCET_DOKUMENTU', 1000);
define('POCET_PRILOH', 145);

define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', 'admin123heslo');




// load bootstrap file
require APP_DIR . '/test_data.php';
