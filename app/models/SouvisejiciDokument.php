<?php

class SouvisejiciDokument extends BaseModel
{

    protected $name = 'souvisejici_dokument';

    public function souvisejici($dokument_id)
    {

        $param = array();
        $param['where_or'] = array();
        $param['where_or'][] = array('dokument_id=%i', $dokument_id);
        $param['where_or'][] = array('spojit_s_id=%i', $dokument_id);

        $dokumenty = array();
        $result = $this->selectComplex($param)->fetchAll();
        if (!count($result))
            return [];
        
        $Dokument = new Dokument();
        foreach ($result as $joinDok) {
            if ($joinDok->spojit_s_id == $dokument_id) {
                // zpetne spojeny s
                $dok = $Dokument->getBasicInfo($joinDok->dokument_id);
                // spojeni s rozepsanym dokumentem musime ignorovat
                if ($dok->stav == 0)
                    continue;
                $dok->spojeni = 'zpetne_spojen';
                $dokumenty[$joinDok->dokument_id] = $dok;
            } else {
                // spojen s
                $dok = $Dokument->getBasicInfo($joinDok->spojit_s_id);
                if ($dok->stav == 0)
                    continue;
                $dok->spojeni = 'spojen';
                $dokumenty[$joinDok->spojit_s_id] = $dok;
            }
        }

        return $dokumenty;
    }

    public function spojit($dokument_id, $spojit_s)
    {
        $row = array();
        $row['dokument_id'] = (int) $dokument_id;
        $row['spojit_s_id'] = (int) $spojit_s;
        $row['type'] = 1;
        $row['date_added'] = new DateTime();
        $row['user_id'] = Nette\Environment::getUser()->getIdentity()->id;

        return $this->insert_basic($row);
    }

    public function odebrat($dokument1_id, $dokument2_id)
    {
        // vykasli se na fakt, odkud kam spojeni vede a smaz v databazi oba pripady
        $param1 = [['spojit_s_id = %i', $dokument1_id], ['dokument_id = %i', $dokument2_id]];
        $param2 = [['dokument_id = %i', $dokument1_id], ['spojit_s_id = %i', $dokument2_id]];
        $this->delete($param1);
        $this->delete($param2);
    }

}
