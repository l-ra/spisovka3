<?php

class ESSMail extends Nette\Mail\Message
{

    public function __construct()
    {
        parent::__construct();

        // Nastaveni identifikace maileru
        $app_info = Nette\Environment::getVariable('app_info');
        if (!empty($app_info)) {
            $app_info = explode("#", $app_info);
        } else {
            $app_info = array('3.x', 'rev.X', 'OSS Spisová služba v3', '1270716764');
        }
        
        $this->setHeader('X-Mailer', Nette\Utils\Strings::webalize($app_info[2], '. ', 0));
    }

    // pro kompatibilitu s kodem napsanym pro stare Nette
    public function send()
    {
        // $mailer = new Nette\Mail\SendmailMailer;
        $mailer = new ESSMailer();
        $mailer->send($this);
    }

    /**
     * Nastavi e-mail adresu odesilatele mailu dle nastaveni v administraci
     */
    public function setFromConfig()
    {
        $ep = (new Spisovka\ConfigEpodatelna())->get();
        $odes = reset($ep['odeslani']);
        $email = $odes['email'];
        if ($email)
            try {
                $this->setFrom($email);                
            } catch (Exception $e) {
                $e->getMessage();
                throw new Exception("E-mailová adresa pro odesílání \"$email\" je neplatná.");
            }
    }

    /**
     * Připojí podpis přihlášené osoby a organizace.
     * Je voláno pouze při odmítnutí e-mailu v e-podatelně.
     * @param string $message
     * @return string
     */
    public static function appendSignature($message)
    {
        // Urad
        $client_config = Nette\Environment::getVariable('client_config');
        $urad = $client_config->urad;

        $tmp = $message;

        // Podpis
        $tmp .= "\n\n--\n";
        $tmp .= "Zpracoval: " . Nette\Environment::getUser()->displayName . "\n\n";
        $tmp .= $urad->nazev . "\n";
        if (!empty($urad->adresa->ulice)) {
            $tmp .= $urad->adresa->ulice . "\n";
        }
        if (!( empty($urad->adresa->psc) && empty($urad->adresa->mesto) )) {
            $tmp .= $urad->adresa->psc . " " . $urad->adresa->mesto . "\n";
        }
        $tmp .= "\n";
        if (!empty($urad->kontakt->telefon)) {
            $tmp .= "telefon: " . $urad->kontakt->telefon . " \n";
        }
        if (!empty($urad->kontakt->email)) {
            $tmp .= "email: " . $urad->kontakt->email . " \n";
        }
        if (!empty($urad->kontakt->www)) {
            $tmp .= "url: " . $urad->kontakt->www . " \n";
        }

        return $tmp;
    }

}
