<?php

class Osoba extends BaseModel
{

    protected $name = 'osoba';
    protected $primary = 'id';

    public function getInfo($osoba_id)
    {

        $result = $this->select(array(array('id=%i', $osoba_id)));
        $row = $result->fetch();
        return ($row) ? $row : NULL;
    }

    // Vrati pole uzivatelskych uctu osoby. Indexem je user_id.
    public function getUser($osoba_id, $active = 0)
    {

        if ($active == 1) {
            $rows = dibi::fetchAll('SELECT u.*
                               FROM [:PREFIX:' . self::OSOBA2USER_TABLE . '] ou
                                LEFT JOIN [:PREFIX:' . self::USER_TABLE . '] u ON (u.id = ou.user_id)
                                WHERE ou.osoba_id=%i', $osoba_id, ' AND ou.active=1');
        } else {
            $rows = dibi::fetchAll('SELECT u.*
                                FROM [:PREFIX:' . self::OSOBA2USER_TABLE . '] ou
                                LEFT JOIN [:PREFIX:' . self::USER_TABLE . '] u ON (u.id = ou.user_id)
                                WHERE ou.osoba_id=%i', $osoba_id);
        }


        $res = array();
        foreach ($rows as $row) {
            $res[$row->id] = $row;
        }

        return $res ? : null;
    }

    public function seznam($args = null)
    {

        $select = $this->select($args, array('prijmeni', 'jmeno'));
        return $select;
    }

    public function ulozit($data, $osoba_id = null)
    {

        $user_id = Nette\Environment::getUser()->getIdentity()->id;
        if (empty($user_id))
            $user_id = 1;

        if (!is_null($osoba_id)) {

            // ulozit do historie
            $old_data = (array) $this->getInfo($osoba_id);
            $old_data['osoba_id'] = $osoba_id;
            $old_data['user_created'] = $user_id;
            $old_data['date_created'] = new DateTime();
            unset($old_data['id'], $old_data['user_modified'], $old_data['date_modified']);
            $OsobaHistorie = new OsobaHistorie();
            $OsobaHistorie->insert($old_data);

            // aktualizovat
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = $user_id;
            $this->update($data, array(array('id = %i', $osoba_id)));
        } else {

            // insert
            $data['date_created'] = new DateTime();
            $data['user_created'] = $user_id;
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = $user_id;
            $data['stav'] = 0;
            $osoba_id = $this->insert($data);
        }

        if ($osoba_id) {
            return $osoba_id;
        } else {
            return false;
        }
    }

    public static function displayName($data, $display = 'full')
    {

        if (is_string($data))
            return $data;
        if (is_array($data)) {
            $tmp = new stdClass();
            $tmp->jmeno = $data['jmeno'];
            $tmp->prijmeni = $data['prijmeni'];
            $tmp->titul_pred = $data['titul_pred'];
            $tmp->titul_za = $data['titul_za'];
            $data = $tmp;
            unset($tmp);
        }
        if (!is_object($data))
            return "";

        if (isset($data->osoba_prijmeni)) {
            $tmp = new stdClass();
            $tmp->jmeno = $data->osoba_jmeno;
            $tmp->prijmeni = $data->osoba_prijmeni;
            $tmp->titul_pred = $data->osoba_titul_pred;
            $tmp->titul_za = $data->osoba_titul_za;
            $data = $tmp;
            unset($tmp);
        }

        if (isset($data->user_prijmeni) && $display == 'user') {
            $tmp = new stdClass();
            $tmp->jmeno = $data->user_jmeno;
            $tmp->prijmeni = $data->user_prijmeni;
            $tmp->titul_pred = $data->user_titul_pred;
            $tmp->titul_za = $data->user_titul_za;
            $data = $tmp;
            unset($tmp);
        }

        // Sestaveni prvku z jmena

        $titul_pred = "";
        $titul_pred_item = "";
        if (isset($data->titul_pred)) {
            if (!empty($data->titul_pred)) {
                $titul_pred = $data->titul_pred . " ";
                $titul_pred_item = ", " . $data->titul_pred;
            }
        }

        $jmeno = "";
        if (isset($data->jmeno)) {
            if (!empty($data->jmeno)) {
                $jmeno = $data->jmeno;
            }
        }

        $prijmeni = "";
        if (isset($data->prijmeni)) {
            if (!empty($data->prijmeni)) {
                $prijmeni = $data->prijmeni;
            }
        }

        $titul_za = "";
        if (isset($data->titul_za)) {
            if (!empty($data->titul_za)) {
                $titul_za = ', ' . $data->titul_za;
            }
        }

        $jmeno_prijmeni = $jmeno ? "$jmeno $prijmeni" : $prijmeni;
        $prijmeni_jmeno = $jmeno ? "$prijmeni $jmeno" : $prijmeni;

        // Sestaveni jmena
        switch ($display) {
            case 'full':
            default:
                return "$titul_pred$jmeno_prijmeni$titul_za";
            case 'basic':
                return $jmeno_prijmeni;
            case 'full_item':
                return $prijmeni_jmeno . $titul_pred_item . $titul_za;
            case 'basic_item':
                return $prijmeni_jmeno;
            case 'last_name':
                return $prijmeni;
        }
    }

    public function deleteAll()
    {

        $OsobaHistorie = new OsobaHistorie();
        $OsobaHistorie->deleteAll();

        $Osoba2User = new Osoba2User();
        $Osoba2User->deleteAll();

        parent::deleteAll();
    }

}

class Osoba2User extends BaseModel
{

    protected $name = 'osoba_to_user';

    public function seznam()
    {

        $result = dibi::query('SELECT ou.osoba_id, ou.user_id, o.*, u.username, sq.pocet_uctu' .
                        ' FROM %n ou', $this->name, ' JOIN %n o', $this->osoba,
                        'ON o.id=ou.osoba_id' .
                        ' JOIN %n u', $this->tb_user,
                        'ON u.id=ou.user_id' .
                        ' JOIN (SELECT osoba_id, COUNT(*) as pocet_uctu FROM %n', $this->name,
                        ' WHERE active=1 GROUP BY osoba_id) AS sq ON o.id = sq.osoba_id' .
                        ' WHERE ou.active=1' .
                        ' ORDER BY o.prijmeni, o.jmeno'
        );

        return $result->fetchAll();
    }

    public function hledat($text)
    {

        $result = dibi::query('SELECT ou.osoba_id, ou.user_id, o.*, u.username, sq.pocet_uctu' .
                        ' FROM %n ou', $this->name, ' JOIN %n o', $this->osoba,
                        'ON o.id=ou.osoba_id' .
                        ' JOIN %n u', $this->tb_user,
                        'ON u.id=ou.user_id' .
                        ' JOIN (SELECT osoba_id, COUNT(*) as pocet_uctu FROM %n', $this->name,
                        ' WHERE active=1 GROUP BY osoba_id) AS sq ON o.id = sq.osoba_id' .
                        " WHERE ou.active=1 AND" .
                        " ( LOWER(CONCAT(o.jmeno,' ',o.prijmeni)) LIKE LOWER(%s)", "%$text%",
                        " OR LOWER(CONCAT(o.prijmeni,' ',o.jmeno)) LIKE LOWER(%s)", "%$text%",
                        " ) " .
                        ' ORDER BY o.prijmeni, o.jmeno'
        );

        return $result->fetchAll();
    }

}

class OsobaHistorie extends BaseModel
{

    protected $name = 'osoba_historie';
    protected $primary = 'id';

}
