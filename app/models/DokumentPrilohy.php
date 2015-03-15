<?php

class DokumentPrilohy extends BaseModel
{

    protected $name = 'dokument_to_file';

    // Pouzito v sestavach
    public static function pocet_priloh(array $dokument_ids)
    {
        $result = dibi::query("SELECT dokument_id, COUNT(*) AS pocet FROM [:PREFIX:dokument_to_file] WHERE dokument_id IN %in GROUP BY dokument_id", $dokument_ids)->fetchPairs('dokument_id', 'pocet');
        
        return count($result) ? $result : array();
    }

    public function prilohy($dokument_id)
    {

        $sql = array(
            'distinct'=>null,
            'from' => array($this->name => 'df'),
            'cols' => array('dokument_id'),
            'leftJoin' => array(
                'from' => array($this->tb_file => 'f'),
                'on' => array('f.id=df.file_id'),
                'cols' => array('*')
            ),
            'order_by' => array('s.nazev')
        );

        $sql['where'] = array();
        $sql['where'][] = array('df.active=1');

        if ( is_array($dokument_id) ) {
            $sql['where'][] = array('dokument_id IN %in', $dokument_id);
        } else {
            $sql['where'][] = array('dokument_id=%i',$dokument_id);
        }

        $prilohy = array();
        $result = $this->selectComplex($sql)->fetchAll();
        if ( count($result)>0 ) {
            foreach ($result as $joinFile) {

                // Kvůli Tomášově chybě ve verzi 3.2 se jméno ve spisovce nezobrazovalo (byl změněn počet parametrů této metody).
                // Nikdo si nestěžoval, nechme to takto být. Tato informace je dostupná ještě v transakčním logu dokumentu.
                $joinFile->user_name = '';
                
                $joinFile->typ_name = FileModel::typPrilohy($joinFile->typ);
                // Nahrazeni online mime-type
                $joinFile->mime_type = FileModel::mimeType($joinFile->real_path);
                // Osetreni ikony - pokud neexistuje, pak nahradit defaultni
                $mime_type_webalize = Nette\Utils\Strings::webalize($joinFile->mime_type);
                $mime_type_icon = APP_DIR ."/../public/images/mimetypes/". $mime_type_webalize .".png" ;
                if ( @file_exists($mime_type_icon) ) {
                    $joinFile->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/". $mime_type_webalize .".png";
                } else {
                    $joinFile->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/application-octet-stream.png";
                }
                
                $prilohy[ $joinFile->dokument_id ][ $joinFile->id ] = $joinFile;
            }

            if ( !is_array($dokument_id) ) {
                return $prilohy[ $dokument_id ];
            } else {
                return $prilohy;
            }
        } else {
            return null;
        }
    }

    public function pripojit($dokument_id, $file_id, $dokument_version = null, $file_version = null) {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['file_id'] = $file_id;
        $row['date_added'] = new DateTime();
        $row['user_id'] = Nette\Environment::getUser()->getIdentity()->id;

        return $this->insert($row);

    }

    public function deaktivovat($dokument_id, $file_id) {

        $row = array();
        $row['active'] = 0;

        $where = array(
            array('dokument_id=%i',$dokument_id),
            array('file_id=%i',$file_id)
        );

        return $this->update($row, $where);

    }

    public function odebrat($dokument_id, $file_id)
    {
        $param = array( array('file_id=%i',$file_id),array('dokument_id=%i',$dokument_id) );
        return $this->delete($param);
    }

    public function odebratVsechnySubjekty($dokument_id) {
        return $this->delete(array(array('dokument_id=%i',$dokument_id)));
    }

    public static function maxVelikostUploadu($lidsky_vystup = false)
    {
        function _getSize($str) {
            
            $c = substr($str, -1);
            
            switch(strtoupper($c)) {
                case 'K':
                    $n = substr($str, 0, -1);
                    return $n * pow(2, 10);
                case 'M':
                    $n = substr($str, 0, -1);
                    return $n * pow(2, 20);
                case 'G':
                    $n = substr($str, 0, -1);
                    return $n * pow(2, 30);
            }
            
            return (int)$str;
        }
        
        if (function_exists("ini_get")) {
            $s1 = ini_get("upload_max_filesize");
            $n1 = _getSize($s1);
            $s2 = ini_get("post_max_size");
            $n2 = _getSize($s2);
            $min = min($n1, $n2);
            
            if (!$lidsky_vystup)
                return $min;
            if ($min % pow(2, 20) == 0)
                return $min/pow(2, 20) . " MB";
            return $min/1024 . " kB";
        }
        
        return $lidsky_vystup ? "nepodařilo se zjistit" : false;
    }
}
