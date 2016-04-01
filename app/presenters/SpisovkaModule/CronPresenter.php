<?php

// Trida NESMI dedit z BasePresenteru (kvuli autentizaci)
// 2015-10-26  to uz zrejme neplati, protoze ve spisovce 3.5.0 se session
// startuje automaticky, ne az pri kontrole prihlaseni
class Spisovka_CronPresenter extends Nette\Application\UI\Presenter
{

    protected $tasks = [
        'UpdateAgent',
        'SurveyAgent',
        'CleanSessionFiles',
    ];

    /**
     * file handle 
     * @var resource   
     */
    protected $lock;

    /**
     *   Ziska zamek. Pripadne chyby funkce ignoruje.
     */
    protected function acquireLock()
    {
        $filename = TEMP_DIR . '/cron.lock';
        $this->lock = fopen($filename, 'w');
        flock($this->lock, LOCK_EX);
    }

    protected function releaseLock()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
    }

    public function actionSpustit()
    {
        $this->getHttpResponse()->setContentType('text/plain', 'utf8');
        ob_end_clean();

        // odemkni session soubor, neblokuj ostatni pozadavky
        $this->getSession()->close();

        echo "waiting for lock\n";
        flush();
        $this->acquireLock();

        try {
            $last_run = Settings::get('cron_last_run', 0);
            $now = time();
            if ($now - $last_run > 10 * 60) {
                // kazdych 10 minut
                Settings::set('cron_last_run', $now);
                $this->_run();
            } else
                echo 'nothing to do';
        } catch (Exception $e) {
            $this->releaseLock();
            throw $e;
        }

        $this->releaseLock();
        $this->terminate();
    }

    protected function _run()
    {
        foreach ($this->tasks as $task) {
            $name = $task;
            $function = 'task' . $name;
            echo "Task $task ";
            flush();
            try {
                $result = $this->$function();
                if ($result !== null)
                    echo $result ? 'OK' : 'error';
            } catch (Exception $e) {
                $msg = $e->getMessage();
                echo 'exception: ' . $msg;
            }
            echo "\n";
        }

        echo 'finished';
        flush();
    }

    protected function taskUpdateAgent()
    {
        /* Kontrola novych zprav z webu */
        UpdateAgent::update(UpdateAgent::CHECK_NOTICES);

        /* Kontrola nove verze */
        UpdateAgent::update(UpdateAgent::CHECK_NEW_VERSION);
    }

    /**
     * 
     * @return boolean
     */
    protected function taskCleanSessionFiles()
    {
        $session = $this->getSession();
        $directory = $session->options['savePath'];
        $directory = rtrim($directory, '/');

        $dir_handle = opendir($directory);
        if ($dir_handle === FALSE)
            return false;

        while (($filename = readdir($dir_handle)) !== false)
            if (substr($filename, 0, 5) === 'sess_') {
                $last = filemtime("$directory/$filename");
                if (!$last)
                    continue;
                $now = time();
                if ($now - $last > 14 * (24 * 60 * 60)) // 2 tydny
                    unlink("$directory/$filename");
            }

        closedir($dir_handle);
        return true;
    }

    protected function taskSurveyAgent()
    {
        // Zjisti, kdy naposledy byly odeslany informace o uzivateli a po uplynuti urciteho intervalu je odesli znovu
        $send = true;
        $params = $this->context->parameters;
        if (isset($params['send_survey']))
            $send = $params['send_survey'];

        if ($send) {
            $last_run = Settings::get('survey_agent_last_run', 0);
            $now = time();
            if ($now - $last_run > 15 * (24 * 60 * 60)) {
                Settings::set('survey_agent_last_run', $now);
                SurveyAgent::send();
            }
        }
    }

}
