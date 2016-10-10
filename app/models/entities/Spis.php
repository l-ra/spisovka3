<?php

class Spis extends DBEntity
{

    const TBL_NAME = 'spis';

    public function canUserDelete()
    {
        if ($this->typ != 'F')
            return false;

        $count = dibi::query("SELECT COUNT(*) FROM %n WHERE [parent_id] = %i", self::TBL_NAME,
                        $this->id)->fetchSingle();
        return $count === 0;
    }

    public function getDocuments()
    {
        $docs = [];
        $result = dibi::query("SELECT [dokument_id] FROM [:PREFIX:dokument_to_spis] WHERE [spis_id] = $this->id");
        $result = $result->fetchPairs();
        foreach ($result as $id)
            $docs[] = new DocumentWorkflow($id);

        return $docs;
    }

    public function returnFromSpisovna()
    {
        if ($this->stav != 2)
            return false;

        dibi::begin();
        try {
            $docs = $this->getDocuments();
            foreach ($docs as $doc) {
                $ok = $doc->returnFromSpisovna(false); // don't nest transactions
                if (!$ok) {
                    dibi::rollback();
                    return false;
                }
            }

            $spis_model = new SpisModel();
            $spis_model->zmenitStav($this->id, SpisModel::UZAVREN);

            dibi::commit();
            return true;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

}
