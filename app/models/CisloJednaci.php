<?php

class CisloJednaci extends BaseModel
{

    protected $name = 'cislo_jednaci';
    protected $primary = 'id';

    protected $tb_dokument = 'dokument';

    protected $info;
    protected $unique;
    protected $urad;
    protected $user_info;
    protected $org;
    protected $pocatek_cisla;
    protected $pouzij_minuly_rok;

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokument = $prefix . $this->tb_dokument;

        $user_config = Environment::getVariable('user_config');
        $this->info = $user_config->cislo_jednaci;
        $this->urad = $user_config->urad;

        // pocatek cisla
        $this->pocatek_cisla = 1;
        if ( @$this->info->pocatek_cisla > 1 ) {
             $count = $this->fetchAll()->count();
             if ( $count == 0 ) {
                 $this->pocatek_cisla = isset($this->info->pocatek_cisla)?$this->info->pocatek_cisla:1;
             }
        }

        $unique_info = Environment::getVariable('unique_info');
        $unique_part = explode('#',$unique_info);
        $this->unique = 'OSS-'. $unique_part[0];

        try {
            $user = Environment::getUser()->getIdentity()->id;
        } catch ( Exception $e ) {
            $user = 1;
        }
        $UserModel = new UserModel();
        $this->user_info = $UserModel->getUser($user, 1);

        $orgjednotka_id = null;
        if ( count($this->user_info->user_roles)>0 ) {
            foreach ( $this->user_info->user_roles as $user_role ) {
                if ( !empty( $user_role->orgjednotka_id ) ) {
                    $orgjednotka_id = $user_role->orgjednotka_id;
                    break;
                }
            }
        }

        if ( empty($orgjednotka_id) ) {
            $this->org = null;
        } else {
            $Org = new Orgjednotka();
            $this->org = $Org->getInfo($orgjednotka_id);
        }

        $this->pouzij_minuly_rok = isset($this->info->minuly_rok) && $this->info->minuly_rok == 1;
    }

    /**
     * Vygeneruje cislo jednaci
     * @return string
     */
    public function generuj($ulozit = 0, $info = null) {

        $maska = $this->info->maska;
        $cislo_jednaci = $maska;

        if ( is_null($info) ) {
            $info = array();

            if ( isset($this->info->typ_deniku) && $this->info->typ_deniku == "org" ) {
                $info['podaci_denik'] = $this->info->podaci_denik . (!empty($this->org)?"_".$this->org->ciselna_rada:"");
            } else {
                $info['podaci_denik'] = $this->info->podaci_denik;
            }
            
            $info['rok'] = date('Y');
            if ($this->pouzij_minuly_rok)
                --$info['rok'];
            $info['poradove_cislo'] = $this->max('poradove_cislo');

            $info['urad_zkratka'] = $this->urad->zkratka;
            $info['urad_poradi'] = $this->max('urad');

            $info['orgjednotka_id'] = !empty($this->org)?$this->org->id:null;
            $info['org'] = !empty($this->org)?$this->org->ciselna_rada:"";
            $info['org_poradi'] = $this->max('org');

            $info['user_id'] = $this->user_info->id;
            $info['user'] = $this->user_info->username;
            $info['prijmeni'] = String::webalize(@$this->user_info->identity->prijmeni);
            $info['user_poradi'] = $this->max('user');
        }


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
            $tmp->id = $cjednaci_id;
            $tmp->cislo_jednaci = $cislo_jednaci;
            $tmp->rok = $info['rok'];
            $tmp->poradove_cislo = $info['poradove_cislo'];
            $tmp->podaci_denik = $info['podaci_denik'];
            $tmp->app_id = $this->unique;
            $tmp->urad = $this->urad->zkratka;
            $tmp->urad_nazev = $this->urad->nazev;
            $tmp->urad_poradi = $info['urad_poradi'];
            $tmp->orgjednotka_id = !is_null($this->org)?$this->org->id:null;
            $tmp->orgjednotka = !is_null($this->org)?$this->org->ciselna_rada:"";
            $tmp->orgjednotka_poradi = $info['org_poradi'];
            $tmp->user_id = $this->user_info->id;
            $tmp->user = $this->user_info->username;
            $tmp->user_poradi = $info['user_poradi'];
            $tmp->prijmeni = String::webalize(@$this->user_info->identity->prijmeni);

            return $tmp;
        } else {

            $tmp = new stdClass();
            $tmp->id = isset($info['id'])?$info['id']:null;
            $tmp->cislo_jednaci = $cislo_jednaci;
            $tmp->rok = $info['rok'];
            $tmp->poradove_cislo = $info['poradove_cislo'];
            $tmp->podaci_denik = $info['podaci_denik'];
            $tmp->app_id = $this->unique;
            $tmp->urad = $this->urad->zkratka;
            $tmp->urad_nazev = $this->urad->nazev;
            $tmp->urad_poradi = $info['urad_poradi'];
            $tmp->orgjednotka_id = !is_null($this->org)?$this->org->id:null;
            $tmp->orgjednotka = !is_null($this->org)?$this->org->ciselna_rada:"";
            $tmp->orgjednotka_poradi = $info['org_poradi'];
            $tmp->user_id = $this->user_info->id;
            $tmp->user = $this->user_info->username;
            $tmp->user_poradi = $info['user_poradi'];
            $tmp->prijmeni = String::webalize($this->user_info->identity->prijmeni);


            return $tmp;
        }
        
    }

    public function nacti($cjednaci_id, $generuj = 0) {

            
        $row = $this->fetchRow(array(array('id=%i',$cjednaci_id)))->fetch();

        if ( $row ) {
            
            $info = array();
            $info['id'] = $cjednaci_id;

            $info['podaci_denik'] = $row->podaci_denik;
            $info['rok'] = $row->rok;
            $info['poradove_cislo'] = $row->poradove_cislo;

            $info['urad_zkratka'] = $row->urad_zkratka;
            $info['urad_poradi'] = $row->urad_poradi;

            $info['orgjednotka_id'] = $row->orgjednotka_id;
            $OrgJednotka = new Orgjednotka();
            $org_info = $OrgJednotka->getInfo($row->orgjednotka_id);
            $info['org'] = @$org_info->ciselna_rada;
            $info['org_poradi'] = $row->org_poradi;

            $info['user_id'] = $row->user_id;
            $User = new UserModel();
            $user_info = $User->getUser($row->user_id,true);

            $info['user'] = $user_info->username;
            $info['prijmeni'] = String::webalize($user_info->identity->prijmeni);
            $info['user_poradi'] = $row->user_poradi;

            return $this->generuj($generuj, $info);
        } else {
            return $this->generuj($generuj);
        }

    }

    private function max($typ) {

        $where = array();
        $where[] = array('rok=%i', $this->pouzij_minuly_rok ? date('Y')-1 : date('Y'));

        $pocatek_cisla = $this->pocatek_cisla;
        $cislo = null;
        switch ($typ) {
            case "poradove_cislo":
                
                if ( isset($this->info->typ_deniku) && $this->info->typ_deniku == "org" ) {
                    $where[] = array('podaci_denik=%s',$this->info->podaci_denik . (!empty($this->org)?"_".$this->org->ciselna_rada:""));
                } else {
                    $where[] = array('podaci_denik=%s',$this->info->podaci_denik);
                }                
                
                $result = $this->fetchAll(array('poradove_cislo'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->poradove_cislo)?($row->poradove_cislo)+1 : $pocatek_cisla;
                break;
            case "urad":
                $where[] = array('urad_zkratka=%s',$this->urad->zkratka);
                $result = $this->fetchAll(array('urad_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->urad_poradi)?($row->urad_poradi)+1 : $pocatek_cisla;
                break;
            case "org":
                if ( is_null($this->org) ) {
                    $where[] = array('orgjednotka_id IS NULL');
                } else {
                    $where[] = array('orgjednotka_id=%i',$this->org->id);
                }
                $result = $this->fetchAll(array('org_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->org_poradi)?($row->org_poradi)+1 : $pocatek_cisla;
                break;
            case "user":
                $where[] = array('user_id=%i',$this->user_info->id);
                $result = $this->fetchAll(array('user_poradi'=>'DESC'),$where,null,1);
                $row = $result->fetch();
                $cislo = (@$row->user_poradi)?($row->user_poradi)+1 : $pocatek_cisla;
                break;
                
            default: break;
        }
        return $cislo;
    }

    public function  get_minuly_rok() {
        
        return $this->pouzij_minuly_rok;
    }
    
}
