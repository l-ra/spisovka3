<?php

class Spisovka_DefaultPresenter extends BasePresenter
{

    public function renderDefault()
    {
        if ( Nette\Environment::getUser()->isAllowed('Spisovka_DokumentyPresenter') )
            $this->redirect(':Spisovka:Dokumenty:default');
    }

}