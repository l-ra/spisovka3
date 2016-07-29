<?php

/**
 *
 * @author Pavel Laštovička
 */
class Document extends DBEntity
{

    const TBL_NAME = 'dokument';

    /**
     * Je uzivatel vlastnikem dokumentu nebo je opravnen jej menit.
     * @return boolean 
     */
    public function canUserModify()
    {
        $user = self::getUser();
        $ou = $user->getOrgUnit();

        switch ($this->stav) {
            case DocumentWorkflow::STAV_VE_SPISOVNE:
                return $user->isAllowed('Spisovna', 'zmenit_skartacni_rezim') || $user->isAllowed('Zapujcka',
                                'schvalit');

            case DocumentWorkflow::STAV_PREDAN_DO_SPISOVNY:
            case DocumentWorkflow::STAV_SKARTACNI_RIZENI:
            case DocumentWorkflow::STAV_ARCHIVOVAN:
            case DocumentWorkflow::STAV_SKARTOVAN:
                return false;
        }

        if ($ou && $user->isAllowed('Dokument', 'menit_moje_oj') && $ou->id === $this->owner_orgunit_id)
            return true;

        return $this->owner_user_id == $user->id;
    }

    /**
     * @return boolean
     */
    public function canUserForward()
    {
        $allow_forward_finished_documents = Settings::get('spisovka_allow_forward_finished_documents',
                        false);
        return !$this->is_forwarded && ($this->stav <= DocumentWorkflow::STAV_VYRIZUJE_SE || $allow_forward_finished_documents);
    }

    /**
     * @return boolean
     */
    public function canUserTakeOver()
    {
        $user = self::getUser();
        $user_id = $user->id;
        $ou = $user->getOrgUnit();

        if (!$this->is_forwarded)
            return false;

        if ($ou && $user->isAllowed('Dokument', 'menit_moje_oj') && $ou->id === $this->forward_orgunit_id)
            return true;

        return $this->forward_user_id == $user_id;
    }

    /**
     * @return Spis | false
     */
    public function getSpis()
    {
        $spis_id = dibi::query("SELECT [spis_id] FROM [:PREFIX:dokument_to_spis] WHERE [dokument_id] = $this->id")->fetchSingle();
        return $spis_id !== false ? new Spis($spis_id) : null;
    }

    public function getSubjekty()
    {
        $model = new DokumentSubjekt();
        return $model->subjekty($this->id);
    }

    /**
     * Kontrola, zda jsou vyplněny všechny potřebné údaje pro vyřízení / uzavření dokumentu
     * @return array|null
     */
    public function checkComplete()
    {
        $mess = array();
        if (empty($this->nazev))
            $mess[] = "Věc dokumentu nemůže být prázdné!";
        if (empty($this->cislo_jednaci))
            $mess[] = "Číslo jednací dokumentu nemůže být prázdné!";
        if (empty($this->datum_vzniku) || $this->datum_vzniku == "0000-00-00 00:00:00")
            $mess[] = "Datum přijetí/vytvoření nemůže být prázdné!";

        if (true) {
            if (empty($this->zpusob_vyrizeni_id) || $this->zpusob_vyrizeni_id == 0)
                $mess[] = "Není zvolen způsob vyřízení dokumentu!";
            if (empty($this->spisovy_znak_id))
                $mess[] = "Není zvolen spisový znak!";
            if (empty($this->skartacni_znak))
                $mess[] = "Není vyplněn skartační znak!";
            if ($this->skartacni_lhuta === null)
                $mess[] = "Není vyplněna skartační lhůta!";
            if (empty($this->spousteci_udalost_id))
                $mess[] = "Není zvolena spouštěcí událost!";

            if (count($this->subjekty) == 0)
                $mess[] = "Dokument musí obsahovat aspoň jeden subjekt!";
        }

        return $mess ? : null;
    }

    public function forward($user_id, $orgjednotka_id, $note = null)
    {
        dibi::begin();
        try {
            if (!$this->canUserForward())
                throw new Exception('Dokument není možné předat.');

            $Log = new LogModel();

            if ($user_id) {
                $person = Person::fromUserId($user_id);
                $log = "Dokument předán zaměstnanci $person.";
                $log_spis = "Spis předán zaměstnanci $person.";
                if ($orgjednotka_id === null) {
                    $account = new UserAccount($user_id);
                    $ou = $account->getOrgUnit();
                    if ($ou)
                        $orgjednotka_id = $ou->id;
                }
            } else if ($orgjednotka_id !== null) {
                $ou = new OrgUnit($orgjednotka_id);
                $log = "Dokument předán organizační jednotce $ou.";
                $log_spis = "Spis předán organizační jednotce $ou.";
            } else
                throw new InvalidArgumentException(__METHOD__ . "() - neplatné parametry");

            $spis = $this->getSpis();
            $docs = $spis ? $spis->getDocuments() : [$this];
            foreach ($docs as $doc) {
                $doc->is_forwarded = true;
                $doc->forward_user_id = $user_id;
                $doc->forward_orgunit_id = $orgjednotka_id;
                $doc->forward_note = $note;
                $doc->save();

                $Log->logDokument($doc->id, LogModel::DOK_PREDAN, $spis ? $log_spis : $log);
            }
            if ($spis) {
                $Spis = new SpisModel();
                $Spis->predatOrg($spis->id, $orgjednotka_id);
                $Log->logSpis($spis->id, LogModel::SPIS_PREDAN, $log_spis);
            }

            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            $message = $e->getMessage();
            $message .= "\nPředání dokumentu se nepodařilo.";
            if ($this->getSpis())
                $message .= ' Dokument je ve spisu, možná nejste oprávněn měnit některé dokumenty ve spisu.';

            throw new Exception($message, null, $e);
        }

        // posli upozorneni emailem
        try {
            if ($user_id) {
                Notifications::notifyUser($user_id, Notifications::RECEIVE_DOCUMENT,
                        ['document_name' => $this->nazev,
                    'reference_number' => $this->cislo_jednaci]);
            }
        } catch (Exception $e) {
            throw new Exception("Předání proběhlo v pořádku, ale nepodařilo se upozornit příjemce e-mailem: \n"
            . $e->getMessage(), 0, $e);
        }
    }

    public function takeOver()
    {
        if (!$this->canUserTakeOver())
            return false;

        $user = self::getUser();
        $user_orgunit = $user->getOrgUnit();
        if ($user_orgunit)
            $user_orgunit = $user_orgunit->id;

        $log_plus = "";
        if (!$this->forward_user_id)
            $log_plus = " určený organizační jednotce " . new OrgUnit($this->forward_orgunit_id);

        $Log = new LogModel();

        dibi::begin();
        try {
            $spis = $this->getSpis();
            $docs = $spis ? $spis->getDocuments() : [$this];
            $what = $spis ? 'spis' : 'dokument';
            foreach ($docs as $doc) {
                $doc->is_forwarded = false;
                $doc->owner_user_id = $user->id;
                $doc->owner_orgunit_id = $user_orgunit;
                $doc->save();

                $Log->logDokument($this->id, LogModel::DOK_PRIJAT,
                        "Zaměstnanec $user->displayName přijal $what $log_plus");
            }
            if ($spis) {
                $Spis = new SpisModel();
                $Spis->zmenitOrg($spis->id, $user_orgunit);
                $Log->logSpis($spis->id, LogModel::SPIS_PRIJAT,
                        'Zaměstnanec ' . $user->displayName . ' přijal spis' . $log_plus);
            }

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function cancelForwarding()
    {
        if (!$this->is_forwarded || !$this->canUserModify())
            return false;

        dibi::begin();
        try {
            $spis = $this->getSpis();
            $docs = $spis ? $spis->getDocuments() : [$this];
            $what = $spis ? 'spisu' : 'dokumentu';
            foreach ($docs as $doc) {
                $doc->is_forwarded = false;
                $doc->save();

                $Log = new LogModel();
                $Log->logDokument($this->id, LogModel::DOK_PREDANI_ZRUSENO,
                        "Předání $what bylo zrušeno.");
            }
            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function reject()
    {
        if (!$this->canUserTakeOver())
            return false;

        dibi::begin();
        try {
            $spis = $this->getSpis();
            $docs = $spis ? $spis->getDocuments() : [$this];
            $what = $spis ? 'spis' : 'dokument';
            foreach ($docs as $doc) {
                $doc->is_forwarded = false;
                $doc->save();

                $Log = new LogModel();
                $Log->logDokument($this->id, LogModel::DOK_PREVZETI_ODMITNUTO,
                        "Uživatel odmítl převzít $what.");
            }
            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /** Vrátí základní informaci, co uživatel může s dokumentem provádět.
     * @return array
     */
    public function getUserPermissions()
    {
        $user = self::getUser();

        $change_own_unit = $user->isAllowed('Dokument', 'menit_moje_oj');
        $view_own_unit = $user->isAllowed('Dokument', 'cist_moje_oj');
        $view_all = $user->isAllowed('Dokument', 'cist_vse');

        // Uzivatel muze byt vedoucim jenom jednoho utvaru
        $org_unit = $user->getOrgUnit();
        $permitted_org_units = [];
        if ($org_unit)
            $permitted_org_units = $user->isVedouci() ? OrgJednotka::childOrg($org_unit->id) : [$org_unit->id];

        $cancel_forwarding = false;
        $perm_take_over = false;
        $perm_edit = $this->owner_user_id == $user->id || $change_own_unit && in_array($this->owner_orgunit_id,
                        $permitted_org_units);
        $perm_view = $perm_edit || $view_all || $view_own_unit && in_array($this->owner_orgunit_id,
                        $permitted_org_units);
        if ($this->is_forwarded) {
            $perm_take_over = $this->forward_user_id == $user->id || $change_own_unit && in_array($this->forward_orgunit_id,
                            $permitted_org_units);
            $cancel_forwarding = $perm_edit;
            $perm_view = $perm_view || $perm_take_over || $view_own_unit && in_array($this->forward_orgunit_id,
                            $permitted_org_units);
            $perm_edit = false; // Pokud je dokument ve stavu predani, zakaz praci s nim
        }

        if ($this->stav >= DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA)
            $perm_edit = false;

        return [
            'view' => $perm_view,
            'edit' => $perm_edit,
            'take_over' => $perm_take_over,
            'cancel_forwarding' => $cancel_forwarding,
        ];
    }

    /**
     * Existuje odpoved (dokument) na tento dokument?
     * @return boolean
     */
    public function doesReplyExist()
    {
        $count = dibi::query("SELECT COUNT(*) FROM %n WHERE [id] != $this->id AND [cislo_jednaci] = %s",
                        self::TBL_NAME, $this->cislo_jednaci)->fetchSingle();
        return $count != 0;
    }

}
