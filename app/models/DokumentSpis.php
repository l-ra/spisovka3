<?php

class DokumentSpis extends BaseModel
{

    protected $name = 'dokument_to_spis';

    public function spisy( $dokument_id ) {

        $param = array();
        $param['where'] = array();
        $param['where'][] = array('dokument_id=%i',$dokument_id);

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

    public function spis( $dokument_id ) {

        $param = array();
        $param['where'] = array();
        $param['where'][] = array('dokument_id=%i',$dokument_id);

        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Spis = new Spis();
            foreach ($result as $joinSpis) {
                $spis = $Spis->getInfo($joinSpis->spis_id);
                $spis->poradi = $joinSpis->poradi;
                $spis->stav_zarazeni = $joinSpis->stav;
            }
            return $spis;
        } else {
            return null;
        }
    }    
    
    public function dokumenty( $spis_id , $detail = 0 ) {

        $param = array();
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i',$spis_id);

        $dokumenty = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Dokument = new Dokument();

            $dokument_ids = array();
            foreach ($result as $joinDok) {
                $dokument_ids[] = $joinDok->dokument_id;
            }

            $DokSubjekty = new DokumentSubjekt();
            $dataplus['subjekty'] = $DokSubjekty->subjekty($dokument_ids);
            $Dokrilohy = new DokumentPrilohy();
            $dataplus['prilohy'] = $Dokrilohy->prilohy($dokument_ids);
            $DokOdeslani = new DokumentOdeslani();
            $dataplus['odeslani'] = array( '0'=> null );//$DokOdeslani->odeslaneZpravy($dokument_ids);

            foreach ($result as $joinDok) {
                $dok = $Dokument->getInfo($joinDok->dokument_id, null, $dataplus);
                if ( empty($dok->stav_dokumentu) ) continue;
                $dok->poradi = empty($joinDok->poradi)?1:$joinDok->poradi;
                $dok->stav_zarazeni = $joinDok->stav;
                $dokumenty[ $joinDok->poradi ] = $dok;
            }
            return $dokumenty;
        } else {
            return null;
        }
    }

    public function skartacniRezim($spis_id)
    {
        
        $sql = array(
            'distinct'=>null,
            'from' => array($this->tb_spis => 'spis'),
            'cols' => array('nazev'=>'spis_nazev','spisovy_znak_id','skartacni_lhuta','skartacni_znak'),
            'leftJoin' => array(
                'dokspis' => array(
                    'from' => array($this->name => 'ds'),
                    'on' => array('spis.id=ds.spis_id'),
                    'cols' => array('dokument_id','spis_id'),                    
                ),                
                'dokumenty' => array(
                    'from' => array($this->tb_dokument => 'dok'),
                    'on' => array('dok.id=ds.dokument_id'),
                    'cols' => array('nazev'=>'dok_nazev','cislo_jednaci','jid','spisovy_znak_id'=>'dok_spisovy_znak','skartacni_lhuta'=>'dok_skartacni_lhuta','skartacni_znak'=>'dok_skartacni_znak')
                ),

            )
        );
        
        $sql['where'] = array( array('spis.id=%i',$spis_id) );
        
        $select = $this->fetchAllComplet($sql);
        $result = $select->fetchAll();
        if ( count($result)>0 ) {
        
            $sr = new stdClass();
            $sr->dokument_skartacni_lhuta = 0;
            $sr->dokument_skartacni_znak = 'V';
            $sr->spis_skartacni_lhuta = 0;
            $sr->spis_skartacni_znak = 'V';
            
            foreach ($result as $row) {
                
                if ( $row->dok_skartacni_lhuta > $sr->dokument_skartacni_lhuta ) {
                    $sr->dokument_skartacni_lhuta = $row->dok_skartacni_lhuta;
                }
                if ( $row->skartacni_lhuta > $sr->spis_skartacni_lhuta ) {
                    $sr->spis_skartacni_lhuta = $row->skartacni_lhuta;
                }
                $sr->spis_skartacni_znak = $row->skartacni_znak;
            }
            
            return $sr;
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

    public function pripojit($dokument_id, $spis_id, $stav = 1) {

        $Log = new LogModel();

        $odebrat = array(
                        array('dokument_id=%i',$dokument_id)
                   );

        $spisy = $this->dokumenty($dokument_id);
        if ( count($spisy)>0 ) {
            foreach( $spisy as $s ) {
                $Log->logDokument($dokument_id, LogModel::SPIS_DOK_ODEBRAN,'Dokument odebrán ze spisu "'. $s->nazev .'"');
            }
        }

        $this->odebrat($odebrat);

        $poradi = $this->pocetDokumentu($spis_id);

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['spis_id'] = $spis_id;
        $row['poradi'] = $poradi + 1;
        $row['stav'] = $stav;
        $row['date_added'] = new DateTime();
        $row['user_id'] = Environment::getUser()->getIdentity()->id;

        $Spis = new Spis();
        $spis_info = $Spis->getInfo($spis_id);

        $Log->logDokument($dokument_id, LogModel::SPIS_DOK_PRIPOJEN,'Dokument přidán do spisu "'. $spis_info->nazev .'"');

        return $this->insert($row);

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
