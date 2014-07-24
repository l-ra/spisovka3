<?php

function revision_700_check()
{
    // --------------------------------------------------------------------    
    $result = dibi::query("SELECT id FROM [:PREFIX:user_role] WHERE [code] = 'skartacni_dohled'");
    
    if (count($result) == 0)
        throw new Exception('Role "skartacni_dohled" neexistuje! Je nutné ji vytvořit.');
}

?>