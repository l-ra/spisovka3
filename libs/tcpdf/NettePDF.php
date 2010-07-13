<?php

require_once(LIBS_DIR .'/tcpdf/config/lang/eng.php');
require_once(LIBS_DIR .'/tcpdf/tcpdf.php');
require_once(LIBS_DIR .'/tcpdf/fpdi/fpdi.php');

class NettePDF extends FPDI {
    /**
     * "Remembers" the template id of the imported page
     */
    protected $_tplIdx;

    protected $input_pdf = null;

    /**
     * include a background template for every page
     */
    function Header() {

        if ( !is_null($this->input_pdf) ) {
            if (is_null($this->_tplIdx)) {
                $this->setSourceFile($this->input_pdf);
                $this->_tplIdx = $this->importPage(1);
            }
            $this->useTemplate($this->_tplIdx);

        }
    }

    function Footer() {}

    public function inputPDF($file) {
        $this->input_pdf = $file;
        return true;
    }

    public function signed($pdfdoc, $cert, $byterange_string, $signature_max_length) {
        // *** apply digital signature to the document ***
	// remove last newline
	$pdfdoc = substr($pdfdoc, 0, -1);
	$byterange_string_len = strlen($byterange_string);
        // define the ByteRange
	$byte_range = array();
	$byte_range[0] = 0;
	$byte_range[1] = strpos($pdfdoc, $byterange_string) + $byterange_string_len + 10;
	$byte_range[2] = $byte_range[1] + $signature_max_length + 2;
	$byte_range[3] = strlen($pdfdoc) - $byte_range[2];
	$pdfdoc = substr($pdfdoc, 0, $byte_range[1]).substr($pdfdoc, $byte_range[2]);
	// replace the ByteRange
	$byterange = sprintf('/ByteRange[0 %u %u %u]', $byte_range[1], $byte_range[2], $byte_range[3]);
	$byterange .= str_repeat(' ', ($byterange_string_len - strlen($byterange)));
	$pdfdoc = str_replace($byterange_string, $byterange, $pdfdoc);
	// write the document to a temporary folder
	$tempdoc = tempnam(K_PATH_CACHE, 'tmppdf_');
	$f = fopen($tempdoc, 'wb');
	if (!$f) {
            $this->Error('Unable to create temporary file: '.$tempdoc);
	}
	$pdfdoc_length = strlen($pdfdoc);
	fwrite($f, $pdfdoc, $pdfdoc_length);
	fclose($f);
	// get digital signature via openssl library
	$tempsign = tempnam(K_PATH_CACHE, 'tmpsig_');
	if (empty($this->signature_data['extracerts'])) {
            openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED);
	} else {
            openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED, $this->signature_data['extracerts']);
	}
	unlink($tempdoc);
	// read signature
	$signature = file_get_contents($tempsign, false, null, $pdfdoc_length);
	unlink($tempsign);
	// extract signature
	$signature = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));
	$tmparr = explode("\n\n", $signature);
	$signature = $tmparr[1];
	unset($tmparr);
	// decode signature
	$signature = base64_decode(trim($signature));
	// convert signature to hex
	$signature = current(unpack('H*', $signature));
	$signature = str_pad($signature, $signature_max_length, '0');
	// Add signature to the document
	$pdfdoc = substr($pdfdoc, 0, $byte_range[1]).'<'.$signature.'>'.substr($pdfdoc, ($byte_range[1]));
        return $pdfdoc;
    }

}
