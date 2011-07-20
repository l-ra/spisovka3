<?php //netteloader=esignature

/**
 * eSignature - trida pro praci s elektronickym podpisem
 *
 * @author Tomas Vancura
 */
class esignature {
    
    
    protected $user_cert;
    protected $user_cert_data;
    protected $user_cert_path;
    protected $user_prikey;
    protected $user_prikey_data;
    protected $user_prikey_path;
    protected $user_passphrase;

    protected $ca_cert = array();
    protected $ca_cert_real = array();
    protected $ca_info = array();

    public $error_string;

    /**
     * Nastavi uzivatelsky certifikat
     *
     * Vstupnim parametrem $certificate muze byt
     *  - cesta k certifikatu X.509
     *    - pak je vyzadovan privatni klic
     *    - a pripadne heslo privatniho klice
     *  - cesta k certifikatu PFX (pkcs12)
     *    - $private_key ma hodnotu NULL
     *    - $passphrase pak predstavuje chranene heslo PFX

     * @param string $certificate cesta k uzivatelskemu certifikatu
     * @param string $private_key cesta k privatnimu klici
     * @param string $passphrase heslo k privatnimu klici nebo PFX
     * @return bool
     */
    public function setUserCert($certificate,$private_key=null,$passphrase=null) {

        if(file_exists(realpath($certificate))) {

            $this->user_cert_path = realpath($certificate);
            $this->user_cert_data = file_get_contents($this->user_cert_path);

            $pkcs12_enable = function_exists('openssl_pkcs12_read')?TRUE:FALSE;

            if ($pkcs12_enable && @openssl_pkcs12_read($this->user_cert_data,$tmp_cert,$passphrase) ) {
                /* PFX */
                $this->user_cert_data = $tmp_cert['cert'];
                $tmp_ucert = $this->tempnam("", "user_cert");
                    $fp = fopen($tmp_ucert,"w");
                        fwrite($fp,$tmp_cert['cert']);
                    fclose($fp);
                $this->user_cert_path = realpath($tmp_ucert);


                if ($res = openssl_x509_read($tmp_cert['cert'])) {
                    $this->user_cert = openssl_x509_parse($res);
                    $this->user_prikey_data = $tmp_cert['pkey'];
                    $tmp_ukey = $this->tempnam("", "user_pkey");
                        $fp = fopen($tmp_ukey,"w");
                            fwrite($fp,$tmp_cert['pkey']);
                        fclose($fp);
                    $this->user_prikey_path = realpath($tmp_ukey);
                    $this->user_passphrase = $passphrase;
                    return true;
                } else {
                    $this->error_string = openssl_error_string();
                    return false;
                }
            } else {
                /* X.509 */

                if(strpos($this->user_cert_data,"BEGIN")===false) {
                    /* convert to PEM format */
                    $this->user_cert_data = $this->der2pem($this->user_cert_data);
                }

                if ($res = @openssl_x509_read($this->user_cert_data)) {
                    $this->user_cert = openssl_x509_parse($res);
                    if(!is_null($private_key)) {
                        $this->user_prikey_path = realpath($private_key);
                        $this->user_prikey_data = @file_get_contents($this->user_prikey_path);
                        if($this->user_prikey = @openssl_pkey_get_private($this->user_prikey_data, $passphrase)) {
                            $this->user_passphrase = $passphrase;
                            return true;
                        } else {
                            $this->error_string = openssl_error_string();
                            return false;
                        }
                    }
                } else {
                    $this->error_string = openssl_error_string();
                    return false;
                }
            }
            
        } else {
            $this->error_string = "Certificate not found";
            return false;
        }
    }

    /**
     * Pridat korenovy certifikat
     *
     * Vstupnim parametrem muze byt
     *  - cesta k souboru CA certifikatu ve formatu PEM
     *  - adresar obsahujici soubory CA certifikatu
     *  - pole obsahujici seznam cest CA certifikatu
     *
     * @param string $mixed
     * @return mixed
     */
    public function setCACert($mixed) {

        if( is_array($mixed) ) {
            /* Param is array - items CA certificates */
            foreach ($mixed as $param) {
                $this->setCACert($param);
            }
            return true;
        } else if ( is_dir($mixed) ) {
            /* Param is dir - CA certifikates is in dir */
            if ($dh = opendir($mixed)) {
                while (($file = readdir($dh)) !== false) {
                    if($file=="." || $file=="..") continue;
                    $this->setCACert($mixed."/".$file);
                }
                closedir($dh);
            }
            return true;
        } else if ( is_file($mixed) ) {
            /* Param is file - CA certifikate file */
            $cacert_path = realpath($mixed);
            $cacert_data = file_get_contents($cacert_path);
            if ($res = @openssl_x509_read($cacert_data)) {
                $data = openssl_x509_parse($res);
                $this->ca_cert[] = $mixed;
                $this->ca_cert_real[] = $cacert_path;
                $this->ca_info[ $data['issuer']['CN'] ] = $data['issuer']['O'];                
                return $res;
            } else {
                $cacert_data = $this->der2pem($cacert_data);
                if ($res = @openssl_x509_read($cacert_data)) {
                    $data = openssl_x509_parse($res);
                    $this->ca_cert[] = $mixed;
                    $this->ca_cert_real[] = $cacert_path;
                    $this->ca_info[ $data['issuer']['CN'] ] = $data['issuer']['O'];
                    return $res;
                } else {
                    $this->error_string = openssl_error_string();
                    return null;
                }
            }
        } else {
            /* Unknow param type */
            $this->error_string = "Unknow param type";
            return null;
        }



    }

    /**
     * Vraci informace dostupnych korenovych certifikatech
     * 
     * @return array[array]
     */
    public function getCA() {

        $out_A = array();
        foreach ($this->ca_cert_real as $index => $cert) {
            $cert_data = file_get_contents($cert);
            $out = array();
            $out['cert_path'] = $this->ca_cert[$index];
            $out['cert_path_real'] = $cert;
            if ($res = openssl_x509_read($cert_data)) {
                $data = openssl_x509_parse($res);
                $out['name'] = $data['issuer']['CN'];
                $out['signature'] = $data['issuer']['CN'];
                $out['email'] = @$data['issuer']['emailAddress'];
                $out['platnost_od'] = date("j.n.Y",$data['validFrom_time_t']);
                $out['platnost_do'] = date("j.n.Y",$data['validTo_time_t']);
                $info = array();
                if(isset($data['issuer']['O'])) $info[] = $data['issuer']['O'];
                if(isset($data['issuer']['L'])) $info[] = $data['issuer']['L'];
                if(isset($data['issuer']['ST'])) $info[] = $data['issuer']['ST'];
                if(isset($data['issuer']['C'])) $info[] = $data['issuer']['C'];
                $out['info'] = implode(", ", $info);
            } else {

            }
            $out_A[] = $out;
            unset($out);
        }
        return $out_A;

    }

    public function getCASimple()
    {
        if ( count($this->ca_info)>0 ) {
            return $this->ca_info;
        } else {
            return null;
        }
    }

    public function verifySignature_source($message,&$cert=null,&$status="") {
        $tmp_mess = $this->tempnam("", "mess");
        $fp = fopen($tmp_mess,"w");
            fwrite($fp,$message);
        fclose($fp);

        return $this->verifySignature($tmp_mess, $cert, $status);
    }

/**
 *
 * @param string $filename
 * @return bool
 */
    public function verifySignature($filename,&$cert=null,&$status="") {
        /* Nacteni CA */
        $caa = $this->getCA();
        $lCertT = array();

        foreach ($caa as $ca) {
            $lCertT[] = $ca['cert_path_real'];
        }

        $Cert = new Cert();

        $tmp_cert = $this->tempnam("", "crt");
        $res = openssl_pkcs7_verify($filename, 0, $tmp_cert, $lCertT);

        if ( $res==1 ) {
            // email overen
            $cert = openssl_x509_parse("file://$tmp_cert");

            // test identity emailu
            if ( $this->verifyEmailAddress($cert, $filename, $email1, $email2) == 0 ) {
                $status = "Email je ověřen a platný, ale má rozdilné emailové adresy! ($email1 <> $email2)";
            } else {
                $status = "Email je ověřen a platný";
            }

            @unlink($tmp_cert);
            return array(
                'return'=>$res,
                'status'=>$status,
                'cert'=>$cert,
                'cert_info'=>$this->getInfo($cert)
            );
        } else {
            // email neprosel overenim
            $status = openssl_error_string();

            if($res == -1) {
                // Chyba
                if( strpos($status,"invalid mime type")!==false ) {
                    $status = "Email není podepsán!";
                } else {
                    $status = "Email nelze ověřit! Email je buď poškozený nebo není kompletní nebo nelze ověřit podpis.";
                    //$status = "Email nelze ověřit! Chyba aplikace! ". openssl_error_string();
                }
                return array(
                    'return'=>$res,
                    'status'=>$status,
                );
            } else {
                // Certifikat neprosel kontrolou
                $Cert = new Cert();
                $email_data = file_get_contents($filename);
                $cert_info = $Cert->fromEmail($email_data);

                if( strpos($status,"digest failure")!==false ) {
                    $status = "Email je podepsán, ale je poškozený!";
                    $res = 0;
                } else if( strpos($status,"certificate verify error")!==false ) {

                    if ( $cert_info->error > 0 ) {
                        $status = "Podpis je neplatný! ". $cert_info->error_message;
                        $res = 3;
                    } else {
                        $status = "Email je podepsán, ale není ověřen kvalifikovanou CA!";
                        $res = 2;
                    }


                } else {
                    $status = "Email je neplatný!";
                    $res = 0;
                }

                @unlink($tmp_cert);
                return array(
                    'return'=>$res,
                    'status'=>$status,
                    'cert'=>$cert_info,
                    'cert_info'=>$this->getInfoCert($cert_info)
                );

            }
        }

    }

    private function verifyEmailAddress($cert,$filename,&$email1=null,&$email2=null) {

        if(isset( $cert['extensions']['subjectAltName'])) {
            if( preg_match("/email:(.*?),/", $cert['extensions']['subjectAltName'],$mathes) ) {
                $email1 = trim($mathes[1]);
                $email_source = file_get_contents($filename);
                if( preg_match("/From:(.*)/", $email_source,$mathes2) ) {
                    $email2 = $mathes2[1];
                    if( strpos($mathes2[1],$email1)!==false ) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return -1;
                }
            } else {
                return -1;
            }
        } else {
            return -1;
        }
        
    }

    public function getInfo($cert=null) {

        if(is_null($cert)) {
            if(!is_null($this->user_cert)) {
                $cert = $this->user_cert;
            } else {
                return null;
            }
            
        }

        $info = array();

        $info['serial_number'] = sprintf("%X",$cert['serialNumber']);
        $info['id'] = @$cert['subject']['serialNumber'];
        $info['jmeno'] = @$cert['subject']['CN'];

        if(isset($cert['subject']['O'])) {
            $info['organizace'] = @$cert['subject']['O'];
            $info['jednotka'] = @$cert['subject']['OU'];
        } else {
            $info['organizace'] = "";
            $info['jednotka'] = "";
        }

        $info['adresa'] = @@$cert['subject']['L'];
        $info['email'] = null;
        if ( !empty($cert['subject']['emailAddress']) ) {
            $info['email'] = $cert['subject']['emailAddress'];
        } else if(isset( $cert['extensions']['subjectAltName'])) {
            if( preg_match("/email:(.*?),/", $cert['extensions']['subjectAltName'],$mathes) ) {
                $info['email'] = trim($mathes[1]);
            }
        }
        $info['platnost_od'] = @$cert['validFrom_time_t'];
        $info['platnost_do'] = @$cert['validTo_time_t'];
        $info['CA'] = @$cert['issuer']['CN'];
        $info['CA_org'] = @$cert['issuer']['O'];
        $info['CA_is_qualified'] = $this->is_qualified(@$cert['issuer']['CN']);

        if ( !empty($cert['extensions']['crlDistributionPoints']) ) {
            $crl_d = str_replace("URI:","",$cert['extensions']['crlDistributionPoints']);
            $info['CRL'] = explode("\n",$crl_d);
        } else {
            $info['CRL'] = null;
        }


        return $info;
    }

    public function getInfoCert($cert=null) {

        if(is_null($cert) || !is_object($cert)) {
            return null;
        }

        $info = array();

        $info['serial_number'] = sprintf("%X",@$cert->id);
        $info['id'] = @$cert->id_name;
        $info['jmeno'] = @$cert->name;

        if(!empty($cert->org)) {
            $info['organizace'] = $cert->org;
            $info['jednotka'] = @$cert->subjekt->organizationUnitName;
        } else {
            $info['organizace'] = "";
            $info['jednotka'] = "";
        }

        $info['adresa'] = @$cert->locality;
        $info['email'] = @$cert->email;
        $info['platnost_od'] = @$cert->subjekt->platnost_od_unix;
        $info['platnost_do'] = @$cert->subjekt->platnost_do_unix;
        $info['CA'] = @$cert->CA_name;
        $info['CA_org'] = @$cert->CA_org;
        $info['CA_is_qualified'] = $this->is_qualified(@$cert->CA_name);
        $info['CRL'] = @$cert->CRL;
        $info['error'] = @$cert->error_message;

        return $info;
    }

    public function is_qualified($ca_name)
    {
        if ( isset($this->ca_info[$ca_name]) ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Vypise informaci o certifikatu
     *
     * @param string $certificate path to certificate
     * @return array
     */
    public function certificateInfo($certificate=null) {

        if(!is_null($certificate)) {
            if(file_exists(realpath($certificate))) {
                $cert_data = file_get_contents(realpath($certificate));
                if ($res = openssl_x509_read($cert_data)) {
                    return openssl_x509_parse($res);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            if ($res = openssl_x509_read($this->user_cert_data)) {
                return openssl_x509_parse($res);
            } else {
                return null;
            }
            
        }

    }

    public function getUserCertificate($out = 'F') {
        switch ($out) {
           case 'F': 
               return empty($this->user_cert_path)?null:'file://'.$this->user_cert_path; break;
           case 'D': 
               return empty($this->user_cert_data)?null:$this->user_cert_data; break;
           case 'I': 
               return empty($this->user_cert)?null:$this->user_cert; break;
           default: 
               return empty($this->user_cert_path)?null:'file://'.$this->user_cert_path; break;
        }
    }

    public function getUserPrivateKey($out = 'F') {
        switch ($out) {
           case 'F': 
               return empty($this->user_prikey_path)?null:'file://'.$this->user_prikey_path; break;
           case 'D': 
               return empty($this->user_prikey_data)?null:$this->user_prikey_data; break;
           case 'I': 
               return empty($this->user_prikey)?null:$this->user_prikey; break;
           default: 
               return empty($this->user_prikey_path)?null:'file://'.$this->user_prikey_path; break;
        }
    }

    public function getUserPassphrase() {
        return empty($this->user_passphrase)?null:$this->user_passphrase;
    }

    /**
     * Podepise obsah zpravy
     *
     * @param string $message obsah zpravy
     * @param string $header pole obsahujici hlavicku
     * @return array  podepsana zprava
     */
    public function signMessage($message,$header=array()) {

        $tmp_mess = CLIENT_DIR .'/temp/send_message_plain.txt';
            $fp = fopen($tmp_mess,"w");
            fwrite($fp,$message);
            fclose($fp);
        $tmp_signed = CLIENT_DIR .'/temp/send_message_signed.txt';
            $fp = fopen($tmp_signed,"w");
            fwrite($fp,"");
            fclose($fp);

        if (openssl_pkcs7_sign(realpath($tmp_mess),realpath($tmp_signed),'file://'.$this->user_cert_path,
                    array('file://'.$this->user_prikey_path, $this->user_passphrase),
                    $header,
                    PKCS7_DETACHED))
        {
            $signedo = file_get_contents(realpath($tmp_signed));
            @unlink($tmp_mess);
            @unlink($tmp_signed);
            return $signedo;
        } else {
            throw new InvalidStateException('Email se nepodaril podepsat. SSL: '. openssl_error_string());
            return null;
        }
    }

    /**
     * Prevede format PEM na format DER
     *
     * @param string $pem_data
     * @return string
     */
    private function pem2der($pem_data) {
        $begin = "CERTIFICATE-----";
        $end   = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

    /**
     * Prevede format DER na format PEM
     *
     * @param string $der_data
     * @return string
     */
    private function der2pem($der_data) {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
        return $pem;
    }

    function  __destruct() {

        @unlink($this->user_prikey_path);
        @unlink($this->user_cert_path);

    }

    private function tempnam($dir, $prefix) {

        if (empty($dir)) {
            $file = CLIENT_DIR .'/temp/esign_'.$prefix.'.tmp';
        } else {
            $file = CLIENT_DIR .'/temp/'.$dir.'/esign_'.$prefix.'.tmp';
        }



        if ( $fp = fopen($file,'wb') ) {
            fclose($fp);
            return $file;
        } else {
            return null;
        }


    }

}
?>
