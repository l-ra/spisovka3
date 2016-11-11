<?php

class DokumentSubjekt extends BaseModel
{

    protected $name = 'dokument_to_subjekt';
    protected $autoIncrement = false;

    /**
     * @param int $dokument_id
     * @return DibiRow[]
     */
    public function subjekty($dokument_id)
    {
        $sql = array(
            'from' => array($this->name => 'ds'),
            'cols' => array('ds.typ' => 'rezim_subjektu'),
            'leftJoin' => array(
                'from' => array($this->tb_subjekt => 's'),
                'on' => array('s.id = ds.subjekt_id'),
                'cols' => array('*')
            ),
            'order_sql' => 'CONCAT(s.nazev_subjektu,s.prijmeni,s.jmeno)',
            'where' => [['dokument_id = %i', $dokument_id]]
        );

        $result = $this->selectComplex($sql)->fetchAssoc('id');
        return $result;
    }

    /**
     * Oddelen kod pro ziskani subjektu jednoho a nekolika dokumentu
     * @param array $dokument_ids
     * @return array
     */
    public function subjekty3(array $dokument_ids)
    {
        $sql = array(
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id', 'ds.typ' => 'rezim_subjektu'),
            'leftJoin' => array(
                'from' => array($this->tb_subjekt => 's'),
                'on' => array('s.id=ds.subjekt_id'),
                'cols' => array('*')
            ),
            'order_sql' => 'CONCAT(s.nazev_subjektu,s.prijmeni,s.jmeno)',
            'where' => [['dokument_id IN %in', $dokument_ids]]
        );

        $a = array();
        $result = $this->selectComplex($sql)->fetchAll();
        if (!count($result))
            return null;

        foreach ($result as $row)
            $a[$row->dokument_id][$row->id] = $row;

        return $a;
    }

    /**
     *  Vrátí pole subjektů, které jsou připojeny k určeným dokumentům.
     *  Voláno ze sestavy.
     * @return DibiRow[]
     */
    public static function subjekty2(array $dokument_ids)
    {
        $ids = dibi::query("SELECT [subjekt_id] FROM [dokument_to_subjekt] WHERE [dokument_id] IN %in GROUP BY [subjekt_id]",
                        $dokument_ids)->fetchPairs();
        return dibi::query("SELECT * FROM [subjekt] WHERE [id] IN %in", $ids)->fetchAssoc('id');
    }

    /**
     * Voláno ze sestavy.
     * @param array $dokument_ids
     * @return array
     */
    public static function dejAsociaci(array $dokument_ids)
    {
        $dr = dibi::query("SELECT dokument_id, subjekt_id FROM [:PREFIX:dokument_to_subjekt] WHERE dokument_id IN %in ORDER BY date_added",
                        $dokument_ids);
        $a = array();
        foreach ($dr as $row)
            $a[$row->dokument_id][] = (int) $row->subjekt_id;

        return $a;
    }

    public function pripojit(Document $doc, Subject $subject, $typ = 'AO')
    {
        $row = array();
        $row['dokument_id'] = $doc->id;
        $row['subjekt_id'] = $subject->id;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_id'] = self::getUser()->id;

        $this->insert($row);

        $Log = new LogModel();
        $Log->logDokument($doc->id, LogModel::SUBJEKT_PRIDAN,
                'Přidán subjekt "' . $subject . '"');
    }

    public function zmenit($dokument_id, $subjekt_id, $typ = 'AO')
    {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['subjekt_id'] = $subjekt_id;
        $row['typ'] = $typ;
        $row['date_added'] = new DateTime();
        $row['user_id'] = self::getUser()->id;

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
