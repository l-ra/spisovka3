<?php

// absolute filesystem path to the application root
define('APP_DIR', __DIR__ . "/app");
// absolute filesystem path to the client files
define('CLIENT_DIR', __DIR__ . "/client");

// client identificator
define('KLIENT', 'klient');

define('DEBUG_ENABLE', 0);

// load bootstrap file
require APP_DIR . '/bootstrap.php';
