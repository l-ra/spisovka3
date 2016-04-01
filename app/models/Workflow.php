<?php

class Workflow extends BaseModel
{

    protected $name = 'workflow';
    protected $primary = 'id';

    const STAV_PREDAN_DO_SPISOVNY = 6;
    const STAV_VE_SPISOVNE = 7;
    
    /*
     * 0 - mimo evidenci
     * 1 - novy
     * 2 - predan / pridelen
     * 3 - vyrizuje se 
     * 4 - vyrizeno, ale neni spustena spousteci udalost
     * 5 - vyrizeno a spousteci udalost spustena
     * 6 - predan do spisovny
     * 7 - ve spisovne
     * 8 - ve skartacnim rizeni
     * 9 - archivovan
     * 10 - skartovan
     * 11 - zapujcen
     * 
     * 
     */

    protected function dokument($dokument_id, $stav)
    {

        $param = array();

        if (!is_null($stav)) {
            $param['where'] = array(array('dokument_id=%i', $dokument_id), array('stav_osoby=%i', $stav));
            $param['limit'] = 1;
        } else {
            $param['where'] = array(array('dokument_id=%i', $dokument_id));
        }

        $param['order'] = array('date' => 'DESC');

        $res = $this->selectComplex($param);
        $rows = $res->fetchAll();

        if (count($rows) > 0) {

            $Orgjednotka = new OrgJednotka();
            foreach ($rows as &$wf) {
                if (!empty($wf->prideleno_id)) {
                    $osoba = Person::fromUserId($wf->prideleno_id);
                    if ($osoba) {
                        $wf->prideleno_jmeno = Osoba::displayName($osoba);
                        $wf->prideleno_info = $osoba;
                    }
                }
                if (!empty($wf->orgjednotka_id)) {
                    $org = $Orgjednotka->getInfo($wf->orgjednotka_id);
                    if ($org) {
                        $wf->orgjednotka_info = $org;
                    }
                }
            }

            return $rows;
        }

        return null;
    }

    /**
     * Vytvori novy proces dokumentu
     *
     * @param int $dokument_id
     * @return bool
     */
    public function vytvorit($dokument_id, $poznamka = '')
    {
        if (is_numeric($dokument_id)) {

            $user = self::getUser();

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['stav_dokumentu'] = 1;
            $data['aktivni'] = 1;
            $data['prideleno_id'] = $user->id;
            $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele();
            $data['stav_osoby'] = 1;
            $data['date'] = new DateTime();
            $data['user_id'] = $user->id;
            $data['poznamka'] = $poznamka;

            if ($this->insert($data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function predat($dokument_id, $user_id, $orgjednotka_id, $poznamka = '')
    {
        if (!is_numeric($dokument_id))
            return false;

        try {
            dibi::begin();  // P.L.
            $Dokument = new Dokument();
            $dokument_info = $Dokument->getInfo($dokument_id);

            $user = self::getUser();

            // Vyradime ty zamestanance, kterym byl dokument v minulosti predan
            $update = array('stav_osoby%sql' => 'stav_osoby+100', 'aktivni' => 0);
            $this->update($update,
                    array(array('dokument_id=%i', $dokument_id), array('stav_osoby=0')));
            // Zrus stav dokumentu oznaceny k vyrizeni
            $update = array('stav_dokumentu' => '2');
            $this->update($update,
                    array(array('dokument_id=%i', $dokument_id), array('stav_dokumentu=3')));

            $data = array();
            $data['dokument_id'] = $dokument_info->id;
            $data['stav_dokumentu'] = max(2, $dokument_info->stav_dokumentu);
            $data['aktivni'] = 1;

            $data['stav_osoby'] = 0;

            $log = "";
            $log_spis = "";
            if ($user_id) {
                $person = Person::fromUserId($user_id);
                $data['prideleno_id'] = $user_id;
                $log = 'Dokument předán zaměstnanci ' . Osoba::displayName($person) . '.';
                $log_spis = 'Spis predan zamestnanci ' . Osoba::displayName($person) . '.';
            } else {
                $data['prideleno_id'] = null;
            }

            if ($orgjednotka_id) {
                $OrgJednotka = new OrgJednotka();
                $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                $data['orgjednotka_id'] = $orgjednotka_id;
                if ($org_info) {
                    $log = 'Dokument předán organizační jednotce ' . $org_info->zkraceny_nazev . '.';
                    $log_spis = 'Spis predan organizacni jednotce ' . $org_info->zkraceny_nazev . '.';
                } else {
                    $log = 'Dokument předán organizační jednotce.';
                    $log_spis = 'Spis predan organizacni jednotce.';
                }
            } else {
                $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele($user_id);
            }

            $data['date'] = new DateTime();
            $data['date_predani'] = new DateTime();
            $data['user_id'] = $user->id;
            $data['poznamka'] = $poznamka;

            $Log = new LogModel();

            // [P.L] je-li dokument ve spisu, nepredavej jej dvakrat
            if (!isset($dokument_info->spisy)) {
                $result_insert = $this->insert($data);
                $Log->logDokument($dokument_id, LogModel::DOK_PREDAN, $log);
            }

            // Prirazeni ostatnim dokumentum ve spisu
            if (isset($dokument_info->spisy)) {
                $DokumentSpis = new DokumentSpis();
                $Spis = new Spis();
                // Spis bude vzdy jen jeden
                $spis = current($dokument_info->spisy);

                // Vyradime ty zamestanance, kterym byly spisove dokumenty v minulosti predany
                $this->update_dokumenty_ve_spisu($spis->id,
                        'stav_osoby = stav_osoby + 100, aktivni = 0', 'stav_osoby = 0');

                // Zrus stav dokumentu oznaceny k vyrizeni
                $this->update_dokumenty_ve_spisu($spis->id, 'stav_dokumentu = 2',
                        'stav_dokumentu = 3');

                $seznam_dokumentu = $DokumentSpis->dokumenty($spis->id);
                // Musi vratit minimalne jeden, predavany dokument
                // count($seznam_dokumentu)>0 je nesmysl
                foreach ($seznam_dokumentu as $dokument_other) {

                    $data_other = array();
                    $data_other['dokument_id'] = $dokument_other->id;
                    $data_other['stav_dokumentu'] = max(2, $dokument_other->stav_dokumentu);
                    ;
                    $data_other['aktivni'] = 1;
                    $data_other['stav_osoby'] = 0;
                    $data_other['prideleno_id'] = $data['prideleno_id'];
                    $data_other['orgjednotka_id'] = $data['orgjednotka_id'];
                    $data_other['date'] = new DateTime();
                    $data_other['date_predani'] = new DateTime();
                    $data_other['user_id'] = $data['user_id'];
                    $data_other['poznamka'] = $data['poznamka'];
                    $result_insert = $this->insert($data_other);
                    //Nette\Diagnostics\Debugger::dump($data_other);
                    $Log->logDokument($dokument_other->id, LogModel::DOK_PREDAN, $log);
                }

                $Spis->predatOrg($spis->id, $data['orgjednotka_id']);
                $Log->logSpis($spis->id, LogModel::SPIS_PREDAN, $log_spis);
            }

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }

        // posli upozorneni emailem
        try {
            if (empty($orgjednotka_id)) {
                Notifications::notifyUser($user_id, Notifications::RECEIVE_DOCUMENT,
                        ['document_name' => $dokument_info->nazev,
                    'reference_number' => $dokument_info->cislo_jednaci]);
            }
        } catch (Exception $e) {
            throw new Exception("Předání proběhlo v pořádku, ale nepodařilo se upozornit příjemce e-mailem: \n"
            . $e->getMessage(), 0, $e);
        }

        return true;
    }

    // Funkce je volána z více míst v programu, tudíž je problém zde vytvořit položku v transakčním logu dokumentu
    // - zrušit převzetí dokumentu
    // - odmítnout převzetí dokumentu
    // - označení dokumentu k vyřízení
    // - zrušit převzetí spisu
    // - odmítnout převzetí spisu
    public function zrusit_prevzeti($dokument_id)
    {
        if (!is_numeric($dokument_id))
            return false;

        // Vyradime ty zamestanance, kterym byl dokument v minulosti predan
        $update = array('stav_osoby%sql' => 'stav_osoby+100', 'aktivni' => 0);
        $this->update($update,
                array(array('dokument_id=%i', $dokument_id), array('stav_osoby=0')));

        // Vyradime i spisy, ktere byly predany
        $DokumentSpis = new DokumentSpis();
        $spisy = $DokumentSpis->spisy($dokument_id);
        if (count($spisy) > 0) {
            foreach ($spisy as $spis) {
                $this->update_dokumenty_ve_spisu($spis->id,
                        'stav_osoby = stav_osoby + 100, aktivni = 0', 'stav_osoby = 0');

                $Spis = new Spis();
                $Spis->zrusitPredani($spis->id);
            }
        }

        return true;
    }

    // P.L. Upravy viz komentare v metode priradit()    
    public function prevzit($dokument_id)
    {
        if (!is_numeric($dokument_id))
            return false;

        $predan_array = $this->dokument($dokument_id, 0);
        $predan = is_array($predan_array) ? $predan_array[0] : null;

        if (!$predan)
            return false;

        $user = self::getUser();

        // test predaneho
        // pokud neni predana osoba, tak test na vedouciho org.jednotky
        $access = 0;
        $log = "";
        $log_plus = ".";
        if (empty($predan->prideleno_id)) {
            if (OrgJednotka::isInOrg($predan->orgjednotka_id)) {
                $access = 1;
                $log_plus = " určený organizační jednotce " . @$predan->orgjednotka_info->zkraceny_nazev . ".";
            }
        } else {
            if ($predan->prideleno_id == $user->id || OrgJednotka::isInOrg($predan->orgjednotka_id)) {
                $access = 1;
            }
        }

        if ($access != 1)
            return false;

        $log = "";

        $data = array();
        $data['stav_osoby'] = 1;
        $data['date'] = new DateTime();
        $data['user_id'] = $data['prideleno_id'] = $user->id;
        $data['aktivni'] = 1;

        $Dokument = new Dokument();
        $Log = new LogModel();
        $dokument_info = $Dokument->getInfo($dokument_id);

        try {
            dibi::begin();

            if (!isset($dokument_info->spisy)) {
                // Prirazene zamestanance predame uz nejsou prirazeni
                $update = array('stav_osoby' => 2);
                $this->update($update,
                        array(array('dokument_id=%i', $dokument_id),
                    array('stav_osoby=1')
                        )
                );
                // Deaktivujeme starsi zaznamy
                $this->deaktivovat($dokument_id);

                $where = array('id=%i', $predan->id);
                $result_update = $this->update($data, $where);

                $Log->logDokument($dokument_id, LogModel::DOK_PRIJAT,
                        'Zaměstnanec ' . $user->displayName . ' přijal dokument' . $log_plus);
            }

            // Prevzeti i ostatnim dokumentum ve spisu
            if (isset($dokument_info->spisy)) {
                $DokumentSpis = new DokumentSpis();
                $Spis = new Spis();
                $spis = current($dokument_info->spisy);

                $seznam_dokumentu = $DokumentSpis->dokumenty($spis->id);

                foreach ($seznam_dokumentu as $dokument_other) {

                    // Neni-li dokument ve stavu predani, doslo nekde k vazne chybe
                    // a nesmime provest nasledujici kod (prevzit dokument), jinak se poskodi data v tabulce workflow a dokument zmizi ze systemu
                    if (!isset($dokument_other->predano))
                        continue;

                    // Prirazene zamestanance predame uz nejsou prirazeni - aplikace na ostatni dokumenty ve spisu
                    $update = array('stav_osoby' => 2);
                    $this->update($update,
                            array(array('dokument_id=%i', $dokument_other->id),
                        array('stav_osoby=1')
                            )
                    );
                    $this->deaktivovat($dokument_other->id);

                    $data_other = array();
                    $data_other['stav_osoby'] = 1;
                    $data_other['date'] = new DateTime();
                    $data_other['user_id'] = $data['prideleno_id'] = $user->id;
                    $data_other['aktivni'] = 1;

                    $where = array('id=%i', $dokument_other->predano->id);
                    $result_update = $this->update($data_other, $where);

                    $Log->logDokument($dokument_other->id, LogModel::DOK_PRIJAT,
                            'Zaměstnanec ' . $user->displayName . ' přijal dokument' . $log_plus);
                }

                $Spis->zmenitOrg($spis->id, $predan->orgjednotka_id);
                $Log->logSpis($spis->id, LogModel::SPIS_PRIJAT,
                        'Zaměstnanec ' . $user->displayName . ' přijal spis' . $log_plus);
            }

            dibi::commit();
            return true;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    /**
     * Prevezme dokument k vyrizeni
     * @param int $dokument_id
     * @return boolean
     * @throws Exception
     */
    public function vyrizuje($dokument_id)
    {
        $user = self::getUser();
        $user_id = $user->id;

        if (!is_numeric($dokument_id))
            return false;

        $predan_array = $this->dokument($dokument_id, 1);
        $predan = is_array($predan_array) ? $predan_array[0] : null;

        if (!$predan)
            return false;

        $access = 0;
        if (empty($predan->prideleno_id)) {
            if (OrgJednotka::isInOrg($predan->orgjednotka_id)) {
                $access = 1;
            }
        } else {
            if ($predan->prideleno_id == $user_id || OrgJednotka::isInOrg($predan->orgjednotka_id)) {
                $access = 1;
            }
        }

        if ($access != 1)
            return false;

        // [P.L.] transakce je nutná, bez ní může dojít k poškození dat
        dibi::begin();
        try {
            // Deaktivujeme starsi zaznamy
            $this->deaktivovat($dokument_id);

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['stav_dokumentu'] = 3;
            $data['aktivni'] = 1;

            $data['stav_osoby'] = 1;

            $data['prideleno_id'] = $user_id;
            $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele($user_id);

            $data['date'] = new DateTime();
            $data['user_id'] = $user_id;
            $data['poznamka'] = $predan->poznamka;

            $this->insert($data);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_KVYRIZENI,
                    'Zaměstnanec ' . $user->displayName . ' převzal dokument k vyřízení.');

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Oznaci dokument za vyrizeny
     * @param int $dokument_id
     * @return boolean|string
     * @throws Exception
     */
    public function vyridit($dokument_id, BasePresenter $presenter)
    {
        $user_id = self::getUser()->id;

        if (!is_numeric($dokument_id))
            return false;

        $wf_array = $this->dokument($dokument_id, 1);
        if (!is_array($wf_array))
            return 'neprideleno';

        $wf = $wf_array[0];
        if ($wf->prideleno_id != $user_id && !OrgJednotka::isInOrg($wf->orgjednotka_id))
            return 'neprideleno';

        try {
            dibi::begin();

            $Dokument = new Dokument();
            $dokument_info = $Dokument->getInfo($dokument_id, "subjekty");

            // Test na uplnost dat
            if ($kontrola = $Dokument->kontrola($dokument_info)) {
                foreach ($kontrola as $kmess)
                    $presenter->flashMessage($kmess, 'warning');
                dibi::rollback();
                return false;
            }

            $automaticke_spusteni = $dokument_info->spousteci_udalost_stav == 2;
            if ($automaticke_spusteni) {
                $stav = 5;
                $datum_spusteni = date('Y-m-d');
            } else
                $stav = 4;

            $this->zrusit_prevzeti($dokument_id);

            // Deaktivujeme starsi zaznamy
            $this->deaktivovat($dokument_id);

            $data = array();
            $data['dokument_id'] = $dokument_info->id;
            $data['stav_dokumentu'] = $stav;

            $data['stav_osoby'] = 1;
            $data['aktivni'] = 1;

            $data['prideleno_id'] = $user_id;
            $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele($user_id);

            $data['date'] = new DateTime();
            $data['user_id'] = $user_id;
            $data['poznamka'] = $wf->poznamka;

            $this->insert($data);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_VYRIZEN,
                    'Dokument označen za vyřízený.');

            if ($automaticke_spusteni) {
                $data = array('datum_spousteci_udalosti' => $datum_spusteni);
                $Dokument->ulozit($data, $dokument_id);
                $Log->logDokument($dokument_id, LogModel::DOK_SPUSTEN,
                        'Začíná plynout skartační lhůta.');
            }

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }

        return $automaticke_spusteni ? true : 'udalost';
    }

    // Vetsina kodu je zkopirovana z metody vyrizeno()
    // Kontrola opravneni se provadi uz v presenteru
    public function spustitUdalost($dokument_id, $datum_spusteni)
    {
        $user_id = self::getUser()->id;

        try {
            dibi::begin();

            $Dokument = new Dokument();

            // Deaktivujeme starsi zaznamy
            $this->deaktivovat($dokument_id);

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['stav_dokumentu'] = 5;

            $data['stav_osoby'] = 1;
            $data['aktivni'] = 1;

            $data['prideleno_id'] = $user_id;
            $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele($user_id);

            $data['date'] = new DateTime();
            $data['user_id'] = $user_id;

            $this->insert($data);

            $Dokument->ulozit(array('datum_spousteci_udalosti' => $datum_spusteni),
                    $dokument_id);

            $today = new DateTime();
            $today->setTime(0, 0);
            $given_date = new DateTime($datum_spusteni);
            if ($given_date == $today)
                $msg = 'Začíná plynout skartační lhůta.';
            else
                $msg = 'Skartační lhůta začne plynout od ' . $given_date->format('j.n.Y') . '.';

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_SPUSTEN, $msg);

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function predatDoSpisovny($dokument_id, $volano_ze_spisu)
    {
        // kontrola uzivatele
        $Dokument = new Dokument();
        $dokument_info = $Dokument->getInfo($dokument_id, "subjekty");

        if (!$volano_ze_spisu && isset($dokument_info->spisy)) {
            return 'Dokument ' . $dokument_info->cislo_jednaci . ' nelze přenést do spisovny samostatně, je součástí spisu.';
        }

        // Test na uplnost dat
        if ($kontrola = $Dokument->kontrola($dokument_info)) {
            // nejsou kompletni data - neprenasim
            return 'Dokument ' . $dokument_info->cislo_jednaci . ' nelze přenést do spisovny! Nejsou vyřízeny všechny potřebné údaje.';
        }

        // Kontrola stavu - vyrizen a spusten 5 <
        if ($dokument_info->stav_dokumentu < 4) {
            return 'Dokument ' . $dokument_info->cislo_jednaci . ' nelze přenést do spisovny! Není označen jako vyřízený.';
        } else if ($dokument_info->stav_dokumentu < 5) {
            return 'Dokument ' . $dokument_info->cislo_jednaci . ' nelze přenést do spisovny! Není spuštěna událost.';
        }

        // Predat do spisovny
        $workflow_data = $this->select(array(array('id=%i', $dokument_info['prideleno']->id)))->fetch();
        if ($workflow_data) {
            $workflow_data = (array) $workflow_data;
            unset($workflow_data['id']);
            $workflow_data['stav_dokumentu'] = 6;
            $workflow_data['date'] = new DateTime();
            $workflow_data['user_id'] = self::getUser()->id;

            $this->deaktivovat($dokument_id);
            $result_insert = $this->insert($workflow_data);
            if ($result_insert) {
                //$Dokument->ulozit(array('stav'=>2), $dokument_id);
                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_SPISOVNA_PREDAN);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * @param int $dokument_id
     * @param boolean $samostatny  false = voláno při přezetí celého spisu
     *                             true = voláno při přezetí jednoho dokumentu
     * @return boolean|string
     */
    public function prevzitDoSpisovny($dokument_id, $samostatny)
    {
        $Dokument = new Dokument();
        $dokument_info = $Dokument->getInfo($dokument_id, "subjekty");

        $error_msg = "Dokument $dokument_info->jid nelze přijmout do spisovny!";

        if ($samostatny && isset($dokument_info->spisy)) {
            return "$error_msg Dokument je součásti spisu.";
        }

        // Test na uplnost dat
        if ($kontrola = $Dokument->kontrola($dokument_info)) {
            // nejsou kompletni data - neprenasim
            return "$error_msg Nejsou vyřízeny všechny potřebné údaje.";
        }

        // Kontrola stavu - vyrizen a spusten 5 <
        if ($dokument_info->stav_dokumentu < 4)
            return "$error_msg Není označen jako vyřízený.";
        if ($dokument_info->stav_dokumentu < 5)
            return "$error_msg Není spuštěna událost.";


        // Pripojit do spisovny
        $workflow_data = $this->select(array(array('[id] = %i', $dokument_info['prideleno']->id)))->fetch();
        if (!$workflow_data)
            return false;

        $success = false;
        dibi::begin();
        try {
            $dokument_update = ['stav' => 2];
            if ($Dokument->ulozit($dokument_update, $dokument_id)) {

                $workflow_data = (array) $workflow_data;
                unset($workflow_data['id']);
                $workflow_data['stav_dokumentu'] = 7;
                $workflow_data['date'] = new DateTime();
                $workflow_data['user_id'] = self::getUser()->id;

                $this->deaktivovat($dokument_id);
                if ($this->insert($workflow_data)) {
                    $Log = new LogModel();
                    $Log->logDokument($dokument_id, LogModel::DOK_SPISOVNA_PRIPOJEN,
                            'Dokument přijat do spisovny.');

                    $success = true;
                }
            }
        } catch (Exception $e) {
            dibi::rollback();
            return "Při převzetí dokumentu $dokument_info->jid došlo k výjimce: " . $e->getMessage();
        }

        if ($success)
            dibi::commit();
        else
            dibi::rollback();

        return $success;
    }

    /**
     *   Vraci true, i kdyz existuje neschvalena zapujcka
     */
    protected function jeZapujcen($dokument_id)
    {
        $z = new Zapujcka();
        $stav = $z->stavZapujcky($dokument_id);
        return in_array($stav, [1, 2]);
    }

    public function keskartaci($dokument_id)
    {
        if (!is_numeric($dokument_id))
            return false;

        $user = self::getUser();
        if (!$user->isAllowed('Spisovna', 'skartacni_navrh'))
            return false;

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        $Dokument = new Dokument();
        $dokument_info = $Dokument->getInfo($dokument_id);

        if ($this->jeZapujcen($dokument_id))
            return false;

        // Deaktivujeme starsi zaznamy
        $this->deaktivovat($dokument_id);

        $data = array();
        $data['dokument_id'] = $dokument_info->id;
        $data['stav_dokumentu'] = 8;
        $data['stav_osoby'] = 1;
        $data['aktivni'] = 1;
        $data['prideleno_id'] = $dokument_info->prideleno->prideleno_id;
        $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;

        $data['date'] = new DateTime();
        $data['user_id'] = $user->id;
        $data['poznamka'] = $dokument_info->prideleno->poznamka;

        $result_insert = $this->insert($data);

        //if ($transaction)
        //dibi::commit();

        if ($result_insert) {

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_KESKARTACI,
                    'Dokument přidán do skartačního řízení.');

            return true;
        } else {
            return false;
        }
    }

    public function archivovat($dokument_id)
    {
        if (is_numeric($dokument_id)) {

            $user = self::getUser();
            if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {

                //$transaction = (! dibi::inTransaction());
                //if ($transaction)
                //dibi::begin();

                $Dokument = new Dokument();
                $dokument_info = $Dokument->getInfo($dokument_id);

                if ($this->jeZapujcen($dokument_id))
                    return false;

                // Deaktivujeme starsi zaznamy
                $this->deaktivovat($dokument_id);

                $data = array();
                $data['dokument_id'] = $dokument_info->id;
                $data['stav_dokumentu'] = 9;
                $data['stav_osoby'] = 1;
                $data['aktivni'] = 1;
                $data['prideleno_id'] = $dokument_info->prideleno->prideleno_id;
                $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;

                $data['date'] = new DateTime();
                $data['user_id'] = $user->id;
                $data['poznamka'] = $dokument_info->prideleno->poznamka;

                $result_insert = $this->insert($data);

                //if ($transaction)
                //dibi::commit();

                if ($result_insert) {

                    $Log = new LogModel();
                    $Log->logDokument($dokument_id, LogModel::DOK_ARCHIVOVAN,
                            'Dokument uložen do archivu.');

                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function skartovat($dokument_id)
    {
        if (is_numeric($dokument_id)) {

            $user = self::getUser();
            if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {

                //$transaction = (! dibi::inTransaction());
                //if ($transaction)
                //dibi::begin();

                $Dokument = new Dokument();
                $dokument_info = $Dokument->getInfo($dokument_id);

                if ($this->jeZapujcen($dokument_id))
                    return false;

                // Deaktivujeme starsi zaznamy
                $this->deaktivovat($dokument_id);

                $data = array();
                $data['dokument_id'] = $dokument_info->id;
                $data['stav_dokumentu'] = 10;
                $data['stav_osoby'] = 1;
                $data['aktivni'] = 1;
                $data['prideleno_id'] = $dokument_info->prideleno->prideleno_id;
                $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;

                $data['date'] = new DateTime();
                $data['user_id'] = $user->id;
                $data['poznamka'] = $dokument_info->prideleno->poznamka;

                $result_insert = $this->insert($data);

                //if ($transaction)
                //dibi::commit();

                if ($result_insert) {

                    $Log = new LogModel();
                    $Log->logDokument($dokument_id, LogModel::DOK_SKARTOVAN,
                            'Dokument byl skartován.');

                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function zapujcka_pridelit($dokument_id, $user_id)
    {
        if (!is_numeric($dokument_id))
            return false;

        $user = self::getUser();
        if (!$user->inheritsFromRole('spisovna') && !$user->isInRole('superadmin'))
            return false;

        try {
            dibi::begin();

            // Deaktivujeme starsi zaznamy
            $this->deaktivovat($dokument_id);

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['stav_dokumentu'] = 11;
            $data['stav_osoby'] = 1;
            $data['aktivni'] = 1;
            $data['prideleno_id'] = $user_id;
            $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele($user_id);

            $data['date'] = new DateTime();
            $data['user_id'] = self::getUser()->id;

            $this->insert($data);

            $Dokument = new Dokument();
            $Dokument->update(array('stav' => 1), array(array('id=%i', $dokument_id)));

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::ZAPUJCKA_PRIDELENA,
                    'Dokument byl zapůjčen.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $e->getCode();
            dibi::rollback();
            return false;
        }
    }

    public function zapujcka_vratit($dokument_id, $user_id)
    {
        if (is_numeric($dokument_id)) {

            // Deaktivujeme starsi zaznamy
            $this->deaktivovat($dokument_id);
            $update = array('stav_osoby' => 2);
            $this->update($update,
                    array(array('dokument_id=%i', $dokument_id),
                array('stav_osoby=1')
                    )
            );


            $posledni = $this->posledne_prideleny($dokument_id);
            if (!$posledni) {
                return false;
            }
            $data = $this->obj2array($posledni);
            unset($data['id']);
            $data['aktivni'] = 1;
            $data['date'] = new DateTime();
            $data['user_id'] = $user_id;
            $data['stav_osoby'] = 1;

            $result_insert = $this->insert($data);
            if ($result_insert) {

                $Dokument = new Dokument();
                $Dokument->update(array('stav' => 2), array(array('id=%i', $dokument_id)));

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::ZAPUJCKA_VRACENA,
                        'Dokument byl navrácen do spisovny.');

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function posledne_prideleny($dokument_id)
    {
        $param = array();

        $param['where'] = array(
            array('dokument_id=%i', $dokument_id),
            array('stav_dokumentu<>11')
        );
        $param['limit'] = 1;
        $param['order'] = array('date' => 'DESC');

        $row = $this->selectComplex($param);
        $row = $row->fetch();

        if ($row) {
            return $row;
        }

        return null;
    }

    /**
     * Je uzivatel vlastnikem dokumentu
     * @param int $dokument_id
     * @param int $user_id
     * @return bool 
     */
    public function prirazeny($dokument_id)
    {
        $user = self::getUser();
        $user_id = $user->id;
        $orgjednotka_id = OrgJednotka::dejOrgUzivatele();
        $isVedouci = $user->isAllowed(NULL, 'is_vedouci');

        $param = array();
        $param['where'] = array(
            array('dokument_id=%i', $dokument_id),
            'stav_osoby = 1',
            'aktivni = 1'
        );
        $param['limit'] = 1;

        $result = $this->selectComplex($param);
        $row = $result->fetch();

        if (!$row)
            return false; // chyba integrity dat nebo parametr dokument_id je neplatny

        if ($user->isInRole('superadmin'))
            return true;

        if ($orgjednotka_id !== null && $user->isAllowed('Dokument', 'menit_moje_oj') && $orgjednotka_id == $row->orgjednotka_id)
            return true;

        if (empty($row->prideleno_id)) {
            // P.L. 2013-07-16 Neni prirazeno konkretnimu uzivateli. 
            // Nemelo by se stat, prijmuti dokumentu musi provest konkretni uzivatel
            // Ale treba pocitat s chybami v datech z minulosti

            if ($isVedouci && $orgjednotka_id !== null)
                return $orgjednotka_id == $row->orgjednotka_id;

            return false;
        }

        return $row->prideleno_id == $user_id;
    }

    /**
     * Je uzivatel potencialni vlastnik dokumentu
     * @param int $dokument_id
     * @param int $user_id
     * @return bool
     */
    public function predany($dokument_id)
    {
        $param = array();

        $user = self::getUser();
        $user_id = $user->id;
        $orgjednotka_id = OrgJednotka::dejOrgUzivatele();
        $isVedouci = $user->isAllowed(NULL, 'is_vedouci');

        $param['where'] = array(
            array('dokument_id=%i', $dokument_id),
            'stav_osoby = 0',
            'aktivni = 1'
        );
        $param['limit'] = 1;

        $result = $this->selectComplex($param);
        $row = $result->fetch();

        if (!$row)
            return false; // zaznam nenalezen, dokument neni ve stavu predani

        if ($user->isInRole('superadmin'))
            return true;

        if ($orgjednotka_id !== null && $user->isAllowed('Dokument', 'menit_moje_oj') && $orgjednotka_id == $row->orgjednotka_id)
            return true;

        if (empty($row->prideleno_id)) {
            // Dokument predany pouze na org. jednotku

            if ($isVedouci && $orgjednotka_id !== null)
                return OrgJednotka::dejOrgUzivatele() == $row->orgjednotka_id;

            return false;
        }

        return $row->prideleno_id == $user_id;
    }

    protected function deaktivovat($dokument_id)
    {
        dibi::query("UPDATE {$this->name} SET aktivni = 0 WHERE dokument_id = %i", $dokument_id);
    }

    /* protected function deaktivovatSpis($spis_id) {

      if ( !is_numeric($spis_id) )
      return false;

      $this->update_dokumenty_ve_spisu($spis_id,
      'aktivni = 0', 'aktivni = 1');
      return true;
      } */

    protected function update_dokumenty_ve_spisu($spis_id, $update, $where)
    {

        dibi::query("UPDATE {$this->name} w, {$this->tb_dokspis} ds SET $update "
                . "WHERE w.dokument_id = ds.dokument_id AND ds.spis_id = %i AND $where",
                $spis_id);
    }

    public function vratitZeSpisovny($dokument_id, $use_transaction = true)
    {
        $Dokument = new Dokument();
        if (!is_numeric($dokument_id))
            return false;
        $old_data = $this->select(["dokument_id = $dokument_id", 'aktivni = 1'])->fetch();
        if (!$old_data)
            return false;
        if (!in_array($old_data->stav_dokumentu,
                        [self::STAV_PREDAN_DO_SPISOVNY, self::STAV_VE_SPISOVNE]))
            return false;

        $workflow_data = [];
        $workflow_data['dokument_id'] = $old_data['dokument_id'];
        $workflow_data['prideleno_id'] = $old_data['prideleno_id'];
        $workflow_data['orgjednotka_id'] = $old_data['orgjednotka_id'];
        $workflow_data['user_id'] = self::getUser()->id;
        $workflow_data['stav_dokumentu'] = 5;
        $workflow_data['stav_osoby'] = 1;
        $workflow_data['date'] = new DateTime();
        $workflow_data['aktivni'] = 1;

        if ($use_transaction)
            dibi::begin();
        try {
            $this->deaktivovat($dokument_id);
            $this->insert($workflow_data);

            $Dokument->ulozit(array('stav' => 1), $dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_SPISOVNA_VRACEN);

            if ($use_transaction)
                dibi::commit();
            return true;
        } catch (Exception $e) {
            if ($use_transaction)
                dibi::rollback();
            throw $e;
        }
    }

    public function vratitSpisZeSpisovny($spis_id)
    {
        // kontrola na stav spisu
        $spis_model = new Spis;
        $spis_info = $spis_model->getInfo($spis_id);
        if ($spis_info->stav != 2) {
            return false;
        }

        dibi::begin();
        try {
            $DokumentSpis = new DokumentSpis();
            $dokumenty = $DokumentSpis->dokumenty($spis_id);
            if (count($dokumenty) > 0) {
                foreach ($dokumenty as $dok) {
                    $ok = $this->vratitZeSpisovny($dok->id, false);
                    if (!$ok) {
                        dibi::rollback();
                        return false;
                    }
                }
            }

            // Predat do spisovny
            $spis_model->zmenitStav($spis_id, Spis::UZAVREN);

            dibi::commit();
            return true;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

}
