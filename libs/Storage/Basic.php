<?php

//netteloader=Storage_Basic

class Storage_Basic extends FileModel
{

    private $dokument_dir;
    private $epodatelna_dir;
    private $httpResponse;
    private $error_message;
    private $error_code = 0;

    public function __construct(array $params, Nette\Http\IResponse $httpResponse)
    {
        parent::__construct();

        $this->httpResponse = $httpResponse;

        $this->dokument_dir = $params['path_documents'];
        $this->epodatelna_dir = $params['path_epodatelna'];
        if ($this->dokument_dir{0} != '/')
            $this->dokument_dir = CLIENT_DIR . "/" . $this->dokument_dir;
        if ($this->epodatelna_dir{0} != '/')
            $this->epodatelna_dir = CLIENT_DIR . "/" . $this->epodatelna_dir;
    }

    public function getDocumentDirectory()
    {
        return $this->dokument_dir;
    }

    public function getEpodatelnaDirectory()
    {
        return $this->epodatelna_dir;
    }

    public function remove($file_id)
    {

        $row = $this->select(array(array('id=%i', $file_id)))->fetch();
        if (!$row)
            throw new Exception("Nemohu načíst přílohu ID $file_id.");

        // odstraň záznam z databáze
        $this->delete(array(array('id=%i', $file_id)));
        unlink(CLIENT_DIR . $row['real_path']);
    }

    public function uploadDokument($data)
    {

        $upload = $data['file'];

        if (isset($data['dir'])) {

            if (!file_exists($this->dokument_dir . "/" . $data['dir'])) {
                mkdir($this->dokument_dir . "/" . $data['dir'], 0777, true);
            }
            if (is_writeable($this->dokument_dir . "/" . $data['dir'])) {
                $file_dir = $this->dokument_dir . "/" . $data['dir'];
            } else {
                $file_dir = $this->dokument_dir;
            }
        } else {
            if (!file_exists($this->dokument_dir . "")) {
                mkdir($this->dokument_dir . "", 0777, true);
            }
            if (is_writeable($this->dokument_dir)) {
                $file_dir = $this->dokument_dir;
            } else {
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $file = Nette\Utils\Strings::webalize($upload->getName(), '.');
        $fileName = $file_dir . "/" . $file;
        //$fileName = $this->dokument_dir . "/" . Nette\Utils\Strings::webalize($upload->getName(),'.');
        // test existence souboru
        $fileName = $this->fileExists($fileName);

        if (!$upload instanceof Nette\Http\FileUpload) {
            $this->error_message = 'Soubor se nepodařilo nahrát';
            return null;
        } else if ($upload->isOk()) {
            $upload->move($fileName);

            $file = new stdClass();
            $file->type = $this->getReflection()->getName();
            $file->real_name = Nette\Utils\Strings::webalize($upload->getName(), '.');
            $file->real_path = str_replace(CLIENT_DIR, '', $upload->getTemporaryFile());
            $file->size = $upload->getSize();
            $file->md5_hash = md5_file($upload->getTemporaryFile());

            $row = array();
            $row['nazev'] = empty($data['nazev']) ? $file->real_name : $data['nazev'];
            $file->name = $row['nazev'];
            $row['popis'] = empty($data['popis']) ? '' : $data['popis'];
            $row['typ'] = $data['typ'];

            $row['real_name'] = $file->real_name;
            $row['real_path'] = $file->real_path;
            $row['real_type'] = $file->type;
            $row['md5_hash'] = $file->md5_hash;
            $row['size'] = $file->size;

            if ($file_info = $this->vlozit($row)) {
                return $file_info;
            } else {
                $this->error_message = 'Metadata souboru "' . $row['nazev'] . '" se nepodařilo uložit.';
                return null;
            }
        } else {
            $this->error_code = $upload->error;
            switch ($upload->error) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->error_message = 'Překročena velikost přílohy.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->error_message = 'Nevybrali jste žádný soubor.';
                    break;
                default:
                    $this->error_message = 'Soubor "' . $upload->getName() . '" se nepodařilo nahrát.';
                    break;
            }
            return null;
        }
    }

    public function uploadDokumentSource($source, $data)
    {
        if (isset($data['dir'])) {
            if (!file_exists($this->dokument_dir . "/" . $data['dir'])) {
                mkdir($this->dokument_dir . "/" . $data['dir'], 0777, true);
            }
            if (is_writeable($this->dokument_dir . "/" . $data['dir'])) {
                $file_dir = $this->dokument_dir . "/" . $data['dir'];
            } else {
                $file_dir = $this->dokument_dir;
            }
        } else {
            if (!file_exists($this->dokument_dir . "")) {
                mkdir($this->dokument_dir . "", 0777, true);
            }
            if (is_writeable($this->dokument_dir)) {
                $file_dir = $this->dokument_dir;
            } else {
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $filename = Nette\Utils\Strings::webalize($data['filename'], '.');
        $filepath = $file_dir . "/" . $filename;

        // test existence souboru
        $filepath = $this->fileExists($filepath);

        if ($fp = fopen($filepath, 'w')) {
            if (!fwrite($fp, $source, strlen($source))) {
                $this->error_message = 'Obsah není možné uložit do souboru.';
                return null;
            }
            @fclose($fp);
        } else {
            $this->error_message = 'Obsah není možné uložit do souboru.';
            return null;
        }

        $file = new stdClass();
        $file->type = $this->getReflection()->getName();
        $file->real_name = $filename;
        $file->real_path = str_replace(CLIENT_DIR, '', $filepath);
        $file->size = filesize($filepath);
        $file->md5_hash = md5_file($filepath);

        $row = array();
        $row['nazev'] = empty($data['nazev']) ? $file->real_name : $data['nazev'];
        $file->name = $row['nazev'];
        $row['popis'] = empty($data['popis']) ? '' : $data['popis'];
        $row['typ'] = $data['typ'];
        $row['real_name'] = $file->real_name;
        $row['real_path'] = $file->real_path;
        $row['real_type'] = $file->type;
        $row['md5_hash'] = $file->md5_hash;
        $row['size'] = $file->size;

        if ($file_info = $this->vlozit($row)) {
            return $file_info;
        } else {
            $this->error_message = 'Metadata souboru "' . $row['nazev'] . '" se nepodařilo uložit.';
            return null;
        }
    }

    public function uploadEpodatelna($source, $data)
    {

        if (isset($data['dir'])) {
            if (!file_exists($this->epodatelna_dir . "/" . $data['dir'])) {
                mkdir($this->epodatelna_dir . "/" . $data['dir'], 0777, true);
            }
            if (is_writeable($this->epodatelna_dir . "/" . $data['dir'])) {
                $file_dir = $this->epodatelna_dir . "/" . $data['dir'];
            } else {
                $file_dir = $this->epodatelna_dir;
            }
        } else {
            if (!file_exist($this->epodatelna_dir . "")) {
                mkdir($this->epodatelna_dir . "", 0777, true);
            }
            if (is_writeable($this->epodatelna_dir)) {
                $file_dir = $this->epodatelna_dir;
            } else {
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $filename = Nette\Utils\Strings::webalize($data['filename'], '.');
        $filepath = $file_dir . "/" . $filename;
        if ($fp = fopen($filepath, 'w')) {
            if (!fwrite($fp, $source, strlen($source))) {
                $this->error_message = 'Obsah není možné uložit do souboru.';
                return null;
            }
            @fclose($fp);
        } else {
            $this->error_message = 'Obsah není možné uložit do souboru.';
            return null;
        }

        $file = new stdClass();
        $file->type = $this->getReflection()->getName();
        $file->real_name = $filename;
        $file->real_path = str_replace(CLIENT_DIR, '', $filepath);
        $file->size = filesize($filepath);
        $file->md5_hash = md5_file($filepath);

        $row = array();
        $row['nazev'] = empty($data['nazev']) ? $file->real_name : $data['nazev'];
        $file->name = $row['nazev'];
        $row['popis'] = empty($data['popis']) ? '' : $data['popis'];
        $row['typ'] = $data['typ'];
        $row['real_name'] = $file->real_name;
        $row['real_path'] = $file->real_path;
        $row['real_type'] = $file->type;
        $row['md5_hash'] = $file->md5_hash;
        $row['size'] = $file->size;

        if ($file_info = $this->vlozit($row)) {
            return $file_info;
        } else {
            $this->error_message = 'Metadata souboru "' . $row['nazev'] . '" se nepodařilo uložit.';
            return null;
        }
    }

    public function getFilePath($file)
    {
        $file_path = CLIENT_DIR . $file->real_path;
        return $file_path;
    }

    /**
     * @param int $file_id   ID do tabulky file
     * @param int $output  0 - posle soubor na vystup
     *                     1 - vrati jako retezec
     * @return string|int  
     * @throws Nette\Application\BadRequestException
     */
    public function download($file_id, $output = 0)
    {
        $FileModel = new FileModel();
        $file = $FileModel->getInfo($file_id);

        $file_path = $this->getFilePath($file);
        if (!file_exists($file_path))
            return $output == 0 ? 1 : null; // kdyz data vracime primo, nemuzeme vratit 1

        if ($output == 1)
            return file_get_contents($file_path);

        // poslat primo na vystup
        $basename = basename($file_path);
        $httpResponse = $this->httpResponse;
        $httpResponse->setContentType($file->mime_type ? : 'application/octetstream');
        $httpResponse->setHeader('Content-Description', 'File Transfer');
        $httpResponse->setHeader('Content-Disposition',
                'attachment; filename="' . $basename . '"');
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
        $httpResponse->setHeader('Expires', '0');
        $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $httpResponse->setHeader('Pragma', 'public');
        if (!empty($file->size)) {
            $httpResponse->setHeader('Content-Length', $file->size);
        } else {
            $httpResponse->setHeader('Content-Length', filesize($file_path));
        }

        readfile($file_path);

        return 0;
    }

    protected function fileExists($file, $postfix = 1)
    {

        if (file_exists($file)) {

            $path_parts = pathinfo($file);

            if (!isset($path_parts['extension'])) {
                $ext = "";
            } else {
                $ext = "." . $path_parts['extension'];
            }

            //$filename = str_replace('.'.$ext, '', $path_parts['basename']);
            $filename = $path_parts['filename'];
            $filename = $filename . '_' . $postfix;

            //echo "<pre>$postfix = "; print_r($filename); echo "</pre>";

            $file_new = $path_parts['dirname'] . '/' . $filename . $ext;
            if (file_exists($file_new)) {
                return $this->fileExists($file_new, $postfix++);
            } else {
                return $file_new;
            }
        } else {
            return $file;
        }
    }

    public function errorMessage()
    {
        return $this->error_message;
    }

}

