<?php

class Osoba extends BaseModel
{

    protected $name = 'osoba';
    protected $primary = 'osoba_id';
    
    
    public function getInfo($osoba_id)
    {

        $result = $this->fetchRow(array('osoba_id=%i',$osoba_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function getUser($osoba_id)
    {

        $rows = dibi::fetchAll('SELECT u.*
                            FROM [:PREFIX:' . self::OSOBA2USER_TABLE . '] ou
                            LEFT JOIN [:PREFIX:'. self::USER_TABLE .'] u ON (u.user_id = ou.user_id)
                            WHERE ou.osoba_id=%i',$osoba_id);

        $tmp = array();
        foreach ($rows as $row) {
            $tmp[ $row->user_id ] = $row;
        }
        $rows = $tmp;

        return ($rows) ? $rows : NULL;

    }

    public function seznam($args = null)
    {
        $args = func_get_args();

        $select = $this->fetchAll(array('prijmeni','jmeno'));

        return $select;

        //$rows = $select->fetchAll();
        //return ($rows) ? $rows : NULL;

    }

    public static function displayName($data, $display = 'full')
    {

        if ( is_string($data) ) return $data;
        if ( is_array($data) ) {
            $tmp = new stdClass();
            $tmp->jmeno = $data['jmeno'];
            $tmp->prijmeni = $data['prijmeni'];
            $tmp->titul_pred = $data['titul_pred'];
            $tmp->titul_za = $data['titul_za'];
            $data = $tmp;
            unset($tmp);
        }
        if ( !is_object($data) ) return "";

        // Sestaveni prvku z jmena

        $titul_pred = "";
        $titul_pred_item = "";
        if ( isset( $data->titul_pred ) ) {
            if ( !empty( $data->titul_pred ) ) {
                $titul_pred = $data->titul_pred ." ";
                $titul_pred_item = ", ". $data->titul_pred;
            }
        }

        $jmeno = "";
        if ( isset( $data->jmeno ) ) {
            if ( !empty( $data->jmeno ) ) {
                $jmeno = $data->jmeno;
            }
        }

        $prijmeni = "";
        if ( isset( $data->prijmeni ) ) {
            if ( !empty( $data->prijmeni ) ) {
                $prijmeni = $data->prijmeni;
            }
        }

        $titul_za = "";
        if ( isset( $data->titul_za ) ) {
            if ( !empty( $data->titul_za ) ) {
                $titul_za = ', '. $data->titul_za;
            }
        }

        // Sestaveni jmena
        switch ($display) {
            case 'full':
                return $titul_pred . $jmeno ." ". $prijmeni . $titul_za;
                break;
            case 'basic':
                return $jmeno ." ". $prijmeni;
                break;
            case 'full_item':
                return $prijmeni ." ". $jmeno . $titul_pred_item . $titul_za;
                break;
            case 'basic_item':
                return $prijmeni ." ". $jmeno;
                break;
            case 'last_name':
                return $prijmeni;
                break;
            default:
                return $titul_pred . $jmeno ." ". $prijmeni . $titul_za;
                break;
        }


    }

    
}

class Osoba2User extends BaseModel
{
    protected $name = 'osoba_to_user';
    protected $user_to_role = 'user_to_role';
    protected $osoba = 'osoba';
    protected $tbl_role = 'user_role';

    public function  __construct() {
        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->user_to_role = $prefix . $this->user_to_role;
        $this->osoba = $prefix . $this->osoba;
        $this->tbl_role = $prefix . $this->tbl_role;
    }
    
    public function seznam($aktivni = 1) {


        $result = dibi::query('SELECT ou.osoba_id, ou.user_id, r.role_id, o.*, r.code, r.name, r.orgjednotka_id
                               FROM %n ou',$this->name,
                              ' LEFT JOIN %n ur',$this->user_to_role,'ON ur.user_id=ou.user_id'.
                              ' LEFT JOIN %n o',$this->osoba,'ON o.osoba_id=ou.osoba_id'.
                              ' LEFT JOIN %n r',$this->tbl_role,'ON r.role_id=ur.role_id'.
                              ' WHERE ou.active=1'.
                              ' ORDER BY o.prijmeni, o.jmeno, r.name'
                );

        return $result->fetchAll();


    }

}
