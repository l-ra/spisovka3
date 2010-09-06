<?php

class DokumentSubjekt extends BaseModel
{

    protected $name = 'dokument_to_subjekt';
    protected $tb_dokument = 'dokument';
    protected $tb_subjekt = 'subjekt';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;
        $this->tb_subjekt = $prefix . $this->tb_subjekt;

    }

    public function subjekty( $dokument_id, $dokument_version = null ) {


        $sql = array(

            'distinct'=>null,
            'from' => array($this->name => 'ds'),
            'cols' => array('ds.typ'=>'rezim_subjektu'),
            'leftJoin' => array(
                 'from' => array($this->tb_subjekt => 's'),
                 'on' => array('s.id=ds.subjekt_id'),
                 'cols' => array('*')
            ),
            'order_by' => array('s.nazev_subjektu','s.prijmeni','s.jmeno'),
            'where' => array( array('dokument_id=%i',$dokument_id) )

        );

        $subjekty = array();
        $result = $this->fetchAllComplet($sql)->fetchAll();
        if ( count($result)>0 ) {
            foreach ($result as $subjekt) {
                $subjekty[ $subjekt->id ] = $subjekt;
            }
            return $subjekty;
        } else {
            return null;
        }
    }

    public function pripojit($dokument_id, $subjekt_id, $typ = 'AO', $dokument_version = null, $subjekt_version = null) {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['dokument_version'] = $dokument_version;
        $row['subjekt_id'] = $subjekt_id;
        $row['subjekt_version'] = $subjekt_version;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_added'] = Environment::getUser()->getIdentity()->user_id;

        return $this->insert_basic($row);

    }

    public function zmenit($dokument_id, $subjekt_id, $typ = 'AO', $dokument_version = null, $subjekt_version = null) {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['dokument_version'] = $dokument_version;
        $row['subjekt_id'] = $subjekt_id;
        $row['subjekt_version'] = $subjekt_version;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_added'] = Environment::getUser()->getIdentity()->user_id;

        return $this->update($row,array( array('dokument_id=%i',$dokument_id),array('subjekt_id=%i',$subjekt_id) ));

    }

    public function odebrat($param) {
        return $this->delete($param);
    }

    public function odebratVsechnySubjekty($dokument_id) {
        return $this->delete(array(array('dokument_id=%i',$dokument_id)));
    }


}
