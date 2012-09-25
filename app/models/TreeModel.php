<?php

class TreeModel extends BaseModel
{

    protected $name;
    protected $nazev = "nazev";
    protected $nazev_sekvence = "nazev";
    protected $primary = 'id';

    /*public function  __construct($table) {

        $this->name = $table;

        parent::__construct();
    }*/

    public function getInfo($id) {
        
        $row = $this->fetchRow(array('id=%i',$id));
        $result = $row->fetch();
        return $result;
        
    }

    public function nacti ( $parent_id = null, $child = true, $sort_by_name = true, $params = null )
    {

        $sql = array(
            'from' => array($this->name => 'tb'),
            'cols' => array('*'),
            'leftJoin' => array()
        );

        if ( $child ) {
            if ( !empty($parent_id) ) {

                $sql['leftJoin'] = array(
                    'parent' => array(
                        'from' => array($this->name => 'tbp'),
                        'on' => array( array('tbp.id=%i',$parent_id) )
                    )
                );
                $sql['where'] = array( 
                                    array("tb.sekvence LIKE CONCAT(tbp.sekvence,'.%')"),
                                    array("tb.id <> %i",$parent_id)
                                );
            }
        } else {
            if ( !empty($parent_id) ) {
                $sql['where'] = array(array('tb.parent_id=%i',$parent_id));
            } else {
                $sql['where'] = array( array('tb.parent_id IS NULL')  );
            }
        }

        if ( !$child ) {
            $sql['order'] = array('tb.nazev');
        } else if ( $sort_by_name ) {
            $sql['order'] = array('tb.sekvence_string');
        } else {
            $sql['order'] = array('tb.sekvence');
        }

        if ( is_array($params) ) {
            if ( isset($params['where']) ) { 
                if ( isset($sql['where']) ) {
                    $sql['where'] = array_merge($sql['where'],$params['where']);     
                } else {
                    $sql['where'] = $params['where']; 
                }
            }
            if ( isset($params['order']) ) { $sql['order'] = array_merge($sql['order'],$params['order']); }
            if ( isset($params['leftJoin']) ) { $sql['leftJoin'] = array_merge($sql['leftJoin'],$params['leftJoin']); }
        }

        $result = $this->fetchAllComplet($sql);
        if ( isset($params['paginator']) ) {
            return $result;
        } else {
            $rows = $result->fetchAll();
            return ($rows) ? $rows : NULL;
        }

    }

    public function select($type = 0, $id = null, $parent_id = null, $params = null) {

        $result = array();
        $parent_sekvence = null;

        if ( $type >= 10 && !empty($parent_id) ) {
            $type = $type - 10;
            $null_id = $parent_id;
        } else {
            if ( !empty($parent_id) ) {
                $null_id = $parent_id;
            } else {
                $null_id = 0;
            }
        }

        if ( $type == 1 ) {
            $result[$null_id] = '(hlavní větev)';
        } else if ( $type == 2 ) {
            $result[$null_id] = 'vyberte z nabídky ...';
        } else if ( $type == "2x" ) {
            $result[$null_id] = 'vyberte z nabídky ...';
        } else if ( $type == 3 ) {
            $result[$null_id] = 'všechny ...';
        }

        $rows = $this->nacti($parent_id,true,true,$params);
        if ( count($rows) ) {
            foreach ( $rows as $row_index => $row ) {

                if ( $row->id == $id ) {
                    $parent_sekvence = $row->sekvence;
                    continue;
                }

                if ( $row->id == $parent_id ) {
                    $parent_sekvence = $row->sekvence;
                    continue;
                }

                if ( !empty($parent_sekvence) && strpos($row->sekvence, $parent_sekvence) !== false ) {
                    continue;
                }

                $popis = "";
                if ( !empty($row->popis) ) {
                    $popis = " - ". String::truncate($row->popis,90);
                }
                if ( $type == 10 ) {
                    $result[ $row->id ] = $row->{$this->nazev} .$popis;
                } else if ( $type == 11 ) {
                    $result[ $row->id ] = $row;
                } else if ( $type == "2x" ) {
                    if ( isset($row->selected) && $row->selected == 0 ) {
                        $result[ $row->id ] = Html::el('option')->value($row->id)->setHtml(str_repeat("...", $row->uroven) .' '. $row->{$this->nazev}.$popis)->disabled(TRUE);
                    } else if ( $row->stav == 0 ) {    
                        $result[ $row->id ] = Html::el('option')->value($row->id)->setHtml(str_repeat("...", $row->uroven) .' [neaktivní] '. $row->{$this->nazev}.$popis)->disabled(TRUE);
                    } else {
                        $result[ $row->id ] = Html::el('option')->value($row->id)->setHtml(str_repeat("...", $row->uroven) .' '. $row->{$this->nazev}.$popis);
                    }
                } else {
                    $result[ $row->id ] = str_repeat("...", $row->uroven) .' '. $row->{$this->nazev}.$popis;
                }
                
            }
        }

        return $result;
    }

    public function vlozitH ( $data )
    {

        if ( empty($data['parent_id']) ) $data['parent_id'] = null;
        if ( $data['parent_id'] == 0 ) $data['parent_id'] = null;
        
        dibi::begin();
        try {
            $sekvence_string = isset($data['sekvence_string'])?$data['sekvence_string']:$data[$this->nazev_sekvence];
            unset($data['sekvence_string']);
            
            // 1. clasic insert
            $id = $this->insert( $data );

            // 2. update tree
            $parent_id = $data['parent_id'];
            $data_tree = array();
            if ( empty($parent_id) || $parent_id == 0 ) {
                // is root node
                $data_tree['sekvence'] = $id;
                $data_tree['sekvence_string'] = $sekvence_string .'.'. $id;
                $data_tree['uroven'] = 0;
            } else {
                // is subnode
                $parent = $this->fetchRow(array('id=%i',$parent_id))->fetch();
                if ( !$parent ) {
                    // error - parent is not exist
                    dibi::rollback();
                    return false;
                }

                $data_tree['sekvence'] = $parent->sekvence .'.'. $id;
                $data_tree['sekvence_string'] = $parent->sekvence_string .'#'. $sekvence_string .'.'. $id;
                $data_tree['uroven'] = $parent->uroven + 1;
            }
            $this->update($data_tree, array( array('id=%i',$id)));
        
            dibi::commit();
            return $id;

        } catch (Exception $e) {
            dibi::rollback();
            return $e;
        }

    }

    public function upravitH ( $data, $id )
    {

        // 0. control param
        if ( empty($id) && !is_numeric($id) ) {
            return false;
        }

        // 1. clasic update
        dibi::begin();
        try {

            $sekvence_string = isset($data['sekvence_string'])?$data['sekvence_string']:$data[$this->nazev_sekvence];
            unset($data['sekvence_string']);
            
            $info = $this->fetchRow(array('id=%i',$id))->fetch();
            
            if ( isset($data['spisovy_znak_format']) ) {
                $part = explode(".",$info->{$this->nazev_sekvence});
                if ( count($part)>0 ) {
                    foreach($part as $pi => $pn) {
                        if (is_numeric($pn) ) {
                            $part[$pi] = sprintf("%04d",$pn);
                        }
                    }
                }
                
                $info_nazev_sekvence = implode(".",$part);
                unset($data['spisovy_znak_format']);
            } else {
                $info_nazev_sekvence = $info->{$this->nazev_sekvence};
            }

            $parent_id_old = null;
            if ( isset($data['parent_id_old']) ) {
                $parent_id_old = $data['parent_id_old'];
            }
            unset($data['parent_id_old']);

            if ( $data['parent_id'] == 0 ) $data['parent_id'] = null;

            $this->update( $data , array(array('id=%i',$id)) );

            // 2. update tree
            $parent_id = $data['parent_id'];

            if ( is_null($parent_id) && is_null($parent_id_old) ) {
                $parent_id = 999;
                $parent_id_old = 999;
            }

            $data_tree = array();
            
            if ( empty($parent_id) || $parent_id == 0 ) {
                // is root node

                $parent_old = $this->fetchRow(array('id=%i',$parent_id_old))->fetch();
                if ( !$parent_old ) {
                    // error - new parent is not exist
                    dibi::rollback();
                    return false;
                }

                $data_tree['sekvence'] = $id;
                $data_tree['sekvence_string'] = $sekvence_string .'.'. $id;
                $data_tree['uroven'] = 0;
                $this->update($data_tree, array( array('id=%i',$id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'". $parent_old->sekvence .'.'. $id ."','". $id ."')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $parent_old->sekvence_string ."#". $info_nazev_sekvence .".". $id ."','". $sekvence_string .".". $id ."')";
                $rozdil = $info->uroven - $data_tree['uroven'];
                $data_node['uroven%sql'] = "uroven - (".$rozdil.")";//. $parent_new->uroven + 1;

                $this->update($data_node, array( array("sekvence LIKE %s", $parent_old->sekvence .'.'. $id .".%" ) ));
                
            } else if ( $parent_id != $parent_id_old && empty($parent_id_old) ) {
                // change parent from root
                $parent_new = $this->fetchRow(array('id=%i',$parent_id))->fetch();
                if ( !$parent_new ) {
                    // error - new parent is not exist
                    dibi::rollback();
                    return false;
                }

                $data_tree['sekvence'] = $parent_new->sekvence .'.'. $id;
                $data_tree['sekvence_string'] = $parent_new->sekvence_string .'#'. $sekvence_string .'.'. $id;
                $data_tree['uroven'] = $parent_new->uroven + 1;
                $this->update($data_tree, array( array('id=%i',$id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'". $id ."','". $parent_new->sekvence .'.'. $id ."')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $info_nazev_sekvence .".". $id ."','". $parent_new->sekvence_string ."#". $sekvence_string .".". $id ."')";
                $rozdil = $info->uroven - $data_tree['uroven'];
                $data_node['uroven%sql'] = "uroven - (".$rozdil.")";//. $parent_new->uroven + 1;
                $this->update($data_node, array( array("sekvence LIKE %s", $id .".%") ));
                
            } else if ( $parent_id != $parent_id_old ) {
                // change parent
                $parent_old = $this->fetchRow(array('id=%i',$parent_id_old))->fetch();
                $parent_new = $this->fetchRow(array('id=%i',$parent_id))->fetch();
                if ( !$parent_new ) {
                    // error - new parent is not exist
                    dibi::rollback();
                    return false;
                }

                $data_tree['sekvence'] = $parent_new->sekvence .'.'. $id;
                $data_tree['sekvence_string'] = $parent_new->sekvence_string .'#'. $sekvence_string .'.'. $id;
                $data_tree['uroven'] = $parent_new->uroven + 1;
                $this->update($data_tree, array( array('id=%i',$id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'". $parent_old->sekvence .'.'. $id ."','". $parent_new->sekvence .'.'. $id ."')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $parent_old->sekvence_string ."#". $info_nazev_sekvence .".". $id ."','". $parent_new->sekvence_string ."#". $sekvence_string .".". $id ."')";
                $rozdil = $info->uroven - $data_tree['uroven'];
                $data_node['uroven%sql'] = "uroven - (".$rozdil.")";//. $parent_new->uroven + 1;
                $this->update($data_node, array( array("sekvence LIKE %s", $parent_old->sekvence .'.'. $id .".%") ));
                
            } else {
                // nochange parent
            
                $data_node = array();
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $info_nazev_sekvence .".". $info->id ."','". $sekvence_string .".". $info->id ."')";
                $this->update($data_node, array( array("sekvence_string LIKE %s","%". $info_nazev_sekvence .".". $info->id ."%") ));
            }

            dibi::commit();
            return $id;

        } catch (Exception $e) {
            dibi::rollback();
            return $e;
        }
    }

    public function odstranitH ( $id , $with_nodes = false )
    {

        if ( empty($id) ) return false;

        if ( $with_nodes ) {
            // delete node with subnodes
            $info = $this->getInfo($id);
            if ( $info ) {
                dibi::begin();
                try {
                    $res = $this->delete( array("sekvence LIKE %s", $info->sekvence .".%") );
                    $this->delete( array("id=%i", $id) );
                    dibi::commit();
                    return $res;
                } catch (Exception $e) {
                    dibi::rollback();
                    if ( $e->getCode() == 1451 ) {
                        return -1;
                    } else {
                        return false;
                    }
                }
                
            } else {
                return null;
            }
        } else {
            // delete node and move subnodes at new parent
            $info = $this->getInfo($id);
            if ( $info ) {
                dibi::begin();
                try {
                    $data_node = array();
                    if ( empty($info->parent_id) ) {
                        // parent is root
                        $data_node['sekvence%sql'] = "REPLACE(sekvence,'". $info->sekvence .".','')";
                        $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $info->sekvence_string ."#','')";
                        $data_node['uroven%sql'] = "uroven - 1";//. $parent_new->uroven + 1;
                    } else {
                        $parent_info = $this->getInfo($info->parent_id);
                        // change child nodes
                        $data_node['sekvence%sql'] = "REPLACE(sekvence,'". $info->sekvence ."','". $parent_info->sekvence ."')";
                        $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'". $info->sekvence_string ."','". $parent_info->sekvence_string ."')";
                        //$rozdil = $info->uroven - $parent_info->uroven;
                        //$data_node['uroven%sql'] = "uroven - (".$rozdil.")";//. $parent_new->uroven + 1;
                        $data_node['uroven%sql'] = "uroven - 1";//. $parent_new->uroven + 1;
                    }
                    //Debug::dump($data_node); exit;

                    $res = $this->update($data_node, array( array("sekvence LIKE %s", $info->sekvence .".%") ));
                    $this->delete( array("id=%i", $id) );
                    dibi::commit();
                    return $res;
                } catch (Exception $e) {
                    dibi::rollback();
                    if ( $e->getCode() == 1451 ) {
                        return -1;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    }

    public function rand( $pocet )
    {

        $this->deleteAll();

        function make_seed()
        {
            list($usec, $sec) = explode(' ', microtime());
            return (float) $sec + ((float) $usec * 100000);
        }
        mt_srand(make_seed());

        /*for ( $i = 0; $i <= $pocet; $i++ ) {

            $data = array(
                'nazev' =>  $this->make_string(15),
                'parent_id' => mt_rand(0, $i)
            );
            $this->vlozit($data);
        }*/

        for ( $i = 0; $i < 26; $i++ ) {

            $data = array(
                'nazev' => chr($i+65),
                'parent_id' => mt_rand(0, $i)
            );
            $this->vlozit($data);
        }


    }

    private function make_string($pass_len = 8)
    {
        $salt = 'abcdefghijklmnopqrstuvwxyz';
        $salt = strtoupper($salt);
        $salt_len = strlen($salt);
        /*function make_seed()
        {
            list($usec, $sec) = explode(' ', microtime());
            return (float) $sec + ((float) $usec * 100000);
        }*/
        mt_srand(make_seed());
        $pass = '';
        for ($i=0; $i<$pass_len; $i++) {
            $pass .= substr($salt, mt_rand() % $salt_len, 1);
        }
        return $pass;
    }

}


