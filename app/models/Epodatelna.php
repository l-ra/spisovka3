<?php

class Epodatelna extends BaseModel
{

    protected $name = 'epodatelna';
    protected $primary = 'id';
    protected $tb_file = 'file';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_file = $prefix . $this->tb_file;

    }


    /**
     * Seznam dokumentu s zivotnim cyklem
     * 
     * @param <type> $args 
     */
    public function seznam($args = array(), $detail = 0) {

        if ( isset($args['where']) ) {
            $where = $args['where'];
        } else {
            $where = null;
        }
        if ( isset($args['where_or']) ) {
            $where_or = $args['where_or'];
        } else {
            $where_or = null;
        }
        
        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            $order = array('id'=>'DESC');
        } 
        if ( isset($args['limit']) ) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }
        if ( isset($args['offset']) ) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }
        
        $sql = array(
        
            'distinct'=>1,
            'from' => array($this->name => 'ep'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_file => 'f'),
                    'on' => array('f.id=ep.source_id'),
                    'cols' => array('real_path')
                )
            )
        
        );

        //echo "<pre>";
        //print_r($sql);
        //echo "</pre>";

        $select = $this->fetchAllComplet($sql);
        //$result = $select->fetchAll();

        return $select;

    }

    public function existuje($id_zpravy, $typ = 'ie') {

        if ( $typ == 'isds' ) {
            $args = array(
                'where' => array(
                    array('isds_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'email' ) {
            $args = array(
                'where' => array(
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'vse' ) {
            $args = array(
                'where_or' => array(
                    array('isds_signature = %s',$id_zpravy),
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else {
            return 0;
        }

        $query = $this->fetchAllComplet($args);
        return $query->count();

    }

    public function getInfo($epodatelna_id, $detail = 0) {

        $args = array(
            'where' => array(
                array('id=%i',$epodatelna_id)
            )
        );


        $query = $this->fetchAllComplet($args);
        return $query->fetch();

    }

    public function getMax($smer = 0) {

        $result = $this->fetchAll(array('poradi'=>'DESC'),array('rok'=>date('Y'),array('epodatelna_typ=%i',$smer)),null,null,1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi+1) : 1;

    }

}
