<?php

function revision_1060_after()
{
    global $client;
    
    $config = $client->get_db_config();
    $prefix = $config['prefix'];
    if (!$prefix)
        $prefix = ''; // nesmi byt null
    $m = new Spis($prefix);
    
    $m->rebuildIndex();    
}
