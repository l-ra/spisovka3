<?php

class EpodatelnaPrilohy
{

    public static function getISDSPrilohu($source_file, $priloha_id)
    {
        $contents = file_get_contents($source_file);
        if (!$contents)
            return null;
        $mess = unserialize($contents);

        if (isset($mess->dmDm->dmFiles->dmFile))
            foreach ($mess->dmDm->dmFiles->dmFile as $index => $file)
                if ($priloha_id == $index)
                    return ['data' => $file->dmEncodedContent, 'file_name' => $file->dmFileDescr, 'mime_type' => $file->dmMimeType];

        return null;
    }

    private static function _getISDSPrilohy($source_file)
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

    private static function _getEmailPrilohy($filename)
    {
        $parser = new EmailParser();
        if (!$parser->open($filename))
            return;

        $decoded = $parser->decode_message(true);
        $zprava = $parser->analyze_message($decoded);

        $files = array();
        // Hlavni zprava
        $data = $zprava['Data'];
        $file_size = strlen($data);
        if ($zprava['Type'] == 'html') {
            $file_name = 'zprava.html';
            $mime_type = 'text/html';
        } else {
            $file_name = 'zprava.txt';
            $mime_type = 'text/plain';
        }
        $files[] = array('file' => $data, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type, 'charset' => @$zprava['Encoding']);

        // Alternativni Zpravy
        if (isset($zprava['Alternative'])) {
            // zpravy
            foreach ($zprava['Alternative'] as $fid => $file) {
                $data = $file['Data'];
                $file_size = strlen($data);

                if ($file['Type'] == 'text') {
                    $file_name = 'zprava_' . $fid . '.txt';
                    $mime_type = 'text/plain';
                } else if ($file['Type'] == 'html') {
                    $file_name = 'zprava_' . $fid . '.html';
                    $mime_type = 'text/html';
                } else {
                    $file_name = 'zprava_' . $fid . '.txt';
                    $mime_type = 'text/plain';
                }
                $files[] = array('file' => $data, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type, 'charset' => @$zprava['Encoding']);
            }
        }
        // Prilohy
        if (isset($zprava['Attachments'])) {
            // zprava + prilohy
            foreach ($zprava['Attachments'] as $fid => $file) {
                $data = $file['Data'];
                $file_size = strlen($data);
                if ($file['Type'] == 'text') {
                    $file_name = 'soubor_' . $fid . '.txt';
                    $mime_type = 'text/plain';
                } else if ($file['Type'] == 'html') {
                    $file_name = 'soubor_' . $fid . '.html';
                    $mime_type = 'text/html';
                } else {
                    $file_name = empty($file['FileName']) ? "file_$fid" : $file['FileName'];
                    $mime_type = FileModel::mimeType($file_name);
                }
                $files[] = array(
                    'file' => $data,
                    'file_name' => $file_name,
                    'size' => $file_size,
                    'mime-type' => $mime_type,
                    'charset' => $zprava['Encoding'],
                );
            }
        }

        return $files;
    }

    /**
     * @param string $filename  soubor s e-mailem
     * @param int $part_number    
     * @return array        
     */
    public static function getEmailPrilohu($filename, $part_number)
    {
        $imap = new ImapClient();
        $imap->open($filename);
        $structure = $imap->get_message_structure(1);
        $part = $structure->parts[$part_number];

        $data = $imap->fetch_body_part(1, $part_number + 1);
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
    public static function getEmailPrilohy($epodatelna_id, $storage)
    {
        $model = new Epodatelna();
        $path = $model->getMessageSource($epodatelna_id, $storage);
        return self::_getEmailPrilohy($path);
    }

    /**
     *  Obsah příloh se vrátí přímo v poli.
     * @param int $epodatelna_id
     * @param type $storage
     * @return array
     */
    public static function getIsdsPrilohy($epodatelna_id, $storage)
    {
        $model = new Epodatelna();
        $path = $model->getMessageSource($epodatelna_id, $storage);
        return self::_getISDSPrilohy($path);
    }

}
