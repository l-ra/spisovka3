<?php


class ISDS_Spisovka extends ISDS {

    private $config;

    public function __construct()
    {
        parent::__construct();
        $this->ssl_verify_peer = Settings::get('isds_ssl_verify_peer', true);
    }

    /* Funkce vrati, zda povolit vice schranek v aplikaci.
       Pripraveno jako potencionalni moznost do budoucna. */
    public static function vice_datovych_schranek()
    {
        return Settings::get('isds_allow_more_boxes', false);
    }
    
    /* Vrati true nebo hodi vyjimku s popisem chyby */
    /* Parametr je zrejme zbytecny. Zda se, ze organizace muze mit pouze jednu datovou schranku */
    public function pripojit($params = null) {

        if (is_array($params) || $params instanceof \Nette\Utils\ArrayHash) {
            // prime hodnoty
            if ( !isset($params['typ_pripojeni']) )
                throw new InvalidArgumentException("ISDS_Spisovka::pripojit() - Neplatný parametr.");

            $config = $params;
            
        } else if ( is_object($params) ) {
            // prime hodnoty
            if ( !isset($params->typ_pripojeni) )
                throw new InvalidArgumentException("ISDS_Spisovka::pripojit() - Neplatný parametr.");

            $config = $params->toArray();
        } else {
            
            $ep_config = (new Spisovka\ConfigEpodatelna())->get();
            $ep_config = $ep_config['isds'];
            
            if ( is_numeric($params) ) { // parametrem je index z nastaveni
                if ( !isset( $ep_config[$params] ) ) // existuje takove nastaveni?
                    throw new InvalidArgumentException("ISDS_Spisovka::pripojit() - Neplatný parametr.");
                    
                $config = $ep_config[ $params ];
                
            } else { // zadny parametr
                // Toto nemusí fungovat s ArrayHashem
                $config = current($ep_config);
                if ( $config === false ) // existuje nejake nastaveni?
                    throw new InvalidArgumentException("ISDS_Spisovka::pripojit() - Není definována datová schránka.");                    
            }
        }

        $isds_portaltype = ($config['test']==1)?0:1;
        $this->config = $config;

        if ( $config['typ_pripojeni'] == 0 ) {
            // jmenem a heslem
            $rcISDSBox = $this->ISDSBox($isds_portaltype, 0,$config['login'],$config['password'],"","");
        } else if ( $config['typ_pripojeni'] == 1 ) {
            // certifikatem
            if ( file_exists($config['certifikat']) ) {
                $rcISDSBox = $this->ISDSBox($isds_portaltype, 1,"","",$config['certifikat'],$config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new Nette\FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
            }
        } else if ( $config['typ_pripojeni'] == 2 ) {
            // certifikatem + jmenem a heslem
            if ( file_exists($config['certifikat']) ) {
                $rcISDSBox = $this->ISDSBox($isds_portaltype, 2,$config['login'],$config['password'],$config['certifikat'],$config['cert_pass']);
            } else {
                // certifikat nenalezen
                throw new Nette\FileNotFoundException("Chyba nastavení ISDS! - Certifikát pro připojení k ISDS nenalezen.");
            }
        } else
            throw new Exception("Chyba nastavení ISDS! - Nespecifikovaná chyba.");
        
        if ($rcISDSBox)
            return true;
        
        if ( $this->ErrorInfo == "Služba ISDS je momentálně nedostupná" ) {
            throw new Exception("Server ISDS je dočasně nedostupný.<br />Omlouváme se všem uživatelům datových schránek za dočasné omezení přístupu do systému datových schránek z důvodu plánované údržby systému. Děkujeme za pochopení.");
        } else if ( $this->ErrorInfo == "Neplatné přihlašovací údaje!" ) {
            throw new Exception("Neplatné přihlašovací údaje!");
        } else {
            throw new Exception("Chyba ISDS: " . $this->error());
        }
    }

    public function informaceDS($idDS=null) {

        if ($idDS != null) {
            $Results = $this->FindDataBox($idDS,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null);
            if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
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

        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == "") && isset($Records->dmRecord))
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

        if (($this->StatusCode == "0000" || $this->StatusCode == "") && ($this->ErrorInfo == "") && isset($Records->dmRecord))
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

        $this->NullRetInfo();

        $this->debug_function('odeslatZpravu');

        // je parametr platny
        if ( empty($zprava) ) {
            $this->debug_return('error','Prázdný či neplatný parametr!');
            $this->debug_return('return',false);
            throw new InvalidArgumentException("Prázdný či neplatný parametr!");
            return false;
        }

        // Komu
        $komu = $zprava['dbIDRecipient'];// Identifikace adresata
        if ( empty($komu) ) {
            $this->debug_return('error','Není k dispozici adresát!');
            $this->debug_return('return',false);
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
                $dmFiles = $SentOutFiles->fileInfos();
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

        $this->debug_param('dmEnvelope', $MessageCreateInput['dmEnvelope']);
        $this->debug_param('dmFiles', $MessageCreateInput['dmFiles']);

        try {
            // odeslani zpravy a ziskani ID zpravy
            $MessageCreateOutput = $this->OperationsWS()->CreateMessage($MessageCreateInput);
            $messageStatus = $MessageCreateOutput->dmStatus;
            $this->StatusCode    = $messageStatus->dmStatusCode;
            $this->StatusMessage = $messageStatus->dmStatusMessage;

            if ( isset($MessageCreateOutput->dmID) ) {
                $MessageID = $MessageCreateOutput->dmID;
                $this->debug_return('return',$MessageID,1);
                return $MessageID;
            } else {
                $this->debug_return('error',"Datovou zprávu se nepodařilo odeslat");
                $this->debug_return('return',false,1);
                return false;
            }
        } catch (Exception $e) {
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            $this->debug_return('error',"Datovou zprávu se nepodařilo odeslat");
            $this->debug_return('return',false,1);
            return false;
        }

    }

    public function getConfig() {
        return $this->config;
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

