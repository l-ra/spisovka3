<?php

class SouvisejiciDokument extends BaseModel
{

    protected $name = 'souvisejici_dokument';
    protected $tb_dokument = 'dokument';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;

    }

    public function souvisejici($dokument_id) {

        $param = array();
        $param['where'] = array();
        $param['where'][] = array('dokument_id=%i',$dokument_id);

        $dokumenty = array();
        $result = $this->fetchAllComplet($param)->fetchAll();
        if ( count($result)>0 ) {
            $Dokument = new Dokument();
            foreach ($result as $joinDok) {
                $dok = $Dokument->getBasicInfo($joinDok->spojit_s);
                $dokumenty[ $joinDok->dokument_id ] = $dok;
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
        $row['dokument_id'] = $dokument_id;
        $row['spojit_s'] = $spojit_s;
        $row['type'] = 1;
        $row['date_added'] = new DateTime();
        $row['user_added'] = Environment::getUser()->getIdentity()->user_id;

        return $this->insert_basic($row);

    }

    public function odebrat($param) {
        return $this->delete($param);
    }

}
