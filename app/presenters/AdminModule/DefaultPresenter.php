<?php

class Admin_DefaultPresenter extends BasePresenter
{

    public function renderDefault()
    {

    }

    public function renderSmazatDbCache()
    {
        if (!$this->user->isInRole('admin')) {
            $this->redirect('default');
        }
        
        DbCache::clearCache();

        $this->flashMessage('Databázová cache byla smazána.');
        $this->redirect(':Admin:default:ostatni');
    }
    
    public function renderOstatni()
    {
        $this->template->DU_available = false;
        $filename = TEMP_DIR . '/disk_usage';
        if (is_file($filename) && $data = file_get_contents($filename)) {
            if ($data != 'error') {
                $a = explode(':', $data);
                $this->template->DU_epodatelna = $a[0];
                $this->template->DU_documents = $a[1];
                $this->template->DU_total = $a[0] + $a[1];
                $this->template->DU_available = true;
                $this->template->DU_date = filemtime($filename);
            }
        }
    }
}
