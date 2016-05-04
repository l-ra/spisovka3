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
     * Nastavi certifikat pro podepisování odchozí pošty.
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
            if ($x509cert = openssl_x509_read($cert_info['cert'])) {
                openssl_x509_free($x509cert);
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
        }
    }


    /**
     * Vrací seznam souborů s certifikáty autorit akreditovaných v ČR
     * 
     * @return array
     */
    protected function getCAList()
    {
        $dir = LIBS_DIR . '/email/ca_certifikaty';
        return ["$dir/1_CA.pem", "$dir/PostSignum.pem", "$dir/eIdentity.pem"];
    }

    /**
     *
     * @param string $filename
     * @return bool
     */
    public function verifySignature($filename)
    {
        $cainfo = $this->getCAList();
        $tmp_cert = tempnam(TEMP_DIR, "sender_certificate");
        $res = openssl_pkcs7_verify($filename, 0 /*PKCS7_NOVERIFY*/, $tmp_cert, $cainfo);
        $cert = openssl_x509_parse("file://$tmp_cert");
        unlink($tmp_cert);

        if ($res === true) {
            // podpis overen
            $ok = true;
            $email_cert = $email_real = null;
            $res = $this->verifyEmailAddress($cert, $filename, $email_cert, $email_real);
            $msg = "Podpis je platný.";
            if ($res === false) {
                $msg .= " Ale emailová adresa odesilatele neodpovídá adrese v certifikátu.";
            } else if ($res === -1) {
                $msg .= " Ale z certifikátu se nepodařilo zjistit emailovou adresu.";
            }
        } else {
            // podpis neprosel overenim
            $ok = false;
            $error_string = openssl_error_string();
            if ($res == -1) {
                // Chyba
                if (strpos($error_string, "invalid mime type") !== false) {
                    $msg = "Email není podepsán.";
                } else if (strpos($error_string, "no content type") !== false) {
                    $msg = "Email není podepsán.";
                } else {
                    $msg = "Při ověřování podpisu došlo k chybě";
                    $msg .= $error_string ? ": $error_string." : '.';
                }
            } else {
                // Podpis nebo certifikat pro podpis neni platny
                $res = openssl_pkcs7_verify($filename, PKCS7_NOVERIFY);
                if ($res === true)
                    $msg = "Zpráva nebyla změněna, ale certifikát není kvalifikovaný. Identita odesílajícího nebyla ověřena.";
                else
                    $msg = "Podpis je neplatný! $error_string.";
            }            
        }
        
        return array(
            'ok' => $ok,
            'message' => $msg,
            'cert' => $cert ?: null
        );        
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

    public function parseCertificate()
    {
        if (!$this->certificate)
            return null;
        $cert = openssl_x509_parse($this->certificate);
        if (!$cert)
            return null;
        
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

        if (!empty($cert['extensions']['crlDistributionPoints'])) {
            $crl_d = str_replace("URI:", "", $cert['extensions']['crlDistributionPoints']);
            $info['CRL'] = explode("\n", $crl_d);
        } else {
            $info['CRL'] = null;
        }


        return $info;
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
        unlink($tmp_mess);
        unlink($tmp_signed);
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
    /* protected function pem2der($pem_data)
    {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    } */

    /**
     * Prevede format DER na format PEM
     *
     * @param string $der_data
     * @return string
     */
    /* protected function der2pem($der_data)
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    } */

}
