<?php

class DokumentPrilohy extends BaseModel
{

    protected $name = 'dokument_to_file';
    protected $tb_dokument = 'dokument';
    protected $tb_file = 'file';
    protected $tb_user = 'user';
    protected $tb_osoba = 'osoba';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;
        $this->tb_file = $prefix . $this->tb_file;
        $this->tb_user = $prefix . $this->tb_user;
        $this->tb_osoba = $prefix . $this->tb_osoba;

    }

    public function prilohy( $dokument_id, $dokument_version = null , $detail = 0 ) {


        $sql = array(
            'distinct'=>null,
            'from' => array($this->name => 'df'),
            'cols' => null,
            'leftJoin' => array(
                'from' => array($this->tb_file => 'f'),
                'on' => array('f.id=df.file_id'),
                'cols' => array('*')
            ),
            'order_by' => array('s.nazev')
        );

        $sql['where'] = array();
        $sql['where'][] = array('dokument_id=%i',$dokument_id);
        $sql['where'][] = array('df.active=1');
        if ( !is_null($dokument_version) ) {
            $param['where'][] = array('dokument_version=%i',$dokument_version);
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


               $prilohy[ $joinFile->file_id ] = $joinFile;
            }
            return $prilohy;
        } else {
            return null;
        }
    }

    public function pripojit($dokument_id, $file_id, $dokument_version = null, $file_version = null) {

        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['dokument_version'] = $dokument_version;
        $row['file_id'] = $file_id;
        $row['file_version'] = $file_version;
        $row['date_added'] = new DateTime();
        $row['user_added'] = Environment::getUser()->getIdentity()->user_id;

        return $this->insert_basic($row);

    }

    public function deaktivovat($dokument_id, $file_id, $dokument_version = null, $file_version = null) {

        $row = array();
        $row['active'] = 0;

        if ( !is_null($dokument_version) && !is_null($file_version) ) {
            $where = array(
                array('dokument_id=%i',$dokument_id),
                array('dokument_version=%i',$dokument_version),
                array('file_id=%i',$file_id),
                array('file_version=%i',$file_version)
            );
        } else if ( is_null($dokument_version) && !is_null($file_version) ) {
            $where = array(
                array('dokument_id=%i',$dokument_id),
                array('file_id=%i',$file_id),
                array('file_version=%i',$file_version)
            );
        } else if ( !is_null($dokument_version) && is_null($file_version) ) {
            $where = array(
                array('dokument_id=%i',$dokument_id),
                array('dokument_version=%i',$dokument_version),
                array('file_id=%i',$file_id)
            );
        } else {
            $where = array(
                array('dokument_id=%i',$dokument_id),
                array('file_id=%i',$file_id)
            );
        }

        return $this->update($row, $where);

    }

    public function odebrat($param) {
        return $this->delete($param);
    }

    public function odebratVsechnySubjekty($dokument_id) {
        return $this->delete(array(array('dokument_id=%i',$dokument_id)));
    }


}
