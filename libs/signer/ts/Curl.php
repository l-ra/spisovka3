<?php

require_once(LIBS_DIR .'/signer/ts/Abstract.php');
        
class SetaPDF_Signer_Module_Ts_Curl extends SetaPDF_Signer_Module_Ts_Abstract {
    
    /**
     * User agent send in timestamp request
     * 
     * Required by most TS servers
     *
     * @var string
     */
    public $userAgent = 'SetaPDF-Signer API TS-Module/1.0';
    
    /**
     * The timestamp server URL
     *
     * @var string
     */
    protected $_url = '';
    
    /**
     * Username for http-auth
     *
     * @var string
     */
    protected $_username = null;
    
    /**
     * The password for http-auth
     *
     * @var unknown_type
     */
    protected $_password = '';
    
    /**
     * Value for the curl option CURLOPT_SSL_VERIFYPEER
     *
     * @var boolean
     */
    protected $_verifyPeer = true;
    
    /**
     * Value for the curl option CURLOPT_SSL_VERIFYHOST
     *
     * @var boolean
     */
    protected $_verifyHost = true;
    
    /**
     * Value for the curl option CURLOPT_CAINFO
     *
     * @var string
     */
    protected $_caInfo = null;
    
    /**
     * The ASN.1 object from the last response
     *
     * @var Asn1_Type
     */
    static public $lastResponse = null;
    
    /**
     * The constructor
     *
     * @param string $url URL of the timestamp server
     * @param string $username Username if server requires http-auth
     * @param password $password Password if server requires http-auth
     */
    public function __construct($url, $username=null, $password='') {
        $this->_url = $url;
        $this->_username = $username;
        $this->_password = $password;
    }
    
    /**
     * Sets some curl options
     *
     * @param boolean $verifyPeer Value for CURLOPT_SSL_VERIFYPEER
     * @param boolean $verifyHost Value for CURLOPT_SSL_VERIFYHOST
     * @param string $caInfo Value for CURLOPT_CAINFO
     */
    public function setCurlOpts($verifyPeer=true, $verifyHost=true, $caInfo=null) {
        $this->_verifyPeer = (boolean)$verifyPeer;
        $this->_verifyHost = (boolean)$verifyHost;
        $this->_caInfo     = $caInfo;
    }
    
    /**
     * Creates the timestamp
     * 
     * This method sends the request to the timestamp server and merges the resulting
     * timestamp into the main signature container and returns it.
     *
     * @return string
     */
    public function createTimeStamp() {
        
        $bTimeStampReq = $this->_getTsq();
        $cURL = curl_init();
        
        curl_setopt($cURL, CURLOPT_URL, $this->_url);
        if (!is_null($this->_username)) {
            curl_setopt($cURL, CURLOPT_USERPWD, $this->_username.':'.$this->_password); 
        }
        
        curl_setopt($cURL, CURLOPT_USERAGENT, $this->userAgent);
        
        curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/timestamp-query",
            "Accept: application/timestamp-reply",
            "Content-Length: ".strlen($bTimeStampReq),
            "Pragma: no-cache"
        ));
        
        curl_setopt($cURL, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1); 
        
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $bTimeStampReq);
        curl_setopt($cURL, CURLOPT_POST, 1);
        
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer); 
        curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, $this->_verifyHost);
        if (!is_null($this->_caInfo)) {
            curl_setopt($cURL, CURLOPT_CAINFO, $this->_caInfo);
        }
        
        $tsr = curl_exec($cURL);
        if($tsr === false) {
            $error = curl_error($cURL);
            curl_close($cURL);
            return new SetaPDF_Error($error, E_SETAPDF_SIG_CURL_ERROR);
        }
        
        $httpStatus = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
        if ($httpStatus != '200') {
            curl_close($cURL);
            return new SetaPDF_Error(
                sprintf('Timestamp server (%s) returned HTTP status: %s', $this->_url, $httpStatus),
                E_SETAPDF_SIG_CURL_RES_ERROR
            );
        }
        
        curl_close($cURL);
        
        $result = Asn1_Type::parse($tsr);
        self::$lastResponse = $result;
        
        $pkiStatusInfo = $result->getChild(0);
        $status = ord($pkiStatusInfo->getChild(0)->getValue());
        
        if ($status != 0) {
            return new SetaPDF_Error(
                sprintf('Timestamp response returned status flag %s', $status),
                E_SETAPDF_SIG_TS_ERROR
            );
        }
        
        $tsToken = $result->getChild(1);
        
        $res = $this->_getFinalSignature($tsToken);
        return $res->__toString();
    }
}