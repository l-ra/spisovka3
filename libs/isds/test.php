<?php

// Ukazkovy skript pro komunikaci s ISDS
//
// (c) 2009 Software602 a.s.
//
// Pripadne dotazy a pripominky zasilejte na isds@602.cz

require_once('ISDS.php');	

function Tisk($s)
// pomocna funkce
{
	echo($s."\r\n");
}

// prihlaseni k ISDS - ZDE JE TREBA DOPLNIT PRIHLASOVACI JMENO, HESLO A ID SCHRANKY

$loginname="";
$password="";
$boxid="";

// pri pripojovani pomoci certifikatu je treba zadat soubor s certifikatem v PEM tvaru a pripadne heslo pro privatni klic

$cert="";
$passphrase="";

$ISDSBox=new ISDSBox(0,$loginname,$password,$cert,$passphrase);

// overeni prihlaseni

if (!$ISDSBox->ValidLogin)
{
	Tisk("Nepodarilo se pripojit k ISDS. ErrorInfo: ".$ISDSBox->ErrorInfo);
	exit();
}

// vraceni informaci o schrance ke ktere jsme pripojeni

$Result=$ISDSBox->GetOwnerInfoFromLogin();
Tisk('GetOwnerInfoFromLogin: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Informace o schrance:');
	Tisk('dbID: '.$Result->dbID);
	Tisk('dbType: '.$Result->dbType);
	Tisk('ic: '.$Result->ic);
	Tisk('pnFirstName: '.$Result->pnFirstName);
	Tisk('pnMiddleName: '.$Result->pnMiddleName);
	Tisk('pnLastName: '.$Result->pnLastName);
	Tisk('pnLastNameAtBirth: '.$Result->pnLastNameAtBirth);
	Tisk('firmName: '.$Result->firmName);
	Tisk('biDate: '.$Result->biDate);
	Tisk('biCity: '.$Result->biCity);
	Tisk('biCounty: '.$Result->biCounty);
	Tisk('biState: '.$Result->biState);
	Tisk('adCity: '.$Result->adCity);
	Tisk('adStreet: '.$Result->adStreet);
	Tisk('adNumberInStreet: '.$Result->adNumberInStreet);
	Tisk('adNumberInMunicipality: '.$Result->adNumberInMunicipality);
	Tisk('adZipCode: '.$Result->adZipCode);
	Tisk('adState: '.$Result->adState);
	Tisk('nationality: '.$Result->nationality);
	Tisk('email: '.$Result->email);
	Tisk('telNumber: '.$Result->telNumber);
	Tisk('identifier: '.$Result->identifier);
	Tisk('registryCode: '.$Result->registryCode);
	Tisk('dbState: '.$Result->dbState);
	Tisk('dbEffectiveOVM: '.$Result->dbEffectiveOVM);
	Tisk('dbOpenAddressing: '.$Result->dbOpenAddressing);
}

// vytvoreni testovaciho adresare

$TestDir="C:\\testisdsPHP";
if (!file_exists($TestDir))
{
	if (!mkdir($TestDir))
	{
		Tisk("Nelze vytvorit testovaci adresar ".$TestDir);
		exit();
	}
}
$TestDir=$TestDir."\\";

// vytvoreni testovacich souboru

$TestFileName1=$TestDir."TESTISDS_1.TXT";
$TestFileName2=$TestDir."TESTISDS_2.TXT";
if (!file_put_contents($TestFileName1,"Prvni testovaci soubor",FILE_TEXT))
{
	Tisk("Nelze vytvorit testoavci soubor ".$TestFileName1);
	exit();
}
if (!file_put_contents($TestFileName2,"Druhy testovaci soubor",FILE_TEXT))
{
	Tisk("Nelze vytvorit testoavci soubor ".$TestFileName2);
	exit();
}

// priprava souboru pripojenych ke zprave

$SentOutFiles=new ISDSSentOutFiles();
$SentOutFiles->AddFile($TestFileName1,"text/plain");
$SentOutFiles->AddFile($TestFileName2,"text/plain");

// vytvoreni zpravy

$TestMessageID=$ISDSBox->CreateMessage("hjyaavk","Testovaci zasilka z PHP",
	false,
	null,
	"231", "179", "331", 2010,
	"VZ-147", "Vase org jednotka", -1, "vcj. 253",
	"NZ-557", "Nase org jednotka", -1, "ncj. 589",
	"K rukam p.Novaka", false,
	null,
	$SentOutFiles);

Tisk('CreateMessage - MessageID: '.$TestMessageID.' StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);

Tisk('---------------------------');

Tisk('Cekame 5 vterin aby zprava prosla antivirovou kontrolou a dostala casove razitko');
sleep(5);

// nacteni seznamu prijatych zasilek

$today='2009-11-01T00:00:00';	
$tomorrow='2010-10-01T00:00:00';
$Records=$ISDSBox->GetListOfReceivedMessages($today,$tomorrow,0,100,1023,null);
Tisk('GetListOfReceivedMessages: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	$NumOf=count($Records->dmRecord);
	Tisk("Nalezeno ".$NumOf." zasilek:");
	for ($i=0; $i < $NumOf; $i++)
	{
		$Record=$Records->dmRecord[$i];
		Tisk('------------------------');
		Tisk('Poradove cislo zpravy v seznamu: '.$Record->dmOrdinal);
		Tisk('ID zasilky: '.$Record->dmID);
		Tisk('Identifikace schranky odesilatele: '.$Record->dbIDSender);
		Tisk('Odesilatel slovne: '.$Record->dmSender);
		Tisk('Typ odesilatele: '.$Record->dmSenderType);
		Tisk('Postovni adresa odesilatele: '.$Record->dmSenderAddress);
		Tisk('Prijemce slovne: '.$Record->dmRecipient);
		Tisk('Postovni adresa prijemce: '.$Record->dmRecipientAddress);
		Tisk('Organizacni jednotka odesilatele slovne: '.$Record->dmSenderOrgUnit);
		Tisk('Organizacni jednotka odesilatele hodnotou z ciselniku: '.$Record->dmSenderOrgUnitNum);
		Tisk('Identifikace schranky adresata: '.$Record->dbIDRecipient);
		Tisk('Organizacni jednotka prijemce slovne: '.$Record->dmRecipientOrgUnit);
		Tisk('Organizacni jednotka prijemce hodnotou z ciselniku: '.$Record->dmRecipientOrgUnitNum);
		Tisk('K rukam vlastnika datove schranky: '.$Record->dmToHands);
		Tisk('Textova poznamka: '.$Record->dmAnnotation);
		Tisk('Cislo jednaci ze strany prijemce: '.$Record->dmRecipientRefNumber);
		Tisk('Cislo jednaci ze strany odesilatele: '.$Record->dmSenderRefNumber);
		Tisk('Spisova znacka ze strany prijemce: '.$Record->dmRecipientIdent);
		Tisk('Spisova znacka ze strany odesilatele: '.$Record->dmSenderIdent);
		Tisk('Zmocneni - cislo zakona: '.$Record->dmLegalTitleLaw);
		Tisk('Zmocneni - rok vydani zakona: '.$Record->dmLegalTitleYear);
		Tisk('Zmocneni - paragraf v zakone: '.$Record->dmLegalTitleSect);
		Tisk('Zmocneni - odstavec v paragrafu: '.$Record->dmLegalTitlePar);
		Tisk('Zmocneni - pismeno v odstavci: '.$Record->dmLegalTitlePoint);
		Tisk('Do vlastních rukou: '.$Record->dmPersonalDelivery);
		Tisk('Nahradni doruceni povoleno: '.$Record->dmAllowSubstDelivery);
		Tisk('Ambiguous recipient: '.$Record->dmAmbiguousRecipient);
		Tisk('MessageStatus: '.$Record->dmMessageStatus);
		Tisk('DeliveryTime: '.$Record->dmDeliveryTime);
		Tisk('AcceptanceTime: '.$Record->dmAcceptanceTime);
		Tisk('Velikost priloh: '.$Record->dmAttachmentSize.' kB');
		if ($i == 0)
		{
			$ReceivedMessageID = $Record->dmID;
		}
	}
}

Tisk('---------------------------');

// stazeni zpravy

$Message=$ISDSBox->MessageDownload($ReceivedMessageID);
Tisk('MessageDownload: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk("Stazena zprava:");
	$DM=$Message->dmDm;

	Tisk('MessageStatus: '.$Message->dmMessageStatus);
	Tisk('DeliveryTime: '.$Message->dmDeliveryTime);
	Tisk('AcceptanceTime: '.$Message->dmAcceptanceTime);
	Tisk('Velikost priloh: '.$Message->dmAttachmentSize.' kB');
	$fileTimeStamp=$TestDir."QTIMESTAMP1";
	if (file_put_contents($fileTimeStamp,$Message->dmQTimestamp))
	{
		Tisk('TimeStamp ulozen do '.$fileTimeStamp);
	}

	Tisk('Hash - Algoritmus: '.$Message->dmHash->algorithm);
	$fileHash=$TestDir."HASH";
	if (file_put_contents($fileHash,$Message->dmHash->_))
	{
		Tisk('Vlastni hash ulozen do '.$fileHash);
	}
	
	Tisk('Identifikace zpravy: '.$Message->dmDm->dmID);
	Tisk('Identifikace schranky odesilatele: '.$Message->dmDm->dbIDSender);
	Tisk('Odesilatel slovne: '.$Message->dmDm->dmSender);
	Tisk('Postovni adresa odesilatele: '.$Message->dmDm->dmSenderAddress);
	Tisk('Prijemce slovne: '.$Message->dmDm->dmRecipient);
	Tisk('Postovni adresa prijemce: '.$Message->dmDm->dmRecipientAddress);
	Tisk('Organizacni jednotka odesilatele slovne: '.$Message->dmDm->dmSenderOrgUnit);
	Tisk('Organizacni jednotka odesilatele hodnotou z ciselniku: '.$Message->dmDm->dmSenderOrgUnitNum);
	Tisk('Identifikace schranky adresata: '.$Message->dmDm->dbIDRecipient);
	Tisk('Organizacni jednotka prijemce slovne: '.$Message->dmDm->dmRecipientOrgUnit);
	Tisk('Organizacni jednotka prijemce hodnotou z ciselniku: '.$Message->dmDm->dmRecipientOrgUnitNum);
	Tisk('K rukam vlastnika datove schranky: '.$Message->dmDm->dmToHands);
	Tisk('Textova poznamka: '.$Message->dmDm->dmAnnotation);
	Tisk('Cislo jednaci ze strany prijemce: '.$Message->dmDm->dmRecipientRefNumber);
	Tisk('Cislo jednaci ze strany odesilatele: '.$Message->dmDm->dmSenderRefNumber);
	Tisk('Spisova znacka ze strany prijemce: '.$Message->dmDm->dmRecipientIdent);
	Tisk('Spisova znacka ze strany odesilatele: '.$Message->dmDm->dmSenderIdent);
	Tisk('Zmocneni - cislo zakona: '.$Message->dmDm->dmLegalTitleLaw);
	Tisk('Zmocneni - rok vydani zakona: '.$Message->dmDm->dmLegalTitleYear);
	Tisk('Zmocneni - paragraf v zakone: '.$Message->dmDm->dmLegalTitleSect);
	Tisk('Zmocneni - odstavec v paragrafu: '.$Message->dmDm->dmLegalTitlePar);
	Tisk('Zmocneni - pismeno v odstavci: '.$Message->dmDm->dmLegalTitlePoint);
	Tisk('Do vlastních rukou: '.$Message->dmDm->dmPersonalDelivery);
	Tisk('Nahradni doruceni povoleno: '.$Message->dmDm->dmAllowSubstDelivery);
	Tisk('Ambiguous recipient: '.$Message->dmDM->dmAmbiguousRecipient);
	
	$files=$Message->dmDm->dmFiles->dmFile;
	$NumOfFiles=count($files);
	Tisk('Zasilka obsahuje '.$NumOfFiles.' souboru');

	for ($i=0; $i < $NumOfFiles; $i++)
	{
		Tisk($i.' soubor:');
		$file=$files[$i];
		Tisk('MimeType: '.$file->dmMimeType);
		Tisk('MetaType: '.$file->dmFileMetaType);
		Tisk('FileGuid: '.$file->dmFileGuid);
		Tisk('UpFileGuid: '.$file->dmUpFileGuid);
		Tisk('FileDescr: '.$file->dmFileDescr);
		Tisk('Format: '.$file->dmFormat);
		$fileName=$TestDir.$file->dmFileDescr;
		if (file_put_contents($fileName,$file->dmEncodedContent))
		{
			Tisk('Soubor ulozen do '.$fileName);
		}
	}
}

Tisk('---------------------------');

// stazeni zpravy v podepsanem tvaru

if ($ISDSBox->SignedMessageDownloadToFile($ReceivedMessageID,$TestDir."MESSAGE.ZFO"))
{
	Tisk("Podepsana zasilka byla ulozena do souboru ".$TestDir."MESSAGE.ZFO");
}
else
{
	Tisk('SignedMessageDownload: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
}

Tisk('---------------------------');

// stazeni odeslane zpravy v podepsanem tvaru

if ($ISDSBox->SignedSentMessageDownloadToFile($TestMessageID,$TestDir."MESSAGESENT.ZFO"))
{
	Tisk("Podepsana odeslana zasilka byla ulozena do souboru ".$TestDir."MESSAGESENT.ZFO");
}
else
{
	Tisk('SignedSentMessageDownload: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
}

Tisk('---------------------------');

// overeni hashe zasilky

$Hash=$ISDSBox->VerifyMessage($ReceivedMessageID);
Tisk('VerifyMessage: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Hash - Algoritmus: '.$Hash->algorithm);
	$fileHash=$TestDir."HASH2";
	if (file_put_contents($fileHash,$Hash->_))
	{
		Tisk('Vlastni hash ulozen do '.$fileHash);
	}

}

Tisk('---------------------------');

// stazeni samotne obalky zpravy

$Envelope=$ISDSBox->MessageEnvelopeDownload($ReceivedMessageID);
Tisk('MessageEnvelopeDownload: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Obalka:');
	$DM=$Envelope->dmDm;
	Tisk('Identifikace zpravy: '.$DM->dmID);
	Tisk('Identifikace schranky odesilatele: '.$DM->dbIDSender);
	Tisk('Odesilatel slovne: '.$DM->dmSender);
	Tisk('Typ odesilatele: '.$DM->dmSenderType);
	Tisk('Postovni adresa odesilatele: '.$DM->dmSenderAddress);
	Tisk('Prijemce slovne: '.$DM->dmRecipient);
	Tisk('Postovni adresa prijemce: '.$DM->dmRecipientAddress);
	Tisk('Organizacni jednotka odesilatele slovne: '.$DM->dmSenderOrgUnit);
	Tisk('Organizacni jednotka odesilatele hodnotou z ciselniku: '.$DM->dmSenderOrgUnitNum);
	Tisk('Identifikace schranky adresata: '.$DM->dbIDRecipient);
	Tisk('Organizacni jednotka prijemce slovne: '.$DM->dmRecipientOrgUnit);
	Tisk('Organizacni jednotka prijemce hodnotou z ciselniku: '.$DM->dmRecipientOrgUnitNum);
	Tisk('K rukam vlastnika datove schranky: '.$DM->dmToHands);
	Tisk('Textova poznamka: '.$DM->dmAnnotation);
	Tisk('Cislo jednaci ze strany prijemce: '.$DM->dmRecipientRefNumber);
	Tisk('Cislo jednaci ze strany odesilatele: '.$DM->dmSenderRefNumber);
	Tisk('Spisova znacka ze strany prijemce: '.$DM->dmRecipientIdent);
	Tisk('Spisova znacka ze strany odesilatele: '.$DM->dmSenderIdent);
	Tisk('Zmocneni - cislo zakona: '.$DM->dmLegalTitleLaw);
	Tisk('Zmocneni - rok vydani zakona: '.$DM->dmLegalTitleYear);
	Tisk('Zmocneni - paragraf v zakone: '.$DM->dmLegalTitleSect);
	Tisk('Zmocneni - odstavec v paragrafu: '.$DM->dmLegalTitlePar);
	Tisk('Zmocneni - pismeno v odstavci: '.$DM->dmLegalTitlePoint);
	Tisk('Do vlastních rukou: '.$DM->dmPersonalDelivery);
	Tisk('Nahradni doruceni povoleno: '.$DM->dmAllowSubstDelivery);
	Tisk('Ambiguous recipient: '.$DM->dmAmbiguousRecipient);

	$Hash=$Envelope->dmHash;
	Tisk('Hash - Algoritmus: '.$Hash->algorithm);
	$fileHash=$TestDir."HASH3";
	if (file_put_contents($fileHash,$Hash->_))
	{
		Tisk('Vlastni hash ulozen do '.$fileHash);
	}

	$fileTimeStamp=$TestDir."QTIMESTAMP2";
	if (file_put_contents($fileTimeStamp,$Envelope->dmQTimestamp))
	{
		Tisk('TimeStamp ulozen do '.$fileTimeStamp);
	}

	Tisk('MessageStatus: '.$Envelope->dmMessageStatus);
	Tisk('DeliveryTime: '.$Envelope->dmDeliveryTime);
	Tisk('AcceptanceTime: '.$Envelope->dmAcceptanceTime);
	Tisk('Velikost priloh: '.$Envelope->dmAttachmentSize.' kB');
}

Tisk('---------------------------');

// oznaceni zasilky jako prectene

$ISDSBox->MarkMessageAsDownloaded($ReceivedMessageID);
Tisk('MarkMessageAsDownloaded: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);

Tisk('---------------------------');

// stazeni dorucenky

$DeliveryInfo=$ISDSBox->GetDeliveryInfo($TestMessageID);
Tisk('GetDeliveryInfo: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Dorucenka:');
	$DM=$DeliveryInfo->dmDm;
	Tisk('Identifikace zpravy: '.$DM->dmID);
	Tisk('Identifikace schranky odesilatele: '.$DM->dbIDSender);
	Tisk('Odesilatel slovne: '.$DM->dmSender);
	Tisk('Typ odesilatele: '.$DM->dmSenderType);
	Tisk('Postovni adresa odesilatele: '.$DM->dmSenderAddress);
	Tisk('Prijemce slovne: '.$DM->dmRecipient);
	Tisk('Postovni adresa prijemce: '.$DM->dmRecipientAddress);
	Tisk('Organizacni jednotka odesilatele slovne: '.$DM->dmSenderOrgUnit);
	Tisk('Organizacni jednotka odesilatele hodnotou z ciselniku: '.$DM->dmSenderOrgUnitNum);
	Tisk('Identifikace schranky adresata: '.$DM->dbIDRecipient);
	Tisk('Organizacni jednotka prijemce slovne: '.$DM->dmRecipientOrgUnit);
	Tisk('Organizacni jednotka prijemce hodnotou z ciselniku: '.$DM->dmRecipientOrgUnitNum);
	Tisk('K rukam vlastnika datove schranky: '.$DM->dmToHands);
	Tisk('Textova poznamka: '.$DM->dmAnnotation);
	Tisk('Cislo jednaci ze strany prijemce: '.$DM->dmRecipientRefNumber);
	Tisk('Cislo jednaci ze strany odesilatele: '.$DM->dmSenderRefNumber);
	Tisk('Spisova znacka ze strany prijemce: '.$DM->dmRecipientIdent);
	Tisk('Spisova znacka ze strany odesilatele: '.$DM->dmSenderIdent);
	Tisk('Zmocneni - cislo zakona: '.$DM->dmLegalTitleLaw);
	Tisk('Zmocneni - rok vydani zakona: '.$DM->dmLegalTitleYear);
	Tisk('Zmocneni - paragraf v zakone: '.$DM->dmLegalTitleSect);
	Tisk('Zmocneni - odstavec v paragrafu: '.$DM->dmLegalTitlePar);
	Tisk('Zmocneni - pismeno v odstavci: '.$DM->dmLegalTitlePoint);
	Tisk('Do vlastních rukou: '.$DM->dmPersonalDelivery);
	Tisk('Nahradni doruceni povoleno: '.$DM->dmAllowSubstDelivery);
	Tisk('Ambiguous recipient: '.$DM->dmAmbiguousRecipient);

	$Hash=$DeliveryInfo->dmHash;
	Tisk('Hash - Algoritmus: '.$Hash->algorithm);
	$fileHash=$TestDir."HASH4";
	if (file_put_contents($fileHash,$Hash->_))
	{
		Tisk('Vlastni hash ulozen do '.$fileHash);
	}

	$fileTimeStamp=$TestDir."QTIMESTAMP3";
	if (file_put_contents($fileTimeStamp,$DeliveryInfo->dmQTimestamp))
	{
		Tisk('TimeStamp ulozen do '.$fileTimeStamp);
	}

	Tisk('MessageStatus: '.$DeliveryInfo->dmMessageStatus);
	Tisk('DeliveryTime: '.$DeliveryInfo->dmDeliveryTime);
	Tisk('AcceptanceTime: '.$DeliveryInfo->dmAcceptanceTime);
	Tisk('Velikost priloh: '.$DeliveryInfo->dmAttachmentSize.' kB');

	$Events=$DeliveryInfo->dmEvents->dmEvent;
	$NumOf=count($Events);
	Tisk('Dorucenka obsahuje '.$NumOf.' udalosti');
	for ($i=0; $i < count($Events); $i++)
	{
		$Event=$Events[$i];
		Tisk($i.' udalost:');
		Tisk('Cas: '.$Event->dmEventTime);
		Tisk('Popis: '.$Event->dmEventDescr);
	}
}

Tisk('---------------------------');

// stazeni dorucenky v podepsanem tvaru

if ($ISDSBox->GetSignedDeliveryInfoToFile($TestMessageID,$TestDir."DELIVERY.ZFO"))
{
	Tisk("Podepsana dorucenka byla ulozena do souboru ".$TestDir."DELIVERY.ZFO");
}
else
{
	Tisk('GetSignedDeliveryInfoToFile: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
}

Tisk('---------------------------');

// nacteni seznamu odeslanych zasilek

$today='2009-05-13T00:00:00';	
$tomorrow='2009-05-14T00:00:00';
$Records=$ISDSBox->GetListOfSentMessages($today,$tomorrow,0,100,1023,null);
Tisk('GetListOfSentMessages: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	$NumOf=count($Records->dmRecord);
	Tisk("Nalezeno ".$NumOf." zasilek:");
	for ($i=0; $i < $NumOf; $i++)
	{
		$Record=$Records->dmRecord[$i];
		Tisk('------------------------');
		Tisk('Poradove cislo zpravy v seznamu: '.$Record->dmOrdinal);
		Tisk('ID zasilky: '.$Record->dmID);
		Tisk('Identifikace schranky odesilatele: '.$Record->dbIDSender);
		Tisk('Odesilatel slovne: '.$Record->dmSender);
		Tisk('Typ odesilatele: '.$Record->dmSenderType);
		Tisk('Postovni adresa odesilatele: '.$Record->dmSenderAddress);
		Tisk('Prijemce slovne: '.$Record->dmRecipient);
		Tisk('Postovni adresa prijemce: '.$Record->dmRecipientAddress);
		Tisk('Organizacni jednotka odesilatele slovne: '.$Record->dmSenderOrgUnit);
		Tisk('Organizacni jednotka odesilatele hodnotou z ciselniku: '.$Record->dmSenderOrgUnitNum);
		Tisk('Identifikace schranky adresata: '.$Record->dbIDRecipient);
		Tisk('Organizacni jednotka prijemce slovne: '.$Record->dmRecipientOrgUnit);
		Tisk('Organizacni jednotka prijemce hodnotou z ciselniku: '.$Record->dmRecipientOrgUnitNum);
		Tisk('K rukam vlastnika datove schranky: '.$Record->dmToHands);
		Tisk('Textova poznamka: '.$Record->dmAnnotation);
		Tisk('Cislo jednaci ze strany prijemce: '.$Record->dmRecipientRefNumber);
		Tisk('Cislo jednaci ze strany odesilatele: '.$Record->dmSenderRefNumber);
		Tisk('Spisova znacka ze strany prijemce: '.$Record->dmRecipientIdent);
		Tisk('Spisova znacka ze strany odesilatele: '.$Record->dmSenderIdent);
		Tisk('Zmocneni - cislo zakona: '.$Record->dmLegalTitleLaw);
		Tisk('Zmocneni - rok vydani zakona: '.$Record->dmLegalTitleYear);
		Tisk('Zmocneni - paragraf v zakone: '.$Record->dmLegalTitleSect);
		Tisk('Zmocneni - odstavec v paragrafu: '.$Record->dmLegalTitlePar);
		Tisk('Zmocneni - pismeno v odstavci: '.$Record->dmLegalTitlePoint);
		Tisk('Do vlastních rukou: '.$Record->dmPersonalDelivery);
		Tisk('Nahradni doruceni povoleno: '.$Record->dmAllowSubstDelivery);
		Tisk('Ambiguous recipient: '.$Record->dmAmbiguousRecipient);
		Tisk('MessageStatus: '.$Record->dmMessageStatus);
		Tisk('DeliveryTime: '.$Record->dmDeliveryTime);
		Tisk('AcceptanceTime: '.$Record->dmAcceptanceTime);
		Tisk('Velikost priloh: '.$Record->dmAttachmentSize.' kB');
	}
}

Tisk('---------------------------');


// vyhledani datove schranky a overeni jeji dostupnosti

$Results=$ISDSBox->FindDataBox(null,"OVM",1,null,null,null,null,null,"test",null,null,null,null,null,null,null,null,null,null,null,null,null);
Tisk('FindDataBox: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	$NumOf=count($Results->dbOwnerInfo);
	Tisk('Nalezeno '.$NumOf.' schranek');
	for ($i=0; $i < $NumOf; $i++)
	{
		$Result=$Results->dbOwnerInfo[$i];
		Tisk('------------------------');
		Tisk('dbID: '.$Result->dbID);
		Tisk('dbType: '.$Result->dbType);
		Tisk('ic: '.$Result->ic);
		Tisk('pnFirstName: '.$Result->pnFirstName);
		Tisk('pnMiddleName: '.$Result->pnMiddleName);
		Tisk('pnLastName: '.$Result->pnLastName);
		Tisk('pnLastNameAtBirth: '.$Result->pnLastNameAtBirth);
		Tisk('firmName: '.$Result->firmName);
		Tisk('biDate: '.$Result->biDate);
		Tisk('biCity: '.$Result->biCity);
		Tisk('biCounty: '.$Result->biCounty);
		Tisk('biState: '.$Result->biState);
		Tisk('adCity: '.$Result->adCity);
		Tisk('adStreet: '.$Result->adStreet);
		Tisk('adNumberInStreet: '.$Result->adNumberInStreet);
		Tisk('adNumberInMunicipality: '.$Result->adNumberInMunicipality);
		Tisk('adZipCode: '.$Result->adZipCode);
		Tisk('adState: '.$Result->adState);
		Tisk('nationality: '.$Result->nationality);
		Tisk('email: '.$Result->email);
		Tisk('telNumber: '.$Result->telNumber);
		Tisk('identifier: '.$Result->identifier);
		Tisk('registryCode: '.$Result->registryCode);
		Tisk('dbState: '.$Result->dbState);
		Tisk('dbEffectiveOVM: '.$Result->dbEffectiveOVM);
		Tisk('dbOpenAddressing: '.$Result->dbOpenAddressing);

		// overeni datove schranky

		$DataBoxState=$ISDSBox->CheckDataBox($Result->dbID);
		Tisk('CheckDataBox: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
		if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
		{
			Tisk("Schranka ".$Result->dbID." stav: ".$DataBoxState);
		}
	}
}

Tisk('---------------------------');

$ISDSBox->MarkAllReceivedMessagesAsDownloaded();
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Vsechny dosle zasilky ve schrance byly oznaceny jako prectene');
}

Tisk('---------------------------');

// zjisteni poctu doslych zasilek ve schrance

$Results=$ISDSBox->GetNumOfMessages(true);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Doslych zasilek ve schrance:');
	Tisk($Results[1].' podanych');
	Tisk($Results[2].' co dostaly razitko');
	Tisk($Results[3].' co neprosly antivirem');
	Tisk($Results[4].' dodanych');
	Tisk($Results[5].' dorucenych fikci');
	Tisk($Results[6].' dorucenych prihlasenim');
	Tisk($Results[7].' prectenych');
	Tisk($Results[8].' nedorucitelnych (znepristupnena schranka po odeslani)');
	Tisk($Results[9].' smazanych');
}

Tisk('---------------------------');

// zjisteni poctu odeslanych zasilek ve schrance

$Results=$ISDSBox->GetNumOfMessages(false);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Odeslanych zasilek ve schrance:');
	Tisk($Results[1].' podanych');
	Tisk($Results[2].' co dostaly razitko');
	Tisk($Results[3].' co neprosly antivirem');
	Tisk($Results[4].' dodanych');
	Tisk($Results[5].' dorucenych fikci');
	Tisk($Results[6].' dorucenych prihlasenim');
	Tisk($Results[7].' prectenych');
	Tisk($Results[8].' nedorucitelnych (znepristupnena schranka po odeslani)');
	Tisk($Results[9].' smazanych');
}

Tisk('---------------------------');

// potvrzeni prijeti komercni zpravy

$ISDSBox->ConfirmDelivery($ReceivedMessageID);
Tisk('ConfirmDelivery: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);

Tisk('---------------------------');

// povoleni prijmu komercnich zprav

$ISDSBox->SetOpenAddressing($boxid);
Tisk('SetOpenAddressing: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);

Tisk('---------------------------');

// zakazani prijmu komercnich zprav

$ISDSBox->ClearOpenAddressing($boxid);
Tisk('ClearOpenAddressing: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);

Tisk('---------------------------');

// informace o prihlasenem uzivateli

$Result=$ISDSBox->GetUserInfoFromLogin();
Tisk('GetUserInfoFromLogin: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Informace o uzivateli:');
	Tisk('pnFirstName: '.$Result->pnFirstName);
	Tisk('pnMiddleName: '.$Result->pnMiddleName);
	Tisk('pnLastName: '.$Result->pnLastName);
	Tisk('pnLastNameAtBirth: '.$Result->pnLastNameAtBirth);
	Tisk('adCity: '.$Result->adCity);
	Tisk('adStreet: '.$Result->adStreet);
	Tisk('adNumberInStreet: '.$Result->adNumberInStreet);
	Tisk('adNumberInMunicipality: '.$Result->adNumberInMunicipality);
	Tisk('adZipCode: '.$Result->adZipCode);
	Tisk('adState: '.$Result->adState);
	Tisk('biDate: '.$Result->biDate);
	Tisk('userID: '.$Result->userID);
	Tisk('userType: '.$Result->userType);
	Tisk('userPrivils: '.$Result->userPrivils);
	Tisk('ic: '.$Result->ic);
	Tisk('firmName: '.$Result->firmName);
	Tisk('caStreet: '.$Result->caStreet);
	Tisk('caCity: '.$Result->caCity);
	Tisk('caZipCode: '.$Result->caZipCode);	
}

Tisk('---------------------------');

// informace o expiraci hesla

$Result=$ISDSBox->GetPasswordInfo();
Tisk('GetPasswordInfo: StatusCode: '.$ISDSBox->StatusCode.' StatusMessage: '.$ISDSBox->StatusMessage.' ErrorInfo: '.$ISDSBox->ErrorInfo);
if (($ISDSBox->StatusCode == "0000") && ($ISDSBox->ErrorInfo == ""))
{
	Tisk('Expirace hesla: '.$Result);
}

Tisk('Konec');

?>