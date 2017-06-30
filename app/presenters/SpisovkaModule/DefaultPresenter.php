<?php

namespace Spisovka;

use Nette;

class Spisovka_DefaultPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }
    
    public function renderDefault()
    {
        if ($this->user->isAllowed('Spisovka_DokumentyPresenter'))
            $this->redirect(':Spisovka:Dokumenty:');
    }

}
