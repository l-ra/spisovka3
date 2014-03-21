<?php

class Spisovka_SeznamzmenPresenter extends BasePresenter
{

    public function renderDefault()
    {
        $history_filename = 'historie.txt';
        $log = file_get_contents(APP_DIR ."/../$history_filename");
        if (!$log) {
            $log = '';
            $this->flashMessage("Soubor $history_filename se nepodařilo načíst.", 'warning');
        }
        
        $this->template->changelog = $log;    
    }
    
}