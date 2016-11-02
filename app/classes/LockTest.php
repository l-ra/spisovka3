<?php

define('TEMP_DIR', '/tmp');

require 'Lock.php';

echo "Obtaining lock...\n";

try {
    $lock = new Spisovka\LockNotBlocking('test');

    echo "Sleeping...\n";
    sleep(10);

    echo "Done\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
