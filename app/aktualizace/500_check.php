<?php

    // kontrola na existenci zaznamu
    $result = dibi::query('SELECT id FROM %n', $config['prefix'] ."user_resource"," WHERE code = 'DatovaSchranka' ")->fetchAll();
    
    // zaznam existuje, revize se neprovede
    if ($result)
        $continue = 1;
    
?>