<?php

namespace Spisovka;

use Nette;

class Spisovka_SeznamzmenPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }
    
    public function renderDefault()
    {
        $history_filename = 'historie.txt';
        $log = file_get_contents(dirname(APP_DIR) . "/$history_filename");
        if (!$log) {
            $log = '';
            $this->flashMessage("Soubor $history_filename se nepodařilo načíst.", 'warning');
        }

        $this->template->changelog = $log;
        // $this->setLayout('admin');
    }

}
