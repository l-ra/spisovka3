<?php

/**
 * Spojení mezi dvěma dokumenty jsou obousměrná.
 */
class SouvisejiciDokument extends BaseModel
{

    protected $name = 'souvisejici_dokument';
    protected $autoIncrement = false;

    public function souvisejici($dokument_id)
    {
        $where = [['dokument_id = %i OR spojit_s_id = %i', $dokument_id, $dokument_id]];
        $result = $this->select($where)->fetchAll();
        if (!count($result))
            return [];

        $dokumenty = [];
        foreach ($result as $row) {
            $linked_doc_id = $row->spojit_s_id == $dokument_id ? $row->dokument_id : $row->spojit_s_id;

            $dok = new Document($linked_doc_id);
            if ($dok->stav == 0)
                continue;   // spojeni s rozepsanym dokumentem musime ignorovat - nastava pri odpovedi na dokument

            $dokumenty[$linked_doc_id] = $dok;
        }

        return $dokumenty;
    }

    public function spojit($dokument_id, $dokument2_id)
    {
        $dokument_id = (int) $dokument_id;
        $dokument2_id = (int) $dokument2_id;
        if ($dokument_id == $dokument2_id)
            throw new Exception('Nelze spojit dokument se sebou samým.');

        $row = array();
        // obvykle se spoje nový dokument se starším, proto jsem zvolil,
        // aby dokument_id bylo vždy větší než spojit_s_id
        $row['dokument_id'] = max($dokument_id, $dokument2_id);
        $row['spojit_s_id'] = min($dokument_id, $dokument2_id);
        $row['date_added'] = new DateTime(); // nepouzito
        $row['user_id'] = self::getUser()->id; // nepouzito

        try {
            $this->insert($row);
        } catch (Exception $e) {
            throw new Exception('Dokumenty nebylo možné spojit. Pravděpodobně už byly spojeny předtím.', 0, $e);
        }
    }

    public function odebrat($dokument1_id, $dokument2_id)
    {
        $doc1 = new Document($dokument1_id);
        $doc2 = new Document($dokument2_id);
        
        if ($doc1->cislo_jednaci && $doc1->cislo_jednaci == $doc2->cislo_jednaci) {
            // zabran rozpojení mezi odpovědí a původním dokumentem
            return;
        }
        
        // vykasli se na fakt, odkud kam spojeni vede a smaz v databazi oba pripady
        $param1 = [['spojit_s_id = %i', $dokument1_id], ['dokument_id = %i', $dokument2_id]];
        $param2 = [['dokument_id = %i', $dokument1_id], ['spojit_s_id = %i', $dokument2_id]];
        $this->delete($param1);
        $this->delete($param2);
    }

}
