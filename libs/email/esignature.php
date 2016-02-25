<?php

//netteloader=esignature

/**
 * eSignature - trida pro praci s elektronickym podpisem
 *
 * @author Tomas Vancura, Pavel Lastovicka
 */
class esignature
{

    /**
     * Certifikat pro podepisovani
     * @var string 
     */
    protected $certificate;
    private $private_key;
    private $passphrase;
    
    protected $ca_cert = array();
    protected $ca_cert_real = array();
    protected $ca_info = array();
    
    protected $error_string;

    public function getError()
    {
        return $this->error_string;
    }

    /**
     * Nastavi certifikat.
     *
     * @param string $certificate_path cesta k souboru PKCS #12
     * @param string $passphrase       heslo k soukromemu klici
     * @return bool
     */
    public function setUserCert($certificate_path, $passphrase)
    {
        $certificate_path = realpath($certificate_path);
        if (!file_exists($certificate_path)) {
            $this->error_string = "Soubor s certifikátem nenalezen";
            return false;
        }

        $certificate = file_get_contents($certificate_path);
        $cert_info = [];
        if (openssl_pkcs12_read($certificate, $cert_info, $passphrase)) {
            // Uživatel dodal certifikát ve formátu PKCS 12
            // Následující kontrola je asi zbytečná, ale ponechme ji v programu
            if ($res = openssl_x509_read($cert_info['cert'])) {
                $this->certificate = $cert_info['cert'];
                $this->private_key = $cert_info['pkey'];
                $this->passphrase = $passphrase;
                return true;
            } else {
                $this->error_string = openssl_error_string();
                return false;
            }
        } else {
            $this->error_string = openssl_error_string();
            return false;

            /* Separátní soukromý klíč už nepodporujeme, protože by to žádný
             * uživatel nepoužil, je to zbytečné.

              if (strpos($certificate, "BEGIN") === false) {
              // convert to PEM format
              $certificate = $this->der2pem($certificate);
              }

              if ($res = openssl_x509_read($certificate)) {
              if (!is_null($private_key_path)) {
              $this->user_prikey_path = realpath($private_key_path);
              $this->user_prikey_data = file_get_contents($this->user_prikey_path);
              if ($this->user_prikey = openssl_pkey_get_private($this->user_prikey_data,
              $passphrase)) {
              $this->passphrase = $passphrase;
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
             * 
             */
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
    public function setCACert($mixed)
    {
        if (!function_exists('openssl_x509_read'))
            throw new Exception('Není dostupné PHP rozšíření OpenSSL.');

        if (is_array($mixed)) {
            /* Param is array - items CA certificates */
            foreach ($mixed as $param) {
                $this->setCACert($param);
            }
            return true;
        } else if (is_dir($mixed)) {
            /* Param is dir - CA certifikates is in dir */
            if ($dh = opendir($mixed)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file == "." || $file == "..")
                        continue;
                    $this->setCACert($mixed . "/" . $file);
                }
                closedir($dh);
            }
            return true;
        } else if (is_file($mixed)) {
            /* Param is file - CA certifikate file */
            $cacert_path = realpath($mixed);
            $cacert_data = file_get_contents($cacert_path);
            if ($res = @openssl_x509_read($cacert_data)) {
                $data = openssl_x509_parse($res);
                $this->ca_cert[] = $mixed;
                $this->ca_cert_real[] = $cacert_path;
                $this->ca_info[$data['issuer']['CN']] = $data['issuer']['O'];
                return $res;
            } else {
                $cacert_data = $this->der2pem($cacert_data);
                if ($res = @openssl_x509_read($cacert_data)) {
                    $data = openssl_x509_parse($res);
                    $this->ca_cert[] = $mixed;
                    $this->ca_cert_real[] = $cacert_path;
                    $this->ca_info[$data['issuer']['CN']] = $data['issuer']['O'];
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
    public function getCA()
    {

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
                $out['platnost_od'] = date("j.n.Y", $data['validFrom_time_t']);
                $out['platnost_do'] = date("j.n.Y", $data['validTo_time_t']);
                $info = array();
                if (isset($data['issuer']['O']))
                    $info[] = $data['issuer']['O'];
                if (isset($data['issuer']['L']))
                    $info[] = $data['issuer']['L'];
                if (isset($data['issuer']['ST']))
                    $info[] = $data['issuer']['ST'];
                if (isset($data['issuer']['C']))
                    $info[] = $data['issuer']['C'];
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
        if (count($this->ca_info) > 0) {
            return $this->ca_info;
        } else {
            return null;
        }
    }

    /**
     *
     * @param string $filename
     * @return bool
     */
    public function verifySignature($filename, &$cert = null, &$status = "")
    {
        /* Nacteni CA */
        $caa = $this->getCA();
        $lCertT = array();

        foreach ($caa as $ca) {
            $lCertT[] = $ca['cert_path_real'];
        }

        $Cert = new Cert();

        $tmp_cert = tempnam(TEMP_DIR, "sender_certificate");
        $res = openssl_pkcs7_verify($filename, 0, $tmp_cert, $lCertT);

        if ($res === true) {
            // email overen
            $cert = openssl_x509_parse("file://$tmp_cert");
            unlink($tmp_cert);
            $email_cert = $email_real = null;
            $res2 = $this->verifyEmailAddress($cert, $filename, $email_cert, $email_real);
            $status = "Podpis je ověřen a platný";
            if ($res2 === false) {
                $status .= ", ale emailová adresa odesilatele neodpovídá certifikátu! ($email_cert <> $email_real)";
            } else if ($res2 === -1) {
                $status .= ", ale z certifikátu se nepodařilo zjistit emailovou adresu.";
            }

            return array(
                'return' => 1,
                'status' => $status,
                'cert' => $cert,
                'cert_info' => $this->parseCertificate($cert)
            );
        } else {
            // email neprosel overenim
            unlink($tmp_cert);
            $status = openssl_error_string();
            if ($res == -1) {
                // Chyba
                if (strpos($status, "invalid mime type") !== false) {
                    $status = "Email není podepsán.";
                } else if (strpos($status, "no content type") !== false) {
                    $status = "Email není podepsán.";
                } else {
                    $status = "Email nelze ověřit! Email je buď poškozený nebo není kompletní nebo nelze ověřit podpis.";
                }
                return array(
                    'return' => -1,
                    'status' => $status,
                );
            } else {
                // Certifikat neprosel kontrolou
                $Cert = new Cert();
                $email_data = file_get_contents($filename);
                $cert_info = $Cert->fromEmail($email_data);

                if (strpos($status, "digest failure") !== false) {
                    $status = "Email je podepsán, ale je poškozený!";
                    $res = 0;
                } else if (strpos($status, "certificate verify error") !== false) {

                    if (@$cert_info->error > 0) {
                        $status = "Podpis je neplatný! " . $cert_info->error_message;
                        $res = 3;
                    } else {
                        $status = "Email je podepsán, ale není ověřen kvalifikovanou CA!";
                        $res = 2;
                    }
                } else {
                    $status = "Email je neplatný!";
                    $res = 0;
                }

                return array(
                    'return' => $res,
                    'status' => $status,
                    'cert' => $cert_info,
                    'cert_info' => $this->getInfoCert($cert_info)
                );
            }
        }
    }

    // $email_cert - adresa v certifikatu
    // $email_real - skutecna adresa odesilatele
    protected function verifyEmailAddress($cert, $filename, &$email_cert = null, &$email_real = null)
    {
        $matches = [];
        if (!isset($cert['extensions']['subjectAltName']) || !preg_match("/email:(.*?),/",
                        $cert['extensions']['subjectAltName'], $matches))
            return -1; // K tomuto by nemělo nikdy dojít

        $email_cert = trim($matches[1]);

        $email_source = file_get_contents($filename);
        $headers = imap_rfc822_parse_headers($email_source);
        $sender = current($headers->from);
        $email_real = "{$sender->mailbox}@{$sender->host}";

        return $email_cert == $email_real;
    }

    public function parseCertificate($cert = null)
    {
        if (is_null($cert)) {
            if (!$this->certificate)
                return null;
            $cert = openssl_x509_parse($this->certificate);
        }

        $info = array();

        $info['serial_number'] = sprintf("%X", $cert['serialNumber']);
        $info['id'] = @$cert['subject']['serialNumber'];
        $info['jmeno'] = @$cert['subject']['CN'];

        if (isset($cert['subject']['O'])) {
            $info['organizace'] = @$cert['subject']['O'];
            $info['jednotka'] = @$cert['subject']['OU'];
        } else {
            $info['organizace'] = "";
            $info['jednotka'] = "";
        }

        $info['adresa'] = @@$cert['subject']['L'];
        $info['email'] = null;
        if (!empty($cert['subject']['emailAddress'])) {
            $info['email'] = $cert['subject']['emailAddress'];
        } else if (isset($cert['extensions']['subjectAltName'])) {
            $matches = [];
            if (preg_match("/email:(.*?),/", $cert['extensions']['subjectAltName'], $matches)) {
                $info['email'] = trim($matches[1]);
            }
        }
        $info['platnost_od'] = @$cert['validFrom_time_t'];
        $info['platnost_do'] = @$cert['validTo_time_t'];
        $info['CA'] = @$cert['issuer']['CN'];
        $info['CA_org'] = @$cert['issuer']['O'];
        $info['CA_is_qualified'] = $this->is_qualified(@$cert['issuer']['CN']);

        if (!empty($cert['extensions']['crlDistributionPoints'])) {
            $crl_d = str_replace("URI:", "", $cert['extensions']['crlDistributionPoints']);
            $info['CRL'] = explode("\n", $crl_d);
        } else {
            $info['CRL'] = null;
        }


        return $info;
    }

    protected function getInfoCert($cert)
    {
        if (is_null($cert) || !is_object($cert)) {
            return null;
        }

        $info = array();

        $info['serial_number'] = sprintf("%X", @$cert->id);
        $info['id'] = @$cert->id_name;
        $info['jmeno'] = @$cert->name;

        if (!empty($cert->org)) {
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
        if (isset($this->ca_info[$ca_name]))
            return true;
        return false;
    }

    /**
     * Podepise zpravu
     *
     * @param string $message zprava
     * @param string $header pole obsahujici hlavicku
     * @return string  podepsana zprava
     */
    public function signMessage($message, $header = array())
    {
        $tmp_mess = tempnam(TEMP_DIR, 'outgoing_message');
        file_put_contents($tmp_mess, $message);
        $tmp_signed = tempnam(TEMP_DIR, 'outgoing_message_signed');

        $ok = openssl_pkcs7_sign($tmp_mess, $tmp_signed, $this->certificate,
                        [$this->private_key, $this->passphrase], $header, PKCS7_DETACHED);
        if ($ok)
            $signed_msg = file_get_contents($tmp_signed);
        // unlink($tmp_mess);
        // unlink($tmp_signed);
        if ($ok)
            return $signed_msg;
        
        throw new Exception('Email se nepodařilo podepsat. SSL: ' . openssl_error_string());
    }

    /**
     * Prevede format PEM na format DER
     *
     * @param string $pem_data
     * @return string
     */
    protected function pem2der($pem_data)
    {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
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
    protected function der2pem($der_data)
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

}
