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
            if ( $detail == 1 ) {
                $DokumentySubjekt = new DokumentSubjekt();
                $DokumentyPrilohy = new DokumentPrilohy();
                $Workflow = new Workflow();
                $Osoba = new UserModel();
            }

            foreach ($result as $joinDok) {

                $dok = $Dokument->getInfo($joinDok->dokument_id, $joinDok->dokument_version);
                $dok->poradi = $joinDok->poradi;
                $dok->stav_zarazeni = $joinDok->stav;

                if ( $detail == 1 ) {

                    $dok->typ_dokumentu = Dokument::typDokumentu($dok->typ_dokumentu);
                    $dok->subjekty = $DokumentySubjekt->subjekty($dok->dokument_id);
                    $dok->prilohy = $DokumentyPrilohy->prilohy($dok->dokument_id);
                    $dok->spisy = $this->spisy($dok->dokument_id);

                    $dok->workflow = $Workflow->dokument($dok->dokument_id);
                    $dok->prideleno = null;
                    $dok->predano = null;
                    $prideleno = $predano = $stav = 0;
                    if ( count($dok->workflow)>0 ) {
                        foreach ($dok->workflow as $wf) {

                            // Pridelen
                            if ( $wf->stav_osoby == 1 && $prideleno==0 ) {
                                $dok->prideleno = $wf;
                                $prideleno=1;
                            }
                            // Predan
                            if ( $wf->stav_osoby == 0 && $predano==0 ) {
                                $dok->predano = $wf;
                                $predano=1;
                            }
                            // Stav
                            if ( $stav <= $wf->stav_dokumentu ) {
                                $stav = $wf->stav_dokumentu;
                            }
                        }
                    }
                    $dok->stav_dokumentu = $stav;


                    if ( !empty($dok->lhuta) ) {
                        $datum_vzniku = strtotime($dok->date_created);
                        $dok->lhuta_do = $datum_vzniku + ($dok->lhuta * 86400);
                    } else {
                        $dok->lhuta_do = 'neurčeno';
                    }

                }


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

        $odebrat = array(
                        array('dokument_id=%i',$dokument_id)
                   );
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
