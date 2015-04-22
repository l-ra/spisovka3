<?php

class Spisovka_DefaultPresenter extends BasePresenter
{

    public function renderDefault()
    {
        if ( $this->user->isAllowed('Spisovka_DokumentyPresenter') )
            $this->redirect(':Spisovka:Dokumenty:default');
    }

}