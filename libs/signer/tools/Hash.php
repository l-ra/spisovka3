<?php
/**
 * This file defines an own hash()-function if not available.
 * 
 * The hash()-function is needed for AES-256bit en/decryption.
 * 
 * In PHP5 the hash()-function is enabled by default as of PHP 5.1.2
 * For PHP4 you can use the mhash()-function if available or use a
 * native php implementation of the sha256-algorithm.
 */

if (!function_exists('hash') && extension_loaded('mhash')) {
    function hash($algo, $data, $raw_output=false) {
        switch ($algo) {
            case 'sha256':
                $hashStr = mhash(MHASH_SHA256, $data);
                if ($raw_output)
                    return $hashStr;
                else 
                    return current(unpack("H*",$hashStr));
                
            default:
                trigger_error(sprintf('hash(): Unknown hashing algorithm: %s', $algo), E_USER_WARNING);
                break;
        }
    }
} /* else if (!function_exists('hash')) {
    function hash($algo, $data, $raw_output=false) {
        switch ($algo) {
            case 'sha256':
                // implement your own sha256 function here
                
            default:
                trigger_error(sprintf('hash(): Unknown hashing algorithm: %s', $algo), E_USER_WARNING);
                break;
        }
    }
} */