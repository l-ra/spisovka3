<?php

class HttpClient
{

    /** Performs HTTP request and returns its result
     * 
     * @param string $url
     * @param int $timeout
     * @return string
     * @throws Exception
     */
    public static function get($url, $timeout = 10)
    {
        if (function_exists('curl_init'))
            return self::get_cURL($url, $timeout);

        if (ini_get("allow_url_fopen"))
            return self::get_stream($url, $timeout);

        throw new Exception(__METHOD__ . '() - Chybí implementace HTTP komunikace.');
    }

    private static function get_stream($url, $timeout)
    {
        $context = stream_context_create(['http' => ['timeout' => $timeout]]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            $msg = $error['message'];
            if (stripos($msg, 'timed out') !== false)
                $msg = 'Vypršel časový limit pro operaci.';
            
            throw new Exception($msg);
        }

        return $response;
    }

    private static function get_cURL($url, $timeout)
    {
        if (!($ch = curl_init($url)))
            throw new Exception('Funkce curl_init() neproběhla úspěšně.');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        $errno = curl_errno($ch);
        $errmsg = $errno ? curl_error($ch) : '';
        $success = ($errno === 0) && (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200);
        curl_close($ch);

        if ($errno == CURLE_OPERATION_TIMEDOUT)
            throw new Exception('Vypršel časový limit pro operaci.');
        else if ($errno)
            throw new Exception('cURL chyba: ' . $errmsg);

        return $success ? $response : null;
    }

}
