<?php

class DokumentSpis extends BaseModel
{

    protected $name = 'dokument_to_spis';
    protected $autoIncrement = false;

    // Funkce je zde kvůli kompatibilitě se starým kódem, aby se nemusel předělávat celý program
    public function spisy($dokument_id)
    {

        $spis = $this->spis($dokument_id);
        return $spis ? array($spis->id => $spis) : null;
    }

    public function spis($dokument_id)
    {

        $param = array();
        $param['where'] = array(array('dokument_id=%i', $dokument_id));

        $result = $this->selectComplex($param)->fetch();
        if (!$result)
            return null;

        $Spis = new SpisModel();
        $spis = $Spis->getInfo($result->spis_id);
        $spis->poradi = $result->poradi;

        return $spis;
    }

    public function dokumenty($spis_id)
    {
        $param = array();
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i', $spis_id);

        $dokumenty = array();

        $query = $this->selectComplex($param);
        $result = $query->fetchAll();

        if (count($result) > 0) {
            $Dokument = new Dokument();

            $dokument_ids = array();
            foreach ($result as $joinDok) {
                $dokument_ids[] = $joinDok->dokument_id;
            }

            $DokSubjekty = new DokumentSubjekt();
            $subjekty = $DokSubjekty->subjekty3($dokument_ids);
            $pocty_souboru = DokumentPrilohy::pocet_priloh($dokument_ids);

            foreach ($result as $joinDok) {
                $dok = $Dokument->getInfo($joinDok->dokument_id, '');
                if (empty($dok->stav))
                    continue;
                $dok->poradi = empty($joinDok->poradi) ? 1 : $joinDok->poradi;
                $id = $dok->id;
                $dok->subjekty = isset($subjekty[$id]) ? $subjekty[$id] : null;
                $dok->pocet_souboru = isset($pocty_souboru[$id]) ? $pocty_souboru[$id] : 0;
                $dokumenty[$joinDok->dokument_id] = $dok;
            }
            sort($dokumenty);
            return $dokumenty;
        } else {
            return null;
        }
    }

    public function pocetDokumentu($spis_id)
    {

        $param = array();
        //$param['distinct'] = 1;
        //$param['cols'] = array('subjekt_id','typ');
        $param['where'] = array();
        $param['where'][] = array('spis_id=%i', $spis_id);

        $result = $this->selectComplex($param)->fetchAll();
        if (count($result) > 0) {
            return count($result);
        } else {
            return 0;
        }
    }

    public function pripojit($dokument_id, $spis_id)
    {

        $Log = new LogModel();

        $Spis = new SpisModel();
        $spis_info = $Spis->getInfo($spis_id);
        if ($spis_info->stav != 1)
            throw new Exception('Do spisu, který není otevřený, není možné přidávat dokumenty.');

        $odebrat = array(
            array('dokument_id=%i', $dokument_id)
        );
        try {
            dibi::begin();

            // je treba zjistit informace o puvodnim spisu, nez z nej dokument vyjmeme
            $puvodni_spis = $this->spis($dokument_id);
            $this->odebrat($odebrat);

            if ($puvodni_spis)
                $Log->logDokument($dokument_id, LogModel::SPIS_DOK_ODEBRAN,
                        'Dokument odebrán ze spisu "' . $puvodni_spis->nazev . '"');

            $poradi = $this->pocetDokumentu($spis_id);

            $row = array();
            $row['dokument_id'] = $dokument_id;
            $row['spis_id'] = $spis_id;
            $row['poradi'] = $poradi + 1;
            $row['date_added'] = new DateTime();
            $row['user_id'] = self::getUser()->id;

            $this->insert($row);

            $Log->logDokument($dokument_id, LogModel::SPIS_DOK_PRIPOJEN,
                    'Dokument přidán do spisu "' . $spis_info->nazev . '"');
            $Log->logSpis($spis_id, LogModel::SPIS_DOK_PRIPOJEN,
                    "Pripojen dokument " . $dokument_id);

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function odebrat($param)
    {
        return $this->delete($param);
    }

    public function odebratVsechnySpisy($dokument_id)
    {
        return $this->delete(array(array('dokument_id=%i', $dokument_id)));
    }

    public function odebratVsechnyDokumenty($spis_id)
    {
        return $this->delete(array(array('spis_id=%i', $spis_id)));
    }

}
