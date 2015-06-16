<?php

class BonzAgent
{

    // Serverova cast zatim neni navrzena
    const BONZ_URL = 'http://www.mojespisovka.cz/pruzkum';

    public static function bonzuj()
    {
        $app_info = Nette\Environment::getVariable('app_info');
        if (!empty($app_info)) {
            $app_info = explode("#", $app_info);
        } else {
            $app_info = array('3.x', 'rev.X', 'OSS Spisová služba v3', '1270716764');
        }

        $user_config = Nette\Environment::getVariable('user_config');
        $klient_info = $user_config->urad;

        $unique_info = Nette\Environment::getVariable('unique_info');
        $unique_part = explode('#', $unique_info);


        $zprava = "id={$unique_part[0]}\n" . // je to install_id
                "zkratka={$klient_info->zkratka}\n" .
                "name={$klient_info->nazev}\n" .
                "ic={$klient_info->firma->ico}\n" .
                "tel={$klient_info->kontakt->telefon}\n" .
                "mail={$klient_info->kontakt->email}\n" .
                "version={$app_info[0]}\n" .
                "klient_ip={$_SERVER['REMOTE_ADDR']}\n" .
                "server_ip={$_SERVER['SERVER_ADDR']}\n" .
                "server_name={$_SERVER['SERVER_SOFTWARE']}\n";

        $url = self::BONZ_URL;
        $url .= "?msg=" . base64_encode($zprava);

        HttpClient::get($url);
    }

}
