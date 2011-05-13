<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

// client identificator
define('KLIENT', 'default');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/client');
//define('CLIENT_DIR', WWW_DIR . '/clients/client2');

// setting

// smazat predchozi data?
define('TRUNCATE', true);

// generovat data 1=ANO 0=NE
define('GENEROVAT_SPISOVE_ZNAKY', 1);
define('GENEROVAT_SPISY', 1);
define('GENEROVAT_SUBJEKTY', 1);
define('GENEROVAT_ORGJEDNOTKY', 1);
define('GENEROVAT_ZAMESTNANCE', 1);
define('GENEROVAT_DOKUMENTY', 1);
define('GENEROVAT_PRILOHY', 1);

// pocet zaznamu
define('POCET_SPISOVYCH_ZNAKU', 200);
define('POCET_SPISU', 1500);
define('POCET_SUBJEKTU', 3000);
define('POCET_ORGJEDNOTEK', 300);
define('POCET_ZAMESTNANCU', 600);
define('POCET_DOKUMENTU', 50000);
define('POCET_PRILOH', 1000);

define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', 'admin');




// load bootstrap file
require APP_DIR . '/test_data.php';
