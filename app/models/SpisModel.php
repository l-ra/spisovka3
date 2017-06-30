<?php

namespace Spisovka;

class SpisModel extends TreeModel
{

    const OTEVREN = 1;
    const UZAVREN = 0;
    const PREDAN_DO_SPISOVNY = 2;
    const VE_SPISOVNE = 3;

    protected $name = 'spis';

    // Vrátí první spis z daným názvem, protože bohužel není zaručeno, že název spisu bude jedinečný
    public function findByName($spis_name)
    {
        $result = $this->select(array(array('nazev=%s', $spis_name)));
        $row = $result->fetch();
        return $row ? $row : null;
    }

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
                'on' => array('org1.id = tb.orgjednotka_id'),
                'cols' => array('zkraceny_nazev' => 'orgjednotka_prideleno')
            ),
            'orgjednotka2' => array(
                'from' => array($this->tb_orgjednotka => 'org2'),
                'on' => array('org2.id = tb.orgjednotka_id_predano'),
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

    public function poctyDokumentu(array $spis_ids)
    {
        $result = dibi::query('SELECT [spis_id], COUNT(*) AS pocet FROM %n'
                        . ' WHERE [spis_id] IN %in GROUP BY [spis_id]', Document::TBL_NAME,
                        $spis_ids);
        if (!$result)
            return null;

        return $result->fetchPairs();
    }

    private function omezeni_org(array $filter)
    {
        $user = self::getUser();
        $oj_id = OrgJednotka::dejOrgUzivatele();

        if ($user->isAllowed('Dokument', 'cist_vse'))
            ; // vsechny spisy bez ohledu na organizacni jednotku
        else if ($oj_id === null)
            $filter[] = array("0");
        else {
            if ($user->isVedouci())
                $org_jednotky = OrgJednotka::childOrg($oj_id);
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
        $filter[] = "tb.typ = 'F' OR tb.stav <= " . self::PREDAN_DO_SPISOVNY;
        return $this->omezeni_org($filter);
    }

    public function spisovna(array $filter = [])
    {
        $filter[] = "tb.typ = 'F' OR tb.stav = " . self::VE_SPISOVNE;
        return $filter;
    }

    public function spisovna_prijem(array $filter = [])
    {
        $filter[] = "tb.typ = 'F' OR tb.stav = " . self::PREDAN_DO_SPISOVNY;
        return $filter;
    }

    public function vytvorit($data)
    {
        $data['date_created'] = new \DateTime();
        $data['user_created'] = self::getUser()->id;
        if (isset($data['typ']) && $data['typ'] == 'F')
            $data['orgjednotka_id'] = null; // slozky nemaji vlastnika
        else
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
        $data['date_modified'] = new \DateTime();
        $data['user_modified'] = self::getUser()->id;

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

    public static function stav($stav = null)
    {
        $stav_array = array(
            self::OTEVREN => 'otevřen',
            self::UZAVREN => 'uzavřen',
            self::PREDAN_DO_SPISOVNY => 'uzavřen a předán do spisovny',
            self::VE_SPISOVNE => 've spisovně',
        );

        if (is_null($stav)) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array) ? $stav_array[$stav] : null;
        }
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
        $user = self::getUser();
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
