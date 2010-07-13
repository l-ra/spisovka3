<?php

class CisloJednaci extends BaseModel
{

    protected $name = 'cislo_jednaci';
    protected $primary = 'cjednaci_id';

    protected $tb_dokument = 'dokument';

    protected $info;
    protected $unique;
    protected $urad;
    protected $user_info;
    protected $org;

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;

        $user_config = Environment::getVariable('user_config');
        $this->info = $user_config->cislo_jednaci;
        $this->urad = $user_config->urad;

        $unique_info = Environment::getVariable('unique_info');
        $unique_part = explode('#',$unique_info);
        $this->unique = 'OSS-'. $unique_part[0];

        $user = Environment::getUser()->getIdentity()->user_id;
        $UserModel = new UserModel();
        $this->user_info = $UserModel->getUser($user, 1);

        $Org = new Orgjednotka();
        $this->org = $Org->getInfo(1);


    }

    /**
     * Vygeneruje cislo jednaci
     * @return string
     */
    public function generuj($ulozit = 0) {

        $maska = $this->info->maska;
        $cislo_jednaci = $maska;

        $info = array();

        $info['podaci_denik'] = $this->info->podaci_denik;
        $info['rok'] = date('Y');
        $info['poradove_cislo'] = $this->max('poradove_cislo');
        
        $info['urad_zkratka'] = $this->urad->zkratka;
        $info['urad_poradi'] = $this->max('urad');

        $info['orgjednotka_id'] = $this->org->orgjednotka_id;
        $info['org'] = @$this->org->zkratka;
        $info['org_poradi'] = $this->max('org');

        $info['user_id'] = $this->user_info->user_id;
        $info['user'] = $this->user_info->username;
        $info['prijmeni'] = String::webalize($this->user_info->identity->prijmeni);
        $info['user_poradi'] = $this->max('user');

        $cislo_jednaci = str_replace("{podaci_denik}", $info['podaci_denik'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{evidence}", $info['podaci_denik'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{rok}", $info['rok'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{poradove_cislo}", $info['poradove_cislo'], $cislo_jednaci);

        if (preg_match('/{poradove_cislo\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $poradove_cislo = sprintf('%0'.$matches[1].'d', $info['poradove_cislo']);
                $cislo_jednaci = preg_replace('/{poradove_cislo\|(\d{1,6})}/', $poradove_cislo, $cislo_jednaci);
                unset($poradove_cislo);
            }
        }
        
        $cislo_jednaci = str_replace("{urad}", $info['urad_zkratka'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{urad_poradi}", $info['urad_poradi'], $cislo_jednaci);
        if (preg_match('/{urad_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $poradove_cislo = sprintf('%0'.$matches[1].'d', $info['urad_poradi']);
                $cislo_jednaci = preg_replace('/{urad_poradi\|(\d{1,6})}/', $poradove_cislo, $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $cislo_jednaci = str_replace("{org}", $info['org'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{org_id}", $info['orgjednotka_id'], $cislo_jednaci);
        if (preg_match('/{org_id\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $cislo = sprintf('%0'.$matches[1].'d', $info['orgjednotka_id']);
                $cislo_jednaci = preg_replace('/{org_id\|(\d{1,6})}/', $cislo, $cislo_jednaci);
                unset($cislo);
            }
        }
        $cislo_jednaci = str_replace("{org_poradi}", $info['org_poradi'], $cislo_jednaci);
        if (preg_match('/{org_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $poradove_cislo = sprintf('%0'.$matches[1].'d', $info['org_poradi']);
                $cislo_jednaci = preg_replace('/{org_poradi\|(\d{1,6})}/', $poradove_cislo, $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $cislo_jednaci = str_replace("{user_id}", $info['user_id'], $cislo_jednaci);
        if (preg_match('/{user_id\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $poradove_cislo = sprintf('%0'.$matches[1].'d', $info['user_id']);
                $cislo_jednaci = preg_replace('/{user_id\|(\d{1,6})}/', $poradove_cislo, $cislo_jednaci);
                unset($poradove_cislo);
            }
        }
        $cislo_jednaci = str_replace("{user}", $info['user'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{prijmeni}", $info['prijmeni'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{user_poradi}", $info['user_poradi'], $cislo_jednaci);
        if (preg_match('/{user_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches) ) {
            if ( isset($matches[1]) ) {
                $poradove_cislo = sprintf('%0'.$matches[1].'d', $info['user_poradi']);
                $cislo_jednaci = preg_replace('/{user_poradi\|(\d{1,6})}/', $poradove_cislo, $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        if ( $ulozit == 1 ) {
            unset($info['user'],$info['prijmeni'],$info['org']  );
            $cjednaci_id = $this->insert($info);

            $tmp = new stdClass();
            $tmp->cjednaci_id = $cjednaci_id;
            $tmp->cislo_jednaci = $cislo_jednaci;
            $tmp->rok = $info['rok'];
            $tmp->poradove_cislo = $info['poradove_cislo'];
            $tmp->podaci_denik = $info['podaci_denik'];
            $tmp->app_id = $this->unique;
            $tmp->urad = $this->urad->zkratka;
            $tmp->urad_nazev = $this->urad->nazev;
            $tmp->urad_poradi = $info['urad_poradi'];
            $tmp->orgjednotka_id = @$this->org->orgjednotka_id;
            $tmp->orgjednotka = @$this->org->zkratka;
            $tmp->orgjednotka_poradi = $info['org_poradi'];
            $tmp->user_id = $this->user_info->user_id;
            $tmp->user = $this->user_info->username;
            $tmp->user_poradi = $info['user_poradi'];
            $tmp->prijmeni = String::webalize($this->user_info->identity->prijmeni);

            return $tmp;
        } else {

            $tmp = new stdClass();
            $tmp->cislo_jednaci = $cislo_jednaci;
            $tmp->rok = $info['rok'];
            $tmp->poradove_cislo = $info['poradove_cislo'];
            $tmp->podaci_denik = $info['podaci_denik'];
            $tmp->app_id = $this->unique;
            $tmp->urad = $this->urad->zkratka;
            $tmp->urad_nazev = $this->urad->nazev;
            $tmp->urad_poradi = $info['urad_poradi'];
            $tmp->orgjednotka_id = @$this->org->orgjednotka_id;
            $tmp->orgjednotka = @$this->org->zkratka;
            $tmp->orgjednotka_poradi = $info['org_poradi'];
            $tmp->user_id = $this->user_info->user_id;
            $tmp->user = $this->user_info->username;
            $tmp->user_poradi = $info['user_poradi'];
            $tmp->prijmeni = String::webalize($this->user_info->identity->prijmeni);


            return $tmp;
        }
        
    }

    private function max($typ) {

        $where = array();
        $where[] = array('rok=%i',date('Y'));

        $cislo = null;
        switch ($typ) {
            case "poradove_cislo":
                $where[] = array('podaci_denik=%s',$this->info->podaci_denik);
                $result = $this->fetchAll(array('poradove_cislo'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->poradove_cislo)?($row->poradove_cislo)+1 : 1;
                break;
            case "urad":
                $where[] = array('urad_zkratka=%s',$this->urad->zkratka);
                $result = $this->fetchAll(array('urad_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->urad_poradi)?($row->urad_poradi)+1 : 1;
                break;
            case "org":
                $where[] = array('orgjednotka_id=%i',$this->org->orgjednotka_id);
                $result = $this->fetchAll(array('org_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->org_poradi)?($row->org_poradi)+1 : 1;
                break;
            case "user":
                $where[] = array('user_id=%i',$this->user_info->user_id);
                $result = $this->fetchAll(array('user_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->user_poradi)?($row->user_poradi)+1 : 1;
                break;
                
            default: break;
        }
        return $cislo;
    }


}
