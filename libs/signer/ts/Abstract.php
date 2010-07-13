<?php

require_once(LIBS_DIR .'/signer/tools/Asn1/Type.php');

abstract class SetaPDF_Signer_Module_Ts_Abstract {
    
    /**
     * The original signature
     *
     * @var string
     */
    protected $_signature = '';
    
    /**
     * The original signature in object form
     *
     * @var Asn1_Type
     */
    protected $_parsedSignature = null;
    
    /**
     * The value for reqPolicy in TS request
     *
     * @var string
     */
    protected $_reqPolicy = null;
    
    /**
     * Defines if the nonce value should be included in the TS request
     *
     * @var boolean
     */
    protected $_nonce = true;
    
    /**
     * The value of the nonce value
     *
     * @var string
     */
    protected $_nonceValue = null;
    
    /**
     * Defines the value of the certReq value
     *
     * @var string
     */
    protected $_certReq = "\xFF";
    
    /**
     * Set the signature string
     *
     * @param string $signature
     */
    public function setSignature($signature) {
        $this->_signature = $signature;
        $this->_parsedSignature = null;
    }
    
    /**
     * Get the signature string
     *
     * @return unknown
     */
    public function getSignature() {
        return $this->_signature;
    }
    
    /**
     * Gets the signature in object form
     *
     * @return Asn1_Type
     */
    public function getParsedSignature() {
        if (is_null($this->_parsedSignature)) {
            $this->_parsedSignature = Asn1_Type::parse($this->_signature);
        }
        return $this->_parsedSignature;
    }
    
    /**
     * Gets the hash for the timestamp request
     *
     * @return string
     */
    public function getHash() {
        $hash = Asn1_Type::findByPath('1/0/4/0/5', $this->getParsedSignature());
        //$hash = $hash->getValue();
        $hash = hash('sha256', (string)$hash, true);
        $hash = sha1((string)$hash, true);
        return $hash;
    }
    
    /**
     * Set the reqPolicy value / OID
     *
     * @param string $reqPolicy
     */
    public function setReqPolicy($reqPolicy) {
        $this->_reqPolicy = $reqPolicy;
    }
    
    /**
     * Get the reqPolicy value / OID
     *
     * @return string
     */
    public function getReqPolicy() {
        return $this->_reqPolicy;
    }
    
    /**
     * Define if the nonce value should be set or not
     *
     * @param boolean $nonce
     */
    public function setNonce($nonce) {
        $this->_nonce = (boolean)$nonce;
    }
    
    /**
     * Queries if nonce should be set
     *
     * @return boolean
     */
    public function getNonce() {
        return $this->_nonce;
    }
    
    /**
     * Set the certReq value
     *
     * @param boolean $certReq
     */
    public function setCertReq($certReq) {
        $this->_certReq = $certReq ? "\xFF" : "\x00";
    }
    
    /**
     * Get the certReq value
     *
     * @return boolean
     */
    public function getCertReq() {
        return $this->_certReq;    
    }
    
    /**
     * Create the timestamp request 
     *
     * @return string The timestamp request in BER format
     */
    protected function _getTsq() {
        $data = array(
            new Asn1_Type(Asn1_Type::INTEGER, "\x01"),
            // MessageImprint
            new Asn1_Type(Asn1_Type::IS_CONSTRUCTED | Asn1_Type::SEQUENCE, '', array(
                // hashAlgorithm
                new Asn1_Type(Asn1_Type::IS_CONSTRUCTED | Asn1_Type::SEQUENCE, '', array(
                    new Asn1_Type(Asn1_Type::OBJECT_IDENTIFIER, Asn1_Type::encodeOid('1.3.14.3.2.26')), // SHA1
                    // new Asn1_Type(Asn1_Type::OBJECT_IDENTIFIER, Asn1_Type::encodeOid('2.16.840.1.101.3.4.2.1')), // SHA256
                    new Asn1_Type(Asn1_Type::NULL)
                )),
                // hashedMessage
                new Asn1_Type(Asn1_Type::OCTET_STRING, $this->getHash())
            )),
        );
        
        // reqPolicy: optional
        if ($this->_reqPolicy) {
            $data[] = new Asn1_Type(Asn1_Type::OBJECT_IDENTIFIER, Asn1_Type::encodeOid($this->_reqPolicy));
        }
        // nonce: optional
        if ($this->_nonce) {
            $this->_nonceValue = sprintf('%c%c%c%c%c%c%c%c', rand(0,256), rand(0,256), rand(0,256), rand(0,256), rand(0,256), rand(0,256), rand(0,256), rand(0,256));
            $data[] = new Asn1_Type(Asn1_Type::INTEGER, $this->_nonceValue);
        }
        // certReq : optional
        $data[] = new Asn1_Type(Asn1_Type::BOOLEAN, $this->_certReq);
        
        $TimeStampReq = new Asn1_Type(Asn1_Type::IS_CONSTRUCTED | Asn1_Type::SEQUENCE, '', $data);
        
        return ((string)$TimeStampReq->__toString());
    }
    
    /**
     * Mix the timestamp token with the original signature and return it.
     *
     * @param Asn1_Type $tsToken
     * @return Asn1_Type
     */
    protected function _getFinalSignature(Asn1_Type $tsToken) {
        $tsEntry = new Asn1_Type(Asn1_Type::SET | Asn1_Type::IS_CONSTRUCTED, $tsToken->__toString());
        
        $target = Asn1_Type::findByPath('1/0/4/0/', $this->_parsedSignature);
        
        $target->addChild(
            new Asn1_Type(Asn1_Type::TAG_CLASS_CONTEXT_SPECIFIC | Asn1_Type::IS_CONSTRUCTED | "\x01", '', array(
                new Asn1_Type(Asn1_Type::SEQUENCE | Asn1_Type::IS_CONSTRUCTED, '', array(
                    new Asn1_Type(Asn1_Type::OBJECT_IDENTIFIER, Asn1_Type::encodeOid('1.2.840.113549.1.9.16.2.14')),
                    $tsEntry         
                ))
            ))
        );
        
        return $this->_parsedSignature;
    }
    
    /**
     * Will be called when ever the module is attached to the Signer class
     *
     * @param SetaPDF_Signer $signer
     */
    public function onSet(SetaPDF_Signer &$signer) {
        // make sure there's enough space
        $signer->setSignatureContentMinLength(10500);
    }
    
    /**
     * This method has to be implemented to create the timestamp
     * 
     * @return string The final signature in BER format
     */
    abstract public function createTimeStamp();
    
}