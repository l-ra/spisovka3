<?php

/*
 * Pridani a uprava prav
 */

echo "\n\n Pridani opravneni Spisovka_ZpravyPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovka_ZpravyPresenter', 'note' => null,'name' => 'Zprávy' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Zobrazení zpráv','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);

    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}

