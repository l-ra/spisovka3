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

    public function seznamOsobSUcty($search = null)
    {
        $where = ['u.active = 1'];
        if ($search)
            $where[] = ["CONCAT(o.jmeno,' ',o.prijmeni) LIKE %s", "%$search%"];

        $result = dibi::query('SELECT u.osoba_id, u.id AS user_id, o.*, u.username, sq.pocet_uctu' .
                        ' FROM %n o JOIN %n u ON o.id = u.osoba_id' .
                        ' JOIN (SELECT osoba_id, COUNT(*) AS pocet_uctu FROM %n WHERE active = 1 GROUP BY osoba_id) AS sq ON o.id = sq.osoba_id' .
                        ' WHERE %and' .
                        ' ORDER BY o.prijmeni, o.jmeno', $this->name, $this->tb_user,
                        $this->tb_user, $where
        );

        return $result->fetchAll();
    }

    public function hledat($search)
    {
        return $this->seznamOsobSUcty($search);
    }

    /**
     * @param object|array $data
     * @param string $display
     * @return string
     */
    public static function displayName($data, $display = 'full')
    {
        if (is_string($data))
            return $data;
        if (is_array($data))
            $data = (object)$data;
        if (!is_object($data))
            return "";

        $titul_pred = $titul_pred_item = "";
        if (isset($data->titul_pred) && !empty($data->titul_pred)) {
            $titul_pred = $data->titul_pred . " ";
            $titul_pred_item = ", " . $data->titul_pred;
        }

        $jmeno = "";
        if (isset($data->jmeno) && !empty($data->jmeno))
            $jmeno = $data->jmeno;

        $prijmeni = "";
        if (isset($data->prijmeni) && !empty($data->prijmeni))
            $prijmeni = $data->prijmeni;

        $titul_za = "";
        if (isset($data->titul_za) && !empty($data->titul_za))
            $titul_za = ', ' . $data->titul_za;

        $jmeno_prijmeni = $jmeno ? "$jmeno $prijmeni" : $prijmeni;
        $prijmeni_jmeno = $jmeno ? "$prijmeni $jmeno" : $prijmeni;

        switch ($display) {
            case 'full':
            default:
                return "$titul_pred$jmeno_prijmeni$titul_za";
            case 'basic':
                return $jmeno_prijmeni;
            case 'full_item':
                return $prijmeni_jmeno . $titul_pred_item . $titul_za;
            /* case 'basic_item':
              return $prijmeni_jmeno; */
        }
    }

    /* public function deleteAll()
      {
      $Osoba2User = new Osoba2User();
      $Osoba2User->deleteAll();

      parent::deleteAll();
      } */
}
