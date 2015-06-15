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

    // pro kompatibilitu s kodem napsanym pro stare Nette
    public function send() {
        
        // $mailer = new Nette\Mail\SendmailMailer;
        $mailer = new ESSMailer();
        $mailer->send($this);
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
     * Nastavi e-mail adresu odesilatele dle uzivatelske nastaveni e-podatelny
     */
    public function setFromConfig($foo = null)
    {
        $ep = (new Spisovka\ConfigEpodatelna())->get();
        $odes = reset($ep['odeslani']);
        $email = $odes['email'];
        if ($email)
            $this->setFrom($email);
    }

    public function setBodySign($body)
    {

        // Urad
        $user_config = Nette\Environment::getVariable('user_config');
        $urad = $user_config->urad;

        // Uzivatel
        $user_system = Nette\Environment::getUser();
        if ($user_system->isLoggedIn()) {
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


