<?php

// Modul pro komunikaci s ISDS
//
// Verze 3.0
//
// Verze 2.0 (c) 2009 Software602 a.s.
// Verze 3.0 (c) 2010 blue.point Solutions, s.r.o.
//

// veskere stringy jsou ocekavany v kodovani UTF-8
// vyzaduje PHP 5 >= 5.0.1
// v php.ini je treba mit povolene nasledujici extensions: php_curl.dll, php_openssl.dll, php_soap.dll

class ISDS {

    protected $portal = 0; // 0 = czebox.cz, 1 = mojedatovaschranka.cz
    protected $type = 0; // 0 = basic, 1 = spisovka, 2 = hostovana spisovka

    protected $ch;

    protected $params_soap = array();

    protected $debug = 0;
    protected $proxy = 0;

    protected $StatusCode;	// statuscode posledni akce
    protected $StatusMessage;	// statusmessage posledni akce
    protected $ErrorCode;	// kod chyby vznikle pri volani sluzby posledni akce
    protected $ErrorInfo;	// popis chyby vznikle pri volani sluzby posledni akce
    protected $ValidLogin;	// true pokud probehlo uspesne prihlaseni

    /**
     *
     * @param int    $portalType    rezim pristupu k portalu (0 = czebox.cz, 1 = mojedatovaschranka.cz)
     * @param int    $type          rezim prihlaseni ()
     * @param string $login         prihlasovaci jmeno
     * @param string $password      prihlasovaci heslo
     * @param string $certfilename  cesta k certifikatu
     * @param string $passphrase    heslo klice certifikatu
     */
    public function ISDSBox($portalType, $type, $login, $password, $certfilename, $passphrase)
    {
        $this->ValidLogin = false;
        $this->NullRetInfo();

        $this->debug("===========================\nISDSBox\n=================");

        $this->portal = $portalType;

        $params = array(
            'trace'=>true,
            'exceptions'=>true,
            'user_agent'=>$this->userAgent()
        );

        $this->type = $type;
        switch ($type) {
            case 0: // basic = login + password
                $params['login'] = $login;
                $params['password'] = $password;
                break;
            case 1: // spisovka = certifikat
                $params['local_cert'] = $certfilename;
                $params['passphrase'] = $passphrase;
                break;
            case 2: // hostovana spisovka = certifikat + login + password
                $params['login'] = $login;
                $params['password'] = $password;
                $params['local_cert'] = $certfilename;
                $params['passphrase'] = $passphrase;
                break;
            default: // neplatny typ
                $params = null;
                break;
        }

        if ( 0 ) { // proxy
            $params['proxy_host'] = "localhost";
            $params['proxy_port'] = 8080;
            $params['proxy_login'] = "";
            $params['proxy_password'] = "";
        }

        $params['location'] = $this->GetServiceURL(3);

        $this->params_soap = $params;

        $this->debug("PortalType",$portalType);
        $this->debug("LoginType",$type);
        $this->debug("SOAP Params",$params);

        $this->GetOwnerInfoFromLogin();
        if (($this->StatusCode == "0000") && ($this->ErrorInfo == "")) {
            return true;
        } else {
            throw new Exception("Chyba připojení k ISDS! - ".$this->ErrorInfo,$this->ErrorCode);
            return false;
        }

    }

    /**************************************************************************
     * Web services 
     **************************************************************************/

    /**
     *  OperationsWS
     *  - WSDL pro sluzby pracujici s datovymi zpravami
     *
     *   1. Vytvoření/odeslání datové zprávy - CreateMessage
     *   2. stažení kompletní přijaté zprávy v čitelné podobě - MessageDownload
     *   3. stažení podepsané přijaté zprávy - SignedMessageDownload
     *   4. stažení podepsané odeslané zprávy - SignedSentMessageDownload
     *   5. vytvoření hromadné zprávy (oběžníku) - CreateMultipleMessage
     *
     * @param array $params Paramtery pro SoapClient. (nepovinne - prebira nastaveni z tridy)
     * @return SoapClient
     */
    protected function OperationsWS($params = null) {

        $this->debug("===========================\nOperationsWS\n=================");

        if ( is_null($params) ) {
            $params = $this->params_soap;
            $params['location'] = $this->GetServiceURL(0);
        }

        $this->debug(">> Volam SoapClient");
        $this->debug(">>       type:",$this->GetServiceWSDL(0));
        $this->debug(">>       params:",$params);

        $operationsWS = new SoapClient($this->GetServiceWSDL(0),$params);

        return $operationsWS;
    }

    /**
     *  InfoWS
     *   - WSDL pro sluzby informacniho charakteru
     *
     *   1. Ověření neporušení datové zprávy - VerifyMessage
     *   2. Stažení obálky přijaté zprávy v čitelné podobě - MessageEnvelopeDownload
     *   3. Označení přijaté zprávy jako Přečtené - MarkMessageAsDownloaded
     *   4. Oznámení o dodání, doručení, nedoručení datové zprávy (Dodejka, Doručenka, Nedoručenka) - GetDeliveryInfo
     *   5. Podepsané oznámení o dodání, doručení, nedoručení datové zprávy (Dodejka, Doručenka, Nedoručenka) - GetSignedDeliveryInfo
     *   6. Stažení seznamů odeslaných datových zpráv - GetListOfSentMessages
     *   7. Stažení seznamů přijatých datových zpráv - GetListOfReceivedMessages
     *   8. Doručení komerční datové zprávy - ConfirmDelivery
     *
     * @param array $params Paramtery pro SoapClient. (nepovinne - prebira nastaveni z tridy)
     * @return SoapClient
     */
    protected function InfoWS($params = null) {

        $this->debug("===========================\nInfoWS\n=================");

        if ( is_null($params) ) {
            $params = $this->params_soap;
            $params['location'] = $this->GetServiceURL(1);
        }

        $this->debug(">> Volam SoapClient");
        $this->debug(">>       type:",$this->GetServiceWSDL(1));
        $this->debug(">>       params:",$params);

        $infoWS = new SoapClient($this->GetServiceWSDL(1),$params);

        return $infoWS;
    }

    /**
     *  ManipulationsWS
     *  - WSDL pro sluzby manipulujicimi s datovymi schrankami
     *
     * @param array $params Paramtery pro SoapClient. (nepovinne - prebira nastaveni z tridy)
     * @return SoapClient
     */
    protected function ManipulationsWS($params = null) {

        $this->debug("===========================\nManipulationsWS\n=================");

        if ( is_null($params) ) {
            $params = $this->params_soap;
            $params['location'] = $this->GetServiceURL(2);
        }

        $this->debug(">> Volam SoapClient");
        $this->debug(">>       type:",$this->GetServiceWSDL(0));
        $this->debug(">>       params:",$params);

        $manipulationsWS = new SoapClient($this->GetServiceWSDL(2),$params);
        return $manipulationsWS;
    }

    /**
     *  AccessWS
     *  - WSDL pro doplňkové služby související s přihlašováním
     *     *
     * @param array $params Paramtery pro SoapClient. (nepovinne - prebira nastaveni z tridy)
     * @return SoapClient
     */
    protected function AccessWS($params = null) {

        $this->debug("===========================\nAccessWS\n=================");

        if ( is_null($params) ) {
            $params = $this->params_soap;
            $params['location'] = $this->GetServiceURL(3);
        }

        $this->debug(">> Volam SoapClient");
        $this->debug(">>       type:",$this->GetServiceWSDL(3));
        $this->debug(">>       params:",$params);

        $accessWS = new SoapClient($this->GetServiceWSDL(3),$params);

        return $accessWS;
    }

    /**************************************************************************
     * Vzdalene funkce SAOP
     **************************************************************************/

    /**
     * Vrati udaje o majiteli schranky, ke ktere jsme prihlaseni
     * 
     * @return dbOwnerInfo
     */
    public function GetOwnerInfoFromLogin()
    {

        $this->debug("===========================\nGetOwnerInfoFromLogin\n=================");


        $this->NullRetInfo();
	$Input = array('dbDummy'=>"");

        $this->debug("param: ", $Input);

	try {

            $output = $this->AccessWS()->GetOwnerInfoFromLogin($Input);

            $this->debug("result: ", $output);
            $this->StatusCode = $output->dbStatus->dbStatusCode;
            $this->StatusMessage = $output->dbStatus->dbStatusMessage;
            return $output->dbOwnerInfo;
	} catch (Exception $e) {
            $this->ErrorCode = $e->getCode();
            $this->ErrorInfo = $e->getMessage();
            $this->StatusCode = @$output->dbStatus->dbStatusCode;
            $this->StatusMessage = @$output->dbStatus->dbStatusMessage;
            return false;
	}
    }

    /**
     * Vytvori datovou zpravu a umisti ji do dane schranky. Vrati identifikaci vytvorene zpravy.
     *
     * @param <type> $IDRecipient           Identifikace adresata
     * @param <type> $Annotation            Textova poznamka (vec, predmet, anotace)
     * @param <type> $AllowSubstDelivery    Nahradni doruceni povoleno/nepovoleno - pouze pro nektere subjekty (napr. soudy)
     * @param <type> $LegalTitleLaw         Zmocneni - cislo zakona
     * @param <type> $LegalTitlePar         Zmocneni - odstavec v paragrafu
     * @param <type> $LegalTitlePoint       Zmocneni - pismeno v odstavci
     * @param <type> $LegalTitleSect        Zmocneni - paragraf v zakone
     * @param <type> $LegalTitleYear        Zmocneni - rok vydani zakona
     * @param <type> $RecipientIdent        Spisova znacka ze strany prijemce, Nepovinne.
     * @param <type> $RecipientOrgUnit      Organizacni jednotka prijemce slovne, nepovinne, mozne upresneni prijemce pri podani z portalu
     * @param <type> $RecipientOrgUnitNum   Organizacni jednotka prijemce hodnotou z ciselniku, nepovinne, pokud nechcete urcit zadejte -1
     * @param <type> $RecipientRefNumber    Cislo jednaci ze strany prijemce, nepovinne
     * @param <type> $SenderIdent           Spisova znacka ze strany odesilatele
     * @param <type> $SenderOrgUnit         Organizacni jednotka odesilatele slovne. Nepovinne
     * @param <type> $SenderOrgUnitNum      Organizacni jednotka odesilatele hodnotou z ciselniku. Nepovinne.
     * @param <type> $SenderRefNumber       Cislo jednaci ze strany odesilatele. Nepovinne
     * @param <type> $ToHands               Popis komu je zasilka urcena
     * @param <type> $PersonalDelivery      Priznak "Do vlastnich rukou" znacici, ze zpravu muze cist pouze adresat nebo osoba s explicitne danym opravnenim
     * @param <type> $OVM                   Priznak je-li DS odesilana v rezimu OVM
     * @param <type> $outFiles              Pripojene soubory (pisemnosti)
     * @return dmID
     */
    public function CreateMessage(
		$IDRecipient, $Annotation, $AllowSubstDelivery,
		$LegalTitleLaw,	$LegalTitlePar,	$LegalTitlePoint, $LegalTitleSect, $LegalTitleYear,
		$RecipientIdent, $RecipientOrgUnit, $RecipientOrgUnitNum, $RecipientRefNumber,
		$SenderIdent, $SenderOrgUnit, $SenderOrgUnitNum, $SenderRefNumber,
		$ToHands, $PersonalDelivery, $OVM,
                $outFiles)
    {

        $this->NullRetInfo();
        
	$envelope = array(
            'dmSenderOrgUnit' => $SenderOrgUnit,
            'dmSenderOrgUnitNum' => $SenderOrgUnitNum,
            'dbIDRecipient' => $IDRecipient,
            'dmRecipientOrgUnit' => $RecipientOrgUnit,
            'dmRecipientOrgUnitNum' => $RecipientOrgUnitNum,
            'dmToHands' => $ToHands,
            'dmAnnotation' => $Annotation,
            'dmRecipientRefNumber' => $RecipientRefNumber,
            'dmSenderRefNumber' => $SenderRefNumber,
            'dmRecipientIdent' => $RecipientIdent,
            'dmSenderIdent' => $SenderIdent,
            'dmLegalTitleLaw' => $LegalTitleLaw,
            'dmLegalTitleYear' => $LegalTitleYear,
            'dmLegalTitleSect' => $LegalTitleSect,
            'dmLegalTitlePar' => $LegalTitlePar,
            'dmLegalTitlePoint' => $LegalTitlePoint,
            'dmPersonalDelivery' => $PersonalDelivery,
            'dmAllowSubstDelivery' => $AllowSubstDelivery,
            'dmOVM'=>$OVM);
        
	$messageCreateInput = array(
            'dmEnvelope' => $envelope,
            'dmFiles' => $outFiles->FileInfos);
	
        try {
            $messageCreateOutput = $this->OperationsWS()->CreateMessage($messageCreateInput);
            $messageID = $messageCreateOutput->dmID;
            $messageStatus = $MessageCreateOutput->dmStatus;
            $this->StatusCode    = $messageStatus->dmStatusCode;
            $this->StatusMessage = $messageStatus->dmStatusMessage;
            return $messageID;
	} catch (Exception $e) {
            $this->StatusCode    = $messageStatus->dmStatusCode;
            $this->StatusMessage = $messageStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
        }
    }

    /**
     * Vytvori hromadnou datovou zpravu a umisti ji do danych schranek
     *
     * $Recipients = array(
     *                  dbIDRecipient = ID adresata
     *			dmRecipientOrgUnit = Organizacni jednotka adresata slovne
     *			dmRecipientOrgUnitNum = Cislo organizacni jednotky adresata
     *			dmToHands = popis komu je zasilka urcena
     * );
     * - pole adresatu muze obsahovat maximalne 50 prvku
     *
     * Vraci pole informujici o tom jak dopadlo odeslani zasilky jednotlivym adresatum
     * return array(
     *          dmID = identifikace vytvorene zpravy
     *          dmStatus->dmStatusCode
     *          dmStatus->dmStatusMessage
     * );
     *
     * @param array $Recipients             Adresati zasilky
     * @param <type> $Annotation            Textova poznamka (vec, predmet, anotace)
     * @param <type> $AllowSubstDelivery    Nahradni doruceni povoleno/nepovoleno - pouze pro nektere subjekty (napr. soudy)
     * @param <type> $LegalTitleLaw         Zmocneni - cislo zakona
     * @param <type> $LegalTitlePar         Zmocneni - odstavec v paragrafu
     * @param <type> $LegalTitlePoint       Zmocneni - pismeno v odstavci
     * @param <type> $LegalTitleSect        Zmocneni - paragraf v zakone
     * @param <type> $LegalTitleYear        Zmocneni - rok vydani zakona
     * @param <type> $RecipientIdent        Spisova znacka ze strany prijemce, Nepovinne.
     * @param <type> $RecipientRefNumber    Cislo jednaci ze strany prijemce, nepovinne
     * @param <type> $SenderIdent           Spisova znacka ze strany odesilatele
     * @param <type> $SenderOrgUnit         Organizacni jednotka odesilatele slovne. Nepovinne
     * @param <type> $SenderOrgUnitNum      Organizacni jednotka odesilatele hodnotou z ciselniku. Nepovinne.
     * @param <type> $SenderRefNumber       Cislo jednaci ze strany odesilatele. Nepovinne
     * @param <type> $PersonalDelivery      Priznak "Do vlastnich rukou" znacici, ze zpravu muze cist pouze adresat nebo osoba s explicitne danym opravnenim
     * @param <type> $OVM                   Priznak je-li DS odesilana v rezimu OVM
     * @param <type> $OutFiles              Pripojene soubory (pisemnosti)
     * @return array(dmID) Vraci pole informujici o tom jak dopadlo odeslani zasilky jednotlivym adresatum
     */
    public function CreateMultipleMessage(
		$Recipients, $Annotation, $AllowSubstDelivery,
		$LegalTitleLaw, $LegalTitlePar, $LegalTitlePoint, $LegalTitleSect, $LegalTitleYear,
		$RecipientIdent, $RecipientRefNumber,
                $SenderIdent, $SenderOrgUnit, $SenderOrgUnitNum, $SenderRefNumber,
		$PersonalDelivery, $OVM, $OutFiles)
    {

        $this->NullRetInfo();
	$Envelope = array(
            'dmSenderOrgUnit' => $SenderOrgUnit,
            'dmSenderOrgUnitNum' => $SenderOrgUnitNum,
            'dmAnnotation' => $Annotation,
            'dmRecipientRefNumber' => $RecipientRefNumber,
            'dmSenderRefNumber' => $SenderRefNumber,
            'dmRecipientIdent' => $RecipientIdent,
            'dmSenderIdent' => $SenderIdent,
            'dmLegalTitleLaw' => $LegalTitleLaw,
            'dmLegalTitleYear' => $LegalTitleYear,
            'dmLegalTitleSect' => $LegalTitleSect,
            'dmLegalTitlePar' => $LegalTitlePar,
            'dmLegalTitlePoint' => $LegalTitlePoint,
            'dmPersonalDelivery' => $PersonalDelivery,
            'dmAllowSubstDelivery' => $AllowSubstDelivery,
            'dmOVM'=>$OVM
        );

        $MultipleMessageCreateInput = array(
            'dmRecipients' => $Recipients,
            'dmEnvelope' => $Envelope,
            'dmFiles' => $OutFiles->FileInfos
        );
	
        try {

            $MultipleMessageCreateOutput = $this->OperationsWS()->CreateMultipleMessage($MultipleMessageCreateInput);
            $MessageStatus = $MultipleMessageCreateOutput->dmStatus;
            $this->StatusCode = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            return $this->PrepareArray($MultipleMessageCreateOutput->dmMultipleStatus->dmSingleStatus);

	} catch (Exception $e) {
            
            $this->StatusCode    = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;

        }

    }

    /**
     * Stazeni prijate zpravy z ISDS bez elektronickeho podpisu. Vrati stazenou zpravu.
     *
     * @param int $MessageID    Identifikace zpravy
     * @return dmReturnedMessage
     */
    public function MessageDownload($MessageID)
    {

        $this->NullRetInfo();

        $MessInput = array('dmID'=>$MessageID);

	try {

            $MessageDownloadOutput = $this->OperationsWS()->MessageDownload($MessInput);
            $ReturnedMessage = $MessageDownloadOutput->dmReturnedMessage;
            $MessageStatus = $MessageDownloadOutput->dmStatus;
            $this->StatusCode = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            $ReturnedMessage->dmDm->dmFiles->dmFile = $this->PrepareArray($ReturnedMessage->dmDm->dmFiles->dmFile);
            return $ReturnedMessage;

	} catch (Exception $e) {

            $this->StatusCode    = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Stazeni podepsane prijate zpravy. Vrati stazenou zpravu v binarnim formatu.
     *
     * @param int $MessageID
     * @return dmSignature
     */
    public function SignedMessageDownload($MessageID)
    {

        $this->NullRetInfo();

        $MessInput=array('dmID'=>$MessageID);
		
        try {
                
            $SignedMessDownOutput = $this->OperationsWS()->SignedMessageDownload($MessInput);
            $ReturnedMessage = $SignedMessDownOutput->dmSignature;
            $MessageStatus = $SignedMessDownOutput->dmStatus;
            $this->StatusCode = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;

            return $ReturnedMessage;

	} catch (Exception $e) {

            $this->StatusCode    = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;

        }
    }

    /**
     * Stazeni podepsane prijate zpravy do souboru.
     *
     * @param int    $MessageID Identifikace zpravy
     * @param string $FileName  Jmeno souboru, do ktereho bude zprava ulozena
     * @return bool
     */
    public function SignedMessageDownloadToFile($MessageID, $FileName)
    {

        $this->NullRetInfo();

        $Message = $this->SignedMessageDownload($MessageID);
	if (($this->StatusCode == "0000") && ($this->ErrorInfo == "")) {
            if (@file_put_contents($FileName,$Message)) {
                return true;
            } else {
                $this->ErrorInfo = 0;
                $this->ErrorInfo = 'Nepodařilo se uložit zprávu do souboru "'.$FileName.'"!';
                return false;
            }
	} else {
            return false;
        }
    }

    /**
     * Stazeni podepsane odeslane zpravy. Vrati stazenou zpravu v binarnim formatu.
     * 
     * @param int $MessageID Identifikace zpravy
     * @return dmSignature
     */
    public function SignedSentMessageDownload($MessageID)
    {

        $this->NullRetInfo();

        $MessInput=array('dmID'=>$MessageID);
        
        try {
            
            $SignedMessDownOutput = $this->OperationsWS()->SignedSentMessageDownload($MessInput);

            $ReturnedMessage = $SignedMessDownOutput->dmSignature;
            $MessageStatus = $SignedMessDownOutput->dmStatus;
            $this->StatusCode = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            return $ReturnedMessage;
            
        } catch (Exception $e) {
            
            $this->StatusCode    = $MessageStatus->dmStatusCode;
            $this->StatusMessage = $MessageStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;

	}
    }

    /**
     * Stazeni podepsane odeslane zpravy do souboru
     *
     * @param int    $MessageID Identifikace zpravy
     * @param string $FileName  Jmeno souboru, do ktereho bude zprava ulozena
     * @return bool
     */
    public function SignedSentMessageDownloadToFile($MessageID, $FileName)
    {
        
        $Message = $this->SignedSentMessageDownload($MessageID);
	if (($this->StatusCode == "0000") && ($this->ErrorInfo == "")) {
            if (@file_put_contents($FileName,$Message)) {
                return true;
            } else {
                $this->ErrorInfo = 0;
                $this->ErrorInfo = 'Nepodařilo se uložit zprávu do souboru "'.$FileName.'"!';
                return false;
            }
	} else {
            return false;
        }
		
    }

    /**
     * DummyOperation
     */
    public function DummyOperation() {
        $this->OperationsWS()->DummyOperation();
    }

    /**
     * Slouzi k porovnani hashe datove zpravy ulozene mimo ISDS s originalem. Vrati hash zpravy
     *
     * @param int $MessageID Identifikace zpravy
     * @return dmHash
     */
    public function VerifyMessage($MessageID)
    {

        $this->NullRetInfo();

        $MessInput=array('dmID'=>$MessageID);
	
        try {

            $MessageVerifyOutput = $this->InfoWS()->VerifyMessage($MessInput);
            $Status = $MessageVerifyOutput->dmStatus;
            $this->StatusCode = $Status->dmStatusCode;
            $this->StatusMessage = $Status->dmStatusMessage;
            return $MessageVerifyOutput->dmHash;

	} catch (Exception $e) {

            $this->StatusCode    = $Status->dmStatusCode;
            $this->StatusMessage = $Status->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Stazeni pouhe obalky prijate zpravy (bez pisemnosti). Vrati obalku zpravy.
     *
     * @param int $MessageID
     * @return dmReturnedMessageEnvelope
     */
    public function MessageEnvelopeDownload($MessageID)
    {

        $this->NullRetInfo();
	$MessInput=array('dmID'=>$MessageID);
        
        try {
	
            $MessEnvelDownOutput = $this->InfoWS()->MessageEnvelopeDownload($MessInput);

            $Status = $MessEnvelDownOutput->dmStatus;
            $this->StatusCode = $Status->dmStatusCode;
            $this->StatusMessage = $Status->dmStatusMessage;
            return $MessEnvelDownOutput->dmReturnedMessageEnvelope;

        } catch (Exception $e) {

            $this->StatusCode    = $Status->dmStatusCode;
            $this->StatusMessage = $Status->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;

	}
    }

    /**
     * Vybrana dorucena zprava bude oznacena jako prectena.
     * 
     * @param int $MessageID Identifikace zpravy
     * @return bool
     */
    public function MarkMessageAsDownloaded($MessageID)
    {

        $this->NullRetInfo();

        $MessInput=array('dmID'=>$MessageID);
        
        try {

            $MarkMessOut = $this->InfoWS()->MarkMessageAsDownloaded($MessInput);
            $this->StatusCode = $MarkMessOut->dmStatus->dmStatusCode;
            $this->StatusMessage = $MarkMessOut->dmStatus->dmStatusMessage;
            return true;
            
        } catch (Exception $e) {
            $this->StatusCode    = $MarkMessOut->dmStatus->dmStatusCode;
            $this->StatusMessage = $MarkMessOut->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Prepare array
     *
     * @param array $A
     * @return array
     */
    protected function PrepareArray($A)
    {
        if (count($A) != 1) {
            return $A;
        }

	$B=array();
	$B[0]=$A;
	return $B;
        
    }

    /**
     * Stazeni informace o dodani, doruceni nebo nedoruceni zpravy. Vrati dorucenku.
     * 
     * @param int $MessageID    Identifikace zpravy
     * @return dmDelivery 
     */
    public function GetDeliveryInfo($MessageID)
    {
        
        $this->NullRetInfo();

	$MessInput=array('dmID'=>$MessageID);

	try {

            $DeliveryMessageOutput = $this->InfoWS()->GetDeliveryInfo($MessInput);

            $this->StatusCode = $DeliveryMessageOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $DeliveryMessageOutput->dmStatus->dmStatusMessage;

            $DeliveryMessageOutput->dmDelivery->dmEvents->dmEvent = $this->PrepareArray($DeliveryMessageOutput->dmDelivery->dmEvents->dmEvent);
            return $DeliveryMessageOutput->dmDelivery;

	} catch (Exception $e) {
            $this->StatusCode    = $DeliveryMessageOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $DeliveryMessageOutput->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
        }
    }

    /**
     * Stazeni podepsane informace o dodani, doruceni nebo nedoruceni zpravy
     *
     * @param int $MessageID Identifikace zpravy
     * @return dmSignature
     */
    public function GetSignedDeliveryInfo($MessageID)
    {

        $this->NullRetInfo();
	$MessInput=array('dmID'=>$MessageID);
	
        try {
            
            $SignDeliveryMessOutput = $this->InfoWS()->GetSignedDeliveryInfo($MessInput);
            $this->StatusCode = $SignDeliveryMessOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $SignDeliveryMessOutput->dmStatus->dmStatusMessage;
            return $SignDeliveryMessOutput->dmSignature;

	} catch (Exception $e) {
            $this->StatusCode    = $SignDeliveryMessOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $SignDeliveryMessOutput->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
        }
    }

    /**
     * Stazeni dorucenky v podepsanem tvaru do souboru
     *
     * @param int    $MessageID Identifikace zpravy
     * @param string $FileName  Jmeno souboru
     * @return bool
     */
    public function GetSignedDeliveryInfoToFile($MessageID, $FileName)
    {
        
        $Delivery = $this->GetSignedDeliveryInfo($MessageID);
        if (($this->StatusCode == "0000") && ($this->ErrorInfo == "")) {
            if (@file_put_contents($FileName,$Delivery)) {
                return true;
            } else {
                $this->ErrorInfo = 0;
                $this->ErrorInfo = 'Nepodařilo se uložit zprávu do souboru "'.$FileName.'"!';
                return false;
            }
	} else {
            return false;
        }
    }

    /**
     * Stazeni seznamu odeslanych zprav urceneho casovym intervalem, organizacni jednotkou odesilatele,
     * filtrem na stav zprav a usekem poradovych cisel zaznamu. Vrati seznam zprav.
     * 
     * StatusFilter - Filtr na stav zpravy. Je mozne specifikovat pozadovane
     *                zpravy kombinaci nasledujicich hodnot (jde o bitove priznaky):
     *        1 - podana
     *        2 - dostala razitko
     *        3 - neprosla antivirem
     *        4 - dodana
     *        5 - dorucena fikci - tzn. uplynutim casu 10 dnu
     *        6 - dorucena prihlasenim
     *        7 - prectena
     *        8 - nedorucitelna (znepristupnena schranka po odeslani)
     *        9 - smazana* 
     * 
     * @param dmFromTime         $FromTime          Pocatek casoveho intervalu z nehoz maji byt zpravy nacteny
     * @param dmToTime           $ToTime            Konec casoveho intervalu z nehoz maji byt zpravy nacteny
     * @param dmOffset           $Offset            Cislo prvniho pozadovaneho zaznamu
     * @param dmLimit            $Limit             Pocet pozadovanych zaznamu
     * @param dmStatusFilter     $StatusFilter      Filtr na stav zpravy. Je mozne specifikovat pozadovane
     * @param dmSenderOrgUnitNum $SenderOrgUnitNum  Organizacni slozka odesilatele (z ciselniku)
     * @return dmRecords
     */
    public function GetListOfSentMessages($FromTime, $ToTime, $Offset, $Limit, $StatusFilter, $SenderOrgUnitNum)
    {
        
        $this->NullRetInfo();

        $ListOfSentInput=array(
            'dmFromTime'=>$FromTime,
            'dmToTime'=>$ToTime,
            'dmSenderOrgUnitNum'=>$SenderOrgUnitNum,
            'dmStatusFilter'=>$StatusFilter,
            'dmOffset'=>$Offset,
            'dmLimit'=>$Limit
        );
        
	try {

            $ListOfSentOutput = $this->InfoWS()->GetListOfSentMessages($ListOfSentInput);
            $this->StatusCode = $ListOfSentOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $ListOfSentOutput->dmStatus->dmStatusMessage;
            $ListOfSentOutput->dmRecords->dmRecord = $this->PrepareArray($ListOfSentOutput->dmRecords->dmRecord);
            return $ListOfSentOutput->dmRecords;

        } catch (Exception $e) {
            $this->StatusCode    = @$ListOfSentOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = @$ListOfSentOutput->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Stazeni seznamu doslych zprav urceneho casovym intervalem,
     * zpresnenim organizacni jednotky adresata (pouze ESS), filtrem na stav zprav
     * a usekem poradovych cisel zaznamu
     *
     * StatusFilter - Filtr na stav zpravy. Je mozne specifikovat pozadovane
     *                zpravy kombinaci nasledujicich hodnot (jde o bitove priznaky):
     *        1 - podana
     *        2 - dostala razitko
     *        3 - neprosla antivirem
     *        4 - dodana
     *        5 - dorucena fikci - tzn. uplynutim casu 10 dnu
     *        6 - dorucena prihlasenim
     *        7 - prectena
     *        8 - nedorucitelna (znepristupnena schranka po odeslani)
     *        9 - smazana*
     *
     * @param dmFromTime         $FromTime          Pocatek casoveho intervalu z nehoz maji byt zpravy nacteny
     * @param dmToTime           $ToTime            Konec casoveho intervalu z nehoz maji byt zpravy nacteny
     * @param dmOffset           $Offset            Cislo prvniho pozadovaneho zaznamu
     * @param dmLimit            $Limit             Pocet pozadovanych zaznamu
     * @param dmStatusFilter     $StatusFilter      Filtr na stav zpravy. Je mozne specifikovat pozadovane
     * @param dmSenderOrgUnitNum $SenderOrgUnitNum  Organizacni slozka odesilatele (z ciselniku)
     * @return dmRecords
     */
    public function GetListOfReceivedMessages($FromTime, $ToTime, $Offset, $Limit, $StatusFilter, $RecipientOrgUnitNum)
    {

        $this->NullRetInfo();

        $ListOfReceivedInput=array(
            'dmFromTime'=>$FromTime,
            'dmToTime'=>$ToTime,
            'dmRecipientOrgUnitNum'=>$RecipientOrgUnitNum,
            'dmStatusFilter'=>$StatusFilter,
            'dmOffset'=>$Offset,
            'dmLimit'=>$Limit
        );
        
        try {
            
            $ListOfRecOutput = $this->InfoWS()->GetListOfReceivedMessages($ListOfReceivedInput);
            $this->StatusCode = $ListOfRecOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = $ListOfRecOutput->dmStatus->dmStatusMessage;
            $ListOfRecOutput->dmRecords->dmRecord = $this->PrepareArray($ListOfRecOutput->dmRecords->dmRecord);
            return $ListOfRecOutput->dmRecords;
            
        } catch (Exception $e) {
            $this->StatusCode    = @$ListOfRecOutput->dmStatus->dmStatusCode;
            $this->StatusMessage = @$ListOfRecOutput->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}

    }

    /**
     * Vyhledani datove schranky
     * 
     * @param int       $IdDb
     * @param string    $Type              typ datove schranky. Muze byt jedna z hodnot: FO,PFO,PFO_ADVOK,PFO_DANPOR,PFO_INSSPR,PO,PO_ZAK,PO_REQ,OVM,OVM_NOTAR,OVM_EXEKUT
     * @param int       $dbState           stav schranky: 1 pristupna
     * @param string    $ic
     * @param string    $FirstName
     * @param string    $MiddleName,
     * @param string    $LastName,
     * @param string    $LastNameAtBirth,
     * @param string    $firmName,
     * @param string    $biDate,
     * @param string    $biCity,
     * @param string    $biCounty,
     * @param string    $biState,
     * @param string    $adCity,
     * @param string    $adStreet,
     * @param string    $adNumberInStreet,
     * @param string    $adNumberInMunicipality,
     * @param string    $adZipCode,
     * @param string    $adState,
     * @param string    $nationality,
     * @param string    $email,
     * @param string    $telNumber
     * @return dbResults
     *
     */
    public function FindDataBox($IdDb, $Type, $dbState,	$ic, $FirstName, $MiddleName, $LastName, $LastNameAtBirth,
                        $firmName, $biDate, $biCity, $biCounty, $biState, $adCity, $adStreet, $adNumberInStreet,
                        $adNumberInMunicipality, $adZipCode, $adState, $nationality, $email, $telNumber)
    {

        $this->NullRetInfo();
	$OwnerInfo=array(
            'dbID'=>$IdDb,
            'dbType'=>$Type,
            'dbState'=>$dbState,
            'ic'=>$ic,
            'pnFirstName'=>$FirstName,
            'pnMiddleName'=>$MiddleName,
            'pnLastName'=>$LastName,
            'pnLastNameAtBirth'=>$LastNameAtBirth,
            'firmName'=>$firmName,
            'biDate'=>$biDate,
            'biCity'=>$biCity,
            'biCounty'=>$biCounty,
            'biState'=>$biState,
            'adCity'=>$adCity,
            'adStreet'=>$adStreet,
            'adNumberInStreet'=>$adNumberInStreet,
            'adNumberInMunicipality'=>$adNumberInMunicipality,
            'adZipCode'=>$adZipCode,
            'adState'=>$adState,
            'nationality'=>$nationality,
            'email'=>$email,
            'telNumber'=>$telNumber
        );
	
        $FindInput=array('dbOwnerInfo'=>$OwnerInfo);

	try {
            
            $FindOutput=$this->ManipulationsWS()->FindDataBox($FindInput);
            $this->StatusCode = $FindOutput->dbStatus->dbStatusCode;
            $this->StatusMessage = $FindOutput->dbStatus->dbStatusMessage;
            $FindOutput->dbResults->dbOwnerInfo = $this->PrepareArray($FindOutput->dbResults->dbOwnerInfo);
            return $FindOutput->dbResults;
	
        } catch (Exception $e) {
            $this->StatusCode    = $FindOutput->dbStatus->dbStatusCode;
            $this->StatusMessage = $FindOutput->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
        }
    }

    /**
     * Overeni dostupnosti datove schranky
     * 
     * vrati:
     *   1 = schranka je pristupna
     *   2 = schranka je docasne znepristupnena a muze byt pozdeji opet zpristupnena
     *   3 = schranka je dosud neaktivni, existuje mene nez 15 dni a nikdo se do ni dosud neprihlasil
     *   4 = schranka je trvale znepristupnena a ceka 3 roky na smazani
     *   5 = schranka je smazana
     *
     * @params dbID     $DataBoxID
     * @return dbState
     *
     */
    public function CheckDataBox($DataBoxID)
    {

        $this->NullRetInfo();

	$Inputdb = array('dbID'=>$DataBoxID);
	
        try {
            $Output = $this->ManipulationsWS()->CheckDataBox($Inputdb);
            $this->StatusCode = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            return $Output->dbState;
	} catch (Exception $e) {
            $this->StatusCode    = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}

    }


    /**
     * Funkce vrati pole s pocty zasilek, indexovane stavem zasilek
     *
     * stavy:
     *    1 - podana
     *    2 - dostala razitko
     *    3 - neprosla antivirem
     *    4 - dodana
     *    5 - dorucena fikci - tzn. uplynutim casu 10 dnu
     *    6 - dorucena prihlasenim
     *    7 - prectena
     *    8 - nedorucitelna (znepristupnena schranka po odeslani)
     *    9 - smazana
     *
     * @param bool  $received   pokud true, ctou se prijate zasilky, pokud false, ctou se odeslane
     * @return int
     */
    public function GetNumOfMessages($received)
    {
    
        $Result = array();
	
        for ($i=1; $i<=9; $i++)	{
            $Result[$i]=0;
	}
	
        $Step=512; $Start=1;
        
        do {
            if ($received) {
                $Records = $this->GetListOfReceivedMessages('2000','3000',$Start,$Step,1023,null);
            } else {
		$Records = $this->GetListOfSentMessages('2000','3000',$Start,$Step,1023,null);
            }
	
            $Start = $Start + $Step;
            $NumOf=count($Records->dmRecord);
            for ($i=0;$i<$NumOf;$i++) {
                $Result[$Records->dmRecord[$i]->dmMessageStatus]++;
            }
        } while ($NumOf == $Step);
        
	return $Result;

    }

    /**
     * Vsechny dorucene zpravy oznaci jako prectene
     *
     * @return bool
     */
    public function MarkAllReceivedMessagesAsDownloaded()
    {

        $Step=512;
	$Start=1;
	
        do {
            $Records = $this->GetListOfReceivedMessages('2000','3000',$Start,$Step,1023,null);
            $Start = $Start + $Step;
            $NumOf = count($Records->dmRecord);
            for ($i=0;$i<$NumOf;$i++) {
                if ($Records->dmRecord[$i]->dmMessageStatus != 7) {
                    $this->MarkMessageAsDownloaded($Records->dmRecord[$i]->dmID);
		}
            }
	} while ($NumOf == $Step);
        
	return true;
    }

    /**
     * Potvrzeni doruceni komercni zpravy
     *
     * @params dmID $MessageID
     * @return bool
     *
     */
    public function ConfirmDelivery($MessageID)
    {

        $this->NullRetInfo();

        $MessInput=array('dmID'=>$MessageID);
	
        try {
            $ConDerOut = $this->InfoWS()->ConfirmDelivery($MessInput);
            $this->StatusCode=$ConDerOut->dmStatus->dmStatusCode;
            $this->StatusMessage=$ConDerOut->dmStatus->dmStatusMessage;
            return true;
	} catch (Exception $e) {
            $this->StatusCode    = $ConDerOut->dmStatus->dmStatusCode;
            $this->StatusMessage = $ConDerOut->dmStatus->dmStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Povoli zadane schrance prijem komercnich zprav
     *
     * @param dbID  $BoxID
     * @return bool
     *
     */
    public function SetOpenAddressing($BoxID)
    {

        $this->NullRetInfo();

        $IDDBInput=array('dbID'=>$BoxID);
	
        try {
            $ReqStatusOut = $this->ManipulationsWS()->SetOpenAddressing($IDDBInput);
            $this->StatusCode = $ReqStatusOut->dbStatus->dbStatusCode;
            $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
            return true;
	} catch (Exception $e) {
            $this->StatusCode    = $ReqStatusOut->dbStatus->dbStatusCode;
            $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}


    }

    /**
     * Zakaze zadane schrance prijem komercnich zprav
     *
     * @param dbID  $BoxID
     * @return bool
     *
     */
    public function ClearOpenAddressing($BoxID)
    {

        $this->NullRetInfo();

        $IDDBInput=array('dbID'=>$BoxID);
		
        try {
            $ReqStatusOut = $this->ManipulationsWS()->ClearOpenAddressing($IDDBInput);
            $this->StatusCode = $ReqStatusOut->dbStatus->dbStatusCode;
            $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
            return true;
	} catch (Exception $e) {
            $this->StatusCode    = $ReqStatusOut->dbStatus->dbStatusCode;
            $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Vrati informace o prihlasenem uzivateli
     * 
     * @return dbUserInfo
     * 
     */
    public function GetUserInfoFromLogin()
    {
        $this->NullRetInfo();
	$Input=array('dbDummy'=>"");
	
        try {
            $Output = $this->AccessWS()->GetUserInfoFromLogin($Input);
            $this->StatusCode = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            return $Output->dbUserInfo;
	} catch (Exception $e) {
            $this->StatusCode    = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
        }

    }

    /**
     * Vrati udaj o expiraci hesla
     *
     * @return pswExpDate
     *
     */
    public function GetPasswordInfo()
    {

        $this->NullRetInfo();

        $Input=array('dbDummy'=>"");
	
        try {
            $Output=$this->AccessWS()->GetPasswordInfo($Input);
            $this->StatusCode = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            return $Output->pswExpDate;
	} catch (Exception $e) {
            $this->StatusCode    = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    /**
     * Zmeni heslo prihlaseneho uzivatele
     *
     * @param dbOldPassword     $OldPassword    Stare heslo
     * @param dbNewPassword     $NewPassword    Nove heslo
     * @return bool
     * 
     */
    public function ChangeISDSPassword($OldPassword,$NewPassword)
    {

        $this->NullRetInfo();
	$Input=array(
            'dbOldPassword'=>$OldPassword,
            'dbNewPassword'=>$NewPassword
        );
	
        try {
            $Output=$this->AccessWS->ChangeISDSPassword($Input);
            $this->StatusCode = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            return($this->StatusCode == "0000");
	} catch (Exception $e) {
            $this->StatusCode    = $Output->dbStatus->dbStatusCode;
            $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
            $this->ErrorCode     = $e->getCode();
            $this->ErrorInfo     = $e->getMessage();
            return false;
	}
    }

    

    /************************************************************************
     * Pomocne funkce
     ************************************************************************/

    
    protected function NullRetInfo()
    {
        $this->ErrorInfo = "";
	$this->StatusCode = "";
	$this->StatusMessage = "";
        return true;
    }

    protected function GetServiceURL($serviceType, $loginType = null, $portalType = null)
    {

        if ( is_null($loginType) ) {
            $loginType = $this->type;
        }
        if ( is_null($portalType) ) {
            $portalType = $this->portal;
        }

        // https://ws1.czebox.cz/DS/DsManage
	$res = "https://ws1";

	switch ($portalType):
            case 0:
                $res = $res.".czebox.cz/";
                break;
            case 1:
		$res = $res.".mojedatovaschranka.cz/";
		break;
	endswitch;

	switch ($loginType):
	case 1:
		$res = $res."cert/";
		break;
	case 2:
		$res = $res."hspis/";
		break;
	endswitch;
	$res = $res."DS/";
	switch ($serviceType):
            case 0:
		$res = $res."dz";
    		break;
            case 1:
		$res = $res."dx";
		break;
            case 2:
		$res = $res."DsManage";
		break;
            case 3:
		$res = $res."DsManage";
		break;
	endswitch;

	return $res;
    }

    protected function GetServiceWSDL($serviceType)
    {
        switch ($serviceType):
            case 0:
		return LIBS_DIR ."/isds/dm_operations.wsdl";
            case 1:
		return LIBS_DIR ."/isds/dm_info.wsdl";
            case 2:
		return LIBS_DIR ."/isds/db_manipulations.wsdl";
            case 3:
		return LIBS_DIR ."/isds/db_access.wsdl";
	endswitch;
    }

    /**
    *
    */
    public function userAgent() {

        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
            $user_agent = "OSS Spisova sluzba v". $app_info[0];
        } else {
            $user_agent = "OSS Spisova sluzba v3";
        }

        $curli = @curl_version();
        // OSS Spisovka v3.0 (i686-pc-linux-gnu) libcurl 7.7.3 (OpenSSL 0.9.6)
        if(isset($curli['host'])) $user_agent .= " (". $curli['host'] .")";
        if(isset($curli['version'])) $user_agent .= " libcurl ". $curli['version'] ."";
        if(isset($curli['ssl_version'])) $user_agent .= " (". $curli['ssl_version'] .")";

        return $user_agent;

    }

    protected function debug($message, $variable = null)
    {
        if ( $this->debug == 1 ) {

            echo "<pre>";
            echo $message;
            if ( !is_null($variable) ) {
                echo "\n";
                print_r($variable);
            }
            echo "</pre>";
        }

    }

    /**
     *
     * @param string $logintype         zpusob prihlaseni. 0=jmeno heslo, 1=spisovka (certifikat), 2=hostovana spisovka (jmeno, heslo, certifikat)
     * @param string $loginname         prihlasovaci jmeno
     * @param string $password          prihlasovaci heslo
     * @param string $certifilename     cesta k certifikatu
     * @param string $passphrase        heslo k soukromemu klici v certifikatu
     *
     */
    //public function InitCurl($logintype, $loginname, $password, $certfilename, $passphrase)
    public function InitCurl($logintype, $params)
    {

        $this->debug("===========================\nInitCurl\n=================");
        $this->debug("Parametry:");
        $this->debug("  logintype: ". $logintype);
        $this->debug("  params: ", $params);

	$this->ch = curl_init();

	curl_setopt($this->ch, CURLOPT_POST, true);
	curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
	curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($this->ch, CURLOPT_UNRESTRICTED_AUTH,false);
	curl_setopt($this->ch, CURLOPT_NOBODY,false);

	if (!empty($params['login'])) {
            $this->debug("   CURLOPT_USERPWD: ". $params['login'].":".$params['password']);
            curl_setopt($this->ch, CURLOPT_USERPWD,$params['login'].":".$params['password']);
	}

	// na Linuxu nastavit verzi 3, na Windows ne !
	if (stristr(PHP_OS,'WIN') === false) {
            curl_setopt($this->ch, CURLOPT_SSLVERSION,3);
	}

	if ($logintype != 0) {
            curl_setopt($this->ch, CURLOPT_SSLCERT,$params['local_cert']);
            curl_setopt($this->ch, CURLOPT_SSLCERTPASSWD,$params['passphrase']);
	}

        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // kasle se na https certy
	if (!empty($params['proxy_host'])) {
            curl_setopt($this->ch, CURLOPT_PROXY, 'http://'.$params['proxy_host'].':'.$params['proxy_port']);
            curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, true);
            if (!empty($params['proxy_login'])) {
                curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $params['proxy_login'].':'.$params['proxy_password']);
            }
	}

        $this->debug("   url: ". $this->GetServiceURL(0,$logintype));
	curl_setopt($this->ch, CURLOPT_URL, $this->GetServiceURL(0,$logintype));
	curl_setopt($this->ch, CURLOPT_POSTFIELDS,
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
            "    <soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\n".
            "  <soap:Body>\n".
            "    <DummyOperation xmlns=\"http://isds.czechpoint.cz\">\n".
            "    </DummyOperation>\n".
            "  </soap:Body>\n".
            "</soap:Envelope>\n");

        $this->debug(">>>> CURL_EXEC");
        $response = curl_exec($this->ch);
        $this->debug(">>>>    CurlErrNo: ". curl_errno($this->ch));
        $this->debug(">>>>    CurlError: ". curl_error($this->ch));
        $this->debug(">>>>    response: ". $response);
       

    }

    public function __destruct()
    {
        if ( isset($this->ch) ) {
            if ($this->ch != 0) {
                try {
                    curl_close($this->ch);
                } catch (Exception $e) {
                }
            }
        }
    }

}

class ISDSSoapClient extends SoapClient
{
    public $ch;

    private $params;

    public function __construct($wsdl, $options, $params) {

        parent::__construct($wsdl, $options);
        $this->params = $params;

    }

    public function __doRequest($request, $location, $action, $version, $one_way = null) {

        $headers = array(
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: '.$this->userAgent(),
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "'.$action.'"'
	);

        $this->__last_request_headers = $headers;

	curl_setopt($this->ch, CURLOPT_URL, $location);
	curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($this->ch, CURLOPT_POSTFIELDS,$request);

	$response = curl_exec($this->ch);

	if (curl_errno($this->ch) != 0) {
            throw new exception('CurlError: '.curl_errno($this->ch).' Message:'.curl_error($this->ch));
	}

	return $response;
    }

    public function userAgent() {

        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
            $user_agent = "OSS Spisova sluzba v". $app_info[0];
        } else {
            $user_agent = "OSS Spisova sluzba v3";
        }

        $curli = @curl_version();
        // OSS Spisovka v3.0 (i686-pc-linux-gnu) libcurl 7.7.3 (OpenSSL 0.9.6)
        if(isset($curli['host'])) $user_agent .= " (". $curli['host'] .")";
        if(isset($curli['version'])) $user_agent .= " SoapClient via libcurl ". $curli['version'] ."";
        if(isset($curli['ssl_version'])) $user_agent .= " (". $curli['ssl_version'] .")";

        return $user_agent;

    }

    protected function debug($message, $variable = null)
    {

        echo "<pre>";
        echo $message;
        if ( !is_null($variable) ) {
            var_dump($variable);
        }
        echo "</pre>";

    }

}


/**
 * Trida pouzita pro pridavani souboru do odesilane zpravy
 */
class ISDSSentOutFiles {

    private $FileInfos = array();
    private $FullFileNames = array();

    public function  __construct() {
        ;
    }

    /**
     * Prida soubor
     *
     * @param <type> $file         bytove pole obsahujici soubor
     * @param <type> $MimeType     Typ souboru v MIME zapisu, napr. application/pdf nebo image/tiff
     * @param <type> $MetaType     Typ pisemnosti
     * @param <type> $Guid         Nepovinny interni identifikator tohoto dokumentu - pro vytvareni stromu zavislosti dokumentu
     * @param <type> $UpFileGuid   Nepovinny interni identifikator nadrizeneho dokumentu (napr. pro vztah soubor - podpis aj.)
     * @param <type> $FileDescr    Muze obsahovat jmeno souboru, prip. jiny popis. Objevi se v seznamu priloh na portale
     * @param <type> $Format       Nepovinny udaj - odkaz na definici formulare
     * @param <type> $FullFileName
     * @return bool
     */
    public function AddFileSpecFromMemory($file, $MimeType, $MetaType, $Guid, $UpFileGuid, $FileDescr, $Format, $FullFileName)
    {
        QX("MimeType: ".$MimeType);
	QX("MetaType: ".$MetaType);
	QX("Guid: ".$Guid);
	QX("UpFileGuid: ".$UpFileGuid);
	QX("FileDescr: ".$FileDescr);
	QX("Format: ".$Format);

        $dmFile=array(
            'dmMimeType'=>$MimeType,
            'dmFileMetaType'=>$MetaType,
            'dmFileGuid'=>$Guid,
            'dmUpFileGuid'=>$UpFileGuid,
            'dmFileDescr'=>$FileDescr,
            'dmFormat'=>$Format,
            'dmEncodedContent'=>$file
	);
	
        if ($this->FileInfos == null) {
            $this->FileInfos[0] = $dmFile;
            $this->FullFileNames[0] = $FullFileName;
	} else {
            $this->FileInfos[count($this->FileInfos)]=$dmFile;
            $this->FullFileNames[count($this->FullFileNames)]=$FullFileName;
	}

	return true;
    }

    /**
     *
     * @param <type> $FullFileName  Uplne jmeno souboru (vcetne cesty)
     * @param <type> $MimeType      Typ souboru v MIME zapisu, napr. application/pdf nebo image/tiff
     * @param <type> $MetaType      Typ pisemnosti
     * @param <type> $Guid          Nepovinny interni identifikator tohoto dokumentu - pro vytvareni stromu zavislosti dokumentu
     * @param <type> $UpFileGuid    Nepovinny interni identifikator nadrizeneho dokumentu (napr. pro vztah soubor - podpis aj.)
     * @param <type> $FileDescr     Muze obsahovat jmeno souboru, prip. jiny popis. Objevi se v seznamu priloh na portale
     * @param <type> $Format        Nepovinny udaj - odkaz na definici formulare
     * @return bool
     */
    public function AddFileSpecFromFile($FullFileName, $MimeType, $MetaType, $Guid, $UpFileGuid, $FileDescr, $Format)
    {

        if (!file_exists($FullFileName)) {
            return false;
	}

	$file = file_get_contents($FullFileName);
	if (!$file) {
            return false;
	}

	return $this->AddFileSpecFromMemory($file,$MimeType,$MetaType,$Guid,$UpFileGuid,$FileDescr,$Format,$FullFileName);
    }

    /**
     * zjednodusena funkce pro pridani souboru
     *
     * @param <type> $FullFileName  Uplne jmeno souboru (vcetne cesty)
     * @param <type> $MimeType      Typ souboru v MIME zapisu, napr. application/pdf nebo image/tiff
     * @return object
     */
    public function AddFile($FullFileName,$MimeType)
    {

        if ($this->FileInfos == null) {
            $MetaType="main";
	} else {
            $MetaType="enclosure";
	}

	$path_parts = pathinfo($FullFileName);
	
        return $this->AddFileSpecFromFile(
                        $FullFileName,
			$MimeType,
			$MetaType,"","",$path_parts['basename'],""
                );
    }

}


