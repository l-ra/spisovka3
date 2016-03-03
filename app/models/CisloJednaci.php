<?php

class CisloJednaci extends BaseModel
{

    protected $name = 'cislo_jednaci';
    protected $primary = 'id';
    protected $info;
    protected $urad;
    protected $user_account;
    protected $person;
    protected $org;
    protected $pocatek_cisla;
    protected $pouzij_minuly_rok;

    public function __construct()
    {
        parent::__construct();

        $client_config = Nette\Environment::getVariable('client_config');
        $this->info = $client_config->cislo_jednaci;
        $this->urad = $client_config->urad;

        // pocatek cisla
        $this->pocatek_cisla = 1;
        if ($this->info->pocatek_cisla > 1) {
            $count = $this->select()->count();
            if ($count == 0) {
                $this->pocatek_cisla = isset($this->info->pocatek_cisla) ? $this->info->pocatek_cisla
                            : 1;
            }
        }

        $user = Nette\Environment::getUser();
        $this->user_account = new UserAccount($user->id);
        $this->person = $this->user_account->getPerson();

        $orgjednotka_id = Orgjednotka::dejOrgUzivatele();

        if (empty($orgjednotka_id)) {
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
    public function generuj($ulozit = 0, $info = null)
    {

        $maska = $this->info->maska;
        $cislo_jednaci = $maska;

        if (is_null($info)) {
            $info = array();

            if (isset($this->info->typ_deniku) && $this->info->typ_deniku == "org") {
                $info['podaci_denik'] = $this->info->podaci_denik . (!empty($this->org) ? "_" . $this->org->ciselna_rada
                                    : "");
            } else {
                $info['podaci_denik'] = $this->info->podaci_denik;
            }

            $info['rok'] = date('Y');
            if ($this->pouzij_minuly_rok)
                --$info['rok'];
            $info['poradove_cislo'] = $this->max('poradove_cislo');

            $info['urad_zkratka'] = $this->urad->zkratka;
            $info['urad_poradi'] = $this->max('urad');

            $info['orgjednotka_id'] = !empty($this->org) ? $this->org->id : null;
            $info['org'] = !empty($this->org) ? $this->org->ciselna_rada : "";
            $info['org_poradi'] = $this->max('org');

            $info['user_id'] = $this->user_account->id;
            $info['user'] = $this->user_account->username;
            $info['prijmeni'] = Nette\Utils\Strings::webalize($this->person->prijmeni);
            $info['user_poradi'] = $this->max('user');
        }


        $cislo_jednaci = str_replace("{podaci_denik}", $info['podaci_denik'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{evidence}", $info['podaci_denik'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{rok}", $info['rok'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{poradove_cislo}", $info['poradove_cislo'],
                $cislo_jednaci);

        $matches = [];
        if (preg_match('/{poradove_cislo\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $poradove_cislo = sprintf('%0' . $matches[1] . 'd', $info['poradove_cislo']);
                $cislo_jednaci = preg_replace('/{poradove_cislo\|(\d{1,6})}/', $poradove_cislo,
                        $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $cislo_jednaci = str_replace("{urad}", $info['urad_zkratka'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{urad_poradi}", $info['urad_poradi'], $cislo_jednaci);
        if (preg_match('/{urad_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $poradove_cislo = sprintf('%0' . $matches[1] . 'd', $info['urad_poradi']);
                $cislo_jednaci = preg_replace('/{urad_poradi\|(\d{1,6})}/', $poradove_cislo,
                        $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $cislo_jednaci = str_replace("{org}", $info['org'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{org_id}", $info['orgjednotka_id'], $cislo_jednaci);
        if (preg_match('/{org_id\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $cislo = sprintf('%0' . $matches[1] . 'd', $info['orgjednotka_id']);
                $cislo_jednaci = preg_replace('/{org_id\|(\d{1,6})}/', $cislo, $cislo_jednaci);
                unset($cislo);
            }
        }
        $cislo_jednaci = str_replace("{org_poradi}", $info['org_poradi'], $cislo_jednaci);
        if (preg_match('/{org_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $poradove_cislo = sprintf('%0' . $matches[1] . 'd', $info['org_poradi']);
                $cislo_jednaci = preg_replace('/{org_poradi\|(\d{1,6})}/', $poradove_cislo,
                        $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $cislo_jednaci = str_replace("{user_id}", $info['user_id'], $cislo_jednaci);
        if (preg_match('/{user_id\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $poradove_cislo = sprintf('%0' . $matches[1] . 'd', $info['user_id']);
                $cislo_jednaci = preg_replace('/{user_id\|(\d{1,6})}/', $poradove_cislo,
                        $cislo_jednaci);
                unset($poradove_cislo);
            }
        }
        $cislo_jednaci = str_replace("{user}", $info['user'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{prijmeni}", $info['prijmeni'], $cislo_jednaci);
        $cislo_jednaci = str_replace("{user_poradi}", $info['user_poradi'], $cislo_jednaci);
        if (preg_match('/{user_poradi\|(\d{1,6})}/', $cislo_jednaci, $matches)) {
            if (isset($matches[1])) {
                $poradove_cislo = sprintf('%0' . $matches[1] . 'd', $info['user_poradi']);
                $cislo_jednaci = preg_replace('/{user_poradi\|(\d{1,6})}/', $poradove_cislo,
                        $cislo_jednaci);
                unset($poradove_cislo);
            }
        }

        $tmp = new stdClass();
        if ($ulozit == 1) {
            unset($info['user'], $info['prijmeni'], $info['org']);
            $cjednaci_id = $this->insert($info);
            $tmp->id = $cjednaci_id;
        } else {
            $tmp->id = isset($info['id']) ? $info['id'] : null;
        }
        
        $tmp->cislo_jednaci = $cislo_jednaci;
        $tmp->rok = $info['rok'];
        $tmp->poradove_cislo = $info['poradove_cislo'];
        $tmp->podaci_denik = $info['podaci_denik'];
        $tmp->urad = $this->urad->zkratka;
        $tmp->urad_nazev = $this->urad->nazev;
        $tmp->urad_poradi = $info['urad_poradi'];
        $tmp->orgjednotka_id = !is_null($this->org) ? $this->org->id : null;
        $tmp->orgjednotka = !is_null($this->org) ? $this->org->ciselna_rada : "";
        $tmp->orgjednotka_poradi = $info['org_poradi'];
        $tmp->user_id = $this->user_account->id;
        $tmp->user = $this->user_account->username;
        $tmp->user_poradi = $info['user_poradi'];
        $tmp->prijmeni = Nette\Utils\Strings::webalize($this->person->prijmeni);

        return $tmp;
    }

    public function nacti($cjednaci_id, $generuj = 0)
    {


        $row = $this->select(array(array('id=%i', $cjednaci_id)))->fetch();

        if ($row) {

            $info = array();
            $info['id'] = $cjednaci_id;

            $info['podaci_denik'] = $row->podaci_denik;
            $info['rok'] = $row->rok;
            $info['poradove_cislo'] = $row->poradove_cislo;

            $info['urad_zkratka'] = $row->urad_zkratka;
            $info['urad_poradi'] = $row->urad_poradi;

            $orgjednotka_id = $row->orgjednotka_id;
            $info['orgjednotka_id'] = $orgjednotka_id;
            if ($orgjednotka_id !== null) {
                $OrgJednotka = new Orgjednotka();
                $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                $info['org'] = $org_info->ciselna_rada;
            } else {
                $info['org'] = null;
            }

            $info['org_poradi'] = $row->org_poradi;

            $info['user_id'] = $row->user_id;
            $account = new UserAccount($row->user_id);
            $person = $account->getPerson();
            $info['user'] = $account->username;
            $info['prijmeni'] = Nette\Utils\Strings::webalize($person->prijmeni);
            $info['user_poradi'] = $row->user_poradi;

            return $this->generuj($generuj, $info);
        } else {
            return $this->generuj($generuj);
        }
    }

    private function max($typ)
    {

        $where = array();
        $where[] = array('rok=%i', $this->pouzij_minuly_rok ? date('Y') - 1 : date('Y'));

        $pocatek_cisla = $this->pocatek_cisla;
        $cislo = null;
        switch ($typ) {
            case "poradove_cislo":

                if (isset($this->info->typ_deniku) && $this->info->typ_deniku == "org") {
                    $where[] = array('podaci_denik=%s', $this->info->podaci_denik . (!empty($this->org)
                                    ? "_" . $this->org->ciselna_rada : ""));
                } else {
                    $where[] = array('podaci_denik=%s', $this->info->podaci_denik);
                }

                $result = $this->select($where, array('poradove_cislo' => 'DESC'), null, 1);
                $row = $result->fetch();
                $cislo = (@$row->poradove_cislo) ? ($row->poradove_cislo) + 1 : $pocatek_cisla;
                break;
            case "urad":
                $where[] = array('urad_zkratka=%s', $this->urad->zkratka);
                $result = $this->select($where, array('urad_poradi' => 'DESC'), null, 1);
                $row = $result->fetch();
                $cislo = (@$row->urad_poradi) ? ($row->urad_poradi) + 1 : $pocatek_cisla;
                break;
            case "org":
                if (is_null($this->org)) {
                    $where[] = array('orgjednotka_id IS NULL');
                } else {
                    $where[] = array('orgjednotka_id=%i', $this->org->id);
                }
                $result = $this->select($where, array('org_poradi' => 'DESC'), null, 1);
                $row = $result->fetch();
                $cislo = (@$row->org_poradi) ? ($row->org_poradi) + 1 : $pocatek_cisla;
                break;
            case "user":
                $where[] = array('user_id=%i', $this->user_account->id);
                $result = $this->select($where, array('user_poradi' => 'DESC'), null, 1);
                $row = $result->fetch();
                $cislo = (@$row->user_poradi) ? ($row->user_poradi) + 1 : $pocatek_cisla;
                break;

            default: break;
        }
        return $cislo;
    }

    public function get_minuly_rok()
    {

        return $this->pouzij_minuly_rok;
    }

}
