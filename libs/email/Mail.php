<?php

namespace Spisovka;

use Nette;

class Mail extends Nette\Mail\Message
{

    /** 
     * Pro kompatibilitu s kodem napsanym pro stare Nette
     */
    public function send()
    {
        $mailer = new Mailer();
        $mailer->send($this);
    }

    /**
     * Nastavi e-mail adresu odesilatele mailu dle nastaveni v administraci
     */
    public function setFromConfig()
    {
        $ep = (new ConfigEpodatelna())->get();
        $email = $ep['odeslani']['email'];
        if ($email)
            try {
                $this->setFrom($email);                
            } catch (Exception $e) {
                $e->getMessage();
                throw new \Exception("E-mailová adresa pro odesílání \"$email\" je neplatná.");
            }
    }

    /**
     * Připojí podpis přihlášené osoby a organizace.
     */
    public function appendSignature(User $user)
    {
        // Urad
        $client_config = GlobalVariables::get('client_config');
        $urad = $client_config->urad;

        $tmp = $this->getBody();

        // Podpis
        $tmp .= "\n\n--\n";
        $tmp .= "Zpracoval: " . $user->displayName . "\n\n";
        $tmp .= $urad->nazev . "\n";
        if (!empty($urad->adresa->ulice))
            $tmp .= $urad->adresa->ulice . "\n";
        if (!( empty($urad->adresa->psc) && empty($urad->adresa->mesto) ))
            $tmp .= $urad->adresa->psc . " " . $urad->adresa->mesto . "\n";
        $tmp .= "\n";
        if (!empty($urad->kontakt->telefon))
            $tmp .= "telefon: " . $urad->kontakt->telefon . " \n";
        if (!empty($urad->kontakt->email))
            $tmp .= "e-mail: " . $urad->kontakt->email . " \n";
        if (!empty($urad->kontakt->www))
            $tmp .= "url: " . $urad->kontakt->www . " \n";

        $this->setBody($tmp);
    }

}
