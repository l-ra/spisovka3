<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../../../app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../../../libs');

// absolute or relative url path to public dir
define('BASE_URI', '/public/');
// absolute or relative url path to app dir
define('BASE_APP', '/');


// client identificator
define('KLIENT', 'klient2');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/..');


// load bootstrap file
require APP_DIR . '/bootstrap.php';
