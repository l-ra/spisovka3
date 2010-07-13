<?php


class Storage_Basic extends FileModel {

    private $dokument_dir = '';
    private $epodatelna_dir = '';

    private $error_message;
    private $error_code;


    public function  __construct() {

        parent::__construct();

        $storage_conf = Environment::getConfig('storage');
        $this->dokument_dir = $storage_conf->dokument_dir;
        $this->epodatelna_dir = $storage_conf->epodatelna_dir;
        
    }

    public function uploadDokument($data) {

        $upload = $data['file'];

        if ( isset($data['dir']) ) {

            if ( !file_exists(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir']) ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir'], 0777, true);
                umask($old);
            }
            if ( is_writeable(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir']) ) {
                $file_dir = WWW_DIR .''. $this->dokument_dir . "/" .$data['dir'];
            } else {
                $file_dir = WWW_DIR .''. $this->dokument_dir;
            }
        } else {
            if ( !file_exists(WWW_DIR .''. $this->dokument_dir . "") ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->dokument_dir . "", 0777, true);
                umask($old);
            }
            if ( is_writeable(WWW_DIR .''. $this->dokument_dir) ) {
                $file_dir = WWW_DIR .''. $this->dokument_dir;
            } else {
                $this->error_code = '0';
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $file = String::webalize($upload->getName(),'.');
        $fileName = $file_dir . "/" . $file;
        //$fileName = WWW_DIR .''. $this->dokument_dir . "/" . String::webalize($upload->getName(),'.');

        // test existence souboru
        $fileName = $this->fileExists($fileName);

        if (!$upload instanceof HttpUploadedFile) {
            $this->error_message = 'Soubor se nepodařilo nahrát';
            $this->error_code = 0;
            return null;
        } else if ( $upload->isOk() ) {
            $dest = $upload->move($fileName);

            $file = new stdClass();
            $file->type = $this->getReflection()->getName();
            $file->real_name = String::webalize($upload->getName(),'.');
            $file->real_path = str_replace(WWW_DIR, '', $dest->getTemporaryFile());
            $file->size = $dest->getSize();
            $file->content_type = $dest->getContentType();
            $file->md5_hash = md5_file($dest->getTemporaryFile());

            $row = array();
            $row['nazev'] = empty($data['nazev'])?$file->real_name:$data['nazev'];
            $file->name = $row['nazev'];
            $row['popis'] = empty($data['popis'])?'':$data['popis'];
            $row['typ'] = $data['typ'];

            $row['real_name'] = $file->real_name;
            $row['real_path'] = $file->real_path;
            $row['real_type'] = $file->type;
            $row['md5_hash'] = $file->md5_hash;
            $row['size'] = $file->size;

            if ( $file_info = $this->vlozit($row) ) {
                return $file_info;
            } else {
                $this->error_code = '0';
                $this->error_message = 'Metadata souboru "'. $row['nazev'] .'" se nepodařilo uložit.';
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
                    $this->error_message = 'Soubor "'. $upload->getName() .'" se nepodařilo nahrát.';
                    break;
            }
            return null;
        }

    }

    public function uploadDokumentSource($source, $data) {

        if ( isset($data['dir']) ) {

            if ( !file_exists(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir']) ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir'], 0777, true);
                umask($old);
            }
            if ( is_writeable(WWW_DIR .''. $this->dokument_dir . "/" .$data['dir']) ) {
                $file_dir = WWW_DIR .''. $this->dokument_dir . "/" .$data['dir'];
            } else {
                $file_dir = WWW_DIR .''. $this->dokument_dir;
            }
        } else {
            if ( !file_exists(WWW_DIR .''. $this->dokument_dir . "") ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->dokument_dir . "", 0777, true);
                umask($old);
            }
            if ( is_writeable(WWW_DIR .''. $this->dokument_dir) ) {
                $file_dir = WWW_DIR .''. $this->dokument_dir;
            } else {
                $this->error_code = '0';
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $filename = String::webalize($data['filename'],'.');
        $filepath = $file_dir . "/" . $filename;

        // test existence souboru
        $filepath = $this->fileExists($filepath);

        if ( $fp = fopen($filepath,'w') ) {
            if (!fwrite( $fp, $source, strlen($source) ) ) {
                $this->error_code = '0';
                $this->error_message = 'Obsah není možné uložit do souboru.';
                return null;
            }
            @fclose($fp);
        } else {
            $this->error_code = '0';
            $this->error_message = 'Obsah není možné uložit do souboru.';
            return null;
        }

        $file = new stdClass();
        $file->type = $this->getReflection()->getName();
        $file->real_name = $filename;
        $file->real_path = str_replace(WWW_DIR, '', $filepath);
        $file->size = filesize($filepath);
        $file->content_type = FileModel::mimeType($filename);
        $file->md5_hash = md5_file($filepath);

        $row = array();
        $row['nazev'] = empty($data['nazev'])?$file->real_name:$data['nazev'];
        $file->name = $row['nazev'];
        $row['popis'] = empty($data['popis'])?'':$data['popis'];
        $row['typ'] = $data['typ'];
        $row['real_name'] = $file->real_name;
        $row['real_path'] = $file->real_path;
        $row['real_type'] = $file->type;
        $row['md5_hash'] = $file->md5_hash;
        $row['size'] = $file->size;

        if ( $file_info = $this->vlozit($row) ) {
            return $file_info;
        } else {
            $this->error_code = '0';
            $this->error_message = 'Metadata souboru "'. $row['nazev'] .'" se nepodařilo uložit.';
            return null;
        }


    }


    public function uploadEpodatelna($source, $data) {

        if ( isset($data['dir']) ) {
            if ( !file_exists(WWW_DIR .''. $this->epodatelna_dir . "/" .$data['dir']) ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->epodatelna_dir . "/" .$data['dir'], 0777, true);
                umask($old);
            }
            if ( is_writeable(WWW_DIR .''. $this->epodatelna_dir . "/" .$data['dir']) ) {
                $file_dir = WWW_DIR .''. $this->epodatelna_dir . "/" .$data['dir'];
            } else {
                $file_dir = WWW_DIR .''. $this->epodatelna_dir;
            }
        } else {
            if ( !file_exist(WWW_DIR .''. $this->epodatelna_dir . "") ) {
                $old = umask(0);
                mkdir(WWW_DIR .''. $this->epodatelna_dir . "", 0777, true);
                umask($old);                
            }
            if ( is_writeable(WWW_DIR .''. $this->epodatelna_dir) ) {
                $file_dir = WWW_DIR .''. $this->epodatelna_dir;
            } else {
                $this->error_code = '0';
                $this->error_message = 'Soubor nelze uložit do adresáře.';
                return null;
            }
        }

        $filename = String::webalize($data['filename'],'.');
        $filepath = $file_dir . "/" . $filename;
        if ( $fp = fopen($filepath,'w') ) {
            if (!fwrite( $fp, $source, strlen($source) ) ) {
                $this->error_code = '0';
                $this->error_message = 'Obsah není možné uložit do souboru.';
                return null;
            }
            @fclose($fp);
        } else {
            $this->error_code = '0';
            $this->error_message = 'Obsah není možné uložit do souboru.';
            return null;            
        }

        $file = new stdClass();
        $file->type = $this->getReflection()->getName();
        $file->real_name = $filename;
        $file->real_path = str_replace(WWW_DIR, '', $filepath);
        $file->size = filesize($filepath);
        $file->content_type = FileModel::mimeType($filename);
        $file->md5_hash = md5_file($filepath);

        $row = array();
        $row['nazev'] = empty($data['nazev'])?$file->real_name:$data['nazev'];
        $file->name = $row['nazev'];
        $row['popis'] = empty($data['popis'])?'':$data['popis'];
        $row['typ'] = $data['typ'];
        $row['real_name'] = $file->real_name;
        $row['real_path'] = $file->real_path;
        $row['real_type'] = $file->type;
        $row['md5_hash'] = $file->md5_hash;
        $row['size'] = $file->size;

        if ( $file_info = $this->vlozit($row) ) {
            return $file_info;
        } else {
            $this->error_code = '0';
            $this->error_message = 'Metadata souboru "'. $row['nazev'] .'" se nepodařilo uložit.';
            return null;
        }


    }

    public function download($file, $output = 0)
    {
        try {

            $file_path = WWW_DIR ."". $file->real_path;

            if ( file_exists($file_path) ) {

                //if ( empty($file->real_name) ) {
                    $basename = basename($file_path);
                //} else {
                //    $basename = $file->real_name;
                //}

                if ( $output == 1 ) {
                    // poslat jako retezec
                    if ( $fp = fopen($file_path,'rb') ) {
                        return fread($fp, filesize($file_path));
                    } else {
                        return null;
                    }
                } else if ( $output == 2 ) {
                    // poslat do docasneho souboru
                    return $file_path;
                } else {
                    // primy stream - poslat ven

                    $httpResponse = Environment::getHttpResponse();
                    if ( !empty($file->mime_type) ) {
                        $httpResponse->setContentType($file->mime_type);
                    } else {
                        $httpResponse->setContentType('application/octetstream');
                    }
                    $httpResponse->setHeader('Content-Description', 'File Transfer');
                    $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $basename . '"');
                    $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                    $httpResponse->setHeader('Expires', '0');
                    $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
                    $httpResponse->setHeader('Pragma', 'public');
                    if ( !empty( $file->size ) ) {
                        $httpResponse->setHeader('Content-Length', $file->size);
                    } else {
                        $httpResponse->setHeader('Content-Length', filesize($file_path));
                    }
                
                    readfile($file_path);

                    return 0;
                }
            } else {
                return 1;
            }

        } catch (InvalidStateException $e) {
            throw new BadRequestException($e->getMessage());
            return 2;
        }
    }

    protected function fileExists($file, $postfix = 1) {

        if ( file_exists($file) ) {
            $path_parts = pathinfo($file);
            $ext = $path_parts['extension'];
            $filename = str_replace('.'.$ext, '', $path_parts['basename']);
            $filename = $filename .'_'. $postfix;

            $file_new = $path_parts['dirname'] .'/'. $filename .'.'. $path_parts['extension'];
            if ( file_exists($file_new) ) {
                return $this->fileExists($file, $postfix++);
            } else {
                return $file_new;
            }
        } else {
            return $file;
        }
    }

    public function errorMessage() {
        return $this->error_message;
    }
    public function errorCode() {
        return $this->error_code;
    }


}
?>
