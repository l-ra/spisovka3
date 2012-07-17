<?php

/*echo "\n\n ... ";
dibi::begin();
try {
    
    
    echo "<span style='color:green;'>OK</span>";
    dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    dibi::rollback();
}*/

/* spisovy_znak - uprava sekvence */
echo "\n\n Úprava spisových znaků pro správné řazení ... ";
try {
    
    $sz_rows = dibi::query('SELECT * FROM %n', $config['prefix'] ."spisovy_znak","ORDER BY parent_id ASC")->fetchAll();
    if ( count($sz_rows)>0 ) {
        $parent_id = array('0' => '', '' => '');
        $parent_sekvence = array('0' => '', '' => '');
        foreach ( $sz_rows as $sznak ) {
            $parent = empty($sznak->parent_id)?null:$sznak->parent_id;
            $sekvence = $parent_id[ $sznak->parent_id ] . $sznak->id;
            $sekvence_string = $parent_sekvence[ $sznak->parent_id ] . $sznak->nazev .".". $sznak->id;
            $parent_id[ $sznak->id ] = $sekvence.".";
            $parent_sekvence[ $sznak->id ] = $sekvence_string."#";
            $uroven_explode = explode(".",$sekvence);
            $uroven = count($uroven_explode)-1;
            $spousteci_udalost = empty($sznak->spousteci_udalost_id)?null:$sznak->spousteci_udalost_id;
            
            dibi::update($config['prefix'] ."spisovy_znak", 
                    array('parent_id'=>$parent, 'spousteci_udalost_id' => $spousteci_udalost,
                          'sekvence'=>$sekvence,'sekvence_string'=>$sekvence_string,'uroven'=>$uroven))
                    ->where(array(array('id=%i',$sznak->id)))
                    ->execute();            
        }
    }

    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}

/* spis - uprava */
echo "\n\n Úprava spisů (správné řazení) ... ";
try {
    
    $sz_rows = dibi::query('SELECT * FROM %n', $config['prefix'] ."spis","ORDER BY parent_id ASC")->fetchAll();
    if ( count($sz_rows)>0 ) {
        $parent_id = array( '0' => '', '' => '');
        $parent_sekvence = array('0' => '', '' => '');
        foreach ( $sz_rows as $row ) {

            $parent = empty($row->parent_id)?null:$row->parent_id;
            $sekvence = $parent_id[ $row->parent_id ] . $row->id;
            $sekvence_string = $parent_sekvence[ $row->parent_id ] . $row->nazev .".". $row->id;
            $parent_id[ $row->id ] = $sekvence.".";
            $parent_sekvence[ $row->id ] = $sekvence_string."#";
            $uroven_explode = explode(".",$sekvence);
            $uroven = count($uroven_explode)-1;
            $spousteci_udalost = empty($row->spousteci_udalost_id)?null:$row->spousteci_udalost_id;
            $spisovy_znak_id = empty($row->spisovy_znak_id)?null:$row->spisovy_znak_id;
            $datum_otevreni = $row->date_created;
            if ( $row->stav == 0 ) {
                $datum_uzavreni = $row->date_modified;
            } else {
                $datum_uzavreni = null;
            }
            
            dibi::update($config['prefix'] ."spis", 
                    array('parent_id'=>$parent,
                          'sekvence'=>$sekvence,'sekvence_string'=>$sekvence_string,'uroven'=>$uroven,
                          'datum_otevreni'=>$datum_otevreni,'datum_uzavreni'=>$datum_uzavreni,
                          'spousteci_udalost_id'=>$spousteci_udalost,'spisovy_znak_id'=>$spisovy_znak_id ))
                    ->where(array(array('id=%i',$row->id)))
                    ->execute();

        }
    }

    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}

/* user_role - uprava */
echo "\n\n Úprava role (správné řazení) ... ";
try {
    
    $sz_rows = dibi::query('SELECT * FROM %n', $config['prefix'] ."user_role","ORDER BY parent_id ASC")->fetchAll();
    if ( count($sz_rows)>0 ) {
        $parent_id = array( '0' => '', '' => '');
        $parent_sekvence = array('0' => '', '' => '');
        foreach ( $sz_rows as $row ) {

            $parent = empty($row->parent_id)?null:$row->parent_id;
            $sekvence = $parent_id[ $row->parent_id ] . $row->id;
            $sekvence_string = $parent_sekvence[ $row->parent_id ] . $row->code .".". $row->id;
            $parent_id[ $row->id ] = $sekvence.".";
            $parent_sekvence[ $row->id ] = $sekvence_string."#";
            $uroven_explode = explode(".",$sekvence);
            $uroven = count($uroven_explode)-1;
            
            dibi::update($config['prefix'] ."user_role", 
                    array('parent_id'=>$parent,
                          'sekvence'=>$sekvence,'sekvence_string'=>$sekvence_string,'uroven'=>$uroven,
                           ))
                    ->where(array(array('id=%i',$row->id)))
                    ->execute();

        }
    }

    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Úprava workflow (vazba na organizacni jednotku a spis) ... ";
try {

    $wf_users = array();
    $wf_spisy = array();
    $sz_rows = dibi::query('SELECT ur.user_id, ur.role_id, r.orgjednotka_id FROM %n', $config['prefix'] ."user_to_role"," AS ur LEFT JOIN %n",$config['prefix'] ."user_role"," AS r ON r.id=ur.role_id ORDER BY ur.user_id")->fetchAll();
    if ( count($sz_rows)>0 ) {
        foreach ( $sz_rows as $row ) {
            if ( !empty($row->orgjednotka_id) ) {
                $wf_users[ $row->user_id ] = $row->orgjednotka_id;
            }
        }
    }

    $sz_rows = dibi::query('SELECT * FROM %n', $config['prefix'] ."dokument_to_spis"," ORDER BY dokument_id")->fetchAll();
    if ( count($sz_rows)>0 ) {
        foreach ( $sz_rows as $row ) {
            $wf_spisy[ $row->dokument_id ] = $row->spis_id;
        }
    }

    for ( $offset=0; $offset<=1000; $offset++ ) {
        
        // z dusledku pametove narocnosti se rozdeli zatez po 500 
        // potrva tak dlouho, dokud zadne dalsi stranky nebudou
    
        $sz_rows = dibi::query('SELECT id,orgjednotka_id,prideleno_id,dokument_id,spis_id FROM %n', $config['prefix'] ."workflow","")->fetchAll($offset*1000, 1000);
        if ( count($sz_rows)>0 ) {
            foreach ( $sz_rows as $row ) {
                
                $update_row = array();
                if ( empty($row->orgjednotka_id) && !empty($row->prideleno_id) && isset($wf_users[$row->prideleno_id]) ) {
                    //$update_row['orgjednotka_id'] = $wf_users[$row->prideleno_id];
                }
                if ( empty($row->spis_id) && isset($wf_spisy[ $row->dokument_id ]) ) {
                    $update_row['spis_id'] = $wf_spisy[ $row->dokument_id ];
                }
            
                if ( count($update_row)>0 ) {
                    dibi::update($config['prefix'] ."workflow", $update_row)
                            ->where(array(array('id=%i',$row->id)))
                        ->execute();         
                }
                
            }
            
            if ( count($sz_rows) < 1000 ) {
                $offset = 1001;
                break;                
            }
            
        } else {
            $offset = 1001;
            break;
        }   
    }
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}



/*
 * Pridani a uprava prav
 */

echo "\n\n Pridani opravneni Spisovna_DefaultPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovna_DefaultPresenter', 'note' => null,'name' => 'Spisovna' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Přístup do spisovny','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);

    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani opravneni Spisovna_DokumentyPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovna_DokumentyPresenter', 'note' => null,'name' => 'Spisovna - dokumenty' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Přístup k dokumentům ve spisovně','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani opravneni Spisovna_SpisyPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovna_SpisyPresenter', 'note' => null,'name' => 'Spisovna - spisy' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Přístup ke spisům ve spisovně','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani opravneni Spisovna_VyhledatPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovna_VyhledatPresenter', 'note' => null,'name' => 'Spisovna - vyhledavani' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Vyhledávání dokumentů ve spisovně','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani opravneni Spisovna_ZapujckyPresenter ... ";
//dibi::begin();
try {

    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovna_ZapujckyPresenter', 'note' => null,'name' => 'Spisovna - zapujcky' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Spisovna - zápůjčky','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);

    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani opravneni Spisovka_VypravnaPresenter ... ";
//dibi::begin();
try {
    
    $resource_id = dibi::insert($config['prefix'] ."user_resource", array(
        'code' => 'Spisovka_VypravnaPresenter', 'note' => null,'name' => 'Výpravna' ))->execute(dibi::IDENTIFIER);
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => $resource_id, 'name' => 'Výpravna','note' => '', 'privilege' => '' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 4, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani pravidla Příjem dokumentů v jednotce ... ";
//dibi::begin();
try {
    
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => null, 'name' => 'Příjem dokumentů v jednotce','note' => 'Může přijímat dokumenty na organizační jednotku', 'privilege' => 'prijem_na_org' ))->execute(dibi::IDENTIFIER);
    //dibi::insert($config['prefix'] ."user_acl", array(
    //    'role_id' => '', 'rule_id' => null, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);    
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani pravidla Odesílání dokumentu ... ";
//dibi::begin();
try {
    
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => 1, 'name' => 'Odesílání dokumentu','note' => 'Povolit přímé odesílání dokumentu', 'privilege' => 'odeslat' ))->execute(dibi::IDENTIFIER);
    //dibi::insert($config['prefix'] ."user_acl", array(
    //    'role_id' => '', 'rule_id' => null, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);    
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


echo "\n\n Pridani pravidla Odhlášení uživatele ... ";
//dibi::begin();
try {
    
    $rule_id = dibi::insert($config['prefix'] ."user_rule", array(
        'resource_id' => 4, 'name' => 'Odhlášení uživatele','note' => '', 'privilege' => 'logout' ))->execute(dibi::IDENTIFIER);
    dibi::insert($config['prefix'] ."user_acl", array(
        'role_id' => 2, 'rule_id' => $rule_id, 'allowed' => 'Y' ))->execute(dibi::IDENTIFIER);    
    
    echo "<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}
























    /* Prejit na revizi 309 */
    /* Revize mezi 160 a 309 jsou jiz provedeny v revizi 160 */
    $revision = 309;

?>