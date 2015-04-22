<?php

class Spisovna_VyhledatPresenter extends Spisovka_VyhledatPresenter
{
    public function getCookieName()
    {
        return 's3_spisovna_hledat';
    }

    public function getRedirectPath()
    {
        return ':Spisovna:Dokumenty:default';
    }

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Spisovna', 'cist_dokumenty');
    }
}

