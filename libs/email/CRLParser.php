<?php

class CRLParser extends DERParser {

    private $cached = 0;
    private $cache_dir = "";
    private $cache_time = 24; // hour


    public function cache($enable = 1, $cache_dir = "", $cache_time = 24)
    {
        $this->cached = $enable;
        $this->cache_dir = $cache_dir;
        $this->cache_time = $cache_time;
    }

    public function fromData($data)
    {

        $der = $this->parse($data);
        return $this->decode($der);

    }

    public function fromFile($filename)
    {
        if ( file_exists($filename) ) {
            $data = file_get_contents($filename);
            $der = $this->parse($data);
            return $this->decode($der);
        } else {
            return null;
        }
    }

    public function fromX509($cert)
    {

        if ($res = openssl_x509_read($cert)) {
            $cert_info = openssl_x509_parse($res);
            $uri_crl = explode("\n", str_replace("URI:","",$cert_info['extensions']['crlDistributionPoints']));
            $data = $this->sourceCRL($uri_crl[0]);
            $der = $this->parse($data);
            return $this->decode($der);

        } else {
            new Exception(openssl_error_string());
            return null;
        }

    }

    public function fromCA()
    {

        $uri = "asn/qica09_crl1256.crl";

        $data = $this->sourceCRL($uri);
        $der = $this->parse($data);
        return $this->decode($der);

    }

    public function fromUrl($url)
    {

        if ( $this->cached ) {
            $file = $this->cache_dir ."crl_". md5($url) .".tmp";
            if (file_exists($file) ) {
                $mtime = filemtime($file) + (3600 * $this->cache_time);
                if ( time() < $mtime ) {
                    $in = file_get_contents($file);
                    return unserialize($in);
                }
            }
        }

        $data = $this->sourceCRL($url);
        $der = $this->parse($data);
        $out = $this->decode($der);
        if ( $this->cached ) {
            $hash = md5($url);
            $file = $this->cache_dir ."crl_". $hash .".tmp";
            file_put_contents($file, serialize($out));
        }
        return $out;
        
    }

    private function decode( $der )
    {

        $info = new stdClass();
        // $der[0]['data'][0]['data'][XXX]['data']
        // 2 = CA
        // 3 = od
        // 4 = do
        // 5 = items

        /*
         * Certifikacni autorita
         */
        $tmp = $der[0]['data'][0]['data'][2]['data'];
        $info->CA = array();
        foreach( $tmp as $item ) {
            
            $identifier = $item['data'][0]['data'][0]['data'];
            $value = $item['data'][0]['data'][1]['data'];
            $info->CA[$identifier] = $value;
            
        }

        /*
         * Datum zacatku platnosti
         */
        $info->datum_zacatku_platnosti = $der[0]['data'][0]['data'][3]['data'];

        /*
         * Datum pristi aktualizace
         */
        $info->datum_pristi_aktualizace = $der[0]['data'][0]['data'][4]['data'];

        /*
         * Seznam
         */
        $tmp = $der[0]['data'][0]['data'][5]['data'];
        $crl_items = array();
        foreach ( $tmp as $item ) {

            $crl = new stdClass();
            foreach ( $item['data'] as $i ) {

                if ( $i['tag'] == '02' ) {
                    // Seriove cislo certifikatu
                    $crl->id = $i['data'];
                    $crl->hex = sprintf("%06X", $i['data']);
                }
                if ( $i['tag'] == '17' ) {
                    // Datum zneplatneni
                    $crl->datum = $i['data'];
                }

                if ( $i['tag'] == '30' ) {
                    //$crl->add_info = array();
                    foreach ( $i['data'] as $crl_addinfo ) {
                        $key = $crl_addinfo['data'][0]['data'];
                        $value = $crl_addinfo['data'][1]['data'][0]['data'];

                        if ( $key == "55 1D 15 " ) {
                            $crl->duvod = $value;
                        }
                        //$crl->add_info[$key] = $value;
                    }
                }
            }
            if ( !isset($crl->duvod) ) {
                $crl->duvod = '00';
            }

            $crl_items[ $crl->id ] = $crl;
            unset($crl);

        }
        $info->seznam = $crl_items;


        return $info;
    }

    private function sourceCRL($zdroj) {

        if (@ini_get("allow_url_fopen")) {
            return file_get_contents($zdroj);
        } else if ( function_exists('curl_init') ) {
            if ( $ch = curl_init($zdroj) ) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }


}

