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
        
        DBCache::clearCache();

        $this->flashMessage('Databázová cache byla smazána.');
        $this->redirect(':Admin:default:ostatni');
    }
}
