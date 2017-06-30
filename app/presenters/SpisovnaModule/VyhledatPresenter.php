<?php

namespace Spisovka;

use Nette;

class Spisovna_VyhledatPresenter extends Spisovka_VyhledatPresenter
{

    public function getSettingName()
    {
        return 'spisovna_dokumenty_hledat';
    }

    public function getRedirectPath($backlink = null)
    {
        $view = $backlink ?: $this->getParameter('zpet', 'default');
        return ":Spisovna:Dokumenty:$view";
    }

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Spisovna', 'cist_dokumenty');
    }

}
