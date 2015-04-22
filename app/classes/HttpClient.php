<?php

class HttpClient {

    public static function get($url) {
              
        if ( !function_exists('curl_init') )
            return null;
            
        if ( !($ch = curl_init($url)) )     
            return null;
                        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_TIMEOUT , 10); 

        $response = curl_exec($ch);
        $success = (curl_errno($ch) == 0) && (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200);
        curl_close($ch);

        return $success ? $response : null;
    }
}

