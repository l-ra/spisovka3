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
        return Environment::getUser()->isInRole('admin');
    }

    
    public function renderDefault()
    {
        echo '<pre>';
        echo "Informace pro technickou podporu\n";
        echo "================================\n\n";
        
        $verze = $this->template->AppInfo[0];
        echo "Verze aplikace:  $verze\n\n";
        
        echo "Maximální velikost nahraného souboru:  ";
        $max = DokumentPrilohy::maxVelikostUploadu(true);
        echo "$max\n\n";
        
        echo "Nastavení uložená v databázi:\n\n";
        $db_settings = Settings::getAll();
        foreach ($db_settings as $key => $val) {
            if ($val === true)
                $val = 'true';
            if ($val === false)
                $val = 'false';
            printf("%-35s  %s\n", $key, $val);
        }
        echo "\n";
        
        echo "Konfigurace systému:\n\n";
        // $config = Environment::getConfig();
        $config = parse_ini_file(CLIENT_DIR . "/configs/system.ini", false);
        $config['database.password'] = 'XXXXXX';
        $config['authenticator.ldap.pass'] = 'XXXXXX';
        print_r($config);
        echo "\n";
        
        echo "Konfigurace klienta:\n\n";
        $config = parse_ini_file(CLIENT_DIR . "/configs/klient.ini", true);
        print_r($config);
        echo "\n";
        
        echo "\n\n</pre>\n";
        
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        
        echo $phpinfo;
        
        die;
    }
    
}
