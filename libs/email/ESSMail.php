<?php


class ESSMail extends Nette\Mail\Message {


    public $signed = 0;
    public $config;

    public function __construct() {
        $ret = parent::__construct();

        // Nastaveni identifikace maileru
        $app_info = Nette\Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }
        $this->setHeader('X-Mailer', Nette\Utils\Strings::webalize($app_info[2], '. ', 0));

        return $ret;
    }

    /**
     * Povolit podepisovani emailu?
     *
     * @param int $signed hodnoty 0|1
     * @return return int stejny jako vstup
     */
    public function signed($signed = 1)
    {
        return $this->signed = $signed;
    }

    /**
     * Nastavi konfiguraci emailu dle uzivatelske nastaveni e-podatelny
     * V pripade prazdneho parametru se pouzije defaultni ucet e-podatelny
     *
     * @param array $config konfigurace dle nastaveni e-podatelny
     * @return bool
     */
    public function setFromConfig($config = null)
    {

        if ( is_null($config) ) {

            $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
            $ep = $ep_config->toArray();
            if ( isset($ep['odeslani'][0]) ) {
                if ( $ep['odeslani'][0]['aktivni'] == '1' ) {
                    $this->config = $ep['odeslani'][0];
                    return true;
                } else {
                    throw new Nette\InvalidStateException('Nebyl zjištěn aktivní účet pro odesílání emailů.');
                    return false;
                }
            } else {
                throw new Nette\InvalidStateException('Nebyl zjištěn účet pro odesílání emailů.');
                return false;
            }
        } else {

            if ( isset($config['email']) ) {
                $this->config = $config;
                return true;
            } else {
                throw new Nette\InvalidStateException('Konfigurace pro odesílání emailů není platná.');
                return false;
            }
        }
    }

    public function getFromConfig()
    {
        return $this->config;
    }

    public function setBodySign($body)
    {

        // Urad
        $user_config = Nette\Environment::getVariable('user_config');
        $urad = $user_config->urad;

        // Uzivatel
        $user_system = Nette\Environment::getUser();
        if ( $user_system->isAuthenticated() ) {
            $user = $user_system->getIdentity();
        } else {
            $user = new stdClass();
            $user->name = "";
            $user->user_roles = array();
            $user->user_roles = array();
        }

        $tmp = $body;

        // Podpis
        $tmp .= "\n";
        $tmp .= "\n";
        $tmp .= "--\n";
        if ( !empty($user->name) ) {
            $tmp .= "Zpracoval: ". $user->name ."\n";
        }
        $tmp .= "\n";
        $tmp .= $urad->nazev ."\n";
        if ( !empty($urad->adresa->ulice) ) {
            $tmp .= $urad->adresa->ulice ."\n";
        }
        if ( !( empty($urad->adresa->psc) && empty($urad->adresa->mesto) ) ) {
        $tmp .= $urad->adresa->psc ." ". $urad->adresa->mesto ."\n";
        }
        $tmp .= "\n";
        if ( !empty($urad->kontakt->telefon) ) {
            $tmp .= "telefon: ". $urad->kontakt->telefon ." \n";
        }
        if ( !empty($urad->kontakt->email) ) {
            $tmp .= "email: ". $urad->kontakt->email ." \n";
        }
        if ( !empty($urad->kontakt->www) ) {
            $tmp .= "url: ". $urad->kontakt->www ." \n";
        }

        $this->setBody($tmp);
        return $tmp;
    }

}


