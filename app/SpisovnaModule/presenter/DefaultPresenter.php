<?php

class Spisovna_DefaultPresenter extends BasePresenter
{
    public function renderDefault()
    {

        $Acl = Acl::getInstance();
        if ( Environment::getUser()->isAllowed('Spisovna_DokumentyPresenter') ) {
            $this->redirect(':Spisovna:Dokumenty:default');
        }

    }
}