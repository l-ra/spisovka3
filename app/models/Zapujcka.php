<?php

//netteloader=Zapujcka

class Zapujcka extends BaseModel
{

    protected $name = 'zapujcka';
    protected $primary = 'id';

    /**
     * Seznam zapujcek
     *
     */
    public function seznam($args = array(), $detail = 0)
    {

        if (isset($args['where'])) {
            $where = $args['where'];
        } else {
            $where = null;
        }
        if (isset($args['where_or'])) {
            $where_or = $args['where_or'];
        } else {
            $where_or = null;
        }

        if (isset($args['order'])) {
            $order = $args['order'];
        } else {
            //$order = array('dokument_id'=>'DESC');
            $order = array('z.date_od' => 'DESC', 'z.date_do' => 'DESC');
        }
        if (isset($args['limit'])) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }
        if (isset($args['offset'])) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }

        $sql = array(
            'distinct' => 1,
            'from' => array($this->name => 'z'),
            'cols' => array('*', '%sqlCASE WHEN z.date_od IS NULL THEN 1 ELSE 0 END' => 'casovy_stav'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=z.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                ),
                'prideleno_user' => array(
                    'from' => array($this->tb_osoba_to_user => 'ou'),
                    'on' => array('ou.user_id=z.user_id'),
                    'cols' => null
                ),
                'prideleno_osoba' => array(
                    'from' => array($this->tb_osoba => 'o'),
                    'on' => array('o.id=ou.osoba_id'),
                    'cols' => array('jmeno' => 'osoba_jmeno', 'prijmeni' => 'osoba_prijmeni', 'titul_pred' => 'osoba_titul_pred', 'titul_za' => 'osoba_titul_za')
                ),
            )
        );

        if (isset($args['leftJoin'])) {
            $sql['leftJoin'] = array_merge($sql['leftJoin'], $args['leftJoin']);
        }

        $select = $this->selectComplex($sql);

        if ($detail == 1) {
            // return array(DibiRow)
            $result = $select->fetchAll();
            if (count($result) > 0) {
                $tmp = array();
                foreach ($result as $index => $row) {
                    $zapujcka = new stdClass();
                    $tmp[$row->id] = $zapujcka;
                }
                return $tmp;
            } else {
                return null;
            }
        } else {
            // return DibiResult
            return $select;
        }
    }

    public function aktivniSeznam()
    {
        $seznam = array();
        $result = $this->select(array('stav=1 OR stav=2'))->fetchAll();
        if (count($result) > 0) {
            foreach ($result as $zapujcka) {
                $seznam[$zapujcka->dokument_id] = $zapujcka->dokument_id;
            }
        }
        return $seznam;
    }

    public function hledat($query)
    {

        $args = array(
            'where_or' => array(
                array('d.nazev LIKE %s', '%' . $query . '%'),
                array('d.popis LIKE %s', '%' . $query . '%'),
                array('d.cislo_jednaci LIKE %s', '%' . $query . '%'),
                array('d.jid LIKE %s', '%' . $query . '%')
            )
        );
        return $args;
    }

    public function seradit(&$args, $typ = null)
    {

        switch ($typ) {
            case 'stav':
                $args['order'] = array('z.stav');
                break;
            case 'stav_desc':
                $args['order'] = array('z.stav' => 'DESC');
                break;
            case 'cj':
                $args['order'] = array('d.podaci_denik_rok', 'd.podaci_denik_poradi', 'd.poradi');
                break;
            case 'cj_desc':
                $args['order'] = array('d.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC', 'd.poradi' => 'DESC');
                break;
            case 'vec':
                $args['order'] = array('d.nazev');
                break;
            case 'vec_desc':
                $args['order'] = array('d.nazev' => 'DESC');
                break;
            case 'od':
                $args['order'] = array('z.date_od');
                break;
            case 'od_desc':
                $args['order'] = array('z.date_od' => 'DESC');
                break;
            case 'do':
                $args['order'] = array('z.date_do');
                break;
            case 'do_desc':
                $args['order'] = array('z.date_do' => 'DESC');
                break;
            case 'prideleno':
                //$args['order'] = array('podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
                break;
            default:
                break;
        }
        return $args;
    }

    public function filtr($nazev)
    {

        switch ($nazev) {
            case 'aktualni':
                $args = array(
                    'where' => array('z.stav < 3'),
                    'order_by' => array('z.stav', 'z.date_created')
                );
                break;
            case 'zapujcene':
                $args = array(
                    'where' => array('z.stav = 2'),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'ke_schvaleni':
                $args = array(
                    'where' => array('z.stav = 1'),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'vracene':
                $args = array(
                    'where' => array('z.stav = 3'),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'odmitnute':
                $args = array(
                    'where' => array('z.stav = 4'),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'vse':
                $args = null;
                break;
            default:
                $args = null;
                break;
        }

        return $args;
    }

    public function getInfo($zapujcka_id)
    {

        $sql = array(
            'distinct' => 1,
            'from' => array($this->name => 'z'),
            'cols' => array('*'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=z.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                ),
                'prideleno_user' => array(
                    'from' => array($this->tb_osoba_to_user => 'ou'),
                    'on' => array('ou.user_id=z.user_id'),
                    'cols' => null
                ),
                'prideleno_osoba' => array(
                    'from' => array($this->tb_osoba => 'o'),
                    'on' => array('o.id=ou.osoba_id'),
                    'cols' => array('jmeno' => 'osoba_jmeno', 'prijmeni' => 'osoba_prijmeni', 'titul_pred' => 'osoba_titul_pred', 'titul_za' => 'osoba_titul_za')
                ),
            )
        );
        $sql['where'] = array(array('z.id=%i', $zapujcka_id));

        $select = $this->selectComplex($sql);
        $result = $select->fetch();
        if ($result)
            return $result;

        return null;
    }

    public function getDokumentID($zapujcka_id)
    {

        $sql = array(
            'distinct' => 1,
            'from' => array($this->name => 'z'),
            'cols' => array('dokument_id')
        );
        $sql['where'] = array(array('z.id=%i', $zapujcka_id));

        $select = $this->selectComplex($sql);
        $result = $select->fetch();
        if ($result) {
            return $result->dokument_id;
        } else {
            return null;
        }
    }

    public function getDokument($dokument_id)
    {

        $sql = array(
            'distinct' => 1,
            'from' => array($this->name => 'z'),
            'cols' => array('*'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=z.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                ),
                'prideleno_user' => array(
                    'from' => array($this->tb_osoba_to_user => 'ou'),
                    'on' => array('ou.user_id=z.user_id'),
                    'cols' => null
                ),
                'prideleno_osoba' => array(
                    'from' => array($this->tb_osoba => 'o'),
                    'on' => array('o.id=ou.osoba_id'),
                    'cols' => array('jmeno' => 'osoba_jmeno', 'prijmeni' => 'osoba_prijmeni', 'titul_pred' => 'osoba_titul_pred', 'titul_za' => 'osoba_titul_za')
                ),
            )
        );
        $sql['where'] = array(array('z.dokument_id=%i AND z.stav=2', $dokument_id));

        $select = $this->selectComplex($sql);
        $result = $select->fetch();
        if ($result)
            return $result;

        return null;
    }

    public function getBasicInfo($zapujcka_id)
    {

        $where = array(array('id=%i', $zapujcka_id));
        $limit = 1;

        $select = $this->select($where, null, null, $limit);
        $result = $select->fetch();

        return $result;
    }

    public function ulozit($data, $zapujcka_id = null)
    {

        $is_in_role = isset($data['is_in_role']) ? 1 : null;
        unset($data['user_text'], $data['dokument_text'], $data['is_in_role']);

        if (is_null($data)) {
            return false;
        } else if (is_null($zapujcka_id)) {
            // nova zapujcka

            $data['user_vytvoril_id'] = Nette\Environment::getUser()->getIdentity()->id;
            $data['date_created'] = new DateTime();

            if ($is_in_role) {
                // prijat a schvalen
                $data['stav'] = 2;
                $data['user_schvalil_id'] = Nette\Environment::getUser()->getIdentity()->id;
                $data['date_schvaleni'] = new DateTime();
            } else {
                // prijat
                $data['stav'] = 1;
            }

            $zapujcka_id = $this->insert($data);

            // Zaneseni do logu a dokumentu

            return $zapujcka_id;
        } else {
            // uprava existujici zapujcky
            unset($data['id'], $data['user_id'], $data['dokument_id'], $data['date_od']);
            $update = $this->update($update_data,
                    array(
                array('id=%i', $zapujcka_id)
                    )
            );
            return $update;
        }
    }

    public function osobni($args)
    {

        $user = Nette\Environment::getUser()->getIdentity();

        if (isset($args['where'])) {
            $args['where'][] = array(array('z.user_id = %i AND z.stav < 3', $user->id));
        } else {
            $args['where'] = array(array(array('z.user_id = %i AND z.stav < 3', $user->id)));
        }

        return $args;
    }

    public function schvalit($zapujcka_id)
    {

        if (empty($zapujcka_id) || !is_numeric($zapujcka_id))
            return null;

        $data = array(
            'stav' => 2,
            'user_schvalil_id' => Nette\Environment::getUser()->getIdentity()->id,
            'date_schvaleni' => new DateTime()
        );

        try {
            $update = $this->update($data, array(array('id=%i', $zapujcka_id)));

            $Workflow = new Workflow();
            $zapujcka_info = $this->getInfo($zapujcka_id);
            $Workflow->zapujcka_pridelit($zapujcka_info->dokument_id, $zapujcka_info->user_id);

            return true;
        } catch (DibiException $e) {
            echo $e->getMessage();
            echo $e->getSql();
            return false;
        }
    }

    public function odmitnout($zapujcka_id)
    {

        $date = new DateTime();
        $data = array(
            'stav' => 4,
            'user_schvalil_id' => Nette\Environment::getUser()->getIdentity()->id,
            'date_schvaleni' => $date,
            'date_do_skut' => $date,
        );

        try {
            $update = $this->update($data, array(array('id=%i', $zapujcka_id)));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function vraceno($zapujcka_id, $datum_vraceni)
    {

        $date = new DateTime();
        $data = array(
            'stav' => 3,
            'date_do_skut' => $datum_vraceni,
        );
        try {
            $update = $this->update($data, array(array('id=%i', $zapujcka_id)));
            $Workflow = new Workflow();
            $dokument_id = $this->getDokumentID($zapujcka_id);
            $Workflow->zapujcka_vratit($dokument_id,
                    Nette\Environment::getUser()->getIdentity()->id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function zmenitStav($data)
    {

        if (is_array($data)) {
            $zapujcka_id = $data['id'];
            unset($data['id']);
            $this->update($data, array(array('id=%i', $zapujcka_id)));
            return true;
        } else {
            return false;
        }
    }

    public static function stav($stav = null)
    {

        $stavy = array('1' => 'čeká na schválení',
            '2' => 'zapůjčena',
            '3' => 'vrácena',
            '4' => 'odmítnuta'
        );

        if (is_null($stav)) {
            return $stavy;
        } else if (!is_numeric($stav)) {
            return null;
        }

        $index = ($stav >= 100) ? $stav - 100 : $stav;
        if (isset($stavy[$index])) {
            return $stavy[$index];
        } else {
            return null;
        }
    }

}
