<?php

/**
 * AdminModule/presenters/SupportPresenter.php
 *
 * Presenter pro shromáždění informací pro technickou podporu
 */
class Admin_SupportPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return $this->user->isInRole('admin');
    }

    public function renderDefault()
    {
        echo '<pre>';
        echo "Informace pro technickou podporu\n";
        echo "================================\n\n";

        $app_info = new VersionInformation();
        $verze = $app_info->name;
        echo "Verze aplikace:  $verze\n\n";

        echo "Maximální velikost nahraného souboru:  ";
        $max = DokumentPrilohy::maxVelikostUploadu(true);
        echo "$max\n\n";

        echo "Nastavení uložená v databázi:\n";
        echo "-----------------------------\n\n";
        $db_settings = Settings::getAll();
        unset($db_settings['epodatelna']); // Zobrazime pozdeji
        foreach ($db_settings as $key => $val) {
            if ($val === true)
                $val = 'true';
            if ($val === false)
                $val = 'false';            
            printf("%-42s  %s\n", $key, $val);
        }
        echo "\n";

        echo "Nastavení uživatele:\n";
        echo "--------------------\n\n";
        $user_settings = UserSettings::getAll();
        ksort($user_settings);
        foreach ($user_settings as $key => $val) {
            if ($val === true)
                $val = 'true';
            if ($val === false)
                $val = 'false';
            if ($val === null)
                $val = 'null';
            printf("%-37s  %s\n", $key, $val);
        }
        echo "\n";

        echo "Konfigurace klienta:\n";
        echo "--------------------\n\n";
        $config = $this->context->parameters;
        print_r($config['client_config']);
        echo "\n";

        echo "Konfigurace e-podatelny:\n";
        echo "------------------------\n\n";
        $config = (new \Spisovka\ConfigEpodatelna)->get();
        foreach ($config->isds as &$box)
            unset($box->password);
        foreach ($config->email as &$mailbox)
            unset($mailbox->password);
        print_r($config);
        echo "\n";
        
        echo "Konfigurace systému:\n";
        echo "--------------------\n\n";
        $config = $this->context->parameters;
        unset($config['client_config']);  // toto jsme jiz zobrazili
        unset($config['database']['password']);
        unset($config['ldap']['search_password']);
        print_r($config);
        echo "\n";

        echo "\n\n</pre>\n";

        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        echo $phpinfo;

        // Vypis PHP log soubor
        /*
          $month_ago = time() - (30 * 24 * 60 * 60);
          $filename1 = "{$logDir}/php_error_" . date('Ym', $month_ago) . '.log';
          $filename2 = "{$logDir}/php_error_" . date('Ym') . '.log';
          // neni-li jeste 15-teho, zobraz i log z predesleho mesice
          $php_errors_older = date('j') < 15 ? file_get_contents($filename1) : '';
          $php_errors = file_get_contents($filename2);
          echo "<pre>\n\nChybový log PHP\n---------------\n\n$php_errors_older$php_errors";
         */

        $logDir = LOG_DIR;
        echo "<pre>\n\nProtokol s chybami aplikace\n---------------------------\n\n";
        $lines_count = 15;

        echo "error.log\n";
        $lines = $this->read_file("{$logDir}/error.log", $lines_count);
        foreach ($lines as $line)
            echo $line;

        echo "\nexception.log\n";
        $lines = $this->read_file("{$logDir}/exception.log", $lines_count);
        foreach ($lines as $line)
            echo $line;

        die;
    }

    protected function read_file($file, $lines)
    {
        //global $fsize;
        $handle = @fopen($file, "r");
        if (!$handle)
            return [];
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();
        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos --;
            }
            $linecounter --;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning)
                break;
        }
        fclose($handle);
        return array_reverse($text);
    }

}
