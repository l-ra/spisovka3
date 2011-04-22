<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

// absolute or relative url path to public dir
define('BASE_URI', '/public/');
// absolute or relative url path to app dir
define('BASE_APP', '/');


// client identificator
define('KLIENT', 'default');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/client');

// manual debug - 1 = enable, 0 = disable, commented out line = automatic
//define('DEBUG_ENABLE', 1);

// load bootstrap file
require APP_DIR . '/bootstrap.php';
