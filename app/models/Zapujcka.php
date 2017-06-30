<?php

namespace Spisovka;

class Zapujcka extends BaseModel
{

    const STAV_NESCHVALENA = 1;
    const STAV_SCHVALENA = 2;
    const STAV_ODMITNUTA = 4;
    const STAV_VRACENA = 3;

    protected $name = 'zapujcka';

    /**
     * Seznam zapujcek
     * @return DibiResult
     */
    public function seznam($args = array())
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
            'from' => array($this->name => 'z'),
            'cols' => array('*'),
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
            )
        );

        $result = $this->selectComplex($sql);
        return $result;
    }

    /**
     * Vrátí seznam dokumentů, které jsou zapůjčeny nebo na ně existuje žádanka.
     * @return array
     */
    public function seznamZapujcenychDokumentu()
    {
        $seznam = array();
        $result = $this->select(['stav = ' . self::STAV_NESCHVALENA . ' OR stav = ' . self::STAV_SCHVALENA])->fetchAll();
        if ($result)
            foreach ($result as $zapujcka) {
                $seznam[$zapujcka->dokument_id] = $zapujcka->stav;
            }

        return $seznam;
    }

    public function hledat($query, $args)
    {
        if (!$args)
            $args = [];
        $args['where_or'] = [['d.nazev LIKE %s', "%$query%"],
            ['d.popis LIKE %s', "%$query%"],
            ['d.cislo_jednaci LIKE %s', "%$query%"],
            ['d.jid LIKE %s', "%$query%"]
        ];

        return $args;
    }

//    public function seradit(&$args, $typ = null)
//    {
//        switch ($typ) {
//            case 'stav':
//                $args['order'] = array('z.stav');
//                break;
//            case 'stav_desc':
//                $args['order'] = array('z.stav' => 'DESC');
//                break;
//            case 'cj':
//                $args['order'] = array('d.podaci_denik_rok', 'd.podaci_denik_poradi', 'd.poradi');
//                break;
//            case 'cj_desc':
//                $args['order'] = array('d.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC', 'd.poradi' => 'DESC');
//                break;
//            case 'vec':
//                $args['order'] = array('d.nazev');
//                break;
//            case 'vec_desc':
//                $args['order'] = array('d.nazev' => 'DESC');
//                break;
//            case 'od':
//                $args['order'] = array('z.date_od');
//                break;
//            case 'od_desc':
//                $args['order'] = array('z.date_od' => 'DESC');
//                break;
//            case 'do':
//                $args['order'] = array('z.date_do');
//                break;
//            case 'do_desc':
//                $args['order'] = array('z.date_do' => 'DESC');
//                break;
//        }
//        
//        return $args;
//    }

    public function filtr($nazev)
    {
        switch ($nazev) {
            case 'aktualni':
                $args = array(
                    'where' => array('z.stav < ' . self::STAV_VRACENA),
                    'order_by' => array('z.stav', 'z.date_created')
                );
                break;
            case 'zapujcene':
                $args = array(
                    'where' => array('z.stav = ' . self::STAV_SCHVALENA),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'ke_schvaleni':
                $args = array(
                    'where' => array('z.stav = ' . self::STAV_NESCHVALENA),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'vracene':
                $args = array(
                    'where' => array('z.stav = ' . self::STAV_VRACENA),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'odmitnute':
                $args = array(
                    'where' => array('z.stav = ' . self::STAV_ODMITNUTA),
                    'order_by' => array('z.date_created')
                );
                break;
            case 'vse':
            default:
                $args = null;
                break;
        }

        return $args;
    }

    public function getInfo($zapujcka_id)
    {
        $sql = array(
            'from' => array($this->name => 'z'),
            'cols' => array('*'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=z.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                )
            )
        );
        $sql['where'] = array(array('z.id = %i', $zapujcka_id));

        $select = $this->selectComplex($sql);
        $result = $select->fetch();
        if ($result) {
            $result->person = Person::fromUserId($result->user_id);
            return $result;
        }

        return null;
    }

    public function getFromDokumentId($dokument_id)
    {
        $sql = array(
            'from' => array($this->name => 'z'),
            'cols' => array('*'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=z.dokument_id'),
                    'cols' => array('nazev', 'popis', 'cislo_jednaci', 'jid', 'poradi')
                )
            )
        );
        $sql['where'] = array(array('z.dokument_id = %i AND z.stav = ' . self::STAV_SCHVALENA, $dokument_id));

        $select = $this->selectComplex($sql);
        $result = $select->fetch();
        if ($result) {
            $result->person = Person::fromUserId($result->user_id);
            return $result;
        }

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

    public function vytvorit($data)
    {
        unset($data['user_text'], $data['dokument_text']);

        if (is_null($data))
            return false;

        $user = self::getUser();
        $user_id = $user->id;
        $data['date_created'] = new \DateTime();
        $data['stav'] = self::STAV_NESCHVALENA;

        // pracovnik spisovny pujcuje dokument jinym uzivatelum,
        // obycejny pracovnik zada o pujceni svym jmenem
        $pracovnik_spisovny = $user->isAllowed('Zapujcka', 'schvalit');
        if (!$pracovnik_spisovny)
            $data['user_id'] = $user_id;

        $zapujcka_id = $this->insert($data);
        if ($pracovnik_spisovny)
            $this->schvalit($zapujcka_id);

        return $zapujcka_id;
    }

    public function osobni($args)
    {
        $user = self::getUser();

        if (isset($args['where'])) {
            $args['where'][] = array(array('z.user_id = %i AND z.stav < ' . self::STAV_VRACENA, $user->id));
        } else {
            $args['where'] = array(array(array('z.user_id = %i AND z.stav < ' . self::STAV_VRACENA, $user->id)));
        }

        return $args;
    }

    public function schvalit($zapujcka_id)
    {
        try {
            $z = new Loan($zapujcka_id);
            $doc = new DocumentWorkflow($z->dokument_id);
            $doc->lend($z->user_id);

            $z->stav = self::STAV_SCHVALENA;
            $z->save();

            return true;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function odmitnout($zapujcka_id)
    {
        try {
            $z = new Loan($zapujcka_id);
            if ($z->stav != self::STAV_NESCHVALENA)
                return false;

            $z->stav = self::STAV_ODMITNUTA;
            $z->date_do_skut = new \DateTime();
            $z->save();
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function vratit($zapujcka_id)
    {
        $z = new Loan($zapujcka_id);
        $doc = new DocumentWorkflow($z->dokument_id);
        $doc->returnToSpisovna();

        $z->stav = self::STAV_VRACENA;
        $z->date_do_skut = new \DateTime();
        $z->save();
    }

    /**
     *  Vrati ciselny stav posledni zapucky nebo 0, pokud zapujcka neexistuje
     */
    public function stavZapujcky($dokument_id)
    {
        $result = dibi::query("SELECT [stav] FROM %n", $this->name,
                        "WHERE [dokument_id] = %i ORDER BY [id] DESC", $dokument_id);
        $stav = $result->fetch();
        return $stav === false ? 0 : $stav->stav;
    }

    public static function stav($stav = null)
    {
        $stavy = array(self::STAV_NESCHVALENA => 'čeká na schválení',
            self::STAV_SCHVALENA => 'zapůjčena',
            self::STAV_VRACENA => 'vrácena',
            self::STAV_ODMITNUTA => 'odmítnuta'
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
