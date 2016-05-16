<?php

//netteloader=Storage_Basic

class Storage_Basic extends FileModel
{

    private $document_dir;
    private $epodatelna_dir;
    private $httpResponse;
    private $error_message;
    private $error_code = 0;

    public function __construct(array $params, Nette\Http\IResponse $httpResponse)
    {
        parent::__construct();

        $this->httpResponse = $httpResponse;

        $this->document_dir = $params['path_documents'];
        $this->epodatelna_dir = $params['path_epodatelna'];
        if ($this->document_dir{0} != '/')
            $this->document_dir = CLIENT_DIR . "/" . $this->document_dir;
        if ($this->epodatelna_dir{0} != '/')
            $this->epodatelna_dir = CLIENT_DIR . "/" . $this->epodatelna_dir;
    }

    public function getDocumentDirectory()
    {
        return $this->document_dir;
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

    protected function uploadInt($source, array $data, $directory)
    {
        if (isset($data['dir'])) {
            if (!file_exists($directory . "/" . $data['dir'])) {
                mkdir($directory . "/" . $data['dir'], 0777, true);
            }
            if (is_writeable($directory . "/" . $data['dir'])) {
                $file_dir = $directory . "/" . $data['dir'];
            } else {
                $file_dir = $directory;
            }
        } else {
            if (!file_exists($directory . "")) {
                mkdir($directory . "", 0777, true);
            }
            if (is_writeable($directory)) {
                $file_dir = $directory;
            } else {
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $filename = Nette\Utils\Strings::webalize($data['filename'], '.');
        $filepath = $file_dir . "/" . $filename;
        $filepath = $this->getUniqueFilename($filepath);

        if (!file_put_contents($filepath, $source)) {
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

        return $this->vlozit($row);
    }

    /**
     * Voláno při vytváření dokumentu ze zprávy v e-podatelně.
     * @param string $contents
     * @param array $data
     * @return DibiRow
     */
    public function uploadDocument($contents, array $data)
    {
        return $this->uploadInt($contents, $data, $this->document_dir);
    }

    /**
     * Uložení souboru se zprávou v e-podatelně.
     * @param string $contents
     * @param array $data
     * @return DibiRow
     */
    public function uploadEpodatelna($contents, array $data)
    {
        return $this->uploadInt($contents, $data, $this->epodatelna_dir);
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
        $httpResponse = $this->httpResponse;
        $httpResponse->setContentType($file->mime_type ? : 'application/octetstream');
        $httpResponse->setHeader('Content-Description', 'File Transfer');
        $basename = basename($file_path);
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

    protected function getUniqueFilename($filename)
    {
        if (!file_exists($filename))
            return $filename;

        $counter = 1;
        do {
            $new_filename = "$filename-$counter";
            $counter++;
        } while (file_exists($new_filename));

        return $new_filename;
    }

    public function errorMessage()
    {
        return $this->error_message;
    }

}
