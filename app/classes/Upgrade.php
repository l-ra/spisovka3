<?php

final class Upgrade
{

    const SETTINGS_TASKS = 'upgrade_finished_tasks';
    const SETTINGS_NEEDED = 'upgrade_needed';

    static $tasks = ['EmailMailbox' => 'změna souborů s e-maily v e-podatelně do mailbox formátu'];

    public function check()
    {
        $need = Settings::get(self::SETTINGS_NEEDED, false);
        if ($need)
            $this->perform();
    }

    public function perform()
    {
        if (!$this->acquireLock()) {
            // pokud nebude fungovat zámek, nastane problém. upgrade se raději neprovede, než
            // riskovat poškození aplikace
            return;
        }

        try {
            $done = Settings::get(self::SETTINGS_TASKS);
            $done = $done ? explode(',', $done) : [];

            foreach (array_keys(self::$tasks) as $name) {
                if (in_array($name, $done))
                    continue;

                $function = 'upgrade' . $name;
                $this->$function();

                $done[] = $name;
                Settings::set(self::SETTINGS_TASKS, implode(',', $done));
            }
        } catch (Exception $e) {
            $this->releaseLock();
            throw $e;
        }

        Settings::set(self::SETTINGS_NEEDED, false);
        $this->releaseLock();
    }

    /**
     * file handle 
     * @var resource   
     */
    protected $lock;
    protected $lock_filename;

    /**
     * @return boolean  success
     */
    protected function acquireLock()
    {
        $filename = TEMP_DIR . '/upgrade.lock';
        $this->lock_filename = $filename;
        $this->lock = fopen($filename, 'w');
        return flock($this->lock, LOCK_EX);
    }

    protected function releaseLock()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
        unlink($this->lock_filename);
    }

    private function upgradeEmailMailbox()
    {
        $storage = Nette\Environment::getService('storage');
        $file_model = new FileModel();

        $res = dibi::query("SELECT [file_id] FROM [:PREFIX:epodatelna] WHERE [typ] = 'E' AND [odchozi] = 0");
        $processed = 0;
        foreach ($res as $row)
            if ($row->file_id) {
                $file = $file_model->getInfo($row->file_id);
                $filename = $storage->getFilePath($file);

                $handle = fopen($filename, 'r+b');
                if (!$handle)
                    continue;
                $start = fread($handle, 5);
                if ($start == 'From ') {
                    // already converted
                    fclose($handle);
                    continue;
                }

                rewind($handle);
                $data = fread($handle, filesize($filename));
                rewind($handle);
                fwrite($handle, "From unknown  Sat Jan  1 00:00:00 2000\r\n");
                fwrite($handle, $data);
                fclose($handle);
                $processed++;
            }

        dump(__METHOD__ . "() - $processed e-mails converted");
    }

}
