<?php

class DokumentOdeslani extends BaseModel
{

    protected $name = 'dokument_odeslani';
    protected $tb_dokument = 'dokument';
    protected $tb_file = 'file';
    protected $tb_user = 'user';
    protected $tb_osoba = 'osoba';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;
        $this->tb_file = $prefix . $this->tb_file;
        $this->tb_user = $prefix . $this->tb_user;
        $this->tb_osoba = $prefix . $this->tb_osoba;

    }

    public function odeslaneZpravy($dokument_id, $dokument_version = null) {


        $res = $this->fetchAll(array('datum_odeslani'),
                               array( array('dokument_id=%i',$dokument_id) ))
                        ->fetchAssoc('id');

        if ( count($res)>0 ) {
            $Subjekt = new Subjekt();
            foreach ($res as &$row) {
                $row->subjekt_info = $Subjekt->getInfo($row->subjekt_id, $row->subjekt_version);
            }
            return $res;
        } else {
            return null;
        }

    }

    public function ulozit( $row ) {

        if ( !is_array($row) ) {
            return null;
        }

        //$row = array();
        //$row['dokument_id'] = $dokument_id;
        //$row['dokument_version'] = $dokument_version;
        //$row['subjekt_id'] = $subjekt_id;
        //$row['subjekt_version'] = $subjekt_version;
        //$row['zpusob_odeslani'] = $typ;
        //row['epodatelna_id'] = $typ;
        //$row['datum_odeslani'] = $typ;
        $row['user_id'] = Environment::getUser()->getIdentity()->id;
        $row['user_info'] = serialize(Environment::getUser()->getIdentity()->identity);
        $row['date_created'] = new DateTime();


        return $this->insert($row);

    }

}
