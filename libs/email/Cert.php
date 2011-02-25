<?php

class Cert extends DERParser {

    private $enable_crl = 1;
    private $buffer = "";

    public function  __construct() {

        $this->setDateFormat('');
        $this->setOID('name');

    }

    public function enableCRL($status = 1)
    {
        $this->enable_crl = $status;
    }

    public function fromEmail($email_data)
    {

        if ( !empty($email_data) ) {

            //echo $email_data;

            //echo "\n\n\n===================\n\n\n";

            $header = ''; $buffer = '    ';
            for ( $i=0; $i<strlen($email_data); $i++ ) {
                if ( $buffer == "\r\n\r\n" ) { break; }
                $buffer[0] = $buffer[1];
                $buffer[1] = $buffer[2];
                $buffer[2] = $buffer[3];
                $buffer[3] = $email_data[$i];
                $header .= $email_data[$i];
            }
            $header_a = explode("\r\n",$header);
            //print_r($header_a);
            if ( count($header_a)>0 ) {
                $isSMIME = 0; $boundary = null;
                foreach ( $header_a as $h ) {
                    // Detect SMIME
                    if ( strpos($h,'multipart/signed') !== false ) {
                        $isSMIME = 1;
                    }
                    // Detect boundary
                    if (preg_match("#boundary=(.*)(\s)?#", $h, $matches) ) {
                        $boundary = str_replace('"','', $matches[1]);
                    }
                }
                //echo "\n\nBoundary = $boundary\n\n";
                if ( $isSMIME ) {
                    $buffer = ''; $smime_data = '';
                    $boundary_l = strlen($boundary);
                    $stop = strlen($email_data) - 6000;
                    for ( $i = (strlen($email_data)-1); $i > 0; $i-- ) {
                        if ( $buffer == "smime.p7s" ) {
                            break;
                        }
                        $smime_data = $email_data[$i] . $smime_data;
                        $buffer = $this->buffer($email_data[$i],9,'-');

                        //if ( $stop == $i ) break;
                    }

                    //echo "\n\n\n============================\n\n";
                    if ( strpos($smime_data,"\r\n\r\n") !== false ) {
                        $smime_data_a = explode("\r\n\r\n",$smime_data);
                    } else {
                        $smime_data_a = explode("\n\n",$smime_data);
                    }
                    //print_r($smime_data_a);
                    $smime_data = $smime_data_a[1];
                    $smime_data = str_replace("--$boundary--", "", $smime_data);
                    $smime_data = trim($smime_data);
                    //echo $smime_data;
                    //echo "\n\n\n============================\n\n";
                    
                    return $this->fromP7S($smime_data);

                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }

            //return $this->fromP7S($email_data);
    }

    private function buffer($znak, $limit, $smer="+")
    {

        if ( $limit != 0 ) {
            if ( strlen($this->buffer) == $limit ) {
                if ( $smer == "-" ) {
                    $this->buffer = substr($this->buffer, 0, -1);
                } else {
                    $this->buffer = substr($this->buffer, 1);
                }
            }
        }

        if ( $smer == "-" ) {
            $this->buffer = $znak . $this->buffer;
        } else {
            $this->buffer = $this->buffer . $znak;
        }

        return $this->buffer;
    }

    public function fromP7S($p7s_data)
    {

        $data_orig = $this->raw($p7s_data);
        $data = $this->parse($data_orig);
        $out = new stdClass();

        $out->subjekt = null;
        $out->ca = array();

        if ( !empty($data[0]['data'][0]['data']) && $data[0]['data'][0]['data'] == 'signedData' ) {

            if ( count($data[0]['data'][1]['data'][0]['data'][2]['data'][2]['data'])>0 ) {
                $i = 0;

                foreach ( $data[0]['data'][1]['data'][0]['data'][2]['data'][2]['data'] as $part ) {
                    // Jednotlive certifikaty - user + CA
                    //echo "\n\n=============================================\n";

                    // cert to file
                    //$cert = substr($data_orig,$part['offset'],$part['length']);
                    //$cert_f = chr().chr();
                    //file_put_contents('asn/temp/cert_'.$i.'.crt', $cert);

                    $cert = $this->getCert($part['data'][0]['data']);
                    //$cert = $this->getOpenSSLCert($part['data'][0]['data']);
                    if ( $cert ) {
                        if ( isset($cert->isCA) && $cert->isCA == 1 ) {
                            // is CA
                            $out->ca[] = $cert;
                        } else {
                            $out->subjekt = $cert;
                        }
                    }
                    $i++;
                    //break;
                }

                /*
                 * Subjekt info
                 */
                if ( !empty($out->subjekt->subjekt->commonName) ) {
                    $out->id = $out->subjekt->serial_number;
                    $out->id_name = (string) @$out->subjekt->subjekt->serialNumber;
                    $out->name = $out->subjekt->subjekt->commonName;
                    $out->org = (string) @$out->subjekt->subjekt->organizationName;
                    $out->locality = (string) @$out->subjekt->subjekt->localityName;
                    $out->state = (string) @$out->subjekt->subjekt->countryName;
                    if ( !empty($out->subjekt->subjekt->email) ) {
                        $out->email = $out->subjekt->subjekt->email;
                    } else if ( !empty($out->subjekt->subjectAltName)  ) {
                        $out->email = $out->subjekt->subjectAltName;
                    } else {
                        $out->email = "";
                    }
                    $out->CA_name = (string) @$out->subjekt->ca->commonName;
                    $out->CA_org = (string) @$out->subjekt->ca->organizationName;

                } else {
                    $out->name = "(nezjištěno)";
                    $out->email = "";
                }


                /*
                 * Error status + CRL test
                 */
                if ( $out->subjekt->error->status > 0 ) {
                    $out->error = $out->subjekt->error->status;
                    $out->error_message = $out->subjekt->error->message;
                } else if ( $this->enable_crl == 1 ) {
                    $CRL = new CRLParser();
                    $CRL->setDateFormat('j.n.Y G:i:s');
                    if ( isset($out->subjekt->crl) && count($out->subjekt->crl)>0 ) {
                        // CRL from subject
                        foreach ( $out->subjekt->crl as $crl_url ) {
                            //echo "\nCRL: $crl_url = ";
                            $seznam = $CRL->fromUrl($crl_url);
                            //print_r($seznam);
                            if ( isset($seznam->seznam) ) {
                                if ( isset($seznam->seznam[ $out->subjekt->serial_number ]) ) {
                                    $out->error = 2;
                                    $out->error_message = 'Certifikát byl zneplatněn! Datum zneplatnění: '. $seznam->seznam[$out->subjekt->serial_number]->datum;
                                }
                                break;
                            }
                        }
                    } else {
                        // CRL from CA
                        $stop = 0;
                        foreach ( $out->ca as $CA ) {
                            foreach ( $CA->crl as $crl_url ) {
                                $seznam = $CRL->fromUrl($crl_url);
                                if ( isset($seznam->seznam) ) {
                                    if ( isset($seznam->seznam[ $out->subjekt->serial_number ]) ) {
                                        $out->error = 2;
                                        $out->error_message = 'Certifikát byl zneplatněn! Datum zneplatnění: '. $seznam->seznam[$out->subjekt->serial_number]->datum;
                                    }
                                    $stop = 1;
                                    break;
                                }
                            }
                            if ( $stop == 1 ) break;
                        }
                    }

                } else {
                    $out->error = 0;
                    $out->error_message = '';
                }


                return $out;
            } else {
                return null;
            }
        } else {
            return null;
        }

    }

    public function fromX509($x509_data)
    {
        
    }

    public function fromPFX($pfx_data)
    {
        
    }

    private function getCert($data)
    {
        //print_r($data);

        $tmp = new stdClass();
        $error = new stdClass();
        $error->status = 0;
        $error->message = '';
/*
        $serial_number = $date = $subject = $issuer = $extension = $rsa = null;
        foreach( $data as $index => $d ) {
            echo ">> Part: \n";
            if ( $data[$index]['tag_name'] == "INTEGER" ) {
                $serial_number = $index;
            } else if ( $data[$index]['data'][0]['tag_name'] == "UCT TIME" ) {
                $date = $index;
            } else if ( $data[$index]['data'][1]['tag_name'] == "UCT TIME" ) {
                $date = $index;
            } else if ( $data[$index]['data'][0]['tag_name'] == "UCT TIME" ) {
                $date = $index;
            } else if ( $data[$index]['data'][0]['tag_name'] == "OBJECT IDENTIFIER" ) {
                $rsa = $index;
            }

        }

        exit;
*/
        // Serial number
        $tmp->serial_number = $data[1]['data'];

        // Platnost
        $tmp->platnost_od_unix = $data[4]['data'][0]['data'];
        $tmp->platnost_do_unix = $data[4]['data'][1]['data'];
        $tmp->platnost_od = @date('j.n.Y G:i:s',$data[4]['data'][0]['data']);
        $tmp->platnost_do = @date('j.n.Y G:i:s',$data[4]['data'][1]['data']);

        if ( !($tmp->platnost_od_unix < time() && $tmp->platnost_do_unix > time()) ) {
            $error->status = 1;
            $error->message = 'Certifikátu vypršela platnost!';
        }

        // Subjekt Info
        if ( count($data[5]['data'])>0 ) {
            $subject = new stdClass();
            foreach ( $data[5]['data'] as $d ) {

                if ( $d['tag'] != 31 ) continue;

                $code = !empty($d['data'][0]['data'][0]['data'])?$d['data'][0]['data'][0]['data']:"";
                $value = !empty($d['data'][0]['data'][1]['data'])?$d['data'][0]['data'][1]['data']:"";
                if ( $code == 'commonName' || $code == '2.5.4.3' ) {
                    $subject->commonName = $value;
                }
                else if ( $code == 'serialNumber' || $code == '2.5.4.5' ) {
                    $subject->serialNumber = $value;
                }
                else if ( $code == 'localityName' || $code == '2.5.4.7' ) {
                    $subject->localityName = $value;
                }
                else if ( $code == 'countryName' || $code == '2.5.4.6' ) {
                    $subject->countryName = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $subject->organizationName = $value;
                }
                else if ( $code == 'organizationUnitName' || $code == '2.5.4.11' ) {
                    $subject->organizationUnitName = $value;
                }
                else if ( $code == 'locality' || $code == '2.5.4.8' ) {
                    $subject->locality = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $subject->organizationName = $value;
                }
                else if ( $code == 'email' || $code == '1.2.840.113549.1.9.1' ) {
                    $subject->email = $value;
                }
                else if ( $code == 'role' || $code == '2.5.4.12' ) {
                    $subject->role = $value;
                } else {
                    if ( !empty($code) ) {
                        $subject->{$code} = $value;
                    }
                }



            }
            $tmp->subjekt = $subject;
        }

        // CA Info
        if ( count($data[3]['data'])>0 ) {
            $ca = new stdClass();
            foreach ( $data[3]['data'] as $d ) {

                if ( $d['tag'] != 31 ) continue;
                $code = $d['data'][0]['data'][0]['data'];
                $value = $d['data'][0]['data'][1]['data'];
                if ( $code == 'commonName' || $code == '2.5.4.3' ) {
                    $ca->commonName = $value;
                }
                else if ( $code == 'serialNumber' || $code == '2.5.4.5' ) {
                    $ca->serialNumber = $value;
                }
                else if ( $code == 'localityName' || $code == '2.5.4.7' ) {
                    $ca->localityName = $value;
                }
                else if ( $code == 'countryName' || $code == '2.5.4.6' ) {
                    $ca->countryName = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $ca->organizationName = $value;
                }
                else if ( $code == 'organizationUnitName' || $code == '2.5.4.11' ) {
                    $ca->organizationUnitName = $value;
                }
                else if ( $code == 'locality' || $code == '2.5.4.8' ) {
                    $ca->locality = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $ca->organizationName = $value;
                } else {
                    $ca->{$code} = $value;
                }

            }
            $tmp->ca = $ca;
        }

        // Parametry certifikatu
        if ( ($data[7]['data'][0]['data'])>0 ) {
            $param_a = array();
            foreach ( $data[7]['data'][0]['data'] as $param ) {

                $key = $param['data'][0]['data'];

                if ( $key == 'subjectAltName' || $key == '2.5.29.17' ) {
                    // subjectAltName
                    //print_r($param['data'][1]['data']);
                    $tmp->subjectAltName = $param['data'][1]['data'][0]['data'][0]['data'];
                    $param_a[ $key ] = $tmp->subjectAltName;
                } else if ( $key == 'cRLDistributionPoints' || $key == '2.5.29.31' ) {
                    // CRL
                    //print_r($param['data'][1]['data'][0]['data']);
                    if ( count($param['data'][1]['data'][0]['data'])>0 ){
                        $crl_a = array();
                        foreach ( $param['data'][1]['data'][0]['data'] as $crl ) {
                            if ( isset($crl['data'][1]['data']) ) {
                                $crl_a[] = $crl['data'][1]['data'];    
                            } else {
                                $crl_a[] = $crl['data'][0]['data'][0]['data'][0]['data'];
                            }

                        }
                        $tmp->crl = $crl_a;
                        $param_a[ $key ] = $crl_a;
                    } else {
                        $tmp->crl = null;
                        $param_a[ $key ] = null;
                    }
                } else if ( $key == 'basicConstraints' || $key == '2.5.29.19' ) {

                    if ( $param['data'][1]['tag_name'] == 'BOOLEAN' ) {
                        $tmp->isCA = (int) @$param['data'][2]['data'][0]['data'][0]['data'];
                    } else {
                        $tmp->isCA = (int) @$param['data'][1]['data'][0]['data'][0]['data'];
                    }

                    $param_a[ $key ] = $tmp->isCA;
                } else {
                    //$param_a[ $key ] = $param['data'];
                }


            }
            $tmp->params = $param_a;
        }

        $tmp->error = $error;

        return $tmp;
    }

    private function getOpenSSLCert($data)
    {
        //print_r($data);

        $tmp = array();
        $error = array();
        $error['status'] = 0;
        $error['message'] = '';

        // Serial number
        $tmp['serialNumber'] = $data[1]['data'];

        // Platnost
        $tmp['validFrom_time_t'] = $data[4]['data'][0]['data'];
        $tmp['validTo_time_t'] = $data[4]['data'][1]['data'];
        $tmp['validFrom'] = date('j.n.Y G:i:s',$data[4]['data'][0]['data']);
        $tmp['validTo'] = date('j.n.Y G:i:s',$data[4]['data'][1]['data']);

        if ( !($tmp['validFrom_time_t'] < time() && $tmp['validTo_time_t'] > time()) ) {
            $error['status'] = 1;
            $error['message'] = 'Certifikátu vypršela platnost!';
        }

        // Subjekt Info
        if ( count($data[5]['data'])>0 ) {
            $subject = array();
            foreach ( $data[5]['data'] as $d ) {
                $code = $d['data'][0]['data'][0]['data'];
                $value = $d['data'][0]['data'][1]['data'];
                if ( $code == 'commonName' || $code == '2.5.4.3' ) {
                    $subject['CN'] = $value;
                }
                else if ( $code == 'serialNumber' || $code == '2.5.4.5' ) {
                    $subject['serialNumber'] = $value;
                }
                else if ( $code == 'localityName' || $code == '2.5.4.7' ) {
                    $subject['L'] = $value;
                }
                else if ( $code == 'countryName' || $code == '2.5.4.6' ) {
                    $subject['C'] = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $subject['O'] = $value;
                }
                else if ( $code == 'organizationUnitName' || $code == '2.5.4.11' ) {
                    $subject['OU'] = $value;
                }
                else if ( $code == 'locality' || $code == '2.5.4.8' ) {
                    $subject['LOC'] = $value;
                }
                else if ( $code == 'email' || $code == '1.2.840.113549.1.9.1' ) {
                    $subject['email'] = $value;
                }
                else if ( $code == 'role' || $code == '2.5.4.12' ) {
                    $subject['role'] = $value;
                } else {
                    $subject[$code] = $value;
                }



            }
            $tmp['subject'] = $subject;
        }

        // CA Info
        if ( count($data[3]['data'])>0 ) {
            $ca = array();
            foreach ( $data[3]['data'] as $d ) {
                $code = $d['data'][0]['data'][0]['data'];
                $value = $d['data'][0]['data'][1]['data'];
                if ( $code == 'commonName' || $code == '2.5.4.3' ) {
                    $ca['CN'] = $value;
                }
                else if ( $code == 'serialNumber' || $code == '2.5.4.5' ) {
                    $ca['serialNumber'] = $value;
                }
                else if ( $code == 'localityName' || $code == '2.5.4.7' ) {
                    $ca['L'] = $value;
                }
                else if ( $code == 'countryName' || $code == '2.5.4.6' ) {
                    $ca['C'] = $value;
                }
                else if ( $code == 'organizationName' || $code == '2.5.4.10' ) {
                    $ca['O'] = $value;
                }
                else if ( $code == 'organizationUnitName' || $code == '2.5.4.11' ) {
                    $ca['OU'] = $value;
                }
                else if ( $code == 'locality' || $code == '2.5.4.8' ) {
                    $ca['LOC'] = $value;
                } else {
                    $ca[$code] = $value;
                }

            }
            $tmp['issuer'] = $ca;
        }

        // Parametry certifikatu
        if ( ($data[7]['data'][0]['data'])>0 ) {
            $param_a = array();
            foreach ( $data[7]['data'][0]['data'] as $param ) {

                $key = $param['data'][0]['data'];

                if ( $key == 'subjectAltName' || $key == '2.5.29.17' ) {
                    // subjectAltName
                    //print_r($param['data'][1]['data']);
                    $tmp['subjectAltName'] = $param['data'][1]['data'][0]['data'][0]['data'];
                    $param_a[ $key ] = 'email:'. $tmp['subjectAltName'];
                } else if ( $key == 'cRLDistributionPoints' || $key == '2.5.29.31' ) {
                    // CRL
                    //print_r($param['data'][1]['data'][0]['data']);
                    if ( count($param['data'][1]['data'][0]['data'])>0 ){
                        $crl_a = array();
                        foreach ( $param['data'][1]['data'][0]['data'] as $crl ) {
                            if ( isset($crl['data'][1]['data']) ) {
                                $crl_a[] = $crl['data'][1]['data'];
                            } else {
                                $crl_a[] = $crl['data'][0]['data'][0]['data'][0]['data'];
                            }

                        }
                        $tmp['crl'] = $crl_a;
                        $param_a[ $key ] = $crl_a;
                    } else {
                        $tmp['crl'] = null;
                        $param_a[ $key ] = null;
                    }
                } else if ( $key == 'basicConstraints' || $key == '2.5.29.19' ) {

                    if ( $param['data'][1]['tag_name'] == 'BOOLEAN' ) {
                        $tmp['isCA'] = (int) @$param['data'][2]['data'][0]['data'][0]['data'];
                    } else {
                        $tmp['isCA'] = (int) @$param['data'][1]['data'][0]['data'][0]['data'];
                    }

                    $param_a[ $key ] = ($tmp['isCA'])?'CA:TRUE':'CA:FALSE';
                } else {
                    //$param_a[ $key ] = $param['data'];
                }


            }
            $tmp['extensions'] = $param_a;
        }

        $tmp['error'] = $error;

        return $tmp;
    }


}