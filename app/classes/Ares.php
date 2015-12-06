<?php

class Ares
{

    const URL = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?';

    private $data;

    public function get($ic)
    {
        if (!$this->validateIC($ic))
            return "$ic není platné IČO.";

        $url = self::URL . "ico=" . $ic . "&jazyk=cz&xml=0";
        $data = $this->doRequest($url);
        if (empty($data))
            return "Došlo k neznámé chybě.";
        if (is_string($data))
            return $data;

        // P.L. - toto zabrani tomu, aby se do formulare subjektu zapsal retezec "undefined"
        $this->data = new stdClass();
        $this->data->dic = '';
        $this->data->ulice = '';
        $this->data->cislo_popisne = '';
        $this->data->cislo_orientacni = '';

        foreach ($data as $radek) {
            $radek = trim($radek);

            /* ICO */
            if (strpos($radek, "</D:ICO>") !== false) {
                $this->data->ico = strip_tags($radek);
            }
            /* DIC */
            if (strpos($radek, "</D:DIC>") !== false) {
                $this->data->dic = strip_tags($radek);
            }
            /* Nazev */
            if (strpos($radek, "</D:OF>") !== false) {
                $this->data->nazev = strip_tags($radek);
            }
            /* Ulice + cislo */
            if (strpos($radek, "</D:UC>") !== false) {
                $this->data->ulice_str = strip_tags($radek);
            }
            /* Ulice */
            if (strpos($radek, "</D:NU>") !== false) {
                $this->data->ulice = strip_tags($radek);
            }
            /* cislo ulice */
            if (strpos($radek, "</D:CA>") !== false) {
                $this->data->cislo_str = strip_tags($radek);
            }
            /* cislo popisne */
            if (strpos($radek, "</D:CD>") !== false) {
                $this->data->cislo_popisne = strip_tags($radek);
            }
            /* cislo orientacni */
            if (strpos($radek, "</D:CO>") !== false) {
                $this->data->cislo_orientacni = strip_tags($radek);
            }
            /* PSC + mesto */
            if (strpos($radek, "</D:PB>") !== false) {
                $this->data->mesto_str = strip_tags($radek);
            }
            /* mesto */
            if (strpos($radek, "</D:N>") !== false) {
                $this->data->mesto = strip_tags($radek);
            }
            /* psc */
            if (strpos($radek, "</D:PSC>") !== false) {
                $this->data->psc = strip_tags($radek);
            }
            /* stat */
            if (strpos($radek, "</D:NS>") !== false) {
                $this->data->stat = strip_tags($radek);
            }

            /* obchodni rejstrik - soud */
            if (strpos($radek, "</D:T>") !== false) {
                if (!isset($this->data->registrace_soud)) {
                    $this->data->registrace_soud = strip_tags($radek);
                }
            }
            /* obchodni rejstrik - spis */
            if (strpos($radek, "</D:OV>") !== false) {
                $this->data->registrace_spis = strip_tags($radek);
            }
            /* obchodni rejstrik - datum */
            if (strpos($radek, "</D:DV>") !== false) {
                $this->data->registrace_datum = strip_tags($radek);
            }
            /* obchodni rejstrik - typ */
            if (strpos($radek, "</D:NPF>") !== false) {
                $this->data->registrace_typ = strip_tags($radek);
            }
            /* zivnostensky urad - misto */
            if (strpos($radek, "</D:NZU>") !== false) {
                $this->data->zivnostensky_urad = strip_tags($radek);
            }
            /* zivnostensky urad - kod */
            if (strpos($radek, "</D:NFU>") !== false) {
                $this->data->financni_urad = strip_tags($radek);
            }
        }
        
        return $this->data;
    }

    private function doRequest($url)
    {
        if (@ini_get("allow_url_fopen")) {
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $result = @file($url, false, $context);
            if ($result === false) {
                $error = error_get_last();
                $msg = $error['message'];
                if (stripos($msg, 'timed out') !== false)
                    $msg = 'Registr ARES neodpověděl v časovém limitu.';
                return $msg;
            }            
            return $result;
            
        } else if (function_exists('curl_init')) {
            if ($ch = curl_init($url)) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $response = curl_exec($ch);
                curl_close($ch);
                return explode("\n", $response);
            } else {
                return 'Funkce curl_init() neproběhla úspěšně.';
            }
        } else {
            return 'Chybí PHP rozšíření curl.';
        }
    }

    /**
     * 
     * @param string $ic
     * @return boolean
     */
    public function validateIC($ic)
    {
        if (!$ic || !is_numeric($ic))
            return false;
        
        $ic = trim($ic);
        $pocet = strlen($ic);
        // doplnime nuly pokud je cifra < 8
        if ($pocet < 8) {
            $nuly = "00000000";
            $ic = substr($nuly, 0, 8 - $pocet) . $ic;
        }

        // kontrolní součet
        $a = 0;
        $c = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }
        $a = $a % 11;

        if ($a === 0)
            $c = 1;
        elseif ($a === 10)
            $c = 1;
        elseif ($a === 1)
            $c = 0;
        else
            $c = 11 - $a;

        return ((int) $ic[7]) === $c;
    }

}

?>
