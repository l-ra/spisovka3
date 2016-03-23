<?php

class OrgJednotka extends TreeModel
{

    protected $name = 'orgjednotka';
    protected $column_name = 'zkraceny_nazev';
    protected $column_ordering = 'zkraceny_nazev';

    /**
     * @param int $orgjednotka_id
     * @return \OrgUnit
     */
    public function getInfo($orgjednotka_id)
    {
        return new OrgUnit($orgjednotka_id);
    }

    public static function getName($id)
    {
        $o = new OrgUnit($id);
        return $o->zkraceny_nazev;
    }

    /**
     * @return DibiResult
     */
    public function seznam($args = null)
    {
        $params = null;
        if (!empty($args)) {
            $params['where'] = $args;
        }

        return $this->nacti($params);
    }

    /**
     * @return DibiRow[]
     */
    public function linearniSeznam()
    {
        $result = $this->nacti(['order' => 'ciselna_rada',
                    'where' => ['stav != 0']
                ])->fetchAll();

        return $result ? : array();
    }

    public function ulozit($data, $orgjednotka_id)
    {
        if (!isset($data['parent_id']))
            $data['parent_id'] = null;
        if (empty($data['parent_id']))
            $data['parent_id'] = null;
        if (!empty($data['stav']))
            $data['stav'] = (int) $data['stav'];

        $this->upravitH($data, $orgjednotka_id);
        
        // je nutne ulozenim vynutit smazani polozky v cache
        $o = new OrgUnit($orgjednotka_id);
        $o->date_modified = new DateTime();
        $o->user_modified = self::getUser()->id;
        $o->save();
    }

    public function vytvorit($data)
    {
        $data['date_created'] = new DateTime();
        $data['user_created'] =  self::getUser()->id;
        $data['date_modified'] = new DateTime();
        $data['user_modified'] =  self::getUser()->id;
        $data['stav'] = 1;

        if (!isset($data['parent_id']))
            $data['parent_id'] = null;
        if (empty($data['parent_id']))
            $data['parent_id'] = null;

        $orgjednotka_id = $this->vlozitH($data);        
        return $orgjednotka_id;
    }

    public function deleteAllOrg()
    {

        $Workflow = new Workflow();
        $Workflow->update(array('orgjednotka_id' => null), array('orgjednotka_id IS NOT NULL'));

        $CJ = new CisloJednaci();
        $CJ->update(array('orgjednotka_id' => null), array('orgjednotka_id IS NOT NULL'));

        parent::deleteAll();
    }

    // Tato funkce je pouzita pro kontrolu pristupu k dokumentum a spisum
    // Testuje, zda ma uzivatel pravo k urcene org. jednotce
    // TODO: Metoda potrebuje prejmenovat
    // TODO: Testovat na opravneni vedouciho
    public static function isInOrg($orgjednotka_id)
    {
        if (!$orgjednotka_id)
            return false;

        // omezeni, ze uzivatel muze byt jen v jedne o.j.
        $oj_uzivatele = self::dejOrgUzivatele();
        if ($oj_uzivatele === false)
            return false;

        return ($oj_uzivatele === $orgjednotka_id) && self::getUser()->isAllowed('Dokument',
                        'cist_moje_oj');
    }

    public static function childOrg($orgjednotka_id)
    {
        if (empty($orgjednotka_id))
            return null;

        $org = array();
        $org[] = $orgjednotka_id;

        $OrgJednotka = new OrgJednotka();
        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
        if ($org_info) {
            $fetch = $OrgJednotka->select([['sekvence LIKE %s', $org_info->sekvence . '.%'], ['sekvence']]);
            $result = $fetch->fetchAll();
            if (count($result) > 0) {
                foreach ($result as $res) {
                    $org[] = $res->id;
                }
            }
        }

        return $org;
    }

    // Vrátí id org. jednotky aktuálního/zvoleného uživatele
    // nebo null, neni-li uzivatel zarazen do zadne jednotky nebo kdyz nema identitu
    public static function dejOrgUzivatele($user_id = null)
    {
        if ($user_id === null) {
            $user = self::getUser();

            // zjisti z databáze
            $user_id = $user->id;
            if ($user_id === null)
            // nepřihlášený uživatel
                return null;
        }

        $user = new UserAccount($user_id);
        return $user->orgjednotka_id;
    }

}
