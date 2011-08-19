<?php

class Ares {

    //const URL = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?';
    const URL = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?';

    private $ic;
    private $data;
    private $zdroj;
    private $source;

    public function  __construct($ic = null) {

        $this->data = new stdClass();
        $this->ic = $ic;
        $this->zdroj = "";

        if ( !is_null($ic) ) {
            $data = $this->get();
            return (is_null($data))?$this:$data;
        } else {
            return $this;
        }
    }

    public function get() {

        if ( $this->zpracuj() ) {
            return $this->data;
        } else {
            return null;
        }
    }
	
    public function nastavIC($ic) {
        $this->ic = $ic;
        return true;
    }
	
    public function zpracuj() {
  
        if ( $this->jetoIC($this->ic) ) {
     
            $pozadavek = self::URL ."ico=". $this->ic ."&jazyk=cz&xml=0";
       
            $this->zdroj .= $pozadavek ."\n\n";
            $this->source = $data = $this->getSource($pozadavek);
            
            if (!is_null($data) ) {

                foreach ($data as $ir => $radek) {
                    $radek = trim($radek);

                    /* ICO */
                    if ( strpos($radek,"</D:ICO>") !== false ) {
                        $this->data->ico = strip_tags($radek);
                    }
                    /* DIC */
                    if ( strpos($radek,"</D:DIC>") !== false ) {
                        $this->data->dic = strip_tags($radek);
                    }
                    /* Nazev */
                    if ( strpos($radek,"</D:OF>") !== false ) {
                        $this->data->nazev = strip_tags($radek);
                    }
                    /* Ulice + cislo */
                    if ( strpos($radek,"</D:UC>") !== false ) {
                        $this->data->ulice_str = strip_tags($radek);
                    }
                    /* Ulice */
                    if ( strpos($radek,"</D:NU>") !== false ) {
                        $this->data->ulice = strip_tags($radek);
                    }
                    /* cislo ulice */
                    if ( strpos($radek,"</D:CA>") !== false ) {
                        $this->data->cislo_str = strip_tags($radek);
                    }
                    /* cislo popisne */
                    if ( strpos($radek,"</D:CD>") !== false ) {
                        $this->data->cislo_popisne = strip_tags($radek);
                    }
                    /* cislo orientacni */
                    if ( strpos($radek,"</D:CO>") !== false ) {
                        $this->data->cislo_orientacni = strip_tags($radek);
                    }
                    /* PSC + mesto */
                    if ( strpos($radek,"</D:PB>") !== false ) {
                        $this->data->mesto_str = strip_tags($radek);
                    }
                    /* mesto */
                    if ( strpos($radek,"</D:N>") !== false ) {
                        $this->data->mesto = strip_tags($radek);
                    }
                    /* psc */
                    if ( strpos($radek,"</D:PSC>") !== false ) {
                        $this->data->psc = strip_tags($radek);
                    }
                    /* stat */
                    if ( strpos($radek,"</D:NS>") !== false ) {
                        $this->data->stat = strip_tags($radek);
                    }

                    /* obchodni rejstrik - soud */
                    if ( strpos($radek,"</D:T>") !== false ) {
                        if ( !isset($this->data->registrace_soud) ) {
                            $this->data->registrace_soud = strip_tags($radek);
                        }
                    }
                    /* obchodni rejstrik - spis */
                    if ( strpos($radek,"</D:OV>") !== false ) {
                        $this->data->registrace_spis = strip_tags($radek);
                    }
                    /* obchodni rejstrik - datum */
                    if ( strpos($radek,"</D:DV>") !== false ) {
                        $this->data->registrace_datum = strip_tags($radek);
                    }
                    /* obchodni rejstrik - typ */
                    if ( strpos($radek,"</D:NPF>") !== false ) {
                        $this->data->registrace_typ = strip_tags($radek);
                    }
                    /* zivnostensky urad - misto */
                    if ( strpos($radek,"</D:NZU>") !== false ) {
                        $this->data->zivnostensky_urad = strip_tags($radek);
                    }
                    /* zivnostensky urad - kod */
                    if ( strpos($radek,"</D:NFU>") !== false ) {
                        $this->data->financni_urad = strip_tags($radek);
                    }
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function xml() {

        if ( $this->jetoIC($this->ic) ) {

            $pozadavek = self::URL ."ico=". $this->ic ."&jazyk=cz&xml=0";

            $this->zdroj .= $pozadavek ."\n\n";
            $data = $this->getSource($pozadavek);
            if (!is_null($data) ) {

                $data = implode("\n",$data);
                $xml = simplexml_load_string($data);

                return $xml;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function getSource($zdroj) {

        if (@ini_get("allow_url_fopen")) {
            return file($zdroj);
        } else if ( function_exists('curl_init') ) {
            if ( $ch = curl_init($zdroj) ) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $response = curl_exec($ch);
                curl_close($ch);
                return explode("\n",$response);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
   
    public function jetoIC($ic) {
  
        // odstranime vsemozne mezery
        $ic = preg_replace('#\s+#', '', $ic);
        // je to cislo?
        if ( !is_numeric($ic) ) return false;
        // toto nechapu, ale vsude se uvadi
        if ( strlen($ic) == 0 ) return true;
        
        $pocet = strlen($ic);
        // doplnime nuly pokud je cifra < 8
        if ( $pocet < 8 ) {
            $nuly = "00000000";
            $ic = substr($nuly,0,8-$pocet) . $ic;
        }

        // kontrolní součet
        $a = 0; $c = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }
        $a = $a % 11;
        
        if     ($a === 0)  $c = 1;
        elseif ($a === 10) $c = 1;
        elseif ($a === 1)  $c = 0;
        else               $c = 11 - $a;

        return (int) $ic[7] === $c;

    }    
	
}


?>
