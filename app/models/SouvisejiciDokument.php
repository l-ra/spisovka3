<?php

class SouvisejiciDokument extends BaseModel
{

    protected $name = 'souvisejici_dokument';

    public function souvisejici($dokument_id) {

        $param = array();
        $param['where_or'] = array();
        $param['where_or'][] = array('dokument_id=%i',$dokument_id);
        $param['where_or'][] = array('spojit_s_id=%i',$dokument_id);

        $dokumenty = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Dokument = new Dokument();
            foreach ($result as $joinDok) {
                if ( $joinDok->spojit_s_id == $dokument_id ) {
                    // zpetne spojeny s
                    $dok = $Dokument->getBasicInfo($joinDok->dokument_id);
                    $dok->spojeni = 'zpetne_spojen';
                    $dokumenty[ $joinDok->dokument_id ] = $dok;
                } else {
                    // spojen s
                    $dok = $Dokument->getBasicInfo($joinDok->spojit_s_id);
                    $dok->spojeni = 'spojen';
                    $dokumenty[ $joinDok->spojit_s_id ] = $dok;
                }
            }
            return $dokumenty;
        } else {
            return null;
        }

    }

    public function spojit($dokument_id, $spojit_s) {

        $odebrat = array(
                        array('dokument_id=%i',$dokument_id)
                   );
        $this->odebrat($odebrat);

        $row = array();
        $row['dokument_id'] = (int) $dokument_id;
        $row['spojit_s_id'] = (int) $spojit_s;
        $row['type'] = 1;
        $row['date_added'] = new DateTime();
        $row['user_id'] = Environment::getUser()->getIdentity()->id;

        return $this->insert_basic($row);

    }

    public function odebrat($param) {
        return $this->delete($param);
    }

}
