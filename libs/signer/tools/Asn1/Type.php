<?php
/**
 * ASN.1 node / BER Encoder + Decoder
 *
 * A PHP class representing a single ASN.1 node
 * Add. Features:
 *  - parsing and writing of BER encoded values
 *  - interacting with child and parent nodes
 *  - helper functions to find nodes
 * 
 * @todo BIT_STRING parsing/writing is errorious. The values are still valid 
 *       but you cannot work with them.
 */
class Asn1_Type {

    /**
     * Class constants
     */
    const TAG_CLASS_MASK                = "\xC0";
    const TAG_CLASS_UNIVERSAL           = "\x00";
    const TAG_CLASS_APPLICATION         = "\x40";
    const TAG_CLASS_CONTEXT_SPECIFIC	= "\x80";
    const TAG_CLASS_PRIVATE             = "\xC0";
    
    /**
     * Constructed?
     */
    const IS_CONSTRUCTED                = "\x20";
    
    /**
     * Tag mask
     */
    const TAG_MASK                      = "\x1F";
    
    /**
     * Subtypes
     */
    const BOOLEAN                       = "\x01";
    const INTEGER                       = "\x02";
    const BIT_STRING                    = "\x03";
    const OCTET_STRING                  = "\x04";
    const NULL                          = "\x05";
    const OBJECT_IDENTIFIER 	        = "\x06";
    const OBJECT_DESCRIPTOR	            = "\x07";
    const EXTERNAL			            = "\x08";
    const REAL				            = "\x09";
    const ENUMERATED			        = "\x0A";
    const UTF8_STRING                   = "\x0C";
    const RELATIVE_OID                  = "\x0D";
    const SEQUENCE			            = "\x10";
    const SET 				            = "\x11";
    const NUMERIC_STRING                = "\x12";
    const PRINTABLE_STRING              = "\x13";
    const T61_STRING                    = "\x14";
    const VIDEOTEXT_STRING              = "\x15";
    const IA5_STRING                    = "\x16";
    const UTC_TIME                      = "\x17";
    const GENERALIZED_TIME              = "\x18";
    const GRAPHIC_STRING                = "\x19";
    const VISIBLE_STRING                = "\x1A";
    const GENERAL_STRING                = "\x1B";
    const UNIVERSAL_STRING              = "\x1C";
    const BMPSTRING                     = "\x1E";
    
    /**
     * The ident tag
     *
     * @var byte
     */
    protected $_ident = "\x00";
    
    /**
     * The byte value
     *
     * @var string|bytes
     */
    protected $_value = '';
    
    /**
     * Array of child nodes
     *
     * @var array Array of Asn1_Type
     */
    protected $_children = array();
    
    /**
     * The parent node
     *
     * @var Asn1_Type
     */
    protected $_parent = null;
    
    /**
     * Parses a BER encoded string
     *
     * @param string $s
     * @return Asn1_Type|array
     */
    static public function parse($s) {
        $result = array();
        
        $p = 0;
        $sLenght = strlen($s);
        
        while ($p < $sLenght) {
            $ident = $s[$p++];
            $subType = $ident & self::TAG_MASK;
            
            $length = ord($s[$p++]);
            if (($length & 128) == 128) {
    			$tempLength = 0;
    			for ($x=0; $x < ($length & (128-1)); $x++){
    				$tempLength = ord($s[$p++]) + ($tempLength * 256);
    			}
    			$length = $tempLength;
    		}
    		$value = (string)substr($s, $p, $length);
    		
    		switch ($subType) {
    		    case (($ident & self::IS_CONSTRUCTED) != "\x00"):
    		    case self::SEQUENCE:
                case self::SET:
                    $result[] = new self($ident, '', self::parse($value));
                    break;
    		    default:
    		        $result[] = new self($ident, $value);
    		}
    		
    		$p = $p + $length;
        }
		
        if (count($result) == 1)
            return current($result);
        return $result;
    }
    
    /**
     * Decodes an OID
     *
     * @param string $oid OID in binary form
     * @return string The OID in dot form
     */
    static public function decodeOid($oid) {
        $oidStr = '';
        $p = 0;
        $len = strlen($oid);
        $b = ord($oid[$p++]);
        
        $oidStr .= (string)((int)($b/40));
        $oidStr .= '.' . (string)((int)($b%40));
        
        while ($p < $len) {
            $v = self::_decodeOidValue($oid, $p);
            $oidStr .= '.' . $v;
        }
        
        return $oidStr;
    }
    
    static protected function _decodeOidValue($oid, &$p) {
        $v = 0;
        while (1) {
            $b = ord($oid[$p++]);
            $v <<= 7;
            $v += ($b & 0x7f);
            if (($b & 0x80) == 0)
                return $v;
        }
    }
    
    /**
     * Encodes an OID
     *
     * @param string $oidStr OID in dot form
     * @return string OID in binary form
     */
    static public function encodeOid($oidStr) {
        $pieces = explode(".", $oidStr);
		$oid = chr(40 * $pieces[0] + $pieces[1]);
		for ($i = 2, $len = count($pieces); $i < $len; $i++) {
			$current = (int)$pieces[$i];
			if ($current-1 > 0x80){
				$add = chr($current % 0x80);
				$current = floor($current / 0x80);
				while ($current > 127){
					$add = chr(($current % 0x80) | 0x80).$add;
					$current = floor($current / 0x80);
				}
				$add = chr(($current % 0x80) | 0x80).$add;
				$oid .= $add;
			} else {
				$oid .= chr($current);
			}
		}
		return $oid;
    }
    
    /**
     * Finds a node by OID
     *
     * @param string $oid OID in dot form
     * @param Asn1_Type $value Root node to start from
     * @return Asn1_Type|null
     */
    static public function findByOid($oid, Asn1_Type $value) {
        $ident = $value->getIdent();
        $subType = $ident & self::TAG_MASK;
        
        switch ($subType) {
            case (($ident & self::IS_CONSTRUCTED) != "\x00"):
		    case self::SEQUENCE:
            case self::SET:
                foreach ($value->getChildren() as $child) {
                	$res = self::findByOid($oid, $child);
                	if ($res instanceof self)
                	    return $res;
                }
                
                break;
            case (($ident & self::OBJECT_IDENTIFIER) == self::OBJECT_IDENTIFIER):
                if (self::decodeOid($value->getValue()) == $oid) {
                    return $value;
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Finds a node by a path
     *
     * @param string $path A string defining the path of the node (e.g. 1/2/3/5)
     * @param Asn1_Type $value
     * @return Asn1_Type|boolean
     */
    static public function findByPath($path, Asn1_Type $value) {
        $path = trim($path, '/');
        $path = explode('/', $path);
        $tmp = $value;
        foreach ($path as $childId) {
            $tmp = $tmp->getChild($childId);
        	if ($tmp === false)
                return $tmp;
        }
        return $tmp;
    }
    
    /**
     * Returns the length byte in DER encoding
     *
     * @param integer $length
     * @return string
     */
    static public function lengthToDer($length) {
        if ($length < 128) {
            return chr($length);
        } else {
            $out = '';
			while ($length >= 256) {
			    $out = chr($length % 256) . $out;
				$length = floor($length / 256);
			}
			$out = chr($length).$out;
			$out = chr(128 | strlen($out)).$out;
			return $out;
        }
    }
    
    /**
     * Constructor
     *
     * @param string|byte $ident The identifier byte
     * @param string $value The value in binary form
     * @param array $children Array of Asn1_Type instances
     */
    public function __construct($ident, $value='', $children = array()) {
        $this->setIdent($ident);
        $this->setValue($value);
        $this->setChildren($children);
    }
    
    /**
     * Set the identifier byte
     *
     * @param string|byte $ident
     */
    public function setIdent($ident) {
        $this->_ident = $ident;
    }
    
    /**
     * Get the identifier byte
     *
     * @return string|byte
     */
    public function getIdent() {
        return $this->_ident;
    }
    
    /**
     * Set the value
     *
     * @param string $value Value in binary form
     */
    public function setValue($value) {
        $this->_value = $value;
    }
    
    /**
     * Get the value
     *
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }
    
    /**
     * Set child nodes
     *
     * @param array $children Array of Asn1_Type instances
     */
    public function setChildren($children = array()) {
        if (!is_array($children))
            $children = array($children);
            
        $this->_children = array();
        foreach ($children AS $child) {
            $this->addChild($child);
        }
    }
    
    /**
     * Get child nodes
     *
     * @return array Array of Asn1_Type
     */
    public function getChildren() {
        return $this->_children;
    }
     
    /**
     * Add a child node
     *
     * @param Asn1_Type $child
     */
    public function addChild(Asn1_Type $child) {
        $this->_children[] = $child;
        $child->setParent($this);
    }
    
    /**
     * Remove a child node
     *
     * @param Asn1_Type $child
     * @return boolean
     */
    public function removeChild(Asn1_Type $child) {
        foreach ($this->_children AS $k => $_child) {
            if ($child === $_child) {
                unset($this->_children[$k]);
                $this->_children = array_values($this->_children);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get a child by Id
     *
     * @param integer $id
     * @return Asn1_Type|boolean
     */
    public function getChild($id) {
        if (array_key_exists($id, $this->_children))
            return $this->_children[$id];
        return false;
    }
    
    /**
     * Set parent node
     *
     * @param self $parent
     */
    public function setParent(self $parent) {
        $this->_parent = $parent;
    }
    
    /**
     * Get parent
     *
     * @return Asn1_Type
     */
    public function getParent() {
        return $this->_parent;
    }
    
    /**
     * Returns the BER encoded string
     *
     * @return string
     */
    public function __toString() {
        $value = '';
        
        $subType = $this->_ident & self::TAG_MASK;
        
        switch ($subType) {
            case (($this->_ident & self::IS_CONSTRUCTED) != "\x00"):
		    case self::SEQUENCE:
            case self::SET:
                if (count($this->_children) > 0) {
                    foreach ($this->_children as $child) {
                    	$value .= (string)$child->__toString();
                    }
                } else {
                    $value .= (string)$this->_value;
                }
                break;
            default:
                $value = $this->_value;
        }
        
        $length = self::lengthToDer(strlen($value));
        
        return $this->_ident.$length.$value;
    }
}