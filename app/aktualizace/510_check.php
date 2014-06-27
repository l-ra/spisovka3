<?php

    // kontrola na existenci zaznamu
    $result = dibi::query('SELECT id FROM %n', $config['prefix'] ."user_resource"," WHERE name = 'Administrace - spisy' ")->fetchAll();
    
    // zaznam existuje, revize se neprovede
    if ($result)
        $continue = 1;
    
?>