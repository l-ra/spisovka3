<?php

function revision_990_check()
{
    $res = dibi::query("SELECT [id], [jid] FROM [:PREFIX:dokument] WHERE [spisovy_znak_id] NOT IN"
            . " (SELECT [id] FROM [:PREFIX:spisovy_znak])");
    
    if (count($res)) {
        echo "Nalezena chyba v datech! Je nutné ji ručně opravit.\n";
        echo "Jedná se o následující dokumenty:\n\n";
        foreach ($res as $dok)
            echo $dok->jid . "\n";            
        return false;
    }
    
    return true;
}
