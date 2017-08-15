<?php

namespace Spisovka;

class EpodatelnaMessage extends DBEntity
{

    const TBL_NAME = 'epodatelna';

    /**
     * @param int $id
     * @return \self|\Spisovka\IsdsMessage
     * @throws \Exception
     */
    public static function factory($id)
    {
        $result = dibi::query('SELECT [typ] FROM %n WHERE [id] = %i', self::TBL_NAME, $id);
        if (!count($result))
            throw new \Exception(__METHOD__ . "() - zpráva ID $id neexistuje");

        $typ = $result->fetchSingle();
        if ($typ == 'E')
            return new EmailMessage($id);
        if ($typ == 'I')
            return new IsdsMessage($id);

        throw new \Exception(__METHOD__ . "() - neplatný typ zprávy ID $id");
    }

    /**
     * Vrátí (příchozí) zprávu, ze které byl dokument vytvořen.
     * @param Document $doc
     * @throws \Exception
     */
    public static function fromDocument(Document $doc)
    {
        $res = dibi::query("SELECT [id] FROM %n WHERE [dokument_id] = %i AND NOT [odchozi]",
                        self::TBL_NAME, $doc->id);
        if (count($res) != 1)
            throw new \Exception("Nemohu nalézt zprávu, ze které byl dokument ID {$doc->id} vytvořen.");

        $id = $res->fetchSingle();
        return self::factory($id);
    }

}

class EmailMessage extends EpodatelnaMessage
{

    /**
     *  Vrátí odkaz na soubor s e-mailem.
     * @param Storage_Basic $storage
     * @return string  filename
     */
    public function getEmailFile($storage)
    {
        if ($this->typ != 'E')
            throw new \LogicException(__METHOD__);

        if (!$this->file_id)
            return null;

        $path = $storage->getFilePath($this->file_id);

        return $path;
    }

}

class IsdsMessage extends EpodatelnaMessage
{

    public static function getAll(array $params = array())
    {
        if (!$params)
            $params = ['where' => "[typ] = 'I'"];
        
        return parent::getAll($params);
    }
    
    /**
     * Vrátí soubor se serializovaným objektem s informacemi o zprávě.
     * @param Storage_Basic $storage
     * @return string  filename
     */
    public function getIsdsFile($storage)
    {
        if ($this->typ != 'I')
            throw new \LogicException(__METHOD__);

        if (!$this->file_id)
            return null;

        $path = $storage->getFilePath($this->file_id);

        return $path;
    }

    /**
     * Každá datová zpráva má připojen ZFO soubor. Vyjímkou jsou pouze datové zprávy
     * do vlastních rukou, kdy byla kvůli nedostatečnému oprávnění stažena pouze obálka zprávy.
     * @return boolean
     * @throws LogicException
     */
    public function hasZfoFile()
    {
        if ($this->typ != 'I')
            throw new \LogicException(__METHOD__);

        return !empty($this->zfo_id);
    }

    /**
     * @param Storage_Basic $storage
     * @param boolean $download  Download file or return it?
     * @return string  data
     */
    public function getZfoFile($storage, $download)
    {
        if ($this->typ != 'I')
            throw new \LogicException(__METHOD__);

        if (!$this->zfo_id)
            return null;

        $zfo = $storage->download($this->zfo_id, !$download);
        return $zfo;
    }

    /**
     * Zformátuje uloženou obálku zprávy pro zobrazení. Výstup se liší pro příchozí a odchozí zprávu.
     * @param Storage_Basic $storage
     * @return string  plain text
     */
    public function formatEnvelope($storage)
    {
        $env = unserialize($this->isds_envelope);

        $annotation = $env->dmAnnotation;
        $popis = '';
        $popis .= "ID datové zprávy    : " . $env->dmID . "\n";
        $popis .= "Věc, předmět zprávy : " . $annotation . "\n\n";

        if (!empty($env->dmLegalTitleLaw)) {
            $popis .= "Zmocnění : $env->dmLegalTitleLaw / $env->dmLegalTitleYear § $env->dmLegalTitleSect";
            if (!empty($env->dmLegalTitlePar))
                $popis .= " odstavec $env->dmLegalTitlePar";
            if (!empty($env->dmLegalTitlePoint))
                $popis .= " písmeno $env->dmLegalTitlePoint";
            $popis .= "\n\n";
        }
        
        $popis .= "Číslo jednací odesilatele  : " . $env->dmSenderRefNumber . "\n";
        $popis .= "Spisová značka odesilatele : " . $env->dmSenderIdent . "\n";
        $popis .= "Číslo jednací příjemce     : " . $env->dmRecipientRefNumber . "\n";
        $popis .= "Spisová značka příjemce    : " . $env->dmRecipientIdent . "\n\n";

        $popis .= "Do vlastních rukou?      : " . (!empty($env->dmPersonalDelivery) ? "ano" : "ne") . "\n";
        $popis .= "Doručení fikcí povoleno? : " . (!empty($env->dmAllowSubstDelivery) ? "ano" : "ne") . "\n";
        if (!empty($env->dmToHands))
            $popis .= "K rukám                  : " . $env->dmToHands . "\n";

        $popis .= "\nOdesílatel:\n";
        $popis .= "            " . $env->dbIDSender . ", typ " . ISDS_Spisovka::typDS($env->dmSenderType) . "\n";
        $popis .= "            " . $env->dmSender . "\n";
        $popis .= "            " . $env->dmSenderAddress . "\n";
        if ($env->dmSenderOrgUnit)
            $popis .= "            org.jednotka: " . $env->dmSenderOrgUnit . " [" . $env->dmSenderOrgUnitNum . "]\n";

        $popis .= "\nPříjemce:\n";
        $popis .= "            " . $env->dbIDRecipient . "\n";
        $popis .= "            " . $env->dmRecipient . "\n";
        $popis .= "            " . $env->dmRecipientAddress . "\n";
        if ($env->dmRecipientOrgUnit)
            $popis .= "            org.jednotka: " . $env->dmRecipientOrgUnit . " [" . $env->dmRecipientOrgUnitNum . "]\n";

        $dt_dodani = strtotime($env->dmDeliveryTime);
        $popis .= "\nDatum a čas dodání   : " . date("j.n.Y G:i:s", $dt_dodani) . "\n";
        
        // dmAttachmentSize obsahuje chybný údaj, pravděpodobně udává velikost po zakódování do base64
        // $popis .= "Přibližná velikost všech příloh : " . $status->dmAttachmentSize . " kB\n";

        return $popis;
    }

}
