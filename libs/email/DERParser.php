<?php

class DERParser
{

    private $date_format = '';
    private $OID_view = 'both'; /* code - only number, name - only name, both - number + name */
    private $data;
    private $tag_classes = array(
        '00' => 'UNIVERSAL',
        '40' => 'APPLICATION',
        '80' => 'CONTEXT-SPECIFIC',
        'C0' => 'PRIVATE'
    );
    private $tag_type = array(
        '0' => 'PRIMITIVE',
        '1' => 'CONSTRUCTED',
    );
    private $universal_tag = array(
        '00' => 'EOC',
        '01' => 'BOOLEAN',
        '02' => 'INTEGER',
        '03' => 'BIT STRING',
        '04' => 'OCTET STRING',
        '05' => 'NULL',
        '06' => 'OBJECT IDENTIFIER',
        '07' => 'OBJECT DESCRIPTOR',
        '08' => 'EXTERNAL',
        '09' => 'REAL',
        '0A' => 'ENUMERATED',
        '0B' => 'EMBEDDED_PDV',
        '0C' => 'UFT8 STRING',
        '0D' => 'EMBEDDED',
        '0E' => '',
        '0F' => '',
        '10' => 'SEQUENCE',
        '11' => 'SET',
        '12' => 'NUMERIC STRING',
        '13' => 'PRINTABLE STRING',
        '14' => 'TELETEXT STRING',
        '15' => 'VIDEO STRING',
        '16' => 'IA5 STRING (ASCII)',
        '17' => 'UCT TIME',
        '18' => 'GENERALIZED TIME',
        '19' => 'GRAPHICAL STRING',
        '1A' => 'VISIBLE STRING',
        '1B' => 'STRING',
        '1C' => 'UNIVERSAL STRING',
        '1D' => '',
        '1E' => 'BASIC MULTILINGUAL PLANE STRING',
        '1F' => 'TAG_MASK',
        '30' => 'SEQUENCE',
    );

    public function parse($data)
    {
        if (strpos($data, "---") !== FALSE) {
            $data = $this->pem2der($data);
        } else if ($data[0] == "M") {
            $data = $this->pemraw2der($data);
        }

        //file_put_contents(TEMP_DIR .'/test.crl', $data);

        $this->data = $this->_parse($data);
        return $this->data;
    }

    public function parseSimple($data)
    {

        if (strpos($data, "---") !== FALSE) {
            $data = $this->pem2der($data);
        } else if ($data[0] == "M") {
            $data = $this->pemraw2der($data);
        }

        return $this->_parse($data, 1);
    }

    public function parseDump($data, $raw = 0)
    {

        if (strpos($data, "---") !== FALSE) {
            $data = $this->pem2der($data);
        } else if ($data[0] == "M") {
            $data = $this->pemraw2der($data);
        }

        return $this->_parseDump($data, $raw);
    }

    protected function raw($data)
    {
        if (strpos($data, "---") !== FALSE) {
            $data = $this->pem2der($data);
        } else if ($data[0] == "M") {
            $data = $this->pemraw2der($data);
        }
        return $data;
    }

    private function _parse($data, $onelevel = 0, $start = 0)
    {
        $out = array();
        $i = 0;
        while ($i < strlen($data)) {

            $offset = $i;
            $x1 = (isset($data[$i])) ? sprintf("%02X", ord($data[$i])) : null;
            $x2 = (isset($data[$i + 1])) ? sprintf("%02X", ord($data[$i + 1])) : null;
            if ($x2 == "83") {
                // 3bytes length
                $length_hex = sprintf("%02X%02X%02X", ord($data[$i + 2]), ord($data[$i + 3]),
                        ord($data[$i + 4]));
                $length = hexdec($length_hex);
                $offset += 5;
                $x3 = (isset($data[$i + 3])) ? sprintf("%02X", ord($data[$i + 5])) : null;
            } else if ($x2 == "82") {
                // 2bytes length
                $length_hex = sprintf("%02X%02X", ord($data[$i + 2]), ord($data[$i + 3]));
                $length = hexdec($length_hex);
                $offset += 4;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 4])) : null;
            } else if ($x2 == "81") {
                $length_hex = sprintf("%02X", ord($data[$i + 2]));
                $length = hexdec($length_hex);
                $offset += 3;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 3])) : null;
            } else if ($x2 == "80") {
                // nondefined length
                $length = strlen($data);
                $offset += 2;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 2])) : null;
            } else {
                // 1byte length
                $length = hexdec($x2);
                $offset += 2;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 2])) : null;
            }

            // output
            $tag = $this->parseTag($x1, $x2, $x3);
            if ($tag->type == 1) {
                $tmp = array(
                    'tag' => $tag->tag_orig,
                    'tag_name' => $tag->tag_name,
                    'offset' => $i + $start,
                    'length' => $length
                );
                $intmp = $this->_parse(substr($data, $offset, $length), $onelevel,
                        $offset + $start);

                if ($onelevel == 1) {
                    $out[] = $tmp;
                    $out = array_merge($out, $intmp);
                } else {
                    $tmp['data'] = $intmp;
                    $out[] = $tmp;
                }
            } else {
                $_data = $this->paserData($x1, substr($data, $offset, $length));
                $out[] = array(
                    'tag' => $tag->tag_orig,
                    'tag_name' => $tag->tag_name,
                    'offset' => $i + $start,
                    'length' => $length,
                    'data' => $_data
                );
            }

            $i = $offset + $length;
            unset($tmp, $offset, $length, $length_hex, $x1, $x2, $_data);
        }
        return $out;
    }

    private function _parseDump($data, $raw = 0, $start = 0)
    {
        $out = "";
        $i = 0;
        while ($i < strlen($data)) {

            $offset = $i;
            $x1 = (isset($data[$i])) ? sprintf("%02X", ord($data[$i])) : null;
            $x2 = (isset($data[$i + 1])) ? sprintf("%02X", ord($data[$i + 1])) : null;
            if ($x2 == "83") {
                // 3bytes length
                $length_hex = sprintf("%02X%02X%02X", ord($data[$i + 2]), ord($data[$i + 3]),
                        ord($data[$i + 4]));
                $length = hexdec($length_hex);
                $offset += 5;
                $x3 = (isset($data[$i + 3])) ? sprintf("%02X", ord($data[$i + 5])) : null;
            } else if ($x2 == "82") {
                // 2bytes length
                $length_hex = sprintf("%02X%02X", ord($data[$i + 2]), ord($data[$i + 3]));
                $length = hexdec($length_hex);
                $offset += 4;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 4])) : null;
            } else if ($x2 == "81") {
                // 1bytes length with priznakem
                $length_hex = sprintf("%02X", ord($data[$i + 2]));
                $length = hexdec($length_hex);
                $offset += 3;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 3])) : null;
            } else if ($x2 == "80") {
                // nondefined length
                $length = strlen($data);
                $offset += 2;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 2])) : null;
            } else {
                // 1byte length
                $length = hexdec($x2);
                $offset += 2;
                $x3 = (isset($data[$i + 2])) ? sprintf("%02X", ord($data[$i + 2])) : null;
            }

            // output
            $tag = $this->parseTag($x1, $x2, $x3);

            if ($tag->type == 1) {
                // Structure tag
                $out .= sprintf("<span style='color:#808080;'>[%6d][%5d]</span> <span style='color:#880000;'>%2s %s</span>\n",
                        ($i + $start), $length, $tag->tag_orig, $tag->tag_name);
                if ($raw == 1) {
                    $out .= "<div style='color:#888800;'>                ";
                    $part = substr($data, $i, $offset + $length);
                    for ($p = 0; $p < strlen($part); $p++) {
                        $out .= sprintf("%02X ", ord($part[$p]));
                    }
                    $out .= "</div>";
                }
                $out .= $this->_parseDump(substr($data, $offset, $length), $raw, $offset + $start);
            } else {
                // Single tag
                $_data = $this->paserData($tag->tag, substr($data, $offset, $length));
                $out .= sprintf("<span style='color:#808080;'>[%6d][%5d]</span> <span style='color:#008800;'>%2s %s</span> = <span style='color:#000088;'>%s</span>\n",
                        ($i + $start), $length, $tag->tag_orig, $tag->tag_name, $_data);
                if ($raw == 1) {
                    $out .= "<div style='color:#888800;'>                ";
                    $part = substr($data, $i, $offset + $length);
                    for ($p = 0; $p < strlen($part); $p++) {
                        $out .= sprintf("%02X ", ord($part[$p]));
                    }
                    $out .= "</div>";
                }
            }
            $i = $offset + $length;
            unset($tmp, $offset, $length, $length_hex, $x1, $x2, $_data);
        }
        return $out;
    }

    private function parseTag($tag, $special1 = null, $special2 = null)
    {
        $dec = hexdec($tag);
        $binary = decbin($dec);
        if (strlen($binary) < 8) {
            $binary = sprintf("%08d", $binary);
        } else if (strlen($binary) > 8) {
            
        }

        $class = $binary[0] . $binary[1];
        $type = $binary[2];
        $tag_bin = substr($binary, 3);
        $tag_dec = bindec($tag_bin);
        $tag_hex = sprintf("%02X", $tag_dec);

        if ($tag[0] == "A") {
            // CONTEXT SPECIFIC
            $tmp = new stdClass();
            $tmp->tag_orig = $tag;
            $tmp->tag = $tag_hex;
            $tmp->tag_name = "CONTEXT SPECIFIC " . intVal($tag_hex);
            $tmp->type = 1;
            $tmp->class = bindec($class);
        } else if ($tag[0] == "8") {
            // CONTEXT SPECIFIC
            $tmp = new stdClass();
            $tmp->tag_orig = $tag;
            switch ($tag[1]) {
                case 1:
                    $tmp->tag = '13';
                    break;
                case 6:
                    $tmp->tag = '13';
                    break;
                default:
                    $tmp->tag = '00';
                    break;
            }
            $tmp->tag_name = "CONTEXT SPECIFIC " . intVal($tag_hex);
            $tmp->type = 0;
            $tmp->class = bindec($class);
        } else {
            $tmp = new stdClass();
            $tmp->tag_orig = $tag;
            $tmp->tag = $tag_hex;
            $tmp->tag_name = isset($this->universal_tag[$tag_hex]) ? $this->universal_tag[$tag_hex]
                        : "???";
            $tmp->type = $type;
            $tmp->class = bindec($class);
        }

        if ($tag_hex == "04" && $special1 == "81") {
            $tmp->type = 1;
        }

        // OCTET STRING = STRUCTURE
        if ($tag_hex == "04" && isset($this->universal_tag[$special2])) {
            $tmp->type = 1;
        }

        return $tmp;
    }

    /*
     *  Parsuj data dle typu
     */

    private function paserData($tag, $data)
    {
        switch ($tag) {
            case '01': // boolean
                return (ord($data) == 255) ? 1 : 0;
                break;
            case '02': // integer
                return $this->pInteger($data);
                break;
            case '03': // bit string
                return $this->pBitString($data);
                break;
            case '00': // octet string
            case '04': // octet string
            case '08': // External
            case '0A': // ENUMERATED
                return $this->pOctetString($data);
                break;
            case '06': // Object
                return $this->pObject($data);
                break;
            case '0C': // UTF8String
                return $this->pUTF8String($data);
                break;
            case '17': // UTCTime
                return $this->pUTCTime($data);
                break;
            case '18': // Generalised Time
                return $this->pGeneralisedTime($data);
                break;
            default:
                return $data;
                break;
        }
    }

    private function pInteger($data)
    {
        $hex = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $hex .= sprintf("%02X", ord($data[$i]));
        }
        return hexdec($hex);
    }

    private function pBitString($data)
    {
        $hex = '';
        $bin = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $hex .= sprintf("%02X ", ord($data[$i]));
            //$bin .= decbin(hexdec($hex));
        }
        return $hex;
    }

    private function pOctetString($data)
    {
        $hex = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $hex .= sprintf("%02X ", ord($data[$i]));
        }
        return $hex;
    }

    private function pUTF8String($data)
    {
        return $data;
    }

    private function pObject($data)
    {
        $out = '';
        $isFirst = true;
        $isLong = false;
        $longBuffer = '';
        for ($i = 0; $i < strlen($data); $i++) {

            $oid_dec = ord($data[$i]);
            $oid_hex = sprintf("%02X", $oid_dec);
            $oid_bin = decbin($oid_dec);
            $oid_bin_l = strlen($oid_bin);
            if ($oid_bin_l < 8) {
                $oid_bin = str_repeat('0', (8 - $oid_bin_l)) . $oid_bin;
            }

            //echo "\n>> ===============\n";
            //echo ">> HEX: $oid_hex, ";
            //echo "DEC: $oid_dec, ";
            //echo "BIN: $oid_bin \n\n";

            if ($isFirst) {
                // first two number
                $x = floor($oid_dec / 40);
                $y = $oid_dec % 40;

                if ($x > 2) {
                    /* Handle special case for large y if x == 2 */
                    $y += ( $x - 2 ) * 40;
                    $x = 2;
                }
                if ($x < 0 || $x > 2 || $y < 0 ||
                        ( ( $x < 2 && $y > 39 ) ||
                        ( $x == 2 && ( $y > 50 && $y != 100 ) ) )) {
                    break;
                }
                $out .= sprintf("%d.%d", $x, $y);
                $isFirst = false;
            } else {

                if ($oid_bin[0] == 1) {
                    $isLong = true;
                    $longBuffer .= substr($oid_bin, 1);
                    //echo ">> isLong = ".$oid_bin[0]."\n";
                    //echo ">> longBuffer = $longBuffer \n";
                } else {

                    if ($isLong) {
                        //echo ">> prevIsLong \n";
                        $longBuffer .= substr($oid_bin, 1);
                        //echo ">> longBuffer = $longBuffer \n";
                        $oid_dec = bindec($longBuffer);
                        //echo ">> DEC = $oid_dec \n";

                        $out .= sprintf(".%d", $oid_dec);
                        $isLong = false;
                        $longBuffer = '';
                    } else {
                        //echo ">> OUT\n";
                        $out .= sprintf(".%d", $oid_dec);
                    }
                }
            }
        }
        return self::oid_name($out, $this->OID_view);
    }

    public function setOID($type)
    {
        return $this->OID_view = $type;
    }

    private function pUTCTime($data)
    {
        $rr = substr($data, 0, 2);
        $rr = (int) ((($rr > 49 ) ? "19" : "20") . $rr);
        $mm = (int) substr($data, 2, 2);
        $dd = (int) substr($data, 4, 2);
        $hh = (int) substr($data, 6, 2);
        $ii = (int) substr($data, 8, 2);

        if (strlen($data) == 11) {
            // yymmddhhmmZ
            $ss = date('Z');
            $unixtime = mktime($hh, $ii, $ss, $mm, $dd, $rr);
        } else if (strlen($data) == 13) {
            // yymmddhhmmssZ
            $ss = (int) substr($data, 10, 2);
            $ss = $ss + date('Z');
            $unixtime = mktime($hh, $ii, $ss, $mm, $dd, $rr);
        } else if (strlen($data) == 15) {
            // yymmddhhmm+hhmm
            // yymmddhhmm-hhmm
            $tzop = substr($data, 10, 1);
            $tzhh = (int) substr($data, 11, 2);
            $tzmm = (int) substr($data, 13, 2);
            if ($tzop == "-") {
                $tz = (-1) * ( $tzhh * 60 ) + ($tzmm);
            } else {
                $tz = ( $tzhh * 60 ) + ($tzmm);
            }
            $unixtime = mktime($hh, $ii, $ss, $mm + $tz, $dd, $rr);
        } else if (strlen($data) == 17) {
            // yymmddhhmmss+hhmm
            // yymmddhhmmss-hhmm
            $tzop = substr($data, 12, 1);
            $tzhh = (int) substr($data, 13, 2);
            $tzmm = (int) substr($data, 15, 2);
            if ($tzop == "-") {
                $tz = (-1) * ( $tzhh * 60 ) + ($tzmm);
            } else {
                $tz = ( $tzhh * 60 ) + ($tzmm);
            }
            $ss = (int) substr($data, 10, 2);
            $unixtime = mktime($hh, $ii, $ss, $mm + $tz, $dd, $rr);
        } else {
            $strtime = sprintf("%02d-%02d-%02d %02d:%02d:00", $rr, $mm, $dd, $hh, $ii);
            $unixtime = mktime($hh, $ii, 0, $mm, $dd, $rr);
        }

        if (!empty($this->date_format)) {
            return date($this->date_format, $unixtime);
        } else {
            return $unixtime;
        }
    }

    private function pGeneralisedTime($data)
    {
        // 2010 06 14 09 50 05.65Z

        $rr = (int) substr($data, 0, 4);
        $mm = (int) substr($data, 4, 2);
        $dd = (int) substr($data, 6, 2);
        $hh = (int) substr($data, 8, 2);
        $ii = (int) substr($data, 10, 2);

        if (strlen($data) == 13) {
            // yyyymmddhhmmZ
            $ss = date('Z');
            $unixtime = mktime($hh, $ii, $ss, $mm, $dd, $rr);
        } else if (strlen($data) == 15) {
            // yyyymmddhhmmssZ
            $ss = (int) substr($data, 12, 2);
            $ss = $ss + date('Z');
            $unixtime = mktime($hh, $ii, $ss, $mm, $dd, $rr);
        } else if (strlen($data) == 17) {
            // yyyymmddhhmm+hhmm
            // yyyymmddhhmm-hhmm
            $tzop = substr($data, 12, 1);
            $tzhh = (int) substr($data, 13, 2);
            $tzmm = (int) substr($data, 15, 2);
            if ($tzop == "-") {
                $tz = (-1) * ( $tzhh * 60 ) + ($tzmm);
            } else {
                $tz = ( $tzhh * 60 ) + ($tzmm);
            }
            $unixtime = mktime($hh, $ii, $ss, $mm + $tz, $dd, $rr);
        } else if (strlen($data) == 19) {
            // yyyymmddhhmmss+hhmm
            // yyyymmddhhmmss-hhmm
            $tzop = substr($data, 13, 1);
            $tzhh = (int) substr($data, 15, 2);
            $tzmm = (int) substr($data, 17, 2);
            if ($tzop == "-") {
                $tz = (-1) * ( $tzhh * 60 ) + ($tzmm);
            } else {
                $tz = ( $tzhh * 60 ) + ($tzmm);
            }
            $ss = (int) substr($data, 12, 2);
            $unixtime = mktime($hh, $ii, $ss, $mm + $tz, $dd, $rr);
        } else {
            $strtime = sprintf("%02d-%02d-%02d %02d:%02d:00", $rr, $mm, $dd, $hh, $ii);
            $unixtime = mktime($hh, $ii, 0, $mm, $dd, $rr);
        }

        if (!empty($this->date_format)) {
            return date($this->date_format, $unixtime);
        } else {
            return $unixtime;
        }
    }

    public function setDateFormat($format)
    {
        $this->date_format = $format;
        return date($format);
    }

    /*
     *
     */

    public function dump($data)
    {

        $data = str_replace("\n", "", $data);

        for ($i = 0; $i < strlen($data); $i++) {
            echo sprintf("%02X ", ord($data[$i]));

            if ($i % 40 == 39 && $i != 0)
                echo "\n";
        }
    }

    /**
     * 
     * 0/1/2/*
     * 
     *
     * @param string $node 
     */
    public function getNode($node)
    {
        $tmp = $this->data;
        $direct = 0;
        $np = explode("/", $node);
        if ($np[count($np) - 1] == "*") {
            $direct = 1;
            unset($np[count($np) - 1]);
        }

        if (count($np) > 0) {
            foreach ($np as $n) {
                if (isset($tmp[$n])) {
                    $tmp = $tmp[$n];
                } else if (isset($tmp['data'][$n])) {
                    $tmp = $tmp['data'][$n];
                } else {
                    return null;
                    break;
                }
            }
        }

        if ($direct == 1) {
            return $tmp['data'];
        } else {
            return $tmp;
        }
    }

    /**
     * Prevede format PEM na format DER
     *
     * @param string $pem_data
     * @return string
     */
    private function pem2der($pem_data)
    {

        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

    private function pemraw2der($pem_data)
    {

        $der = base64_decode($pem_data);
        return $der;
    }

    /**
     * Prevede format DER na format PEM
     *
     * @param string $der_data
     * @return string
     */
    private function der2pem($der_data)
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    public static function oid_name($oid, $format = "code")
    {
        $code = array(
            '2.5.4.3' => 'commonName',
            '2.5.4.5' => 'serialNumber',
            '2.5.4.6' => 'countryName',
            '2.5.4.7' => 'localityName',
            '2.5.4.10' => 'organizationName',
            '2.5.4.11' => 'organizationUnitName',
            '2.5.4.13' => 'description',
            '2.5.29.14' => 'subjectKeyIdentifier',
            '2.5.29.15' => 'keyUsage',
            '2.5.29.17' => 'subjectAltName',
            '2.5.29.19' => 'basicConstraints',
            '2.5.29.20' => 'cRLNumber',
            '2.5.29.21' => 'cRLReason',
            '2.5.29.24' => 'invalidityDate',
            '2.5.29.31' => 'cRLDistributionPoints',
            '2.5.29.32' => 'certificatePolicies',
            '2.5.29.35' => 'authorityKeyIdentifier',
            '2.5.29.37' => 'extKeyUsage',
            '1.2.840.113549.1.1.1' => 'rsaEncryption',
            '1.2.840.113549.1.1.5' => 'sha1withRSAEncryption',
            '1.2.840.113549.1.7.2' => 'signedData',
            '1.2.840.113549.1.9.3' => 'contentType',
            '1.2.840.113549.1.9.4' => 'messageDigest',
            '1.3.6.1.5.5.7.2.1' => 'cps',
            '1.3.6.1.5.5.7.2.2' => 'unotice',
            '1.3.6.1.5.5.7.3.8' => 'timeStamping',
            '1.3.14.3.2.26' => 'sha1',
        );

        if ($format == 'name') {
            if (isset($code[$oid])) {
                return $code[$oid];
            } else {
                return $oid;
            }
        } else if ($format == 'both') {
            if (isset($code[$oid])) {
                return $code[$oid] . " : " . $oid;
            } else {
                return $oid;
            }
        } else {
            return $oid;
        }
    }

}
