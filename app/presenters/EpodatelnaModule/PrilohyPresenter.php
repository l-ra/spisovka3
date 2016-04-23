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
     * @param string $filename
     * @param type $part    
     * @return array        
     */
    private static function _getEmailPrilohu($filename, $part)
    {
        $parser = new EmailParser();
        if (!$parser->open($filename))
            return;

        $zprava = $parser->decode_message(true);

        if (strpos($part, ".") !== false) {
            $part_i = str_replace(".", "]['Parts'][", $part);
            eval("\$part_info = \$zprava[0]['Parts'][" . $part_i . "];");
            eval("\$body = \$zprava[0]['Parts'][" . $part_i . "]['Body'];");
        } else {
            $part_info = @$zprava[0]['Parts'][$part];
            $body = @$zprava[0]['Parts'][$part]['Body'];
        }

        $file_name = @$part_info['FileName'];
        $file_size = @$part_info['BodyLength'];
        $mime_type = FileModel::mimeType($file_name);

        return array('data' => $body, 'file_name' => $file_name, 'size' => $file_size, 'mime-type' => $mime_type);
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

    public function actionDownload()
    {
        $epodatelna_id = $this->getParameter('id', null);
        $part = $this->getParameter('file', null);

        $res = self::_getMessageSource($this->storage, $epodatelna_id);
        $filename = basename($res);

        if (strpos($filename, '.eml') !== false) {
            // jde o email
            if (!is_null($part)) {
                if (strpos($part, '.') !== false) {
                    $part_a = explode(".", $part);
                    unset($part_a[0]);
                    $part = implode('.', $part_a);
                }
            } else {
                $part = 0;
            }

            $priloha = self::_getEmailPrilohu($res, $part);

            if (!empty($priloha['data'])) {
                $httpResponse = $this->getHttpResponse();
                $httpResponse->setContentType($priloha['mime-type']);
                $httpResponse->setHeader('Content-Description', 'File Transfer');
                $httpResponse->setHeader('Content-Disposition',
                        'attachment; filename="' . $priloha['file_name'] . '"');
                //$httpResponse->setHeader('Content-Disposition', 'inline; filename="' . $file_name . '"');
                $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                $httpResponse->setHeader('Expires', '0');
                $httpResponse->setHeader('Cache-Control',
                        'must-revalidate, post-check=0, pre-check=0');
                $httpResponse->setHeader('Pragma', 'public');
                $httpResponse->setHeader('Content-Length', $priloha['size']);

                echo $priloha['data'];
                $this->terminate();
            }
        } elseif (strpos($filename, '.bsr') !== false) {
            // jde o ds

            $tmp_file = self::_getISDSPrilohu($res, $part);

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setContentType($tmp_file['mime-type']);
            $httpResponse->setHeader('Content-Description', 'File Transfer');
            $httpResponse->setHeader('Content-Disposition',
                    'attachment; filename="' . $tmp_file['file_name'] . '"');
            //$httpResponse->setHeader('Content-Disposition', 'inline; filename="' . $file_name . '"');
            $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
            $httpResponse->setHeader('Expires', '0');
            $httpResponse->setHeader('Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0');
            $httpResponse->setHeader('Pragma', 'public');
            $httpResponse->setHeader('Content-Length', $tmp_file['size']);

            echo $tmp_file['file'];


            $this->terminate();
        }
    }

}
