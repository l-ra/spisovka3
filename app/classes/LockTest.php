<?php

define('TEMP_DIR', '/tmp');

require 'Lock.php';

echo "Obtaining lock...\n";

$lock = new Spisovka\Lock('test');

echo "Sleeping...\n";
sleep(10);

echo "Done\n";