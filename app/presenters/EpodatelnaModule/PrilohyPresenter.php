<?php

//netteloader=Epodatelna_PrilohyPresenter

class Epodatelna_PrilohyPresenter extends BasePresenter
{

    /**
     *  Nemá nic společného s přílohami.
     * @param Storage_Basic $storage
     * @param int $epodatelna_id  ID do tabulky epodatelna
     * @return string
     */
    private static function _getMessageSource($storage, $epodatelna_id)
    {
        $DownloadFile = $storage;

        $Epod = new Epodatelna();
        $FileModel = new FileModel();

        $epod = $Epod->getInfo($epodatelna_id);
        $file = $FileModel->getInfo($epod->file_id);
        $path = $DownloadFile->getFilePath($file);

        return $path;
    }

    private static function _getISDSPrilohu($source_file, $part = null)
    {
        if ($fp = fopen($source_file, 'rb')) {
            $source = fread($fp, filesize($source_file));
            fclose($fp);
        } else {
            return null;
        }

        $mess = unserialize($source);

        if (is_null($part)) {
            if (isset($mess->dmDm->dmFiles->dmFile)) {
                $files = array();
                foreach ($mess->dmDm->dmFiles->dmFile as $index => $file) {
                    $file_name = $file->dmFileDescr;
                    $file_size = strlen($file->dmEncodedContent);
                    $mime_type = $file->dmMimeType;
                    $files[] = array('file' => $file->dmEncodedContent, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type);
                }
                return $files;
            } else {
                return null;
            }
        } else {
            if (isset($mess->dmDm->dmFiles->dmFile)) {
                foreach ($mess->dmDm->dmFiles->dmFile as $index => $file) {
                    if ($part == $index) {
                        $file_name = $file->dmFileDescr;
                        $file_size = strlen($file->dmEncodedContent);
                        $mime_type = $file->dmMimeType;
                        return array('file' => $file->dmEncodedContent, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type);
                    }
                }
            }
            return null; // array('file'=>$tmp_file,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type);
        }
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
     * @param int $part    
     * @return array        
     */
    private static function _getEmailPrilohu($filename, $part_number)
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
     * @param type $storage
     * @param int $epodatelna_id
     * @return array
     */
    public static function getEmailPrilohy($storage, $epodatelna_id)
    {
        $path = self::_getMessageSource($storage, $epodatelna_id);
        return self::_getEmailPrilohy($path);
    }

    /**
     *  Obsah příloh se vrátí přímo v poli.
     * @param type $storage
     * @param int $epodatelna_id
     * @return array
     */
    public static function getIsdsPrilohy($storage, $epodatelna_id)
    {
        $path = self::_getMessageSource($storage, $epodatelna_id);
        return self::_getISDSPrilohu($path);
    }

    public function actionDownload($id, $file)
    {
        $epodatelna_id = $id;
        $part = $file;

        $path = self::_getMessageSource($this->storage, $epodatelna_id);
        $model = new Epodatelna();
        $msg = $model->getInfo($epodatelna_id);

        if ($msg->typ == 'E') {
            // jde o email
            if (!is_null($part)) {
                if (strpos($part, '.') !== false) {
                    $part_a = explode(".", $part);
                    array_shift($part_a);
                    $part = implode('.', $part_a);
                }
            } else {
                $part = 0;
            }

            $priloha = self::_getEmailPrilohu($path, $part);

            if (!empty($priloha['data'])) {
                $data = $priloha['data'];
                $httpResponse = $this->getHttpResponse();
                $mime_type = $this->_getMimeType($data);
                if ($mime_type)
                    $httpResponse->setContentType($mime_type);
                $httpResponse->setHeader('Content-Length', strlen($data));
                $httpResponse->setHeader('Content-Description', 'File Transfer');
                $httpResponse->setHeader('Content-Disposition',
                        'attachment; filename="' . $priloha['file_name'] . '"');
                $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                $httpResponse->setHeader('Expires', '0');
                $httpResponse->setHeader('Cache-Control',
                        'must-revalidate, post-check=0, pre-check=0');
                $httpResponse->setHeader('Pragma', 'public');

                $this->sendResponse(new \Nette\Application\Responses\TextResponse($data));
            }
        } elseif ($msg->typ == 'I') {
            $tmp_file = self::_getISDSPrilohu($path, $part);

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setContentType($tmp_file['mime-type']);
            $httpResponse->setHeader('Content-Description', 'File Transfer');
            $httpResponse->setHeader('Content-Disposition',
                    'attachment; filename="' . $tmp_file['file_name'] . '"');
            $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
            $httpResponse->setHeader('Expires', '0');
            $httpResponse->setHeader('Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0');
            $httpResponse->setHeader('Pragma', 'public');
            $httpResponse->setHeader('Content-Length', $tmp_file['size']);

            $this->sendResponse(new \Nette\Application\Responses\TextResponse($tmp_file['file']));
        }
    }

    protected function _getMimeType($data)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimetype = finfo_buffer($finfo, $data);
            finfo_close($finfo);
            return $mimetype;
        }
        
        return null;
    }
}
