<?php

class ISDS_Spisovka extends ISDS
{
    /* Funkce vrati, zda povolit vice schranek v aplikaci.
      Pripraveno jako potencionalni moznost do budoucna. */

    public static function vice_datovych_schranek()
    {
        return Settings::get('isds_allow_more_boxes', false);
    }

    public function __construct()
    {
        // nastav parametry tridy ISDS
        $this->ssl_verify_peer = Settings::get('isds_ssl_verify_peer', true);
        $log_level = Settings::get('isds_log_level', 0);
        if ($log_level)
            $this->logger = new ISDS_Logger($log_level);
        
        $ep_config = (new Spisovka\ConfigEpodatelna())->get();
        $ep_config = $ep_config['isds'];
        $config = current($ep_config);
        if ($config === false) // existuje nejake nastaveni?
            throw new InvalidArgumentException("ISDS_Spisovka::pripojit() - Není definována datová schránka.");

        $isds_portaltype = ($config['test'] == 1) ? 0 : 1;

        $individual_login = Settings::get(Admin_EpodatelnaPresenter::ISDS_INDIVIDUAL_LOGIN,
                        false);
        if ($individual_login) {
            $login = UserSettings::get('isds_login');
            $password = UserSettings::get('isds_password');
            if (!$login || !$password)
                throw new Exception('Uživatel nemá vyplněny přihlašovací údaje do datové schránky.');

            parent::__construct($isds_portaltype, 0, $login, $password);
        } else if ($config['typ_pripojeni'] == 0) {
            // jmenem a heslem
            parent::__construct($isds_portaltype, 0, $config['login'], $config['password']);
        } else if ($config['typ_pripojeni'] == 1) {
            // certifikatem
            if (file_exists($config['certifikat'])) {
                parent::__construct($isds_portaltype, 1, "", "", $config['certifikat'],
                        $config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new Nette\FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
            }
        } else if ($config['typ_pripojeni'] == 2) {
            // certifikatem + jmenem a heslem
            if (file_exists($config['certifikat'])) {
                parent::__construct($isds_portaltype, 2, $config['login'], $config['password'],
                        $config['certifikat'], $config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new Nette\FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
            }
        } else
            throw new Exception("Chyba nastavení ISDS! - Nespecifikovaná chyba.");

        return true;
    }

    public function seznamPrijatychZprav($od, $do)
    {
        $od = date("c", $od);
        $do = date("c", $do);

        $Records = $this->GetListOfReceivedMessages($od, $do, 0, 10000, 1023, null); // 1023
        return $Records ? $Records->dmRecord : null;
    }

    public function seznamOdeslanychZprav($od, $do)
    {
        $od = date("c", $od);
        $do = date("c", $do);

        $Records = $this->GetListOfSentMessages($od, $do, 0, 10000, 1023, null);
        return $Records ? $Records->dmRecord : null;
    }

    public function odeslatZpravu($zprava, $prilohy)
    {
        // je parametr platny
        if (empty($zprava)) {
            throw new InvalidArgumentException("Prázdný či neplatný parametr!");
        }

        // Komu
        $komu = $zprava['dbIDRecipient']; // Identifikace adresata
        if (empty($komu)) {
            throw new InvalidArgumentException("Není k dispozici adresát!");
        }

        // nacteni zpravy
        $dmEnvelope = array(
            'dbIDRecipient' => $komu,
            'dmRecipientOrgUnit' => "",
            'dmRecipientOrgUnitNum' => -1,
            'dmRecipientRefNumber' => empty($zprava['vase_cj']) ? null : $zprava['vase_cj'],
            'dmRecipientIdent' => empty($zprava['vase_sznak']) ? null : $zprava['vase_sznak'],
            'dmSenderRefNumber' => empty($zprava['cislo_jednaci']) ? null : $zprava['cislo_jednaci'],
            'dmSenderOrgUnit' => '',
            'dmSenderOrgUnitNum' => -1,
            'dmSenderIdent' => empty($zprava['spisovy_znak']) ? null : $zprava['spisovy_znak'],
            'dmToHands' => empty($zprava['k_rukam']) ? null : $zprava['k_rukam'],
            'dmAnnotation' => empty($zprava['anotace']) ? null : $zprava['anotace'],
            'dmLegalTitleLaw' => null,
            'dmLegalTitleYear' => null,
            'dmLegalTitleSect' => null,
            'dmLegalTitlePar' => null,
            'dmLegalTitlePoint' => null,
            'dmPersonalDelivery' => null,
            'dmAllowSubstDelivery' => null
        );

        // Nacteni priloh
        if (!empty($prilohy)) {
            if (count($prilohy) > 0) {
                $SentOutFiles = new ISDSSentOutFiles();
                foreach ($prilohy as $priloha) {
                    if (empty($priloha->mime_type)) {
                        $mime_type = FileModel::mimeType($priloha->tmp_file);
                    } else {
                        $mime_type = $priloha->mime_type;
                    }

                    // funkce pro odesílání do registru smluv
                    if (in_array($priloha->real_name,
                                    ['zverejneni.xml', 'modifikace.xml', 'pridani_prilohy.xml', 'znepristupneni.xml']))
                        $metatype = 'main';
                    else
                        $metatype = 'enclosure';

                    $SentOutFiles->AddFileSpecFromFile(
                            $priloha->tmp_file, $mime_type, $metatype, $priloha->guid, "",
                            $priloha->real_name, "");
                }
                $dmFiles = $SentOutFiles->fileInfos();
            } else {
                $dmFiles = null;
            }
        } else {
            $dmFiles = null;
        }

        $MessageCreateInput = array(
            'dmEnvelope' => $dmEnvelope,
            'dmFiles' => $dmFiles
        );

        $MessageCreateOutput = $this->OperationsWS()->CreateMessage($MessageCreateInput);

        if (isset($MessageCreateOutput->dmID)) {
            $MessageID = $MessageCreateOutput->dmID;
            return $MessageID;
        }
        return false;
    }

    public static function stavDS($dbState)
    {
        $typ = array(
            '0' => 'Nelze zjístit stav datové schránky',
            '1' => 'DS je přístupná',
            '2' => 'DS je dočasně znepřístupněná',
            '3' => 'DS je dosud neaktivní',
            '4' => 'DS je trvale znepřístupněna',
            '5' => 'DS je smazána'
        );

        if (array_key_exists($dbState, $typ)) {
            return $typ[$dbState];
        } else {
            return null;
        }
    }

    public static function typDS($dmType)
    {
        $typ = array(
            '0' => 'Nelze zjístit typ subjektu datové schránky',
            '10' => 'OVM',
            '11' => 'OVM_NOTAR',
            '12' => 'OVM_EXEKUT',
            '13' => 'OVM_REQ',
            '20' => 'PO',
            '21' => 'PO_ZAK',
            '22' => 'PO_REQ',
            '30' => 'PFO',
            '31' => 'PFO_ADVOK',
            '32' => 'PFO_DANPOR',
            '33' => 'PFO_INSSPR',
            '40' => 'FO'
        );

        if (array_key_exists($dmType, $typ)) {
            return $typ[$dmType];
        } else {
            return null;
        }
    }

    public static function stavZpravy($dmMessageStatus)
    {
        $typ = array(
            '0' => 'Nelze zjístit stav zprávy',
            '1' => 'zpráva byla podána',
            '2' => 'hash datové zprávy včetně všech písemnosti označen časovým razítkem',
            '3' => 'zpráva neprošla AV kontrolou; nakažená písemnost je smazána; konečný stav zprávy',
            '4' => 'zpráva dodána do ISDS (zapsán čas dodání)',
            '5' => 'veřejná zpráva byla doručena fikcí (uplynutím 10 dnů od dodání)',
            '6' => 'veřejná zpráva byla doručena přihlášením, komerční zpráva pomocí ComfirmDelivery',
            '7' => 'zpráva byla přečtená (na portále nebo akcí ESS)',
            '8' => 'zpráva byla označená jako nedoručitelná (DS byla zpětně znepřístupněna)',
            '9' => 'obsah zprávy byl smazán',
            '10' => 'zpráva je v Datovém trezoru'
        );

        if (array_key_exists($dmMessageStatus, $typ)) {
            return $typ[$dmMessageStatus];
        } else {
            return null;
        }
    }

    /**
     *
     * @param string $addr
     * @return array
     */
    public static function parseAddress($addr)
    {
        $ret = [];
        $matches = [];
        if (preg_match('/(.*), ([0-9]*) (.*), (.*)/', $addr, $matches)) {
            $ret['adresa_ulice'] = $matches[1];
            $ret['adresa_psc'] = $matches[2];
            $ret['adresa_mesto'] = $matches[3];
            // Bohuzel spisovka pouziva 3 znakove kody statu, ne 2 jako DS.
            // Ale vsechny datove schranky by mely byt v CR.
            // $ret['adresa_stat'] = $matches[4];
            if (preg_match('#(.*) ([\d]*)/([\d]*)#', $ret['adresa_ulice'], $matches)) {
                $ret['adresa_ulice'] = $matches[1];
                $ret['adresa_cp'] = $matches[2];
                $ret['adresa_co'] = $matches[3];
            }
        } else
            $ret['adresa_ulice'] = $addr;

        return $ret;
    }

    /**
     * Vyhledani datove schranky
     *
     * dbOwnerInfo = array(
     *       dbID, dbType, dbState,
     *       pnFirstName, pnMiddleName, pnLastName, pnLastNameAtBirth,
     *       ic, firmName,
     *       biDate, biCity, biCounty, biState,
     *       adCity, adStreet, adNumberInStreet, adNumberInMunicipality
     *       adZipCode, adState, nationality,
     *       email, telNumber
     * )
     *
     * @param array     $filtr      pole obsahujici hodnoty dbOwnerInfo
     * @return dbResults
     *
     */
    public function FindDataBoxEx($filtr)
    {
        $OwnerInfo = array(
            'dbID' => (!empty($filtr['dbID']) ? $filtr['dbID'] : null),
            'dbType' => (!empty($filtr['dbType']) ? $filtr['dbType'] : null),
            'dbState' => (!empty($filtr['dbState']) ? $filtr['dbState'] : null),
            'ic' => (!empty($filtr['ic']) ? $filtr['ic'] : null),
            'pnFirstName' => (!empty($filtr['pnFirstName']) ? $filtr['pnFirstName'] : null),
            'pnMiddleName' => (!empty($filtr['pnMiddleName']) ? $filtr['pnMiddleName'] : null),
            'pnLastName' => (!empty($filtr['pnLastName']) ? $filtr['pnLastName'] : null),
            'pnLastNameAtBirth' => (!empty($filtr['pnLastNameAtBirth']) ? $filtr['pnLastNameAtBirth']
                        : null),
            'firmName' => (!empty($filtr['firmName']) ? $filtr['firmName'] : null),
            'biDate' => (!empty($filtr['biDate']) ? $filtr['biDate'] : null),
            'biCity' => (!empty($filtr['biCity']) ? $filtr['biCity'] : null),
            'biCounty' => (!empty($filtr['biCounty']) ? $filtr['biCounty'] : null),
            'biState' => (!empty($filtr['biState']) ? $filtr['biState'] : null),
            'adCity' => (!empty($filtr['adCity']) ? $filtr['adCity'] : null),
            'adStreet' => (!empty($filtr['adStreet']) ? $filtr['adStreet'] : null),
            'adNumberInStreet' => (!empty($filtr['adNumberInStreet']) ? $filtr['adNumberInStreet']
                        : null),
            'adNumberInMunicipality' => (!empty($filtr['adNumberInMunicipality']) ? $filtr['adNumberInMunicipality']
                        : null),
            'adZipCode' => (!empty($filtr['adZipCode']) ? $filtr['adZipCode'] : null),
            'adState' => (!empty($filtr['adState']) ? $filtr['adState'] : null),
            'nationality' => (!empty($filtr['nationality']) ? $filtr['nationality'] : null),
            'email' => (!empty($filtr['email']) ? $filtr['email'] : null),
            'telNumber' => (!empty($filtr['telNumber']) ? $filtr['telNumber'] : null)
        );

        $FindInput = array('dbOwnerInfo' => $OwnerInfo);

        $FindOutput = $this->ManipulationsWS()->FindDataBox($FindInput);
        if (!empty($FindOutput->dbResults->dbOwnerInfo)) {
            $FindOutput->dbResults->dbOwnerInfo = $this->PrepareArray($FindOutput->dbResults->dbOwnerInfo);
            return $FindOutput->dbResults;
        } else {
            return null;
        }
    }

}
