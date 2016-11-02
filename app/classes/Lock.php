<?php

namespace Spisovka;

/**
 * Implementation of mutex semaphore
 *
 * @author Pavel Laštovička
 */
class Lock
{

    /**
     * file handle 
     * @var resource   
     */
    protected $lock;
    protected $filename;

    /**
     * Delete lock file after releasing the lock?
     * @var boolean 
     */
    public $delete_file = false;

    public function __construct($name)
    {
        $filename = TEMP_DIR . "/$name.lock";
        $this->filename = $filename;

        $this->lock = fopen($filename, 'w');
        chmod($filename, 0600);
        $ok = flock($this->lock, LOCK_EX);
        if (!$ok)
            throw new Exception("Nepodařilo se získat zámek na souboru $filename.");
    }

    public function __destruct()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
        if ($this->delete_file)
            unlink($this->filename);
    }

}
