<?php

class DokumentSpis
{

    /**
     * Vrať kromě dokumentů i informace o jejich subjektech a přílohách.
     * Použito pro zobrazení detailu spisu.
     * @param int $spis_id
     * @return array|null
     */
    public static function dokumentyVeSpisu($spis_id)
    {
        $result = dibi::query('SELECT [id] FROM %n WHERE [spis_id] = %i ORDER BY [id]',
                        Document::TBL_NAME, $spis_id);
        $result = $result->fetchPairs();
        if (!$result)
            return null;

        $Dokument = new Dokument();
        $DokSubjekty = new DokumentSubjekt();
        $subjekty = $DokSubjekty->subjekty3($result);
        $pocty_souboru = DokumentPrilohy::pocet_priloh($result);

        $dokumenty = array();
        foreach ($result as $doc_id) {
            $dok = $Dokument->getInfo($doc_id);
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
