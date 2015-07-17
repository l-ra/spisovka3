<?php

class Spisovna_VyhledatPresenter extends Spisovka_VyhledatPresenter
{

    public function getSettingName()
    {
        return 'spisovna_dokumenty_hledat';
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
