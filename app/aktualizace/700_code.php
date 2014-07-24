<?php

function revision_700_check()
{
    // --------------------------------------------------------------------    
    $result = dibi::query("SELECT id FROM [:PREFIX:user_role] WHERE [code] = 'referent'");
    $pole = $result->fetch();
    if (!$pole || $pole['id'] != 4)
        throw new Exception('Role "referent" je chybně definována!');
}

?>