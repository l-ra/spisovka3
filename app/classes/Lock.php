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
        if (!$this->lock)
            throw new \Exception(__METHOD__ . "() - Nemohu vytvořit soubor $this->filename.");
        chmod($filename, 0600);
        $this->lock();
    }

    protected function lock()
    {
        $ok = flock($this->lock, LOCK_EX);
        if (!$ok)
            throw new \Exception(__METHOD__ . "() - Nepodařilo se získat zámek na souboru $this->filename.");
    }

    public function __destruct()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
        if ($this->delete_file)
            @unlink($this->filename); // záměrně @ - soubor mohl být už automaticky smazán při volání fclose
    }

}

class LockNotBlocking extends Lock
{

    protected function lock()
    {
        $would_block = null;
        $ok = flock($this->lock, LOCK_EX | LOCK_NB, $would_block);
        if (!$ok)
            if ($would_block)
                throw new WouldBlockException();
            else
                throw new \Exception(__METHOD__ . "() - Nepodařilo se získat zámek na souboru $this->filename.");
    }

}

class WouldBlockException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Operace by blokovala aplikaci.');
    }

}
