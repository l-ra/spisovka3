<?php

class SurveyAgent
{

    const SURVEY_URL = 'http://www.mojespisovka.cz/pruzkum';

    public static function send()
    {
        $app_info = Nette\Environment::getVariable('app_info');
        if (!empty($app_info)) {
            $app_info = explode("#", $app_info);
            $version = $app_info[0];
        } else {
            $version = 'unknown';
        }

        $client_config = Nette\Environment::getVariable('client_config');
        $klient_info = $client_config->urad;

        // je to ID instalace, bohuzel neni moc jedinecne
        // pokud jsou dve spisovky instalovany behem hodiny, budou mit stejne toto ID
        $app_id = Nette\Environment::getVariable('app_id'); 

        $zprava = "id={$app_id}\n" . 
                "zkratka={$klient_info->zkratka}\n" .
                "name={$klient_info->nazev}\n" .
                "ic={$klient_info->firma->ico}\n" .
                "tel={$klient_info->kontakt->telefon}\n" .
                "mail={$klient_info->kontakt->email}\n" .
                "version={$version}\n" .
                "server_ip={$_SERVER['SERVER_ADDR']}\n" .
                "server_name={$_SERVER['SERVER_SOFTWARE']}\n";

        $url = self::SURVEY_URL;
        $url .= "?msg=" . base64_encode($zprava);

        $response = HttpClient::get($url);
        return $response !== null;
    }

}
