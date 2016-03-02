<?php

class Osoba extends BaseModel
{

    protected $name = 'osoba';

    /**
     * @return DibiResult
     */
    public function seznam($args = null)
    {
        $select = $this->select($args, array('prijmeni', 'jmeno'));
        return $select;
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

        if ($data instanceof DibiRow && isset($data->osoba_prijmeni)) {
            $tmp = new stdClass();
            $tmp->jmeno = $data->osoba_jmeno;
            $tmp->prijmeni = $data->osoba_prijmeni;
            $tmp->titul_pred = $data->osoba_titul_pred;
            $tmp->titul_za = $data->osoba_titul_za;
            $data = $tmp;
            unset($tmp);
        }

        if ($data instanceof DibiRow && isset($data->user_prijmeni) && $display == 'user') {
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

    /* public function deleteAll()
      {
      $Osoba2User = new Osoba2User();
      $Osoba2User->deleteAll();

      parent::deleteAll();
      } */
}

class Osoba2User extends BaseModel
{

    protected $name = 'osoba_to_user';

    public function seznam()
    {

        $result = dibi::query('SELECT ou.osoba_id, ou.user_id, o.*, u.username, sq.pocet_uctu' .
                        ' FROM %n ou', $this->name, ' JOIN %n o', $this->tb_osoba,
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
                        ' FROM %n ou', $this->name, ' JOIN %n o', $this->tb_osoba,
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
