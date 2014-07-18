<?php

class Spisovna_DefaultPresenter extends BasePresenter
{

    public function renderDefault()
    {
        if ( Environment::getUser()->isAllowed('Spisovna', 'cist_dokumenty') )
            $this->redirect(':Spisovna:Dokumenty:default');
        if ( Environment::getUser()->isAllowed('Spisovna_ZapujckyPresenter') )
            $this->redirect(':Spisovna:Zapujcky:default');
    }

    protected function isUserAllowed()
    {
        return true;
    }
}