<?php

class DokumentSpis extends BaseModel
{

    protected $name = 'dokument_to_spis';

    // Funkce je zde kvůli kompatibilitě se starým kódem, aby se nemusel předělávat celý program
    public function spisy( $dokument_id ) {

        $spis = $this->spis($dokument_id);
        return $spis ? array($spis->spis_id => $spis) : null;
    }

    public function spis( $dokument_id ) {

        $param = array();
        $param['where'] = array(array('dokument_id=%i',$dokument_id));

        $result = $this->fetchAllComplet($param)->fetch();
        if (!$result)
            return null;

        $Spis = new Spis();
        $spis = $Spis->getInfo($result->spis_id);
        $spis->poradi = $result->poradi;
                
        return $spis;
    }    
    
    public function dokumenty( $spis_id , $detail = 0, &$paginator = null ) {

        $param = array();
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i',$spis_id);

        $dokumenty = array();
        
        $query = $this->fetchAllComplet($param);
        if ( !is_null($paginator) ) {
            $paginator->itemCount = $query->count();
            $result = $query->fetchAll($paginator->offset, $paginator->itemsPerPage);
        } else {
            $result = $query->fetchAll();
        }
        
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
            //$DokOdeslani = new DokumentOdeslani();
            $dataplus['odeslani'] = array( '0'=> null );//$DokOdeslani->odeslaneZpravy($dokument_ids);

            foreach ($result as $joinDok) {
                $dok = $Dokument->getInfo($joinDok->dokument_id, null, $dataplus);
                if ( empty($dok->stav_dokumentu) ) continue;
                $dok->poradi = empty($joinDok->poradi)?1:$joinDok->poradi;
                $dokumenty[ $joinDok->dokument_id ] = $dok;
            }
            sort($dokumenty);
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

    public function pripojit($dokument_id, $spis_id) {

        $Log = new LogModel();

        $Spis = new Spis();
        $spis_info = $Spis->getInfo($spis_id);
        if ($spis_info->stav != 1)
            throw new Exception('Do spisu, který není otevřený, není možné přidávat dokumenty.');        
            
        $odebrat = array(
                        array('dokument_id=%i',$dokument_id)
                   );
        try {
            dibi::begin();
            
            // je treba zjistit informace o puvodnim spisu, nez z nej dokument vyjmeme
            $puvodni_spis = $this->spis($dokument_id);                  
            $this->odebrat($odebrat);
            
            if ($puvodni_spis)
                $Log->logDokument($dokument_id, LogModel::SPIS_DOK_ODEBRAN,'Dokument odebrán ze spisu "'. $puvodni_spis->nazev .'"');

            $poradi = $this->pocetDokumentu($spis_id);

            $row = array();
            $row['dokument_id'] = $dokument_id;
            $row['spis_id'] = $spis_id;
            $row['poradi'] = $poradi + 1;
            $row['date_added'] = new DateTime();
            $row['user_id'] = Environment::getUser()->getIdentity()->id;

            $this->insert($row);

            $Log->logDokument($dokument_id, LogModel::SPIS_DOK_PRIPOJEN,'Dokument přidán do spisu "'. $spis_info->nazev .'"');
            $Log->logSpis($spis_id, LogModel::SPIS_DOK_PRIPOJEN,"Pripojen dokument ". $dokument_id);
            
            dibi::commit();
        }
        catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
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
