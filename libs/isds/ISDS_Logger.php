<?php

class ISDS_Logger
{

    private $handle;
    private $level;
    
    public function __construct($level)
    {
        $this->level = $level;
        $this->handle = fopen(self::getFilename(), 'a');
        if (!$this->handle)
            throw new Exception (__METHOD__ . '() - nemohu otevřít soubor s protokolem');
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function log($text, $level = 1)
    {
        if ($this->level >= $level)
            fwrite($this->handle, $text);
    }

    static public function getFilename()
    {
        $client = KLIENT;
        return LOG_DIR . "/isds_$client.log";        
    }
}
