<?php

class LogModel extends BaseModel {

    protected $name = 'log_system';


    const DOK_UNDEFINED = 00;

    const DOK_NOVY      = 11;
    const DOK_ZMENEN    = 12;
    const DOK_SMAZAN    = 13;
    const DOK_PREDAN    = 14;
    const DOK_PRIJAT    = 15;
    const DOK_KVYRIZENI = 16;
    const DOK_VYRIZEN   = 17;
    const DOK_PRIJATEP  = 18;
    const DOK_PREDODESLAN = 20;
    const DOK_ODESLAN   = 19;
    const DOK_NEODESLAN   = 107;
    const DOK_SPUSTEN   = 101;
    const DOK_KESKARTACI = 102;
    const DOK_SKARTOVAN  = 103;
    const DOK_ARCHIVOVAN = 104;
    const DOK_SPISOVNA_PREDAN = 105;
    const DOK_SPISOVNA_PRIPOJEN = 106;

    const SUBJEKT_VYTVOREN = 21;
    const SUBJEKT_ZMENEN   = 22;
    const SUBJEKT_SMAZAN   = 23;
    const SUBJEKT_PRIDAN   = 24;
    const SUBJEKT_ODEBRAN  = 25;

    const PRILOHA_VYTVORENA = 31;
    const PRILOHA_ZMENENA   = 32;
    const PRILOHA_SMAZANA   = 33;
    const PRILOHA_PRIDANA   = 34;
    const PRILOHA_ODEBRANA  = 35;

    const SPIS_VYTVOREN     = 41;
    const SPIS_ZMENEN       = 42;
    const SPIS_SMAZAN       = 43;
    const SPIS_DOK_PRIPOJEN = 44;
    const SPIS_DOK_ODEBRAN  = 45;
    
    const ZAPUJCKA_VYTVORENA = 51;
    const ZAPUJCKA_SCHVALENA = 52;
    const ZAPUJCKA_PRIDELENA = 53;
    const ZAPUJCKA_VRACENA   = 54;
    const ZAPUJCKA_ODMITNUTA = 55;    


    protected static $typy = array(
        '00' => 'Nedefinovaná činnost',
        '11' => 'Vytvořen nový dokument',
        '12' => 'Dokument změněn',
        '13' => 'Dokument smazán',
        '14' => 'Dokument předán',
        '15' => 'Dokument převzat',
        '16' => 'Dokument označen k vyřízení',
        '17' => 'Dokument vyřízen',
        '18' => 'Dokument přijat e-podatelnou',
        '19' => 'Dokument odeslán',
        '20' => 'Dokument předán k odeslání',
        '107' => 'Dokument byl vracen (nedošlo k odeslání)',
        '101' => 'Spuštěna událost',
        '102' => 'Dokument připraven ke skartaci',
        '103' => 'Dokument skartován',
        '104' => 'Dokument archivován',
        '105' => 'Dokument předán do spisovny',
        '106' => 'Dokument umístěn ve spisovně',

        '21' => 'Vytvořen nový subjekt',
        '22' => 'Subjekt změněn',
        '23' => 'Subjekt smazán',
        '24' => 'Subjekt přidán k dokumentu',
        '25' => 'Subjekt odebrán z dokumentu',
        '31' => 'Vytvořena nová příloha',
        '32' => 'Příloha změněna',
        '33' => 'Příloha smazána',
        '34' => 'Příloha připojena k dokumentu',
        '35' => 'Příloha odebrána z dokumentu',
        '41' => 'Vytvořen nový spis',
        '42' => 'Spis změněn',
        '43' => 'Spis smazán',
        '44' => 'Dokument připojen ke spisu',
        '45' => 'Dokument odebrán ze spisu',
        '51' => 'Zápůjčka vytvořena',
        '52' => 'Zápůjčka schválena',
        '53' => 'Dokument zapůjčen',
        '54' => 'Zapůjčený dokument vracen',
        '55' => 'Zápůjčka odmítnuta'        

    );

    /* ***********************************************************************
     * Logovani aktivity dokumentu
     */

    public function logDokument($dokument_id, $typ, $poznamka = "") {

        $row = array();
        $row['dokument_id'] = (int) $dokument_id;
        $row['typ'] = $typ;
        $row['poznamka'] = $poznamka;
        
        $user = Environment::getUser()->getIdentity();
        $row['user_id'] = $user->id;
        $row['date'] = new DateTime();

        return dibi::insert($this->tb_logdokument, $row)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);

    }

    public function historieDokumentu($dokument_id = null, $limit = 50, $offset = 0) {

        $res = dibi::query(
            'SELECT * FROM %n ld', $this->tb_logdokument,
            'LEFT JOIN %n ou', $this->tb_osoba_to_user ,'ON ou.user_id=ld.user_id',
            'LEFT JOIN %n o', $this->tb_osoba ,'ON o.id=ou.osoba_id',
            '%if', !is_null($dokument_id), 'WHERE %and', !is_null($dokument_id) ? array(array('ld.dokument_id=%i',$dokument_id)) : array(), '%end',
            'ORDER BY ld.date'
        );
        return $res->fetchAll($offset, $limit);

    }

    /* ***********************************************************************
     * Logovani pristupu
     */

    public function logAccess($user_id,$stav=1) {

        $row = array();
        $row['user_id'] = $user_id;
        $row['date'] = new DateTime();
        $row['ip'] = Environment::getHttpRequest()->getRemoteAddress();
        $row['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $row['stav'] = $stav;

        return dibi::insert($this->tb_logaccess, $row)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);

    }

    public function seznamPristupu($limit = 50, $offset = 0, $user_id = null) {

        $res = dibi::query(
            'SELECT * FROM %n la', $this->tb_logaccess,
            'LEFT JOIN %n',$this->tb_user,' u ON (u.id=la.user_id)',
            '%if', !is_null($user_id), 'WHERE %and', !is_null($user_id) ? array('la.user_id=%i',$user_id) : array(), '%end',
            'ORDER BY la.id DESC'
        );
        return $res->fetchAll($offset, $limit);

    }

    public static function typ($typ) {

        return (isset(self::$typy[$typ]))?self::$typy[$typ]:"";
        
        
    }

    public function  deleteAllDokument() {
        return dibi::query('TRUNCATE ['.$this->tb_logdokument.'];');
    }

    public function  deleteDokument( $dokument_id ) {
        return dibi::delete($this->tb_logdokument)->where(array(array('dokument_id=%i',$dokument_id)))->execute();
    }


}