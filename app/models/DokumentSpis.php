<?php

class DokumentSpis extends BaseModel
{

    protected $name = 'dokument_to_spis';
    protected $autoIncrement = false;

    /**
     * Vrať kromě dokumentů i informace o jejich subjektech a přílohách.
     * Použito pro zobrazení detailu spisu.
     * @param int $spis_id
     * @return array|null
     */
    public function dokumentyVeSpisu($spis_id)
    {
        $query = $this->select([["spis_id = %i", $spis_id]], ['dokument_id' => 'asc']);
        $result = $query->fetchAssoc('dokument_id');
        if (!$result)
            return null;

        $Dokument = new Dokument();
        $DokSubjekty = new DokumentSubjekt();
        $dokument_ids = array_keys($result);
        $subjekty = $DokSubjekty->subjekty3($dokument_ids);
        $pocty_souboru = DokumentPrilohy::pocet_priloh($dokument_ids);

        $dokumenty = array();
        foreach ($result as $ds) {
            $dok = $Dokument->getInfo($ds->dokument_id, '');
            if (empty($dok->stav))
                continue;
            $id = $dok->id;
            $dok->subjekty = isset($subjekty[$id]) ? $subjekty[$id] : null;
            $dok->pocet_souboru = isset($pocty_souboru[$id]) ? $pocty_souboru[$id] : 0;
            $dokumenty[] = $dok;
        }

        return $dokumenty;
    }

}
