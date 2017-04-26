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

        if ($user->isAllowed('Dokument', 'menit_vse'))
            return true;
        if ($ou && $user->isAllowed('Dokument', 'menit_moje_oj') && $ou->id === $this->owner_orgunit_id)
            return true;

        return $this->owner_user_id == $user->id;
    }

    /**
     * @return boolean
     */
    public function canUserForward()
    {
        switch ($this->stav) {
            case DocumentStates::STAV_NOVY:
            case DocumentStates::STAV_VYRIZUJE_SE:
                $state_condition = true;
                break;

            case DocumentStates::STAV_VYRIZEN_NESPUSTENA:
            case DocumentStates::STAV_VYRIZEN_SPUSTENA:
                $state_condition = Settings::get('spisovka_allow_forward_finished_documents',
                                false);
                break;

            default:
                $state_condition = false;
                break;
        }

        return !$this->is_forwarded && $state_condition && $this->canUserModify();
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
        $spis_id = $this->spis_id;
        return $spis_id !== null ? new Spis($spis_id) : null;
    }

    /**
     * @return DibiRow[]
     */
    public function getSubjects()
    {
        $model = new DokumentSubjekt();
        return $model->subjekty($this->id);
    }

    public function __get($name)
    {
        // pro zpětnou kompatibilitu
        if ($name != 'lhuta_stav')
            return parent::__get($name);

        if ($this->stav >= DocumentStates::STAV_VYRIZEN_NESPUSTENA || empty($this->lhuta))
            return 0;

        $creation_time = strtotime($this->datum_vzniku);
        $close_until = $creation_time + ($this->lhuta * 86400);
        $difference = $close_until - time();

        if ($difference < 0)
            return 2;
        if ($difference <= 432000)
            return 1;

        return 0;
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

            if (count($this->getSubjects()) == 0)
                $mess[] = "Dokument musí obsahovat aspoň jeden subjekt!";
        }

        return $mess ?: null;
    }

    public function forward($user_id, $orgunit_id, $note = null)
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
                if ($orgunit_id === null) {
                    $account = new UserAccount($user_id);
                    $ou = $account->getOrgUnit();
                    if ($ou)
                        $orgunit_id = $ou->id;
                }
            } else if ($orgunit_id !== null) {
                $ou = new OrgUnit($orgunit_id);
                $log = "Dokument předán organizační jednotce $ou.";
                $log_spis = "Spis předán organizační jednotce $ou.";
            } else
                throw new InvalidArgumentException(__METHOD__ . "() - neplatné parametry");

            $spis = $this->getSpis();
            if ($spis && $orgunit_id === null)
                throw new Exception('Uživatel není zařazen do organizační jednotky, není možné mu předávat spisy.');

            $log_text = $spis ? $log_spis : $log;
            if (!empty($note))
                $log_text .= "\nPoznámka k předání: $note";

            $docs = $spis ? $spis->getDocuments() : [$this];
            foreach ($docs as $doc) {
                $doc->is_forwarded = true;
                $doc->forward_user_id = $user_id;
                $doc->forward_orgunit_id = $orgunit_id;
                $doc->forward_note = $note;
                /**
                 * Je nutné ošetřit případ, kdy dokumenty ve spisu mají různé uživatele.
                 * Tento problém, který je důsledkem mizerného návrhu aplikace z minulosti 
                 * jiným způsobem nemůžeme opravit.
                 */
                if (count($docs) == 1)
                    $doc->save();
                else
                    $doc->_saveInternal(); // neprováděj kontrolu

                $Log->logDokument($doc->id, LogModel::DOK_PREDAN, $log_text);
            }
            if ($spis) {
                $spis->forward(new OrgUnit($orgunit_id));
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

        // posli upozorneni e-mailem
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
                /* @var $doc Document */
                if (!$doc->canUserTakeOver()) {
                    $this->_rollback();
                    return false;
                }
                $doc->_cancelForwardingInternal();
                $doc->owner_user_id = $user->id;
                $doc->owner_orgunit_id = $user_orgunit;
                $doc->_saveInternal();

                $Log->logDokument($doc->id, LogModel::DOK_PRIJAT,
                        "Zaměstnanec $user->displayName přijal $what $log_plus");
            }
            if ($spis)
                $spis->takeOver();

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * @return boolean
     * @throws Exception
     */
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
                $doc->_cancelForwardingInternal();
                $doc->save();

                $Log = new LogModel();
                $Log->logDokument($doc->id, LogModel::DOK_PREDANI_ZRUSENO,
                        "Předání $what bylo zrušeno.");
            }

            if ($spis)
                $spis->cancelForwarding();

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function reject()
    {
        if (!$this->canUserTakeOver())
            return false;

        dibi::begin();
        try {
            $spis = $this->getSpis();
            $docs = $spis ? $spis->getDocuments() : [$this];
            foreach ($docs as $doc) {
                $doc->_rejectInternal((boolean) $spis);
            }

            if ($spis)
                $spis->cancelForwarding();

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    protected function _rejectInternal($inside_spis)
    {
        $this->_cancelForwardingInternal();
        $this->_saveInternal();

        $Log = new LogModel();
        $what = $inside_spis ? 'spis' : 'dokument';
        $Log->logDokument($this->id, LogModel::DOK_PREVZETI_ODMITNUTO,
                "Uživatel odmítl převzít $what.");
    }

    /** Vrátí základní informaci, co uživatel může s dokumentem provádět.
     * @return array
     */
    public function getUserPermissions()
    {
        $user = self::getUser();

        if ($this->stav >= DocumentWorkflow::STAV_VE_SPISOVNE && $this->stav != DocumentWorkflow::STAV_ZAPUJCEN) {
            // V případě spisovny záleží pouze na oprávnění view, ne edit
            return [
                'view' => $user->isAllowed('Spisovna', 'cist_dokumenty'),
                'edit' => false,
                'take_over' => false,
                'cancel_forwarding' => false,
            ];
        }

        $change_own_unit = $user->isAllowed('Dokument', 'menit_moje_oj');
        $view_own_unit = $user->isAllowed('Dokument', 'cist_moje_oj');
        $view_all = $user->isAllowed('Dokument', 'cist_vse');
        $edit_all = $user->isAllowed('Dokument', 'menit_vse');

        // Uzivatel muze byt vedoucim jenom jednoho utvaru
        $org_unit = $user->getOrgUnit();
        $permitted_org_units = [];
        if ($org_unit)
            $permitted_org_units = $user->isVedouci() ? OrgJednotka::childOrg($org_unit->id) : [$org_unit->id];

        $cancel_forwarding = false;
        $perm_take_over = false;
        $perm_edit = $edit_all || $this->owner_user_id == $user->id || $change_own_unit && in_array($this->owner_orgunit_id,
                        $permitted_org_units);
        $perm_view = $perm_edit || $view_all || $view_own_unit && in_array($this->owner_orgunit_id,
                        $permitted_org_units);
        $perm_assign_cj = $perm_edit && empty($this->cislo_jednaci);
        if ($this->is_forwarded) {
            $perm_take_over = $this->forward_user_id == $user->id || $change_own_unit && in_array($this->forward_orgunit_id,
                            $permitted_org_units);
            $cancel_forwarding = $perm_edit;
            $perm_view = $perm_view || $perm_take_over || $view_own_unit && in_array($this->forward_orgunit_id,
                            $permitted_org_units);
            $perm_edit = false; // Pokud je dokument ve stavu predani, zakaz praci s nim
            if ($this->owner_user_id != $user->id)
                $perm_assign_cj = false;
        }

        if ($this->stav >= DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA)
            $perm_edit = false;

        return [
            'view' => $perm_view,
            'edit' => $perm_edit,
            'take_over' => $perm_take_over,
            'cancel_forwarding' => $cancel_forwarding,
            'assign_cj' => $perm_assign_cj
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

    /**
     * @param Spis $spis
     * @return \static[]
     */
    public static function getDocumentsFromSpis(Spis $spis)
    {
        $result = dibi::query("SELECT * FROM %n WHERE spis_id = $spis->id", self::TBL_NAME);

        return self::_createObjectsFromDibiResult($result);
    }

    public function insertIntoSpis(Spis $spis)
    {
        if ($spis->stav != Spis::OTEVREN)
            throw new Nette\InvalidStateException('Do spisu, který není otevřený, není možné vkládat dokumenty.');

        if ($this->getSpis())
            $this->takeOutFromSpis();

        dibi::begin();
        try {
            $this->spis_id = $spis->id;
            $this->save();

            $Log = new LogModel();
            $Log->logDokument($this->id, LogModel::SPIS_DOK_PRIPOJEN,
                    'Dokument přidán do spisu "' . $spis->nazev . '"');
            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function takeOutFromSpis()
    {
        $spis = $this->getSpis();
        if (!$spis)
            throw new Nette\InvalidStateException('Dokument není ve spisu.');

        dibi::begin();
        try {
            $this->spis_id = null;
            $this->save();

            $Log = new LogModel();
            $Log->logDokument($this->id, LogModel::SPIS_DOK_ODEBRAN,
                    'Dokument vyjmut ze spisu "' . $spis->nazev . '"');

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    /**
     * Ošetření vstupních hodnot před uložením do databáze.
     * Kód přenesený z Dokument::ulozit()
     * 
     * @param array $data
     */
    public function modify($data)
    {
        // nula není platná hodnota, databáze by hlásila chybu
        if (isset($data['spisovy_znak_id']) && $data['spisovy_znak_id'] == 0)
            $data['spisovy_znak_id'] = null;

        if (isset($data['zpusob_doruceni_id']) && isset($data['dokument_typ_id'])) {
            // zajisti, aby zpusob doruceni se ulozil pouze u prichozich dokumentu
            $typy_dokumentu = TypDokumentu::vsechnyJakoTabulku();
            if ($typy_dokumentu[$data['dokument_typ_id']]['smer'] == 1) //odchozi
                $data['zpusob_doruceni_id'] = null;
        }

        if (isset($data['pocet_listu'])) {
            if ($data->pocet_listu === "")
                $data->pocet_listu = null;
            if ($data->pocet_listu_priloh === "")
                $data->pocet_listu_priloh = null;
        }
        if (isset($data['vyrizeni_pocet_listu'])) {
            if ($data->vyrizeni_pocet_listu === "")
                $data->vyrizeni_pocet_listu = null;
            if ($data->vyrizeni_pocet_priloh === "")
                $data->vyrizeni_pocet_priloh = null;
        }

        if (array_key_exists('skartacni_lhuta', $data) && $data->skartacni_lhuta === '')
            $data->skartacni_lhuta = null;
        if (array_key_exists('skartacni_znak', $data) && $data->skartacni_znak === '')
            $data->skartacni_znak = null;

        parent::modify($data);
    }

    protected function _cancelForwardingInternal()
    {
        $this->is_forwarded = false;
        $this->forward_user_id = null;
        $this->forward_orgunit_id = null;
    }

}
