<?php

class DokumentSpis extends BaseModel
{

    protected $name = 'dokument_to_spis';
    protected $tb_dokument = 'dokument';
    protected $tb_spis = 'spis';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;
        $this->tb_spis = $prefix . $this->tb_spis;

    }

    public function spisy( $dokument_id, $dokument_version = null ) {

        $param = array();
        //$param['distinct'] = 1;
        //$param['cols'] = array('subjekt_id','typ');
        $param['where'] = array();
        $param['where'][] = array('dokument_id=%i',$dokument_id);
        if ( !is_null($dokument_version) ) {
            $param['where'][] = array('dokument_version=%i',$dokument_version);
        }


        $spisy = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Spis = new Spis();
            foreach ($result as $joinSpis) {
                $spis = $Spis->getInfo($joinSpis->spis_id);
                $spis->poradi = $joinSpis->poradi;
                $spis->stav_zarazeni = $joinSpis->stav;
                $spisy[ $joinSpis->spis_id ] = $spis;
            }
            return $spisy;
        } else {
            return null;
        }
    }

    public function dokumenty( $spis_id , $detail = 0 ) {

        $param = array();
        //$param['distinct'] = 1;
        //$param['cols'] = array('subjekt_id','typ');
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i',$spis_id);

        $dokumenty = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Dokument = new Dokument();
            foreach ($result as $joinDok) {
                $dok = $Dokument->getInfo($joinDok->dokument_id, $joinDok->dokument_version);
                $dok->poradi = $joinDok->poradi;
                $dok->stav_zarazeni = $joinDok->stav;
                $dokumenty[ $joinDok->poradi ] = $dok;
            }
            return $dokumenty;
        } else {
            return null;
        }
    }

    public function pocetDokumentu( $spis_id ) {

        $param = array();
        //$param['distinct'] = 1;
        //$param['cols'] = array('subjekt_id','typ');
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i',$spis_id);

        $dokumenty = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            return count($result);
        } else {
            return 0;
        }
    }

    public function pripojit($dokument_id, $spis_id, $stav = 1, $dokument_version = null) {

        $Log = new LogModel();

        $odebrat = array(
                        array('dokument_id=%i',$dokument_id)
                   );

        $spisy = $this->dokumenty($dokument_id,$dokument_version);
        if ( count($spisy)>0 ) {
            foreach( $spisy as $s ) {
                $Log->logDokument($dokument_id, LogModel::SPIS_DOK_ODEBRAN,'Dokument odebrán ze spisu "'. $s->nazev .'"');
            }
        }

        $this->odebrat($odebrat);

        $poradi = $this->pocetDokumentu($spis_id);

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['dokument_version'] = $dokument_version;
        $row['spis_id'] = $spis_id;
        $row['poradi'] = $poradi + 1;
        $row['stav'] = $stav;
        $row['date_added'] = new DateTime();
        $row['user_added'] = Environment::getUser()->getIdentity()->user_id;

        $Spis = new Spis();
        $spis_info = $Spis->getInfo($spis_id);

        $Log->logDokument($dokument_id, LogModel::SPIS_DOK_PRIPOJEN,'Dokument přidán do spisu "'. $spis_info->nazev .'"');

        return $this->insert_basic($row);

    }

    public function odebrat($param) {
        return $this->delete($param);
    }

    public function odebratVsechnySpisy($dokument_id) {
        return $this->delete(array(array('dokument_id=%i',$dokument_id)));
    }
    public function odebratVsechnyDokumenty($spis_id) {
        return $this->delete(array(array('spis_id=%i',$spis_id)));
    }


}
