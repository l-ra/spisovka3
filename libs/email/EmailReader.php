<?php

class EmailReader {

    private $mode = 0;
    private $part = array();
    private static $charset = "utf-8";

    private $with_content = true;

    public function  __construct($with_content = true) {
        $this->with_content = $with_content;
    }

    public function parse($mixed) {

        if ( !empty($mixed) ) {
            if ( is_file($mixed) ) {
                return $this->parseFile($mixed);
            } else {
                return $this->parseString($mixed);
            }
        } else {
            return null;
        }

    }

    public function parseString($string) {

        $this->parsePart($string);

        //////////////////////////
        return $this->prepare();
        //////////////////////////

    }

    public function parseFile($file) {

        $buffer = null;
        $buffer_i = 0;
        $boundary = null;
        $mode = 0;

        if (file_exists($file) ) {

            if ( $fp = fopen($file,'rb') ) {

                while ( $radek = fgets($fp, 512) ) {

                    //echo ">>>>   "; var_dump($radek);

                    /**********************************
                     *   Hlavicka
                     *********************************/

                    if ( $radek == "\r\n" && $mode == 0 ) {
                        $header = $this->parseHeader($buffer);
                        $this->part[] = $header;
                        unset($buffer);
                        $buffer_i = 0;
                        $mode = 1;

                        //echo "Hlavicky: "; print_r($header);
                        //
                        //echo "\nContent: ". $header['Content-Type']['content-type'];
                        //echo "\nBoundary: ". @$header['Content-Type']['boundary'];
                        //echo "\n\n======================================\n\n";

                    }

                    // Subhlavicky
                    if ( ($radek[0] == " " || $radek[0] == "\t") && $mode == 0 ) {
                        $buffer[ $buffer_i ] .= " ". trim($radek);
                    } else if ( $mode == 0 ) {
                        $buffer_i++;
                        $buffer[ $buffer_i ] = trim($radek);
                    }

                    /**********************************
                     *   Telo
                     *********************************/
                    if ( $mode == 1 ) {
                        if ( isset($header['Content-Type']['boundary']) ) {
                            if ( strpos($radek,$header['Content-Type']['boundary'])!==false ) {
                                // rozdelit
                                $this->parsePart($buffer);
                                unset($buffer);
                            } else {
                                $buffer = (empty($buffer))?$radek:($buffer.$radek);
                            }
                        } else {
                            $buffer = (empty($buffer))?$radek:($buffer.$radek);
                        }
                    }
                }

                $this->part[] = $buffer;
                @fclose($fp);
                ////////////////////////
                return $this->prepare();
                ////////////////////////
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function withContent($bool) {

        return $this->with_content = ($bool)?true:false;

    }

    private function prepare() {

        $tmp = array(
            'headers'=>null,
            'texts'=>null,
            'attachments'=>null,
            'signatures'=>null
        );

        if ( count($this->part)>0 ) {
            $last_header = 0;
            foreach ($this->part as $pi => $part) {
                if ( $pi == 0 ) {
                    // header
                    $tmp['headers'] = $part;
                }
                if ( is_array($part) ) { $last_header = $pi; continue; }
                $part = trim($part);
                if ( empty($part) ) { continue; }

                // Content
                $content_header = $this->part[$last_header];

                if ( $this->part[$last_header]['Content-Type']['content-type'] == "text/plain" ) {
                    // TEXT
                    $tmp['texts'][] = array(
                        'content-type' => "text/plain",
                        'charset' => $this->part[$last_header]['Content-Type']['charset'],
                        'encoding' => $this->part[$last_header]['Content-Transfer-Encoding'],
                        'content' => $part
                    );
                } else if ( $this->part[$last_header]['Content-Type']['content-type'] == "text/html" ) {
                    // HTML
                    $tmp['texts'][] = array(
                        'content-type' => "text/html",
                        'charset' => $this->part[$last_header]['Content-Type']['charset'],
                        'encoding' => $this->part[$last_header]['Content-Transfer-Encoding'],
                        'content' => $part
                    );
                } else if ( ($this->part[$last_header]['Content-Type']['content-type'] == "multipart/signed") ) {
                    //nic
                } else if ( ($this->part[$last_header]['Content-Type']['content-type'] == "application/pkcs7-signature") ) {
                    // SIGNATURE
                    $tmp['signatures'][] = array(
                        'content-type' => $this->part[$last_header]['Content-Type']['content-type'],
                        'filename' => $this->part[$last_header]['Content-Disposition']['filename'],
                        'size' => strlen($part),
                        'encoding' => $this->part[$last_header]['Content-Transfer-Encoding'],
                        'content' => ($this->with_content)?$part:null
                    );
                } else {
                    // ATTACHMENT
                    $tmp['attachments'][] = array(
                        'content-type' => $this->part[$last_header]['Content-Type']['content-type'],
                        'filename' => $this->part[$last_header]['Content-Disposition']['filename'],
                        'size' => strlen($part),
                        'encoding' => $this->part[$last_header]['Content-Transfer-Encoding'],
                        'content' => ($this->with_content)?$part:null
                    );
                }

            }
            return $tmp;
        } else {
            return null;
        }

    }

    private function parsePart($string) {
        
        $radek = null;
        $char_i = 0;
        $buffer = null;
        $buffer_i = 0;
        $boundary = null;
        $mode = 0;

        //echo "\n\n====================\n\n"; print_r($string);

        if (!empty($string) ) {
            for ( $char_i = 0; $char_i < strlen($string); $char_i++ ) {
                $radek = (empty($radek))?$string[$char_i]:($radek.$string[$char_i]);
                //echo "\n >> [". ord($string[$char_i]) ."] = "; var_dump($string[$char_i]);
                if ( $string[$char_i] == "\n" ) {
                    //echo ">>>>   "; var_dump($radek);

                    /**********************************
                     *   Hlavicka
                     *********************************/

                    if ( $radek == "\r\n" && $mode == 0 ) {
                        $header = $this->parseHeader($buffer);
                        $this->part[] = $header;
                        unset($buffer);
                        $buffer_i = 0;
                        $mode = 1;

                        //echo "Hlavicky: "; print_r($header);
                        //
                        //echo "Content: ". $header['Content-Type']['content-type'];
                        //echo "\n\n======================================\n\n";

                    }

                    // Subhlavicky
                    if ( ($radek[0] == " " || $radek[0] == "\t") && $mode == 0 ) {
                        $buffer[ $buffer_i ] .= " ". trim($radek);
                    } else if ( $mode == 0 ) {
                        $buffer_i++;
                        $buffer[ $buffer_i ] = trim($radek);
                    }

                    /**********************************
                     *   Telo
                     *********************************/
                    if ( $mode == 1 ) {
                        if ( isset($header['Content-Type']['boundary']) ) {
                            if ( strpos($radek,$header['Content-Type']['boundary'])!==false ) {
                                // rozdelit
                                $this->parsePart($buffer);
                                unset($buffer);
                            } else {
                                $buffer = (empty($buffer))?$radek:($buffer.$radek);
                            }
                        } else {
                            $buffer = (empty($buffer))?$radek:($buffer.$radek);
                        }
                    }

                    

                    unset($radek);
                }
            }

            $this->part[] = $buffer;
            return true;

        } else {
            return false;
        }
    }

    private function parseHeader($headers) {

        $headers_tmp = array();

        if ( count($headers)==0 ) {
            return null;
        }
        
        foreach ($headers as $head) {
            
            list($name,$value) = explode(":",$head,2);
            $name = trim($name);
            $value = trim($value);

            //echo "@@@ $name = $value \n";

            if ( isset($headers_tmp[$name]) ) {
                if ( count($headers_tmp[$name])>1 ) {
                    $headers_tmp[$name][] = $value;
                } else {
                    $tmp = $headers_tmp[$name];
                    $headers_tmp[$name] = array($tmp);
                    $headers_tmp[$name][] = $value;
                    unset($tmp);
                }
            } else {
                $headers_tmp[$name] = $value;
            }
        }

        /* Decode Content-type */
        if ( isset( $headers_tmp['Content-Type'] ) ) {
            $tmp = array();
            $content_part = explode(";",$headers_tmp['Content-Type']);
            if ( count($content_part)>0 ) {
                foreach( $content_part as $cp ) {
                    if ( strpos($cp,"=")!==false  ) {
                        list($name,$value) = explode("=",$cp,2);
                        $name = trim($name);
                        $value = trim($value);
                        if ( $value[0] == "\"" ) {
                            $tmp[$name] = substr($value, 1, strlen($value)-2);
                        } else {
                            $tmp[$name] = $value;
                        }
                        if ( ($name == "filename") || ($name == "name") ) {
                            $tmp[$name] = $this->decode_header($tmp[$name]);
                        }
                    } else {
                        $tmp['content-type'] = trim($cp);
                    }
                }
                $headers_tmp['Content-Type'] = $tmp;
                unset($tmp,$name,$value);
            }
            unset($content_part);
        }

        /* Decode Content-type */
        if ( isset( $headers_tmp['Content-Disposition'] ) ) {
            $tmp = array();
            $content_part = explode(";",$headers_tmp['Content-Disposition']);
            if ( count($content_part)>0 ) {
                foreach( $content_part as $cp ) {
                    if ( strpos($cp,"=")!==false  ) {
                        list($name,$value) = explode("=",$cp,2);
                        $name = trim($name);
                        $value = trim($value);
                        if ( $value[0] == "\"" ) {
                            $tmp[$name] = substr($value, 1, strlen($value)-2);
                        } else {
                            $tmp[$name] = $value;
                        }
                        if ( ($name == "filename") || ($name == "name") ) {
                            $tmp[$name] = $this->decode_header($tmp[$name]);
                        }

                    } else {
                        $tmp['content-disposition'] = trim($cp);
                    }
                }
                $headers_tmp['Content-Disposition'] = $tmp;
                unset($tmp,$name,$value);
            }
            unset($content_part);
        }

        /* Decode Subject */
        if ( isset( $headers_tmp['Subject'] ) ) {
            $headers_tmp['Subject'] = $this->decode_header($headers_tmp['Subject']);
        }

        /* Decode From */
        if ( isset( $headers_tmp['From'] ) ) {
            $headers_tmp['From'] = $this->decode_address($headers_tmp['From']);
        }

        /* Decode To */
        if ( isset( $headers_tmp['To'] ) ) {
            $headers_tmp['To'] = $this->decode_address($headers_tmp['To']);
        }

        /* Decode Return-path */
        if ( isset( $headers_tmp['Return-path'] ) ) {
            $headers_tmp['Return-path'] = $this->decode_address($headers_tmp['Return-path']);
        }

        /* Decode Reply-To */
        if ( isset( $headers_tmp['Reply-To'] ) ) {
            $headers_tmp['Reply-To'] = $this->decode_address($headers_tmp['Reply-To']);
        }

        /* Decode Cc */
        if ( isset( $headers_tmp['Cc'] ) ) {
            $headers_tmp['Cc'] = $this->decode_address($headers_tmp['Cc']);
        }

        /* Decode Bcc */
        if ( isset( $headers_tmp['Bcc'] ) ) {
            $headers_tmp['Bcc'] = $this->decode_address($headers_tmp['Bcc']);
        }

        return $headers_tmp;

  }

    public static function decode($part) {

        if ( !empty($part['content']) ) {
            $encoding = strtolower($part['encoding']);
            // 8bit
            if ( $encoding == "8bit" ) {
                if ( isset($part['charset']) ) {
                    $content = iconv($part['charset'], self::$charset, $part['content']);
                    return ($content)?$content:$part['content'];
                } else {
                    return $part['content'];
                }
            } else
            // quoted-printable
            if ( $encoding == "quoted-printable" ) {
                if ( isset($part['charset']) ) {
                    $content = imap_qprint($part['content']);
                    $content = iconv($part['charset'], self::$charset, $content);
                    return ($content)?$content:$part['content'];
                } else {
                    return imap_qprint($part['content']);
                }
            } else
            // base64
            if ( $encoding == "base64" ) {
                if ( isset($part['charset']) ) {
                    $content = base64_decode($part['content']);
                    $content = iconv($part['charset'], self::$charset, $content);
                    return ($content)?$content:$part['content'];
                } else {
                    return base64_decode($part['content']);
                }
            } else
            // binary
            if ( $encoding == "xxx" ) {
                if ( isset($part['charset']) ) {
                    $content = imap_binary($part['content']);
                    $content = iconv($part['charset'], self::$charset, $content);
                    return ($content)?$content:$part['content'];
                } else {
                    return imap_binary($part['content']);
                }
            } else
            // neznamy typ
            {
                return $part['content'];
            }



        } else {
            return null;
        }

    }

    private function decode_header($string,$charset=null) {

        if(is_null($charset)) $charset = self::$charset;

        if (function_exists('imap_mime_header_decode')) {
            $parse = "";
            $elements = imap_mime_header_decode($string);
            for ($i=0; $i<count($elements); $i++) {
                if($elements[$i]->charset != "default") {
                    $text = @iconv($elements[$i]->charset, $charset, $elements[$i]->text);
                } else {
                    $text = $elements[$i]->text;
                }
                $parse .= $text;
            }
            return $parse;
        } else {
            return $string;
        }
    }

    private function decode_address($address) {

        $tmp = array();
        $address_part = explode(",",$address);
        foreach( $address_part as $api => $ap ) {
            if ( strpos($ap,"<") !== false  ) {
                $ap_part = explode("<",$ap);
                if ( isset($ap_part[1]) ) {
                    $name = $this->decode_header(trim($ap_part[0]));
                    $email = trim(substr($ap_part[1], 0, strlen($ap_part[1])-1));
                } else {
                    $name = trim($ap_part[0]);
                    $email = trim($ap_part[0]);
                }

                if ( $name != $email ) {
                    $string = $name ." <". $email .">";
                } else {
                    $string = $email;
                }

                $tmp[$api] = array(
                    'raw' => $ap,
                    'string' => $string,
                    'name' => $name,
                    'email' => $email
                );
            } else {
                $tmp[$api] = array(
                    'raw' => $ap,
                    'string' => $ap,
                    'name' => $ap,
                    'email' => $ap
                );
            }
            unset($name,$ap_part,$string,$email);
        }

        return $tmp;

    }

}

