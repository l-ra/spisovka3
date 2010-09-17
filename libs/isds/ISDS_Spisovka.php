<?php


class ISDS_Spisovka extends ISDS {

    private $ISDSBox;
    private $config;

    public function __construct() {
        
    }

    public function pripojit($params = null) {

        if ( is_array($params) ) {
            // prime hodnoty
            if ( isset($params['typ_pripojeni']) ) {
                $config = $params;
            } else {
                throw new InvalidArgumentException("Neplatné pole parametru.");
                return false;
            }
        } else if ( is_object($params) ) {
            // prime hodnoty
            if ( isset($params->typ_pripojeni) ) {
                $config = $params->toArray();
            } else {
                throw new InvalidArgumentException("Neplatný objekt parametru.");
                return false;
            }
        } else {
            
            $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
            $ep_config = $ep_config->toArray();
            $ep_config = $ep_config['isds'];
            
            if ( is_numeric($params) ) { // parametrem je index z nastaveni
                if ( isset( $ep_config[$params] ) ) { // existuje takove nastaveni?
                    $config = $ep_config[ $params ];
                } else {
                    throw new InvalidArgumentException("Požadované nastavení neexistuje.");
                    return false;
                }
            } else { // zadny parametr
                if ( isset( $ep_config[0] ) ) { // existuje nejake nastaveni?
                    $config = $ep_config[0];
                } else {
                    throw new InvalidArgumentException("Nastavení pro ISDS neexistuje.");
                    return false;
                }
            }
        }

        //$this->


        $isds_portaltype = ($config['test']==1)?0:1;
        $this->config = $config;

        if ( $config['typ_pripojeni'] == 0 ) {
            // jmenem a heslem
            $this->ISDSBox = $this->ISDSBox($isds_portaltype, 0,$config['login'],$config['password'],"","");
        } else if ( $config['typ_pripojeni'] == 1 ) {
            // certifikatem
            if ( file_exist($config['certifikat']) ) {
                $this->ISDSBox = $this->ISDSBox($isds_portaltype, 1,"","",$config['certifikat'],$config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
                return false;
            }
        } else if ( $config['typ_pripojeni'] == 2 ) {
            // certifikatem + jmenem a heslem
            if ( file_exist($config['certifikat']) ) {
                $this->ISDSBox = $this->ISDSBox($isds_portaltype, 2,$config['login'],$config['password'],$config['certifikat'],$config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
                return false;
            }
        } else {
            throw new Exception("Chyba nastavení ISDS! - Nespecifikovaná chyba.");
            return false;
        }
        
        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == "")) {
            return $this->ISDSBox;
        } else {
            throw new Exception("Chyba ISDS: ".$this->StatusCode." - ".$this->ErrorInfo);
            return false;
        }
    }

    public function informaceDS($idDS=null) {

        if ($idDS != null) {
            $Results = $this->FindDataBox($idDS,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null);
            if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
            {
                return $Results->dbOwnerInfo[0];
            } else {
                return null;
            }
        } else {
            $Result = $this->GetOwnerInfoFromLogin();
            if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == ""))
            {
                return $Result;
            } else {
                return null;
            }
        }
    }

    public function seznamPrichozichZprav($od=null,$do=null) {

        if($od==null) $od = time() - (86400*90); // od poslednich 90 dni
        if($do==null) $do = time();
        $od = $this->unix2time($od);
        $do = $this->unix2time($do);

        $Records = $this->GetListOfReceivedMessages($od,$do,0,10000,1023,null); // 1023

        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == ""))
        {
            return $Records->dmRecord;
        } else {
            return null;
        }

    }

    function seznamOdeslanychZprav($od=null,$do=null) {

        if($od==null) $od = time() - (86400*90);
        if($do==null) $do = time();
        $od = $this->unix2time($od);
        $do = $this->unix2time($do);

        $Records = $this->GetListOfSentMessages($od,$do,0,10000,1023,null);

        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == ""))
        {
            return $Records->dmRecord;
            //return $Records;
        } else {
            return null;
        }

    }

    public function prectiZpravu($id_zpravy) {

        $Message = $this->MessageDownload($id_zpravy);
        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == "")) {
            return $Message;
        } else {
            return null;
        }
    }

    public function odeslatZpravu($zprava, $prilohy) {

        // je parametr platny
        if ( empty($zprava) ) {
            throw new InvalidArgumentException("Prázdný či neplatný parametr!");
            return false;
        }

        // Komu
        $komu = $zprava['dbIDRecipient'];// Identifikace adresata
        if ( empty($komu) ) {
            throw new InvalidArgumentException("Není k dispozici adresát!");
            return false;
        }

        // nacteni zpravy
        $dmEnvelope = array(
                'dbIDRecipient' => $komu,
                'dmRecipientOrgUnit' => "",
                'dmRecipientOrgUnitNum' => -1,
                'dmRecipientRefNumber' => empty($zprava['vase_cj'])?null:$zprava['vase_cj'],
                'dmRecipientIdent' => empty($zprava['vase_sznak'])?null:$zprava['vase_sznak'],

                'dmSenderRefNumber' => empty($zprava['cislo_jednaci'])?null:$zprava['cislo_jednaci'],
                'dmSenderOrgUnit' => '',
                'dmSenderOrgUnitNum' => -1,
                'dmSenderIdent' => empty($zprava['spisovy_znak'])?null:$zprava['spisovy_znak'],

                'dmToHands' => empty($zprava['k_rukam'])?null:$zprava['k_rukam'],
                'dmAnnotation' => empty($zprava['anotace'])?null:$zprava['anotace'],

                'dmLegalTitleLaw' => empty($zprava['zmocneni_law'])?null:$zprava['zmocneni_law'],
                'dmLegalTitleYear' => empty($zprava['zmocneni_year'])?null:$zprava['zmocneni_year'],
                'dmLegalTitleSect' => empty($zprava['zmocneni_sect'])?null:$zprava['zmocneni_sect'],
                'dmLegalTitlePar' => empty($zprava['zmocneni_par'])?null:$zprava['zmocneni_par'],
                'dmLegalTitlePoint' => empty($zprava['zmocneni_point'])?null:$zprava['zmocneni_point'],
                'dmPersonalDelivery' => empty($zprava['do_vlastnich'])?null:$zprava['do_vlastnich'],
                'dmAllowSubstDelivery' => empty($zprava['doruceni_fikci'])?null:$zprava['doruceni_fikci']
            );

        // Nacteni priloh
        if ( !empty($prilohy) ) {
            if ( count($prilohy)>0 ) {
                $SentOutFiles = new ISDSSentOutFiles();
                foreach ($prilohy as $priloha) {

                    if ( empty($priloha->mime_type) ) {
                        $mime_type = FileModel::mimeType($priloha->tmp_file);
                    } else {
                        $mime_type = $priloha->mime_type;
                    }

                    $metatype = FileModel::typPrilohy($priloha->typ);
                    $metatype = ( $metatype == 'main' )?'main':'enclosure';

                    $SentOutFiles->AddFileSpecFromFile(
                            $priloha->tmp_file,
                            $mime_type,
                            $metatype,
                            $priloha->guid,
                            "",
                            $priloha->real_name,
                            "");
                } // foreach
                $dmFiles = $SentOutFiles->FileInfos;
            } else {
                $dmFiles = null;
            }
        } else {
            $dmFiles = null;
        }


        // Sestaveni zpravy
	$MessageCreateInput = array(
                    'dmEnvelope' => $dmEnvelope,
                    'dmFiles' => $dmFiles
                );

	try {

            // odeslani zpravy a ziskani ID zpravy
            $MessageCreateOutput = $this->OperationsWS->CreateMessage($MessageCreateInput);
            if ( isset($MessageCreateOutput->dmID) ) {
                $MessageID = $MessageCreateOutput->dmID;
                //$MessageStatus=$MessageCreateOutput->dmStatus;
                return $MessageID;
            } else {
                return false;
            }
	} catch (Exception $e) {
            throw new InvalidStateException('Datovou zprávu se nepodařilo odeslat. ISDS: '. $e->getMessage());
            return false;
	}

    }

    public function getConfig() {
        return $this->params;
    }


    private function unix2time($unixtime) {
        return date("c",$unixtime);
    }

    public static function stavDS( $dbState )
    {
        $typ = array(
            '0' => 'Nelze zjístit stav datové schránky',
            '1' => 'DS je přístupná',
            '2' => 'DS je dočasně znepřístupněná',
            '3' => 'DS je dosud neaktivní',
            '4' => 'DS je trvale znepřístupněna',
            '5' => 'DS je smazána'
        );

        if ( array_key_exists($dbState,$typ) ) {
            return $typ[ $dbState ];
        } else {
            return null;
        }

    }

    public static function typDS( $dmType )
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

        if ( array_key_exists($dmType,$typ) ) {
            return $typ[ $dmType ];
        } else {
            return null;
        }

    }

    public static function stavZpravy( $dmMessageStatus )
    {
        $typ = array(
            '0' => 'Nelze zjístit stav zprávy',
            '1' => 'zpráva byla podána',
            '2' => 'hash datové zprávy včetně všech písemnosti označen časovým razítkem',
            '3' => 'zpráva neprošla AV kontrolou; nakažená písemnost je smazána; konečný stav zprávy',
            '4' => 'zpráva dodána do ISDS (zapsán čas dodání)',
            '5' => 'veřejná zpráva byla doručena fikcí (vypršením 10 dnů od dodání) - (zapsán čas doručení)',
            '6' => 'veřejná zpráva byla přihlášením, komerční zpráva pomocí ComfirmDelivery (zapsán čas doručení)',
            '7' => 'zpráva byla přečtená (na portále nebo akcí ESS)',
            '8' => 'zpráva byla označená jako nedoručitelná (DS byla zpětně znepřístupněna)',
            '9' => 'obsah zprávy byl smazán',
            '10' => 'zpráva je v Datovém trezoru'
        );

        if ( array_key_exists($dmMessageStatus,$typ) ) {
            return $typ[ $dmMessageStatus ];
        } else {
            return null;
        }

    }

}

