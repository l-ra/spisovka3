<?php

class DokumentPrilohy extends BaseModel
{

    protected $name = 'dokument_to_file';

    public function prilohy( $dokument_id, $detail = 0 ) {


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
            $sql['where'][] = array('dokument_id IN (%in)', $dokument_id);
        } else {
            $sql['where'][] = array('dokument_id=%i',$dokument_id);
        }


        if ( $detail == 1 ) {
            $UserModel = new UserModel();
        }
        $prilohy = array();
        $result = $this->fetchAllComplet($sql)->fetchAll();
        if ( count($result)>0 ) {
            foreach ($result as $joinFile) {

                if ( $detail == 1 && !empty($joinFile->user_created) ) {
                    $user = $UserModel->getUser($joinFile->user_created, 1);
                    $joinFile->user_name = Osoba::displayName($user->identity);
                } else {
                    $joinFile->user_name = '';
                }
                $joinFile->typ_name = FileModel::typPrilohy($joinFile->typ);
                // Nahrazeni online mime-type
                $joinFile->mime_type = FileModel::mimeType($joinFile->real_path);
                // Osetreni ikony - pokud neexistuje, pak nahradit defaultni
                $mime_type_webalize = String::webalize($joinFile->mime_type);
                $mime_type_icon = APP_DIR ."/../public/images/mimetypes/". $mime_type_webalize .".png" ;
                if ( @file_exists($mime_type_icon) ) {
                    $joinFile->mime_type_icon = BASE_URI ."images/mimetypes/". $mime_type_webalize .".png";
                } else {
                    $joinFile->mime_type_icon = BASE_URI ."images/mimetypes/application-octet-stream.png";
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
        $row['user_id'] = Environment::getUser()->getIdentity()->id;

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

    public function odebrat($param) {
        return $this->delete($param);
    }

    public function odebratVsechnySubjekty($dokument_id) {
        return $this->delete(array(array('dokument_id=%i',$dokument_id)));
    }


}
