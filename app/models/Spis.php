<?php

class Spis extends TreeModel
{

    const OTEVREN = 1;
    const UZAVREN = 0;
    const PREDAN_DO_SPISOVNY = 2;
    const VE_SPISOVNE = 3;

    protected $name = 'spis';

    public function getInfo($spis_id, $detail = false)
    {
        $result = $this->select(array(array('id=%i', $spis_id)));
        $row = $result->fetch();
        if (!$row)
            throw new InvalidArgumentException("Spis id '$spis_id' neexistuje.");

        return $detail ? $this->spisDetail($row) : $row;
    }

    // Vrátí první spis z daným názvem, protože bohužel není zaručeno, že název spisu bude jedinečný
    public function findByName($spis_name)
    {
        $result = $this->select(array(array('nazev=%s', $spis_name)));
        $row = $result->fetch();
        return $row ? $row : null;
    }

    private function spisDetail($row)
    {
        $OrgJednotka = new Orgjednotka();

        if (!empty($row->orgjednotka_id)) {
            $row->orgjednotka_prideleno = $OrgJednotka->getInfo($row->orgjednotka_id);
        } else {
            $row->orgjednotka_prideleno = null;
        }
        if (!empty($row->orgjednotka_id_predano)) {
            $row->orgjednotka_predano = $OrgJednotka->getInfo($row->orgjednotka_id_predano);
        } else {
            $row->orgjednotka_predano = null;
        }

        $query = array(
            "SELECT w.* FROM {$this->tb_dokspis} AS ds INNER JOIN {$this->tb_workflow} AS w"
            . " ON ds.dokument_id = w.dokument_id WHERE ds.spis_id = %i",
            $row->id
        );

        $row->workflow = dibi::query($query)->fetchAll();

        return $row;
    }

    /* Opraveno odstraněním "hlavního spisu".
     * 
    protected function getLevelExpression()
    {
        return parent::getLevelExpression() . ' - 1';
    } */
    
    /**
     * @return DibiResult
     */
    public function seznam($args = null, $order_by = null)
    {
        if (!empty($args['where']))
            $params['where'] = $args['where'];
        
        $params['leftJoin'] = array(
            'orgjednotka1' => array(
                'from' => array($this->tb_orgjednotka => 'org1'),
                'on' => array('org1.id=tb.orgjednotka_id'),
                'cols' => array('zkraceny_nazev' => 'orgjednotka_prideleno')
            ),
            'orgjednotka2' => array(
                'from' => array($this->tb_orgjednotka => 'org2'),
                'on' => array('org2.id=tb.orgjednotka_id_predano'),
                'cols' => array('zkraceny_nazev' => 'orgjednotka_predano')
            ),
        );

        if ($order_by)
            $params['order'] = $order_by;

        return $this->nacti($params);
    }

    public function seznamRychly($where = null)
    {
        $args = ['SELECT id, parent_id, typ, nazev FROM %n AS tb', $this->name];
        if (!empty($where))
            array_push($args, 'WHERE %and', $where);
        $args[] = 'ORDER BY sekvence_string';

        return dibi::query($args);
    }

    public function seznamDokumentu($spis_id)
    {
        if (empty($spis_id))
            return null;
        if (!is_array($spis_id))
            $spis_id = array($spis_id);

        $sql = array(
            'distinct' => 1,
            'from' => array($this->tb_dokspis => 'dokspis'),
            'cols' => array('spis_id', 'dokument_id', 'poradi'),
            'where' => array(array('dokspis.spis_id IN %in', $spis_id), 'd.stav > 0'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=dokspis.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                ),
                'workflow' => array(
                    'from' => array($this->tb_workflow => 'wf'),
                    'on' => array('wf.dokument_id=d.id AND wf.aktivni=1 AND wf.stav_osoby=1'),
                    'cols' => array('stav_dokumentu', 'prideleno_id', 'orgjednotka_id')
                ),
                'orgwf' => array(
                    'from' => array($this->tb_orgjednotka => 'orgwf'),
                    'on' => array('orgwf.id=wf.orgjednotka_id'),
                    'cols' => array('zkraceny_nazev' => 'orgjednotka_prideleno')
                ),
            )
        );

        $select = $this->selectComplex($sql);
        $result = $select->fetchAll();
        if (count($result) > 0) {
            $dokumenty = array();
            foreach ($result as $dok) {
                $dokumenty[$dok->spis_id][$dok->dokument_id] = $dok;
            }
            return $dokumenty;
        } else {
            return null;
        }
    }

    private function omezeni_org(array $filter)
    {
        $user = Nette\Environment::getUser();
        $oj_id = Orgjednotka::dejOrgUzivatele();

        if ($user->isAllowed('Dokument', 'cist_vse'))
            ; // vsechny spisy bez ohledu na organizacni jednotku
        else if ($oj_id === null)
            $filter[] = array("0");
        else {
            if ($user->isAllowed(NULL, 'is_vedouci'))
                $org_jednotky = Orgjednotka::childOrg($oj_id);
            else
                $org_jednotky = array($oj_id);

            if (count($org_jednotky) > 1)
                $filter[] = array('tb.orgjednotka_id IN %in OR tb.orgjednotka_id_predano IN %in OR tb.orgjednotka_id IS NULL', $org_jednotky, $org_jednotky);
            else
                $filter[] = array('tb.orgjednotka_id = %i OR tb.orgjednotka_id_predano = %i OR tb.orgjednotka_id IS NULL', $org_jednotky, $org_jednotky);
        }

        return $filter;
    }

    public function spisovka(array $filter = [])
    {
        $filter[] = "NOT (tb.typ = 'S' AND tb.stav > 2)";
        return $this->omezeni_org($filter);
    }

    public function spisovna(array $filter = [])
    {
        $filter[] = "tb.stav = " . self::VE_SPISOVNE;
        return $filter;
    }

    public function spisovna_prijem(array $filter = [])
    {
        $filter[] = "tb.stav = " . self::PREDAN_DO_SPISOVNY;
        return $filter;
    }

    public function vytvorit($data)
    {
        $data['date_created'] = new DateTime();
        $data['user_created'] = Nette\Environment::getUser()->getIdentity()->id;
        $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele();

        if (empty($data['parent_id']))
            unset($data['parent_id']);
        
        if (empty($data['spisovy_znak_id']))
            unset($data['spisovy_znak_id']);
        if (empty($data['skartacni_znak']))
            unset($data['skartacni_znak']);
        if (isset($data['skartacni_lhuta']) && $data['skartacni_lhuta'] === '')
            unset($data['skartacni_lhuta']);

        $data['stav'] = self::OTEVREN;

        $spis_id = $this->vlozitH($data);

        $Log = new LogModel();
        $Log->logSpis($spis_id, LogModel::SPIS_VYTVOREN);

        return $spis_id;
    }

    public function upravit($data, $spis_id)
    {
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
        
        if (isset($data['spisovy_znak_id']) && !$data['spisovy_znak_id'])
            $data['spisovy_znak_id'] = null;
        if (isset($data['skartacni_znak']) && !$data['skartacni_znak'])
            $data['skartacni_znak'] = null;
        if (isset($data['skartacni_lhuta']) && $data['skartacni_lhuta'] === '')
            $data['skartacni_lhuta'] = null;

        $Log = new LogModel();

        try {
            $this->upravitH($data, $spis_id);
            $Log->logSpis($spis_id, LogModel::SPIS_ZMENEN);
        } catch (Exception $e) {
            $Log->logSpis($spis_id, LogModel::SPIS_CHYBA,
                    'Hodnoty spisu se nepodarilo upravit.');
            throw $e;
        }
    }

    public function zmenitStav($spis_id, $stav)
    {
        if (!is_numeric($spis_id) || !is_numeric($stav))
            return false;

        if ($stav === self::UZAVREN) {
            // Kontrola
            if ($kontrola = $this->kontrolaVyrizeniDokumentu($spis_id)) {
                /* foreach ($kontrola as $kmess) {
                  Nette\Environment::getApplication()->getPresenter()->flashMessage($kmess,
                  'warning');
                  } */
                return -1;
            }
        }

        $data = array();
        $now = new DateTime();
        $data['date_modified'] = $now;
        $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
        if ($stav === self::UZAVREN)
            $data['datum_uzavreni'] = $now;
        $data['stav'] = $stav;

        $Log = new LogModel();
        try {
            $this->update($data, array('id = %i', $spis_id));
            // Logování operací se spisy je nedodělané. Zde logování pracuje chybně
            // při předávání do spisovny
            $Log->logSpis($spis_id,
                    $stav === self::OTEVREN ? LogModel::SPIS_OTEVREN : LogModel::SPIS_UZAVREN);
            return true;
        } catch (Exception $e) {
            $Log->logSpis($spis_id, LogModel::SPIS_CHYBA,
                    'Nepodařilo se změnit stav spisu. ' . $e->getMessage());
            return false;
        }
    }

    public function zmenitOrg($spis_id, $orgjednotka_id)
    {

        if (empty($spis_id))
            return false;
        if (empty($orgjednotka_id))
            return false;

        try {
            $this->update(
                    array('orgjednotka_id' => $orgjednotka_id, 'orgjednotka_id_predano' => null),
                    array(array('id=%i', $spis_id))
            );
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function predatOrg($spis_id, $orgjednotka_id)
    {

        if (empty($spis_id))
            return false;
        if (empty($orgjednotka_id))
            return false;

        try {
            $this->update(
                    array('orgjednotka_id_predano' => $orgjednotka_id),
                    array(array('id=%i', $spis_id))
            );
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function zrusitPredani($spis_id)
    {

        if (empty($spis_id))
            return false;

        try {
            $this->update(
                    array('orgjednotka_id_predano' => null), array(array('id=%i', $spis_id))
            );
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public static function spisovyZnak($spis, $simple = 0)
    {

        $client_config = Nette\Environment::getVariable('client_config');
        if (!isset($client_config->spisovy_znak)) {
            $maska = ".";
            $cifernik = 3;
        } else {
            $maska = $client_config->spisovy_znak->oddelovac;
            if ($client_config->spisovy_znak->pocatecni_nuly == 1) {
                $cifernik = $client_config->spisovy_znak->pocet_znaku;
            } else {
                $cifernik = 0;
            }
        }

        if (is_string($spis)) {
            $simple = 0;
            $spis_tmp = $spis;
            $spis = new stdClass();
            $spis->spisovy_znak_plneurceny = $spis_tmp;
        }

        if ($simple == 1) {
            // spisovy znak
            return sprintf('%0' . $cifernik . 'd', $spis->spisovy_znak);
        } else if ($simple == 2) {
            if (empty($spis->spisovy_znak_plneurceny)) {
                return "";
            } else {
                return $spis->spisovy_znak_plneurceny . ".";
            }
        } else {
            // plneurceny spisovy znak
            if (empty($spis->spisovy_znak_plneurceny)) {
                return "";
            } else {
                $part_in = explode(".", $spis->spisovy_znak_plneurceny);
                $part_out = array();
                foreach ($part_in as $part_index => $part_value) {
                    $part_out[$part_index] = sprintf('%0' . $cifernik . 'd', $part_value);
                }
                return implode($maska, $part_out);
            }
        }
    }

    public function kontrolaSpisovyZnak($spisovy_znak, $spis_parent_id)
    {

        // zjistime rodice
        $spis_parent = $this->getInfo($spis_parent_id);

        // sestavime plneurceny spisovy znak
        $spisovy_znak_plneurceny = $spis_parent->spisovy_znak_plneurceny . '.' . $spisovy_znak;

        // vyhledame, zda existuje
        $spis_exist = $this->select(array(array("spisovy_znak_plneurceny=%s", $spisovy_znak_plneurceny)),
                null, null, 1);
        $spis_exist_fetch = $spis_exist->fetch();

        if (!$spis_exist_fetch) {
            return true;
        } else {
            return false;
        }
    }

    public function kontrolaSpisovyZnakPublic($spisovy_znak, $spis_id)
    {

        // zjistime rodice
        $spis = $this->getInfo($spis_id);
        if ($spis) {
            $spis_parent = $this->getInfo($spis->parent_id);
            if ($spis_parent) {
                // sestavime plneurceny spisovy znak
                $spisovy_znak_plneurceny = $spis_parent->spisovy_znak_plneurceny . '.' . $spisovy_znak;

                // vyhledame, zda existuje
                $spis_exist = $this->select(array(array("spisovy_znak_plneurceny=%s", $spisovy_znak_plneurceny)),
                        null, null, 1);
                $spis_exist_fetch = $spis_exist->fetch();

                if (!$spis_exist_fetch) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function predatDoSpisovny($spis_id)
    {
        $spis_info = $this->getInfo($spis_id);

        // Test na uplnost dat
        if ($kontrola = $this->kontrola($spis_info)) {
            // nejsou kompletni data - neprenasim
            $res = [];
            foreach ($kontrola as $zprava)
                $res[] = "Spis \"$spis_info->nazev\" - $zprava";
            // $res[] = 'Spis ' . $spis_info->nazev . ' nelze přenést do spisovny! Nejsou vyřízeny všechny potřebné údaje.';
            return $res;
        }

        // Kontrola stavu
        if ($spis_info->stav !== self::UZAVREN) {
            return ['Spis "' . $spis_info->nazev . '" nelze přenést do spisovny! Spis není uzavřen.'];
        }

        // Kontrola vlozenych dokumentu (musi byt vyrizene)
        if ($kontrola = $this->kontrolaVyrizeniDokumentu($spis_info)) {
            $res = [];
            foreach ($kontrola as $zprava)
                $res[] = "Spis \"$spis_info->nazev\" - $zprava";
            // $res[] = 'Spis ' . $spis_info->nazev . ' nelze přenést do spisovny! Jeden nebo více dokumentů spisu nejsou vyřízeny.';
            return $res;
        }

        // Prenest vsechny dokumenty do spisovny spolu se spisem
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($spis_id);

        dibi::begin();
        try {
            if (count($dokumenty) > 0) {
                $Workflow = new Workflow();
                foreach ($dokumenty as $dok) {
                    $stav = $Workflow->predatDoSpisovny($dok->id, true);
                    if ($stav !== true)
                        throw new Exception($stav);
                }
            }

            // Predat do spisovny
            $result = $this->zmenitStav($spis_id, self::PREDAN_DO_SPISOVNY);
            if (!$result)
                throw new Exception();

            dibi::commit();
            return true;
        } catch (Exception $e) {
            dibi::rollback();
            $msg = $e->getMessage();
            if (!$msg)
                $msg = 'Došlo k neznámé chybě.';
            return [$msg];
        }
    }

    public function pripojitDoSpisovny($spis_id)
    {

        // kontrola uzivatele
        $spis_info = $this->getInfo($spis_id);

        //echo "<pre>"; print_r($dokument_info); echo "</pre>"; exit;
        // Test na uplnost dat
        if ($kontrola = $this->kontrola($spis_info)) {
            // nejsou kompletni data - neprenasim
            return 'Spis ' . $spis_info->nazev . ' nelze připojit do spisovny! Nejsou vyřízeny všechny potřebné údaje.';
        }

        // Kontrola stavu - uzavren = krome 1
        if ($spis_info->stav != 2) {
            return 'Spis ' . $spis_info->nazev . ' nelze připojit do spisovny! Spis nebyl předán do spisovny.';
        }

        // Kontrola kompletnosti vlozenych dokumentu (musi byt vyrizene)
        if ($kontrola = $this->kontrolaVyrizeniDokumentu($spis_info)) {
            // nejsou kompletni - neprenasim
            foreach ($kontrola as $kmess) {
                Nette\Environment::getApplication()->getPresenter()->flashMessage('Spis ' . $spis_info->nazev . ' - ' . $kmess,
                        'warning');
            }
            return 'Spis ' . $spis_info->nazev . ' nelze připojit do spisovny! Jeden nebo více dokumentů spisu nejsou vyřízeny.';
        }

        // Pripojit vsechny dokumenty do spisovny spolu se spisem
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($spis_id);
        if (count($dokumenty) > 0) {
            $Workflow = new Workflow();
            foreach ($dokumenty as $dok) {
                $stav = $Workflow->prevzitDoSpisovny($dok->id, false);
                if ($stav === true) {
                    
                } else {
                    if (is_string($stav)) {
                        Nette\Environment::getApplication()->getPresenter()->flashMessage($stav,
                                'warning');
                    }
                }
            }
        }

        // Pripojit do spisovny
        $result = $this->zmenitStav($spis_id, self::VE_SPISOVNE);
        return (bool) $result;
    }

    /**
     * @param DibiRow $spis
     * @return array|null
     */
    public function kontrola($spis)
    {
        $mess = array();
        if (empty($spis->nazev))
            $mess[] = "Název spisu nemůže být prázdný.";
        if (empty($spis->spisovy_znak_id))
            $mess[] = "Spisový znak nemůže být prázdný.";
        if (empty($spis->skartacni_znak))
            $mess[] = "Skartační znak nemůže být prázdný.";
        if ($spis->skartacni_lhuta === null)
            $mess[] = "Skartační lhůta musí být zadána.";

        return $mess ? : null;
    }

    /**
     * @param int $spis_id
     * @return boolean
     */
    public static function lzePredatDoSpisovny($spis_id)
    {
        $model = new static;
        $spis = $model->getInfo($spis_id);
        return $model->kontrola($spis) === null;
    }

    /**
     * @param int|DibiRow $data
     * @return array|null
     */
    public function kontrolaVyrizeniDokumentu($data)
    {
        $mess = array();

        if (is_numeric($data)) {
            // $data je id
            $data = $this->getInfo($data);
        }

        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($data->id);

        if (count($dokumenty) > 0)
            foreach ($dokumenty as $dok)
                if ($dok->stav_dokumentu < 5)
                    $mess[] = "Spis \"" . $data->nazev . "\" - Dokument \"" . $dok->cislo_jednaci . "\" není vyřízen.";

        return $mess ? : null;
    }

    public static function stav($stav = null)
    {

        $stav_array = array('1' => 'otevřen',
            '0' => 'uzavřen'/* spisovka */,
            '2' => 'uzavřen a předán do spisovny'/* spisovna */,
            '3' => 'uzavřen ve spisovně',
            '4' => 'zápůjčka',
            '5' => 'archivován',
            '6' => 'skartován'
        );

        if (is_null($stav)) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array) ? $stav_array[$stav] : null;
        }
    }

    public function deleteAll()
    {

        $DokumentSpis = new DokumentSpis();
        $DokumentSpis->deleteAll();

        $LogModel = new LogModel();
        $LogModel->deleteAllSpis();

        parent::deleteAll();
    }

    // $spis - informace, ktere vratilo getInfo
    public static function zjistiOpravneniUzivatele($spis)
    {

        $user = Nette\Environment::getUser();
        $user_id = $user->getIdentity()->id;
        $oj_uzivatele = OrgJednotka::dejOrgUzivatele();
        $Lze_cist = $Lze_menit = $Lze_prevzit = false;

        if ($oj_uzivatele === null)
            $org_jednotky = array();
        else if ($user->isAllowed(NULL, 'is_vedouci'))
            $org_jednotky = Orgjednotka::childOrg($oj_uzivatele);
        else
            $org_jednotky = array($oj_uzivatele);

        // prideleno
        if (in_array($spis->orgjednotka_id, $org_jednotky)) {
            $Lze_menit = true;
            $Lze_cist = true;
        }
        // predano
        if ($oj_uzivatele !== null && $spis->orgjednotka_id_predano == $oj_uzivatele) {
            $Lze_prevzit = true;
            $Lze_cist = true;
        }

        // Oprava ticket #194
        // Mohou nastat situace, kdy spis nema zadneho vlastnika, napr. po migraci ze spisovky 2
        // V tom pripade musi byt videt seznam dokumentu ve spisu
        if (!$spis->orgjednotka_id && !$spis->orgjednotka_id_predano)
            $Lze_cist = 1;

        if ($user->isAllowed('Dokument', 'cist_vse'))
            $Lze_cist = 1;

        if (count($spis->workflow) > 0) {
            $org_cache = array();
            foreach ($spis->workflow as $wf) {

                if (isset($org_cache[$wf->orgjednotka_id])) {
                    $orgjednotka_expr = $org_cache[$wf->orgjednotka_id];
                } else {
                    $orgjednotka_expr = Orgjednotka::isInOrg($wf->orgjednotka_id);
                    $org_cache[$wf->orgjednotka_id] = $orgjednotka_expr;
                }

                if (!$Lze_cist)
                    if (($wf->prideleno_id == $user_id || $orgjednotka_expr) && $wf->stav_osoby < 100) {
                        // uzivatel vlastnil nejaky dokument ve spisu v minulosti
                        $Lze_cist = 1;
                    }

                if (!$Lze_menit)
                    if (($wf->prideleno_id == $user_id || $orgjednotka_expr) && ($wf->stav_osoby == 1 && $wf->aktivni == 1 )) {
                        // uzivatel vlastni nejaky dokument ve spisu ted
                        $Lze_menit = 1;
                    }

                if (!$Lze_prevzit)
                    if (($wf->prideleno_id == $user_id || $orgjednotka_expr) && ($wf->stav_osoby == 0 && $wf->aktivni == 1 )) {
                        $Lze_prevzit = 1;
                    }

                if ($Lze_prevzit && $Lze_menit && $Lze_cist) {
                    break;
                }
            }
        }

        // 2 = predan do spisovny
        if ($spis->stav == 2)
            $Lze_menit = false;

        if ($user->isInRole('superadmin')) {
            $Lze_cist = 1;
            $Lze_menit = 1;
        }

        return array('lze_cist' => $Lze_cist,
            'lze_menit' => $Lze_menit,
            'lze_prevzit' => $Lze_prevzit);
    }

    /**
     *  Hledá ve spisech podle zadaného filtru
     * @param string $title  část názvu spisu
     * @param string $filter    spisovka|admin
     * @return DibiRow[]
     */
    public function search($title, $filter)
    {   
        $args = [["nazev LIKE %s", "%$title%"]];
        $user = \Nette\Environment::getUser();
        $admin = $filter == 'admin' && $user->isAllowed('Admin_SpisyPresenter');
        if (!$admin) {
            $args[] = 'stav = ' . self::OTEVREN;
            $args = $this->spisovka($args);
        }

        $res = dibi::query('SELECT id, nazev as text FROM %n as tb WHERE %and ORDER by nazev',
                        $this->name, $args);
        return $res->fetchAll();
    }

    /**
     * Cisluje podstatne jmeno spis.
     * @param int $pocet
     */
    public static function cislovat($pocet)
    {
        if ($pocet == 1)
            return 'spis';
        if ($pocet >= 2 && $pocet <= 4)
            return 'spisy';
        return 'spisů';
    }

}
