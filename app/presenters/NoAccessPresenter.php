<?php

namespace Spisovka;

use Nette;

class NoAccessPresenter extends BasePresenter
{
    public function startup()
    {
       // Preskoc startup kod v BasePresenteru
       Nette\Application\UI\Presenter::startup();
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout('noaccess');
    }
}

?>