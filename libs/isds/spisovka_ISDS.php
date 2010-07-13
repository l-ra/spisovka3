<?php

class spisovka_ISDS extends ISDSBox
{

    public $ISDSBox;
    public $idDS;
    public $error_code;
    public $error_text;

    function spisovka_ISDS() {

    }

    /**
     * Pripoji se k ISDS
     *
     * @param string $login
     * @param string $pass
     * @return tISDSBox
     */
    function pripojit($login,$pass) {

        $ISDSBox = $this->ISDSBox(0,$login,$pass,"","");

        if ( $this->StatusCode == "0000" ) {
            return $ISDSBox;
        } else {
            $this->error_code = $this->StatusCode;
            $this->error_text = $this->ErrorInfo;
            return null;
        }
    }

    /**
     * Nastavit id datove schranky
     *
     * @param string $id
     * @return bool
     */
    function idDatoveSchranky($id) {
        $this->idDS = $id;
        return true;
    }

    function informaceDS($idDS=null) {

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
            if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
            {
                return $Result;
            } else {
                return "nic";
            }
        }
    }

    /**
     * Vrati pole s pocty prijatych zasilek, indexovane stavem zasilek
     * @return array(int)
     */
    function pocetPrichozichZprav($assoc=0) {

        $slovne = array(1=>"podana",2=>"dostala razitko",3=>"neprosla antivirem",
                        4=>"dodana",5=>"dorucena fikci",
                        6=>"dorucena prihlasenim",7=>"prectena",
                        8=>"nedorucitelna",9=>"smazana");

        $Results=$this->GetNumOfMessages(true);
        if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
        {
            if ($assoc==1) {
                $tmp = array();
                foreach ($Results as $num => $value) {
                    $tmp[$slovne[$num]] = $value;
                }
                return $tmp;
            } else {
                return $Results;
            }
        } else {
            return null;
        }

    }

    function unix2time($unixtime) {

        return date("c",$unixtime);

    }

    function seznamPrichozichZprav($od=null,$do=null) {

        if($od==null) $od = time();
        if($do==null) $do = time();
        $od = $this->unix2time($od);
        $do = $this->unix2time($do);

        $Records = $this->GetListOfReceivedMessages($od,$do,0,10000,1023,null); // 1023

        if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
        {
            return $Records->dmRecord;
        } else {
            return null;
        }
        
    }

    /**
     * Vrati pole s pocty odeslanych zasilek, indexovane stavem zasilek
     * @return array(int)
     */
    function pocetOdeslanychZprav($assoc=0) {

        $slovne = array(1=>"podana",2=>"dostala razitko",3=>"neprosla antivirem",
                        4=>"dodana",5=>"dorucena fikci",
                        6=>"dorucena prihlasenim",7=>"prectena",
                        8=>"nedorucitelna",9=>"smazana");

        $Results=$this->GetNumOfMessages(false);
        if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
        {
            if ($assoc==1) {
                $tmp = array();
                foreach ($Results as $num => $value) {
                    $tmp[$slovne[$num]] = $value;
                }
                return $tmp;
            } else {
                return $Results;
            }
        } else {
            return null;
        }

    }

    function seznamOdeslanychZprav($od=null,$do=null) {

        if($od==null) $od = now();
        if($do==null) $do = now();
        $od = $this->unix2time($od);
        $do = $this->unix2time($do);

        $Records = $this->GetListOfSentMessages($od,$do,0,10000,1023,null);

        if (($this->StatusCode == "0000") && ($this->ErrorInfo == ""))
        {
            //return $Records->dmRecord;
            return $Records;
        } else {
            return null;
        }

    }

    function input($string,$default=null) {
        
        if(isset($string)) {
            if(empty($string)) {
                return $default;
            } else {
                return $string;
            }
        } else {
            return $default;
        }
    }

    function input_e($string,$default=null) {

        if(isset($string)) {
            if(empty($string)) {
                return addslashes($default);
            } else {
                return addslashes($string);
            }
        } else {
            return addslashes($default);
        }
    }

	function poslatZpravu($zprava,$soubory=null) {

        if (is_null($zprava) || !is_array($zprava)) {
            return null;
        }

		$komu = $this->input($zprava['dbIDRecipient']);// Identifikace adresata
        if (is_null($komu)) return null;

        /*
		$Envelope = array(

            'dbIDRecipient' => $IDRecipient,
			'dmRecipientOrgUnit' => $RecipientOrgUnit,
			'dmRecipientOrgUnitNum' => $RecipientOrgUnitNum,
            'dmRecipientRefNumber' => $RecipientRefNumber,
            'dmRecipientIdent' => $RecipientIdent,

			'dmAnnotation' => $Annotation,
			
			'dmSenderRefNumber' => $SenderRefNumber,
			'dmSenderIdent' => $SenderIdent,
            'dmSenderOrgUnit' => $SenderOrgUnit,
			'dmSenderOrgUnitNum' => $SenderOrgUnitNum,
			
			'dmLegalTitleLaw' => $LegalTitleLaw,
			'dmLegalTitleYear' => $LegalTitleYear,
			'dmLegalTitleSect' => $LegalTitleSect,
			'dmLegalTitlePar' => $LegalTitlePar,
			'dmLegalTitlePoint' => $LegalTitlePoint,

            'dmToHands' => $ToHands,
            'dmPersonalDelivery' => $PersonalDelivery,
			'dmAllowSubstDelivery' => $AllowSubstDelivery);
        */
        
		$MessageCreateInput = array(
			'dmEnvelope' => $zprava,
			'dmFiles' => $soubory->FileInfos);
		try
		{
			$MessageCreateOutput = $this->OperationsWS->CreateMessage($MessageCreateInput);
		}
		catch (Exception $e)
		{
			$this->ErrorInfo=$e->getMessage();
			return false;
		}
		$MessageID=$MessageCreateOutput->dmID;
		$MessageStatus=$MessageCreateOutput->dmStatus;
		$this->StatusCode=$MessageStatus->dmStatusCode;
		$this->StatusMessage=$MessageStatus->dmStatusMessage;
		return $MessageID;
	}

    function prectiZpravu($id_zpravy) {
        
        $Message = $this->MessageDownload($id_zpravy);
        //$Message = $this->MessageEnvelopeDownload($id_zpravy);
        if (($this->StatusCode == "0000") && ($this->ErrorInfo == "")) {

            return $Message;
            /*
            $DM=$Message->dmDm;
        	$files=$Message->dmDm->dmFiles->dmFile;
            $NumOfFiles=count($files);
        	for ($i=0; $i < $NumOfFiles; $i++)
            {
        		$file=$files[$i];
        	}
            */
        
        } else {
            return null;
        }
    }

    function hledatDS($adresa) {

        if (is_null($adresa) || !is_array($adresa)) {
            return null;
        }

        $OwnerInfo = array();
        if ( isset($adresa['dbId']) && !@empty($adresa['dbId']) )
            $OwnerInfo['dbId'] = $adresa['dbId'];
        if ( isset($adresa['dbType']) && !@empty($adresa['dbType']) )
            $OwnerInfo['dbType'] = $adresa['dbType'];
        if ( isset($adresa['ic']) && !@empty($adresa['ic']) )
            $OwnerInfo['ic'] = $adresa['ic'];
        if ( isset($adresa['pnFirstName']) && !@empty($adresa['pnFirstName']) )
            $OwnerInfo['pnFirstName'] = $adresa['pnFirstName'];
        if ( isset($adresa['pnMiddleName']) && !@empty($adresa['pnMiddleName']) )
            $OwnerInfo['pnMiddleName'] = $adresa['pnMiddleName'];
        if ( isset($adresa['pnLastName']) && !@empty($adresa['pnLastName']) )
            $OwnerInfo['pnLastName'] = $adresa['pnLastName'];
        if ( isset($adresa['pnLastNameAtBirth']) && !@empty($adresa['pnLastNameAtBirth']) )
            $OwnerInfo['pnLastNameAtBirth'] = $adresa['pnLastNameAtBirth'];
        if ( isset($adresa['firmName']) && !@empty($adresa['firmName']) )
            $OwnerInfo['firmName'] = $adresa['firmName'];
        if ( isset($adresa['biDate']) && !@empty($adresa['biDate']) )
            $OwnerInfo['biDate'] = $adresa['biDate'];
        if ( isset($adresa['biCity']) && !@empty($adresa['biCity']) )
            $OwnerInfo['biCity'] = $adresa['biCity'];
        if ( isset($adresa['biCounty']) && !@empty($adresa['biCounty']) )
            $OwnerInfo['biCounty'] = $adresa['biCounty'];
        if ( isset($adresa['adCity']) && !@empty($adresa['adCity']) )
            $OwnerInfo['adCity'] = $adresa['adCity'];
        if ( isset($adresa['adStreet']) && !@empty($adresa['adStreet']) )
            $OwnerInfo['adStreet'] = $adresa['adStreet'];
        if ( isset($adresa['adNumberInStreet']) && !@empty($adresa['adNumberInStreet']) )
            $OwnerInfo['adNumberInStreet'] = $adresa['adNumberInStreet'];
        if ( isset($adresa['adNumberInMunicipality']) && !@empty($adresa['adNumberInMunicipality']) )
            $OwnerInfo['adNumberInMunicipality'] = $adresa['adNumberInMunicipality'];
        if ( isset($adresa['adZipCode']) && !@empty($adresa['adZipCode']) )
            $OwnerInfo['adZipCode'] = $adresa['adZipCode'];

        $FindInput=array('dbOwnerInfo'=>$OwnerInfo);
        //return $FindInput;
        //break;
	try
	{
            $FindOutput=$this->SearchWS->FindDataBox($FindInput);
	}
	catch (Exception $e)
	{
            $this->ErrorInfo=$e->getMessage();
            return false;
	}
        $this->StatusCode=$FindOutput->dbStatus->dbStatusCode;
	$this->StatusMessage=$FindOutput->dbStatus->dbStatusMessage;
	$FindOutput->dbResults->dbOwnerInfo = $this->PrepareArray($FindOutput->dbResults->dbOwnerInfo);
	return $FindOutput->dbResults->dbOwnerInfo;


    }

}

?>
