<?php


//dibi::begin();
/*try {

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

    print_r($wf_users);
    print_r($wf_spisy);
    
    $sz_rows = dibi::query('SELECT * FROM %n', $config['prefix'] ."workflow","")->fetchAll();
    if ( count($sz_rows)>0 ) {
        foreach ( $sz_rows as $row ) {
            
            //echo ">> ". $row->prideleno_id ." - ". $row->orgjednotka_id ." = ". $wf_users[$row->prideleno_id] ."\n";
            
            $update_row = array();
            if ( empty($row->orgjednotka_id) && !empty($row->prideleno_id) && isset($wf_users[$row->prideleno_id]) ) {
                $update_row['orgjednotka_id'] = $wf_users[$row->prideleno_id];
            }
            if ( empty($row->spis_id) && isset($wf_spisy[ $row->dokument_id ]) ) {
                $update_row['spis_id'] = $wf_spisy[ $row->dokument_id ];
            }
            
            if ( count($update_row)>0 ) {
                dibi::update($config['prefix'] ."workflow", $update_row)
                        ->where(array(array('id=%i',$row->id)))
                        ->test();         
            }

        }
    }    
    
    echo "\n\n<span style='color:green;'>OK</span>";
    //dibi::commit();
} catch (DibiException $e) {
    echo "<span style='color:red;'>CHYBA! ". $e->getMessage() ."</span>";
    //dibi::rollback();
}


exit;*/

