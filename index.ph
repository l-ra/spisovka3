<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

// absolute or relative url path to public dir
define('BASE_URI', '/public/');

// client identificator
define('KLIENT', 'klient');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/client');

define('DEBUG_ENABLE', 0);

// load bootstrap file
require APP_DIR . '/bootstrap.php';
