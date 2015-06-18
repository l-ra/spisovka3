<?php

class DokumentSubjekt extends BaseModel
{

    protected $name = 'dokument_to_subjekt';

    public function subjekty($dokument_id)
    {


        $sql = array(
            'distinct' => null,
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id', 'ds.typ' => 'rezim_subjektu'),
            'leftJoin' => array(
                'from' => array($this->tb_subjekt => 's'),
                'on' => array('s.id=ds.subjekt_id'),
                'cols' => array('*')
            ),
            'order_sql' => 'CONCAT(s.nazev_subjektu,s.prijmeni,s.jmeno)'
        );


        if (is_array($dokument_id)) {
            $sql['where'] = array(array('dokument_id IN %in', $dokument_id));
        } else {
            $sql['where'] = array(array('dokument_id=%i', $dokument_id));
        }

        $subjekty = array();
        $result = $this->selectComplex($sql)->fetchAll();
        if (count($result) > 0) {
            foreach ($result as $subjekt) {
                $subjekty[$subjekt->dokument_id][$subjekt->id] = $subjekt;
            }

            if (!is_array($dokument_id)) {
                return $subjekty[$dokument_id];
            } else {
                return $subjekty;
            }
        } else {
            return null;
        }
    }

    /**
     *  Vrátí pole subjektů, které jsou připojeny k určeným dokumentům
     */
    public static function subjekty2(array $dokument_ids)
    {

        return dibi::query("SELECT s.* FROM [:PREFIX:dokument_to_subjekt] ds INNER JOIN [:PREFIX:subjekt] s ON s.id = ds.subjekt_id WHERE dokument_id IN %in GROUP BY s.id",
                        $dokument_ids)->fetchAssoc('id');
    }

    public static function dejAsociaci(array $dokument_ids)
    {

        $dr = dibi::query("SELECT dokument_id, subjekt_id FROM [:PREFIX:dokument_to_subjekt] WHERE dokument_id IN %in ORDER BY date_added",
                        $dokument_ids);
        $a = array();
        foreach ($dr as $row)
            $a[$row->dokument_id][] = (int) $row->subjekt_id;

        return $a;
    }

    public function pripojit($dokument_id, $subjekt_id, $typ = 'AO')
    {

        $row = array();
        $row['dokument_id'] = (int) $dokument_id;
        $row['subjekt_id'] = (int) $subjekt_id;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_id'] = (int) Nette\Environment::getUser()->getIdentity()->id;

        return $this->insert($row);
    }

    public function zmenit($dokument_id, $subjekt_id, $typ = 'AO')
    {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['subjekt_id'] = $subjekt_id;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_id'] = Nette\Environment::getUser()->getIdentity()->id;

        return $this->update($row,
                        array(array('dokument_id=%i', $dokument_id), array('subjekt_id=%i', $subjekt_id)));
    }

    public function odebrat($param)
    {
        return $this->delete($param);
    }

    public function odebratVsechnySubjekty($dokument_id)
    {
        return $this->delete(array(array('dokument_id=%i', $dokument_id)));
    }

}
