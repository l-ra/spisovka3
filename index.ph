<?php

// absolute filesystem path to the web root
$dir = dirname(__FILE__);
// absolute filesystem path to the application root
define('APP_DIR', "$dir/app");
// absolute filesystem path to the libraries
define('LIBS_DIR', "$dir/libs");
// absolute filesystem path to the client files
define('CLIENT_DIR', "$dir/client");
unset($dir);

// absolute or relative URL to public resources (CSS, Javascript, images)
define('BASE_URI', '/public/');

// client identificator
define('KLIENT', 'klient');

define('DEBUG_ENABLE', 0);

// load bootstrap file
require APP_DIR . '/bootstrap.php';
