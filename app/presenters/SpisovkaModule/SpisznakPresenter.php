<?php

namespace Spisovka;

use Nette;

class Spisovka_SpisznakPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }

    public function renderDetail($id)
    {
        $SpisovyZnak = new SpisovyZnak();
        $sz = $SpisovyZnak->getInfo($id);
        $this->sendJson($sz);
    }

}
