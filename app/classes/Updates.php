<?php


class UpdateAgent {

    const CHECK_NEW_VERSION = 1;
    const CHECK_NOTICES = 2;
    
    // Zkontroluje, kdy byla aktualizace provedena naposledy
    // a po uplynuti urcite lhuty od posledni aktualizace ji provede znovu   
    public static function update($what)
    {
        $directory = CLIENT_DIR .'/temp';
        
        switch ($what) {
            case self::CHECK_NEW_VERSION:
                $url = "https://www.mojespisovka.cz/rss/verze";
                $filename = "$directory/download_aktualizace";
                break;
            case self::CHECK_NOTICES:
                $url = "https://www.mojespisovka.cz/rss/novinky";
                $filename = "$directory/download_novinky";
                break;
            default:
                return;        
        }
        
        $update_needed = true;
        
        if (file_exists($filename)) {
            $cachetime = filemtime($filename);
            if (date("Ymd") == date("Ymd",$cachetime))
                // dnes uz kontrola probehla
                $update_needed = false;
        }
                
        if (!$update_needed)
            return;

        // aktualizuj soubor jako znamku toho, ze stahovani zacalo
        // to by melo zarucit, ze nepobezi nekolik stahovani soucasne
        file_put_contents($filename, '');
        $data = HttpClient::get($url);
        if ($data === null)
            return;   // nepodarilo se stahnout data ze serveru, ignoruj chybu
            
        if ($what == self::CHECK_NOTICES)
            ; //Zpravy::zpracuj_zpravy_ze_serveru($data);
        else
            self::parse_version_information($data);

        // vlastni obsah souboru slouzi pouze pro diagnostiku, jestli aktualizace funguji
        file_put_contents($filename, $data);
    }

    private static function parse_version_information($data)
    {
        $xml = simplexml_load_string($data);
        if ( isset($xml->channel->item) )
            foreach ( $xml->channel->item as $item ) {
                $title = trim((string)$item->title);
                file_put_contents(CLIENT_DIR .'/temp/aktualni_verze', $title);
            }
    }
    
    public static function je_aplikace_aktualni()
    {
        $app_info = Environment::getVariable('app_info');
        if (!empty($app_info) ) {
            $a = explode("#", $app_info);
            $soucasna_verze = trim($a[0]);
        }
        else
            $soucasna_verze = '0.0.0';
                
        
        $dostupna_verze = @file_get_contents(CLIENT_DIR .'/temp/aktualni_verze');
        
        if ( !$dostupna_verze )
            // Nepodarilo se zjistit, zda je dostupna nova verze programu,
            // predstirej, ze uzivatel ma aktualni verzi
            return array("je_aktualni" => true);
                    
        $akt_parts = explode(".", $dostupna_verze);
        $dostupna_cmp = "";
        foreach ( $akt_parts as $part )
            $dostupna_cmp .= sprintf("%05d", (int)$part);

        $soucasna_cmp = "";
        foreach ( explode(".", $soucasna_verze) as $part )
            $soucasna_cmp .= sprintf("%05d", (int)$part);
        
        return array(
            'je_aktualni' => $dostupna_cmp <= $soucasna_cmp,
            'dostupna_verze' => $dostupna_verze,
            'soucasna_verze' => $soucasna_verze);            
    }
    
}
