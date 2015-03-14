<?php //netteloader=Epodatelna_PrilohyPresenter

class Epodatelna_PrilohyPresenter extends BasePresenter
{

    public function downloadSource($file_id, $typ = 2)
    {
        $storage_conf = Nette\Environment::getConfig('storage');
        eval("\$DownloadFile = new ".$storage_conf->type."();");

        $FileModel = new FileModel();
        $file = $FileModel->getInfo($file_id);
        $res = $DownloadFile->download($file,$typ);

        return $res;

    }

    public function downloadEpodSource($epodatelna_id, $typ = 2)
    {
        $storage_conf = Nette\Environment::getConfig('storage');
        eval("\$DownloadFile = new ".$storage_conf->type."();");

        $Epod = new Epodatelna();
        $FileModel = new FileModel();

        $epod = $Epod->getInfo($epodatelna_id);
        $file_id = @explode("-",$epod->file_id);
        $file = $FileModel->getInfo($file_id[0]);
        $res = $DownloadFile->download($file,$typ);

        return $res;

    }

    public function downloadISDSPrilohu( $source_file , $part = null , $output = 0 )
    {

        if ( $fp = fopen($source_file,'rb') ) {

            $source = fread($fp,filesize($source_file));
            fclose($fp);

        } else {
            return null;
        }

        $mess = unserialize($source);

        if ( is_null($part) ) {

            if ( isset($mess->dmDm->dmFiles->dmFile) ) {
                $files = array();
                foreach( $mess->dmDm->dmFiles->dmFile as $index => $file ) {
                    $file_name = $file->dmFileDescr;
                    $file_size = strlen($file->dmEncodedContent);
                    $mime_type = $file->dmMimeType;
                    $files[] = array('file'=>$file->dmEncodedContent,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type);
                }
                return $files;
            } else {
                return null;
            }

        } else {

            if ( isset($mess->dmDm->dmFiles->dmFile) ) {
                foreach( $mess->dmDm->dmFiles->dmFile as $index => $file ) {
                    if ( $part == $index ) {

                        if ( $output == 1 ) {
                            return $file->dmEncodedContent;
                        } else {
                            $file_name = $file->dmFileDescr;
                            $file_size = strlen($file->dmEncodedContent);
                            $mime_type = $file->dmMimeType;
                            return array('file'=>$file->dmEncodedContent,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type);
                        }
                        break;
                    }
                }
            }
            return null;// array('file'=>$tmp_file,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type);
        }

    }

    public function downloadEmailPrilohu( $source , $part = null , $output = 0 )
    {

        $imap = new ImapClientFile();
        if ( $imap->open($source) ) {

            if ( is_null($part) ) {

                $zprava = $imap->analyze_message();
                //Debug::dump($zprava); exit;

                $files = array();
                // Hlavni zprava
                $tmp_file = $zprava['DataFile'];
                $file_size = filesize($tmp_file);
                if ( $zprava['Type'] == 'html' ) {
                    $file_name = 'zprava.html';
                    $mime_type = 'text/html';
                } else {
                    $file_name = 'zprava.txt';
                    $mime_type = 'text/plain';
                }
                $files[] = array('file'=>$tmp_file, 'file_name'=>$file_name, 'size'=>$file_size, 'mime-type'=>$mime_type,'charset'=>@$zprava['Encoding'] );

                // Alternativni Zpravy
                if ( isset($zprava['Alternative']) ) {
                    // zpravy
                    foreach ($zprava['Alternative'] as $fid => $file) {
                        $tmp_file = $file['DataFile'];
                        $file_size = filesize($tmp_file);

                        if ( $file['Type'] == 'text' ) {
                            $file_name = 'zprava_'.$fid.'.txt';
                            $mime_type = 'text/plain';
                        } else if ($file['Type'] == 'html') {
                            $file_name = 'zprava_'.$fid.'.html';
                            $mime_type = 'text/html';
                        } else {
                            $file_name = 'zprava_'.$fid.'.txt';
                            $mime_type = 'text/plain';
                        }
                        $files[] = array('file'=>$tmp_file,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type,'charset'=>@$zprava['Encoding']);
                    }
                }
                // Prilohy
                if ( isset($zprava['Attachments']) ) {
                    // zprava + prilohy
                    foreach ($zprava['Attachments'] as $fid => $file) {
                        $tmp_file = $file['DataFile'];
                        $file_size = filesize($tmp_file);
                        if ( $file['Type'] == 'text' ) {
                            $file_name = 'soubor_'.$fid.'.txt';
                            $mime_type = 'text/plain';
                        } else if ($file['Type'] == 'html') {
                            $file_name = 'soubor_'.$fid.'.html';
                            $mime_type = 'text/html';
                        } else {
                            $file_name = empty($file['FileName'])?"file_$fid":$file['FileName'];
                            $mime_type = FileModel::mimeType($file_name);
                        }
                        $files[] = array(
                                    'file'=>$tmp_file,
                                    'file_name'=>$file_name,
                                    'size'=>$file_size,
                                    'mime-type'=>$mime_type,
                                    'charset'=>$zprava['Encoding'],
                                   );
                    }
                }
                return $files;

            } else {

                $zprava = $imap->decode_message();
                //echo "<pre>$part :"; print_r($zprava); exit;
                if ( strpos($part,".")!==false ) {
                    $part_i = str_replace(".","]['Parts'][",$part);
                    eval("\$part_info = \$zprava[0]['Parts'][".$part_i."];");
                    eval("\$tmp_file = \$zprava[0]['Parts'][".$part_i."]['BodyFile'];");
                } else {
                    $part_info = @$zprava[0]['Parts'][$part];
                    $tmp_file = @$zprava[0]['Parts'][$part]['BodyFile'];
                }


                if ( $output == 1 ) {
                    if ( $fp = fopen($tmp_file,'rb') ) {
                        return fread($fp, filesize($tmp_file) );
                    } else {
                        return null;
                    }
                } else {
                    $file_name = @$part_info['FileName'];
                    $file_size = @$part_info['BodyLength'];
                    $mime_type = FileModel::mimeType($tmp_file);
                    return array('file'=>$tmp_file,'file_name'=>$file_name,'size'=>$file_size,'mime-type'=>$mime_type);
                }
            }
        }

    }


    public function emailPrilohy( $epodatelna_id )
    {
        $res = $this->downloadEpodSource($epodatelna_id,2);
        return $this->downloadEmailPrilohu($res);
    }

    public function isdsPrilohy( $epodatelna_id )
    {
        $res = $this->downloadEpodSource($epodatelna_id,2);
        $prilohy = $this->downloadISDSPrilohu($res);

        return $prilohy;
    }

    public function actionDownload()
    {

        $epodatelna_id = $this->getParam('id',null);
        $part = $this->getParam('file',null);
        $string = $this->getParam('string',null);

        $res = $this->downloadEpodSource($epodatelna_id,2);
        $filename = basename($res);

        if ( strpos($filename,'.eml')!==false ) {
            // jde o email
            if ( !is_null($part) ) {
                if ( strpos($part,'.')!==false ) {
                    $part_a = explode(".",$part);
                    unset($part_a[0]);
                    $part = implode('.',$part_a);
                }
            } else {
                $part = 0;
            }

            $tmp_file = $this->downloadEmailPrilohu($res, $part);

            if ( !empty($tmp_file['file']) ) {

                $httpResponse = Nette\Environment::getHttpResponse();
                $httpResponse->setContentType($tmp_file['mime-type']);
                $httpResponse->setHeader('Content-Description', 'File Transfer');
                $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $tmp_file['file_name'] . '"');
                //$httpResponse->setHeader('Content-Disposition', 'inline; filename="' . $file_name . '"');
                $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                $httpResponse->setHeader('Expires', '0');
                $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
                $httpResponse->setHeader('Pragma', 'public');
                $httpResponse->setHeader('Content-Length', $tmp_file['size']);

                readfile($tmp_file['file']);

                $this->terminate();
            }
                
        } elseif ( strpos($filename,'.bsr')!==false ) {
            // jde o ds

            $tmp_file = $this->downloadISDSPrilohu($res, $part);

            $httpResponse = Nette\Environment::getHttpResponse();
            $httpResponse->setContentType($tmp_file['mime-type']);
            $httpResponse->setHeader('Content-Description', 'File Transfer');
            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $tmp_file['file_name'] . '"');
            //$httpResponse->setHeader('Content-Disposition', 'inline; filename="' . $file_name . '"');
            $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
            $httpResponse->setHeader('Expires', '0');
            $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
            $httpResponse->setHeader('Pragma', 'public');
            $httpResponse->setHeader('Content-Length', $tmp_file['size']);

            echo $tmp_file['file'];


            $this->terminate();
        }

    }


}
