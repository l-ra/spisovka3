<?php

class Spis extends DBEntity
{

    const TBL_NAME = 'spis';
    const OTEVREN = 1;
    const UZAVREN = 0;
    const PREDAN_DO_SPISOVNY = 2;
    const VE_SPISOVNE = 3;

    /**
     * @return boolean
     */
    public function canUserDelete()
    {
        if ($this->typ != 'F')
            return false;

        $count = dibi::query("SELECT COUNT(*) FROM %n WHERE [parent_id] = %i", self::TBL_NAME,
                        $this->id)->fetchSingle();
        return $count === 0;
    }

    /**
     * @return \DocumentWorkflow[]
     */
    public function getDocuments()
    {
        return DocumentWorkflow::getDocumentsFromSpis($this);
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        $count = dibi::query("SELECT COUNT(*) FROM %n WHERE [spis_id] = $this->id",
                        Document::TBL_NAME)
                ->fetchSingle();
        return $count === 0;
    }

    public function cancelForwarding()
    {
        $this->orgjednotka_id_predano = null;
        $this->save();
    }

    /**
     * @return array
     */
    public function getUserPermissions()
    {
        /* @var $user Spisovka\User */
        $user = self::getUser();
        $oj_uzivatele = $user->getOrgUnit();

        $Lze_cist = $Lze_menit = $Lze_prevzit = false;

        if (!$oj_uzivatele)
            $org_jednotky = array();
        else if ($user->isVedouci())
            $org_jednotky = OrgJednotka::childOrg($oj_uzivatele->id);
        else
            $org_jednotky = array($oj_uzivatele->id);

        $Prazdny = $this->isEmpty();

        // prideleno
        if (in_array($this->orgjednotka_id, $org_jednotky)) {
            $Lze_menit = true;
            $Lze_cist = true;
        }
        // predano
        if ($oj_uzivatele && $this->orgjednotka_id_predano == $oj_uzivatele->id) {
            $Lze_prevzit = !$Prazdny;
            $Lze_cist = true;
        }

        // Oprava ticket #194
        // Mohou nastat situace, kdy spis nema zadneho vlastnika, napr. po migraci ze spisovky 2
        // V tom pripade musi byt videt seznam dokumentu ve spisu
        if (!$this->orgjednotka_id && !$this->orgjednotka_id_predano)
            $Lze_cist = 1;

        if ($user->isAllowed('Dokument', 'cist_vse'))
            $Lze_cist = 1;

        $Opravnen_menit = $Lze_menit;
        if ($this->stav !== self::OTEVREN)
            $Lze_menit = false;

        $Je_predan = !empty($this->orgjednotka_id_predano);
        $Lze_predat = $Lze_menit && !$Prazdny && !$Je_predan;
        $Lze_zrusit_predani = $Lze_menit && !$Prazdny && $Je_predan;
        $Lze_uzavrit = $Lze_menit && !$Je_predan && $this->stav == self::OTEVREN;
        $Lze_otevrit = $Opravnen_menit && !$Je_predan && $this->stav == self::UZAVREN;
        $Lze_predat_do_spisovny = $Opravnen_menit && $this->stav == self::UZAVREN && $this->checkMandatoryData();

        return [
            'lze_cist' => $Lze_cist,
            'lze_menit' => $Lze_menit,
            'lze_predat' => $Lze_predat,
            'lze_prevzit' => $Lze_prevzit,
            'lze_zrusit_predani' => $Lze_zrusit_predani,
            'lze_uzavrit' => $Lze_uzavrit,
            'lze_otevrit' => $Lze_otevrit,
            'lze_predat_do_spisovny' => $Lze_predat_do_spisovny,
        ];
    }

    public function forward(OrgUnit $ou)
    {
        $this->orgjednotka_id_predano = $ou->id;
        $this->save();
    }

    public function takeOver()
    {
        $user = self::getUser();
        $user_orgunit = $user->getOrgUnit();
        if ($user_orgunit)
            $user_orgunit = $user_orgunit->id;
        $this->orgjednotka_id = $user_orgunit;
        $this->orgjednotka_id_predano = null;
        $this->save();
    }

    /**
     * Kontrola vyplnění povinných údajů.
     * @param array $result 
     * @return boolean
     */
    protected function checkMandatoryData(&$result = null)
    {
        $errors = array();
        if (empty($this->nazev))
            $errors[] = "Název spisu nemůže být prázdný.";
        if (empty($this->spisovy_znak_id))
            $errors[] = "Spisový znak nemůže být prázdný.";
        if (empty($this->skartacni_znak))
            $errors[] = "Skartační znak nemůže být prázdný.";
        if ($this->skartacni_lhuta === null)
            $errors[] = "Skartační lhůta musí být zadána.";

        if ($result)
            $result = $errors;
        return !$errors;
    }

    /**
     * @throws Exception
     */
    public function close()
    {
        $perm = $this->getUserPermissions();
        if (!$perm['lze_uzavrit'])
            throw new Exception('Přístup odepřen.');

        if (!$this->checkMandatoryData())
            throw new Exception('Spis nelze uzavřít, nemá vyplněny všechny povinné údaje.');

        $documents = $this->getDocuments();
        if ($documents)
            foreach ($documents as $dok)
                if ($dok->stav < DocumentStates::STAV_VYRIZEN_NESPUSTENA)
                    throw new Exception("Spis nelze uzavřít. Dokument \"$dok->cislo_jednaci\" není vyřízen.");

        $this->stav = self::UZAVREN;
        $this->save();
    }

    /**
     * @throws Exception
     */
    public function reopen()
    {
        $perm = $this->getUserPermissions();
        if (!$perm['lze_otevrit'])
            throw new Exception('Přístup odepřen.');

        $this->stav = self::OTEVREN;
        $this->save();
    }

    /**
     * @throws Nette\InvalidStateException
     * @throws Exception
     */
    public function transferToSpisovna()
    {
        if ($this->stav !== self::UZAVREN)
            throw new Nette\InvalidStateException("Spis \"$this->nazev\" není uzavřen.");

        $errors = [];
        if (!$this->checkMandatoryData($errors))
            throw new Exception(implode(' ', $errors));

        $dokumenty = $this->getDocuments();
        dibi::begin();
        try {
            foreach ($dokumenty as $dok) {
                $stav = $dok->transferToSpisovna(true);
                if ($stav !== true)
                    throw new Exception($stav);
            }

            $this->stav = self::PREDAN_DO_SPISOVNY;
            $this->save();

            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function receiveIntoSpisovna()
    {
        if ($this->stav !== self::PREDAN_DO_SPISOVNY)
            throw new Nette\InvalidStateException("Spis \"$this->nazev\" není předán do spisovny.");

        $errors = [];
        if (!$this->checkMandatoryData($errors))
            throw new Exception(implode(' ', $errors));

        dibi::begin();
        try {
            $documents = $this->getDocuments();
            if ($documents) {
                foreach ($documents as $doc) {
                    $res = $doc->receiveIntoSpisovna(false);
                    if (is_string($res))
                        throw new Exception($res);
                }
            }

            $this->stav = self::VE_SPISOVNE;
            $this->save();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * @throws Nette\InvalidStateException
     * @throws Exception
     */
    public function returnFromSpisovna()
    {
        if ($this->stav != self::PREDAN_DO_SPISOVNY)
            throw new Nette\InvalidStateException('Neplatný stav spisu.');

        dibi::begin();
        try {
            $docs = $this->getDocuments();
            if ($docs) {
                foreach ($docs as $doc)
                    $doc->returnFromSpisovna(false); // don't nest transactions
            }

            $this->stav = self::UZAVREN;
            $this->save();

            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

}
