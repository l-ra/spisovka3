<?php

function revision_870_after()
{
    global $client;
    
    deleteDir($client->get_path() . "/sessions/");
}
