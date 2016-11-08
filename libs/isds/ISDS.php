<?php

// Modul pro komunikaci s ISDS
//
// (c) 2009 Software602 a.s.
// (c) 2010-2015 blue.point Solutions, s.r.o.
// (c) 2016 Good Sailors, s.r.o.

class ISDS
{

    private $portal = 0; // 0 = czebox.cz, 1 = mojedatovaschranka.cz
    private $login_type = 0; // 0 = basic, 1 = systemovy certifikat
    private $soap_params = array();
    private $StatusCode;      // status operace vraceny ISDS
    private $StatusMessage;
    private $operations_WS;
    protected $ssl_verify_peer = true;
    protected $logger = null;

    /**
     * @param int    $portalType    rezim pristupu k portalu (0 = czebox.cz, 1 = mojedatovaschranka.cz)
     * @param int    $type          rezim prihlaseni ()
     * @param string $login         prihlasovaci jmeno
     * @param string $password      prihlasovaci heslo
     * @param string $certfilename  cesta k certifikatu
     * @param string $passphrase    heslo k zasifrovanemu klici
     */
    public function __construct($portalType, $type, $login, $password, $certfilename = null, $passphrase
    = null)
    {
        $this->portal = $portalType;
        $this->login_type = $type;

        $contextOptions = array(
            'ssl' => array(
                'verify_peer' => $this->ssl_verify_peer,
                'cafile' => LIBS_DIR . '/isds/cacert.pem',
                'verify_depth' => 5,
                'SNI_enabled' => true,
                'ciphers' => 'ALL!EXPORT!EXPORT40!EXPORT56!aNULL!LOW!RC4'
            )
        );
        // Pozor! V PHP 5.6 je nějaký bug a pokud se řídím dokumentací, tak SOAP nefunguje
        // (server vrátí Bad Request).
        // option peer_name je rozbitý
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            $key = 'CN_match';
            $contextOptions['ssl'][$key] = $portalType == 1 ? 'mojedatovaschranka.cz' : 'czebox.cz';
        }
        $sslContext = stream_context_create($contextOptions);

        $params = array(
            'trace' => false, // mame vlastni tridu pro ladeni
            'exceptions' => true,
            'user_agent' => $this->GetUserAgent(),
            'stream_context' => $sslContext
        );

        switch ($type) {
            case 0: // basic autentizace
                $params['login'] = $login;
                $params['password'] = $password;
                break;
            case 1: // systemovy certifikat
                $params['local_cert'] = $certfilename;
                $params['passphrase'] = $passphrase;
                break;
            default: // neplatny typ
                throw new InvalidArgumentException();
        }

        $this->soap_params = $params;
    }

    /*     * ************************************************************************
     * Web services 
     * ************************************************************************ */

    protected function GetSoapClient($wsdl)
    {
        $params = $this->soap_params;
        $params['location'] = $this->GetServiceURL($wsdl);

        if ($this->logger)
            $client = new DebugSoapClient($this->GetServiceWSDL($wsdl), $params, $this->logger);
        else
            $client = new SoapClient($this->GetServiceWSDL($wsdl), $params);

        return $client;
    }

    /**
     *  OperationsWS
     *  - WSDL pro sluzby pracujici s datovymi zpravami
     *
     * @return SoapClient
     */
    protected function OperationsWS()
    {
        if (!$this->operations_WS)
            $this->operations_WS = $this->GetSoapClient('operations');
        return $this->operations_WS;
    }

    /**
     *  InfoWS
     *   - WSDL pro sluzby informacniho charakteru
     *
     * @return SoapClient
     */
    protected function InfoWS()
    {
        return $this->GetSoapClient('info');
    }

    /**
     *  ManipulationsWS
     *  - WSDL pro sluzby manipulujicimi s datovymi schrankami
     *
     * @return SoapClient
     */
    protected function ManipulationsWS()
    {
        return $this->GetSoapClient('manipulations');
    }

    /**
     *  AccessWS
     *  - WSDL pro doplňkové služby související s přihlašováním
     *     *
     * @return SoapClient
     */
    protected function AccessWS()
    {
        return $this->GetSoapClient('access');
    }

    /*     * ************************************************************************
     * Operace datové schránky
     * ************************************************************************ */

    /**
     * Vrati udaje o majiteli schranky, ke ktere jsme prihlaseni
     * 
     * @return dbOwnerInfo
     */
    public function GetOwnerInfoFromLogin()
    {
        $Input = array('dbDummy' => "");

        $output = $this->AccessWS()->GetOwnerInfoFromLogin($Input);

        $this->StatusCode = $output->dbStatus->dbStatusCode;
        $this->StatusMessage = $output->dbStatus->dbStatusMessage;

        if (isset($output->dbOwnerInfo)) {
            return $output->dbOwnerInfo;
        } else {
            return null;
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
    $IDRecipient, $Annotation, $AllowSubstDelivery, $LegalTitleLaw, $LegalTitlePar, $LegalTitlePoint, $LegalTitleSect, $LegalTitleYear, $RecipientIdent, $RecipientOrgUnit, $RecipientOrgUnitNum, $RecipientRefNumber, $SenderIdent, $SenderOrgUnit, $SenderOrgUnitNum, $SenderRefNumber, $ToHands, $PersonalDelivery, $OVM, $outFiles)
    {
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
            'dmOVM' => $OVM);

        $messageCreateInput = array(
            'dmEnvelope' => $envelope,
            'dmFiles' => $outFiles->fileInfos());

        $messageCreateOutput = $this->OperationsWS()->CreateMessage($messageCreateInput);
        $messageID = $messageCreateOutput->dmID;
        $messageStatus = $messageCreateOutput->dmStatus;
        $this->StatusCode = $messageStatus->dmStatusCode;
        $this->StatusMessage = $messageStatus->dmStatusMessage;
        return $messageID;
    }

    /**
     * Vytvori hromadnou datovou zpravu a umisti ji do danych schranek
     *
     * $Recipients = array(
     *                  dbIDRecipient = ID adresata
     *                  dmRecipientOrgUnit = Organizacni jednotka adresata slovne
     *                  dmRecipientOrgUnitNum = Cislo organizacni jednotky adresata
     *                  dmToHands = popis komu je zasilka urcena
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
    $Recipients, $Annotation, $AllowSubstDelivery, $LegalTitleLaw, $LegalTitlePar, $LegalTitlePoint, $LegalTitleSect, $LegalTitleYear, $RecipientIdent, $RecipientRefNumber, $SenderIdent, $SenderOrgUnit, $SenderOrgUnitNum, $SenderRefNumber, $PersonalDelivery, $OVM, $OutFiles)
    {
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
            'dmOVM' => $OVM
        );

        $MultipleMessageCreateInput = array(
            'dmRecipients' => $Recipients,
            'dmEnvelope' => $Envelope,
            'dmFiles' => $OutFiles->fileInfos()
        );

        $MultipleMessageCreateOutput = $this->OperationsWS()->CreateMultipleMessage($MultipleMessageCreateInput);
        $MessageStatus = $MultipleMessageCreateOutput->dmStatus;
        $this->StatusCode = $MessageStatus->dmStatusCode;
        $this->StatusMessage = $MessageStatus->dmStatusMessage;
        $return = $this->PrepareArray($MultipleMessageCreateOutput->dmMultipleStatus->dmSingleStatus);
        return $return;
    }

    /**
     * Stazeni prijate zpravy z ISDS bez elektronickeho podpisu. Vrati stazenou zpravu.
     *
     * @param int $MessageID    Identifikace zpravy
     * @return dmReturnedMessage
     */
    public function MessageDownload($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $MessageDownloadOutput = $this->OperationsWS()->MessageDownload($MessInput);
        $ReturnedMessage = $MessageDownloadOutput->dmReturnedMessage;
        $MessageStatus = $MessageDownloadOutput->dmStatus;
        $this->StatusCode = $MessageStatus->dmStatusCode;
        $this->StatusMessage = $MessageStatus->dmStatusMessage;
        $ReturnedMessage->dmDm->dmFiles->dmFile = $this->PrepareArray($ReturnedMessage->dmDm->dmFiles->dmFile);
        return $ReturnedMessage;
    }

    /**
     * Stazeni podepsane prijate zpravy. Vrati stazenou zpravu v binarnim formatu.
     *
     * @param int $MessageID
     * @return dmSignature
     */
    public function SignedMessageDownload($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $SignedMessDownOutput = $this->OperationsWS()->SignedMessageDownload($MessInput);
        $ReturnedMessage = $SignedMessDownOutput->dmSignature;
        $MessageStatus = $SignedMessDownOutput->dmStatus;
        $this->StatusCode = $MessageStatus->dmStatusCode;
        $this->StatusMessage = $MessageStatus->dmStatusMessage;
        return $ReturnedMessage;
    }

    /**
     * Stazeni podepsane odeslane zpravy. Vrati stazenou zpravu v binarnim formatu.
     * 
     * @param int $MessageID Identifikace zpravy
     * @return dmSignature
     */
    public function SignedSentMessageDownload($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $SignedMessDownOutput = $this->OperationsWS()->SignedSentMessageDownload($MessInput);
        $ReturnedMessage = $SignedMessDownOutput->dmSignature;
        $MessageStatus = $SignedMessDownOutput->dmStatus;
        $this->StatusCode = $MessageStatus->dmStatusCode;
        $this->StatusMessage = $MessageStatus->dmStatusMessage;
        return $ReturnedMessage;
    }

    /**
     * DummyOperation
     */
    public function DummyOperation()
    {
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
        $MessInput = array('dmID' => $MessageID);

        $MessageVerifyOutput = $this->InfoWS()->VerifyMessage($MessInput);
        $Status = $MessageVerifyOutput->dmStatus;
        $this->StatusCode = $Status->dmStatusCode;
        $this->StatusMessage = $Status->dmStatusMessage;
        return $MessageVerifyOutput->dmHash;
    }

    /**
     * Overeni platnosti zpravy
     *
     * @param string kompletní datová zpráva nebo doručenka v base64 kódování
     * @return dmAuthResult
     */
    public function AuthenticateMessage($source)
    {
        $dmMessage = array('dmMessage' => $source);
        $dmAuthResult = $this->OperationsWS()->AuthenticateMessage($dmMessage);
        $this->StatusCode = $dmAuthResult->dmStatus->dmStatusCode;
        $this->StatusMessage = $dmAuthResult->dmStatus->dmStatusMessage;
        return $dmAuthResult->dmStatus->dmStatusCode == '0000';
    }

    /**
     * Stazeni pouhe obalky prijate zpravy (bez pisemnosti). Vrati obalku zpravy.
     *
     * @param int $MessageID
     * @return dmReturnedMessageEnvelope
     */
    public function MessageEnvelopeDownload($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $MessEnvelDownOutput = $this->InfoWS()->MessageEnvelopeDownload($MessInput);
        $Status = $MessEnvelDownOutput->dmStatus;
        $this->StatusCode = $Status->dmStatusCode;
        $this->StatusMessage = $Status->dmStatusMessage;
        return $MessEnvelDownOutput->dmReturnedMessageEnvelope;
    }

    /**
     * Vybrana dorucena zprava bude oznacena jako prectena.
     * 
     * @param int $MessageID Identifikace zpravy
     * @return bool
     */
    public function MarkMessageAsDownloaded($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $MarkMessOut = $this->InfoWS()->MarkMessageAsDownloaded($MessInput);
        $this->StatusCode = $MarkMessOut->dmStatus->dmStatusCode;
        $this->StatusMessage = $MarkMessOut->dmStatus->dmStatusMessage;
        return true;
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

        $B = array();
        $B[0] = $A;
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
        $MessInput = array('dmID' => $MessageID);

        $DeliveryMessageOutput = $this->InfoWS()->GetDeliveryInfo($MessInput);

        $this->StatusCode = $DeliveryMessageOutput->dmStatus->dmStatusCode;
        $this->StatusMessage = $DeliveryMessageOutput->dmStatus->dmStatusMessage;

        $DeliveryMessageOutput->dmDelivery->dmEvents->dmEvent = $this->PrepareArray($DeliveryMessageOutput->dmDelivery->dmEvents->dmEvent);
        return $DeliveryMessageOutput->dmDelivery;
    }

    /**
     * Stazeni podepsane informace o dodani, doruceni nebo nedoruceni zpravy
     *
     * @param int $MessageID Identifikace zpravy
     * @return dmSignature
     */
    public function GetSignedDeliveryInfo($MessageID)
    {
        $MessInput = array('dmID' => $MessageID);

        $SignDeliveryMessOutput = $this->InfoWS()->GetSignedDeliveryInfo($MessInput);
        $this->StatusCode = $SignDeliveryMessOutput->dmStatus->dmStatusCode;
        $this->StatusMessage = $SignDeliveryMessOutput->dmStatus->dmStatusMessage;
        return $SignDeliveryMessOutput->dmSignature;
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
        $ListOfSentInput = array(
            'dmFromTime' => $FromTime,
            'dmToTime' => $ToTime,
            'dmSenderOrgUnitNum' => $SenderOrgUnitNum,
            'dmStatusFilter' => $StatusFilter,
            'dmOffset' => $Offset,
            'dmLimit' => $Limit
        );

        $ListOfSentOutput = $this->InfoWS()->GetListOfSentMessages($ListOfSentInput);
        $this->StatusCode = $ListOfSentOutput->dmStatus->dmStatusCode;
        $this->StatusMessage = $ListOfSentOutput->dmStatus->dmStatusMessage;

        if (isset($ListOfSentOutput->dmRecords->dmRecord)) {
            $ListOfSentOutput->dmRecords->dmRecord = $this->PrepareArray($ListOfSentOutput->dmRecords->dmRecord);
            return $ListOfSentOutput->dmRecords;
        } else {
            return null;
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
     * @param dmSenderOrgUnitNum $RecipientOrgUnitNum  Organizacni slozka odesilatele (z ciselniku)
     * @return dmRecords
     */
    public function GetListOfReceivedMessages($FromTime, $ToTime, $Offset, $Limit, $StatusFilter, $RecipientOrgUnitNum)
    {
        $ListOfReceivedInput = array(
            'dmFromTime' => $FromTime,
            'dmToTime' => $ToTime,
            'dmRecipientOrgUnitNum' => $RecipientOrgUnitNum,
            'dmStatusFilter' => $StatusFilter,
            'dmOffset' => $Offset,
            'dmLimit' => $Limit
        );

        $ListOfRecOutput = $this->InfoWS()->GetListOfReceivedMessages($ListOfReceivedInput);
        $this->StatusCode = $ListOfRecOutput->dmStatus->dmStatusCode;
        $this->StatusMessage = $ListOfRecOutput->dmStatus->dmStatusMessage;

        if (isset($ListOfRecOutput->dmRecords->dmRecord)) {
            $ListOfRecOutput->dmRecords->dmRecord = $this->PrepareArray($ListOfRecOutput->dmRecords->dmRecord);
            return $ListOfRecOutput->dmRecords;
        } else {
            return null;
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
     * @param string    $MiddleName
     * @param string    $LastName
     * @param string    $LastNameAtBirth
     * @param string    $firmName
     * @param string    $biDate
     * @param string    $biCity
     * @param string    $biCounty
     * @param string    $biState
     * @param string    $adCity
     * @param string    $adStreet
     * @param string    $adNumberInStreet
     * @param string    $adNumberInMunicipality
     * @param string    $adZipCode
     * @param string    $adState
     * @param string    $nationality
     * @param string    $email
     * @param string    $telNumber
     * @return dbResults
     *
     */
    public function FindDataBox($IdDb, $Type, $dbState, $ic, $FirstName, $MiddleName, $LastName, $LastNameAtBirth, $firmName, $biDate, $biCity, $biCounty, $biState, $adCity, $adStreet, $adNumberInStreet, $adNumberInMunicipality, $adZipCode, $adState, $nationality, $email, $telNumber)
    {
        $OwnerInfo = array(
            'dbID' => $IdDb,
            'dbType' => $Type,
            'dbState' => $dbState,
            'ic' => $ic,
            'pnFirstName' => $FirstName,
            'pnMiddleName' => $MiddleName,
            'pnLastName' => $LastName,
            'pnLastNameAtBirth' => $LastNameAtBirth,
            'firmName' => $firmName,
            'biDate' => $biDate,
            'biCity' => $biCity,
            'biCounty' => $biCounty,
            'biState' => $biState,
            'adCity' => $adCity,
            'adStreet' => $adStreet,
            'adNumberInStreet' => $adNumberInStreet,
            'adNumberInMunicipality' => $adNumberInMunicipality,
            'adZipCode' => $adZipCode,
            'adState' => $adState,
            'nationality' => $nationality,
            'email' => $email,
            'telNumber' => $telNumber
        );

        $FindInput = array('dbOwnerInfo' => $OwnerInfo);

        $FindOutput = $this->ManipulationsWS()->FindDataBox($FindInput);
        $this->StatusCode = $FindOutput->dbStatus->dbStatusCode;
        $this->StatusMessage = $FindOutput->dbStatus->dbStatusMessage;
        $FindOutput->dbResults->dbOwnerInfo = $this->PrepareArray($FindOutput->dbResults->dbOwnerInfo);
        return $FindOutput->dbResults;
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
        $Inputdb = array('dbID' => $DataBoxID);

        $Output = $this->ManipulationsWS()->CheckDataBox($Inputdb);
        $this->StatusCode = $Output->dbStatus->dbStatusCode;
        $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
        return $Output->dbState;
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

        for ($i = 1; $i <= 9; $i++) {
            $Result[$i] = 0;
        }

        $Step = 512;
        $Start = 1;

        do {
            if ($received) {
                $Records = $this->GetListOfReceivedMessages('2000', '3000', $Start, $Step,
                        1023, null);
            } else {
                $Records = $this->GetListOfSentMessages('2000', '3000', $Start, $Step, 1023,
                        null);
            }

            $Start = $Start + $Step;
            $NumOf = count($Records->dmRecord);
            for ($i = 0; $i < $NumOf; $i++) {
                $Result[$Records->dmRecord[$i]->dmMessageStatus] ++;
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
        $Step = 512;
        $Start = 1;

        do {
            $Records = $this->GetListOfReceivedMessages('2000', '3000', $Start, $Step, 1023,
                    null);
            $Start = $Start + $Step;
            $NumOf = count($Records->dmRecord);
            for ($i = 0; $i < $NumOf; $i++) {
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
        $MessInput = array('dmID' => $MessageID);

        $ConDerOut = $this->InfoWS()->ConfirmDelivery($MessInput);
        $this->StatusCode = $ConDerOut->dmStatus->dmStatusCode;
        $this->StatusMessage = $ConDerOut->dmStatus->dmStatusMessage;
        return true;
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
        $IDDBInput = array('dbID' => $BoxID);

        $ReqStatusOut = $this->ManipulationsWS()->SetOpenAddressing($IDDBInput);
        $this->StatusCode = $ReqStatusOut->dbStatus->dbStatusCode;
        $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
        return true;
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
        $IDDBInput = array('dbID' => $BoxID);

        $ReqStatusOut = $this->ManipulationsWS()->ClearOpenAddressing($IDDBInput);
        $this->StatusCode = $ReqStatusOut->dbStatus->dbStatusCode;
        $this->StatusMessage = $ReqStatusOut->dbStatus->dbStatusMessage;
        return true;
    }

    /**
     * Vrati informace o prihlasenem uzivateli
     * 
     * @return dbUserInfo
     * 
     */
    public function GetUserInfoFromLogin()
    {
        $Input = array('dbDummy' => "");

        $Output = $this->AccessWS()->GetUserInfoFromLogin($Input);
        $this->StatusCode = $Output->dbStatus->dbStatusCode;
        $this->StatusMessage = $Output->dbStatus->dbStatusMessage;

        if (isset($Output->dbUserInfo)) {
            return $Output->dbUserInfo;
        } else {
            return null;
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
        $Input = array('dbDummy' => "");

        $Output = $this->AccessWS()->GetPasswordInfo($Input);
        $this->StatusCode = $Output->dbStatus->dbStatusCode;
        $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
        return $this->StatusCode == "0000" ? $Output->pswExpDate : null;
    }

    /**
     * Zmeni heslo prihlaseneho uzivatele
     *
     * @param dbOldPassword     $OldPassword    Stare heslo
     * @param dbNewPassword     $NewPassword    Nove heslo
     * @return bool
     * 
     */
    public function ChangeISDSPassword($OldPassword, $NewPassword)
    {
        $Input = array(
            'dbOldPassword' => $OldPassword,
            'dbNewPassword' => $NewPassword
        );

        $Output = $this->AccessWS()->ChangeISDSPassword($Input);
        $this->StatusCode = $Output->dbStatus->dbStatusCode;
        $this->StatusMessage = $Output->dbStatus->dbStatusMessage;
        return $this->StatusCode == "0000";
    }

    /*     * **********************************************************************
     * Pomocne funkce
     * ********************************************************************** */

    protected function GetServiceURL($serviceType)
    {
        $loginType = $this->login_type;
        $portalType = $this->portal;

        // https://ws1.czebox.cz/DS/DsManage
        $res = "https://ws1";
        if ($loginType != 0)
            $res .= 'c';

        switch ($portalType) {
            case 0:
                $res = $res . ".czebox.cz/";
                break;
            case 1:
                $res = $res . ".mojedatovaschranka.cz/";
                break;
        }

        switch ($loginType) {
            case 1:
                $res = $res . "cert/";
                break;
        }

        $res = $res . "DS/";

        switch ($serviceType) {
            case 'operations':
                $res = $res . "dz";
                break;
            case 'info':
                $res = $res . "dx";
                break;
            case 'manipulations':
                $res = $res . "DsManage";
                break;
            case 'access':
                $res = $res . "DsManage";
                break;
        }

        return $res;
    }

    protected function GetServiceWSDL($serviceType)
    {
        switch ($serviceType) {
            case 'operations':
                $filename = "dm_operations.wsdl";
                break;
            case 'info':
                $filename = "dm_info.wsdl";
                break;
            case 'manipulations':
                $filename = "db_manipulations.wsdl";
                break;
            case 'access':
                $filename = "db_access.wsdl";
                break;
            default:
                throw new InvalidArgumentException();
        }

        return LIBS_DIR . "/isds/$filename";
    }

    public function GetStatusCode()
    {
        return $this->StatusCode;
    }

    /**
     * @return string
     */
    public function GetStatusMessage()
    {
        return $this->StatusCode . " - " . $this->StatusMessage;
    }

    /**
     *
     */
    protected function GetUserAgent()
    {
        $app_info = new VersionInformation();
        $user_agent = "OSS Spisova sluzba v" . $app_info->version;
        return $user_agent;
    }

}

//class CurlSoapClient extends SoapClient
//{
//
//    public $ch;
//
//    public function __doRequest($request, $location, $action, $version, $one_way = null)
//    {
//        $headers = array(
//            'Method: POST',
//            'Connection: Keep-Alive',
//            'User-Agent: ' . $this->userAgent(),
//            'Content-Type: text/xml; charset=utf-8',
//            'SOAPAction: "' . $action . '"'
//        );
//
//        $this->__last_request_headers = $headers;
//
//        curl_setopt($this->ch, CURLOPT_URL, $location);
//        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $request);
//
//        $response = curl_exec($this->ch);
//
//        if (curl_errno($this->ch) != 0) {
//            throw new exception('CurlError: ' . curl_errno($this->ch) . ' Message:' . curl_error($this->ch));
//        }
//
//        return $response;
//    }
//
//}

class DebugSoapClient extends SoapClient
{

    private $logger;

    public function __construct($wsdl, $options, $logger)
    {
        $this->logger = $logger;
        $out = "SOAP Client: $wsdl\n------------\n";
        $this->logger->log($out);

        parent::__construct($wsdl, $options);
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $time = date(DATE_ATOM);
        $out = "\nSOAP Request\n------------\n"
                . "Time: $time\nLocation: $location\nAction: $action\n";
        $this->logger->log($out);

        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        $type = gettype($response);
        $result = $type;
        if ($type == 'string')
            $result .= ", length = " . strlen($response);
        $out = "Result: $result\n";
        $this->logger->log($out);

        $this->logger->log($response, 3);
        $this->logger->log("\n\n");

        return $response;
    }

    public function __call($function_name, $arguments)
    {
        $out = "SOAP Call: $function_name\n----------\n";
        $this->logger->log($out);
        /**
         * Nezapisuj binární datovou zprávu u operace AuthenticateMessage
         */
        if ($function_name != 'AuthenticateMessage')
            $this->logger->log(print_r($arguments[0], true), 2);

        return parent::__soapCall($function_name, $arguments);
    }

}

/**
 * Trida pouzita pro pridavani souboru do odesilane zpravy
 */
class ISDSSentOutFiles
{

    private $FileInfos = array();
    private $FullFileNames = array();

    public function fileInfos()
    {
        return $this->FileInfos;
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
        $dmFile = array(
            'dmMimeType' => $MimeType,
            'dmFileMetaType' => $MetaType,
            'dmFileGuid' => $Guid,
            'dmUpFileGuid' => $UpFileGuid,
            'dmFileDescr' => $FileDescr,
            'dmFormat' => $Format,
            'dmEncodedContent' => $file
        );

        if ($this->FileInfos == null) {
            $this->FileInfos[0] = $dmFile;
            $this->FullFileNames[0] = $FullFileName;
        } else {
            $this->FileInfos[count($this->FileInfos)] = $dmFile;
            $this->FullFileNames[count($this->FullFileNames)] = $FullFileName;
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

        return $this->AddFileSpecFromMemory($file, $MimeType, $MetaType, $Guid, $UpFileGuid,
                        $FileDescr, $Format, $FullFileName);
    }

    /**
     * zjednodusena funkce pro pridani souboru
     *
     * @param <type> $FullFileName  Uplne jmeno souboru (vcetne cesty)
     * @param <type> $MimeType      Typ souboru v MIME zapisu, napr. application/pdf nebo image/tiff
     * @return object
     */
    public function AddFile($FullFileName, $MimeType)
    {
        if ($this->FileInfos == null) {
            $MetaType = "main";
        } else {
            $MetaType = "enclosure";
        }

        $path_parts = pathinfo($FullFileName);

        return $this->AddFileSpecFromFile(
                        $FullFileName, $MimeType, $MetaType, "", "", $path_parts['basename'],
                        ""
        );
    }

}
