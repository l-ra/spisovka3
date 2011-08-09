<?php

class DokumentOdeslani extends BaseModel
{

    protected $name = 'dokument_odeslani';

    public function odeslaneZpravy($dokument_id) {

        $sql = array(
            'distinct'=>null,
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id','subjekt_id','datum_odeslani','zpusob_odeslani_id','user_id','zprava','cena','hmotnost','cislo_faxu','stav','druh_zasilky'),
            'leftJoin' => array(
                'subjekt' => array(
                    'from' => array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => array('*')
                 ),
                'zpusob_odeslani' => array(
                    'from' => array($this->tb_zpusob_odeslani => 'odes'),
                    'on' => array('odes.id=ds.zpusob_odeslani_id'),
                    'cols' => array('nazev'=>'zpusob_odeslani_nazev')
                 ),
            ),
            'order_by' => array('ds.datum_odeslani','s.nazev_subjektu','s.prijmeni','s.jmeno')
        );


        if ( is_array($dokument_id) ) {
            $sql['where'] = array( array('dokument_id IN (%in)', $dokument_id) );
        } else {
            $sql['where'] = array( array('dokument_id=%i',$dokument_id) );
        }
        
        $subjekty = array();
        $result = $this->fetchAllComplet($sql)->fetchAll();
        if ( count($result)>0 ) {
            foreach ($result as $subjekt_index => $subjekt) {
                $subjekty[ $subjekt->dokument_id ][ $subjekt_index ] = $subjekt;
                $subjekty[ $subjekt->dokument_id ][ $subjekt_index ]->druh_zasilky = unserialize($subjekty[ $subjekt->dokument_id ][ $subjekt_index ]->druh_zasilky);
            }

            if ( !is_array($dokument_id) ) {
                return $subjekty[ $dokument_id ];
            } else {
                return $subjekty;
            }
        } else {
            return null;
        }


        /*if ( is_array($dokument_id) ) {
            $res = $this->fetchAll(array('datum_odeslani'),
                                   array( array('dokument_id IN (%in)',$dokument_id) ))
                        ->fetchAssoc('id');
        } else {
            $res = $this->fetchAll(array('datum_odeslani'),
                                   array( array('dokument_id=%i',$dokument_id) ))
                        ->fetchAssoc('id');
        }

        if ( count($res)>0 ) {
            $Subjekt = new Subjekt();
            foreach ($res as &$row) {
                $row->subjekt_info = $Subjekt->getInfo($row->subjekt_id);
            }
            return $res;
        } else {
            return null;
        }*/

    }

    public function ulozit( $row ) {

        if ( !is_array($row) ) {
            return null;
        }

        //$row = array();
        //$row['dokument_id'] = $dokument_id;
        //$row['subjekt_id'] = $subjekt_id;
        //$row['zpusob_odeslani_id'] = $typ;
        //$row['epodatelna_id'] = $typ;
        //$row['datum_odeslani'] = $typ;
        if ( empty($row['zpusob_odeslani_id']) ) $row['zpusob_odeslani_id'] = null;
        if ( empty($row['epodatelna_id']) ) $row['epodatelna_id'] = null;
        $row['user_id'] = Environment::getUser()->getIdentity()->id;
        $row['date_created'] = new DateTime();


        return $this->insert($row);

    }

}
