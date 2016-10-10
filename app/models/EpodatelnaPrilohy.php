<?php

class EpodatelnaPrilohy
{

    /**
     * @param string $filename
     * @param int $priloha_id
     * @return array|null
     */
    public static function getIsdsFile($filename, $priloha_id)
    {
        $contents = file_get_contents($filename);
        if (!$contents)
            return null;
        $mess = unserialize($contents);

        if (isset($mess->dmDm->dmFiles->dmFile))
            foreach ($mess->dmDm->dmFiles->dmFile as $index => $file)
                if ($priloha_id == $index)
                    return ['data' => $file->dmEncodedContent, 'file_name' => $file->dmFileDescr, 'mime_type' => $file->dmMimeType];

        return null;
    }

    private static function _getIsdsFiles($source_file)
    {
        $contents = file_get_contents($source_file);
        if (!$contents)
            return null;
        $mess = unserialize($contents);

        if (!isset($mess->dmDm->dmFiles->dmFile))
            return null;

        $files = array();
        foreach ($mess->dmDm->dmFiles->dmFile as $file) {
            $file_name = $file->dmFileDescr;
            $file_size = strlen($file->dmEncodedContent);
            $mime_type = $file->dmMimeType;
            $files[] = array('file' => $file->dmEncodedContent, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type);
        }
        return $files;
    }

    /**
     * @param string $filename  soubor s e-mailem
     * @param int|string $part_number    
     * @return array        
     */
    public static function getEmailPart($filename, $part_number)
    {
        $imap = new ImapClient();
        $imap->open($filename);
        $data = $imap->fetch_body_part(1, $part_number);
        
        $structure = $imap->get_message_structure(1);
        $part = $structure;
        $part_numbers = explode('.', $part_number);
        foreach ($part_numbers as $pn) {
            $part = $part->parts[$pn - 1];            
        }
        $data = $imap->decode_data($data, $part);
        $imap->close();

        $filename = $part->dparameters['FILENAME']; // Content-Disposition
        if (!$filename) {
            // pri poruseni MIME standardu v e-mailu zkusime jeste tuto moznost
            $filename = $part->parameters['NAME'];
        }

        return ['data' => $data, 'file_name' => $filename];
    }

    /**
     *  Obsah příloh se vrátí přímo v poli.
     * @param int $epodatelna_id
     * @param type $storage
     * @return array
     */
    public static function getIsdsFiles($epodatelna_id, $storage)
    {
        $msg = new EpodatelnaMessage($epodatelna_id);
        $path = $msg->getIsdsFile($storage);
        return self::_getIsdsFiles($path);
    }

    /**
     * @param object $storage
     * @return array
     */
    public static function getFileList(EpodatelnaMessage $message, $storage)
    {
        $model = new Epodatelna();
        if ($message->typ == 'I') {
            // vrat, co uz je v databazi, zde nebyly bugy v kodu
            return unserialize($message->prilohy);
        }

        // email
        // u odchozích zpráv se od verze 3.5.0 neukládá kopie emailu, vrať medatadata z databáze
        if ($message->odchozi && !$message->file_id)
            return unserialize($message->prilohy);
        
        $filename = $message->getEmailFile($storage);
        
        $imap = new ImapClient();
        $imap->open($filename);
        $structure = $imap->get_message_structure(1);
        $attachments = $imap->get_attachments($structure);
        $imap->close();

        $result = [];
        foreach ($attachments as $id => $at) {
            $filename = $at->dparameters['FILENAME'];
            $size = $at->bytes;
            if ($at->encoding == ENCBASE64)
                $size = floor($size * 3 / 4 * 73 / 74);
            $result[] = ['id' => $id, 'name' => $filename, 'size' => $size];
        }
        
        return $result;
    }

}
