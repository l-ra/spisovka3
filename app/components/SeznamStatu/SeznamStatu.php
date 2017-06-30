<?php

namespace Spisovka\Components;

use Nette;

class SeznamStatu extends Nette\Application\UI\Control
{

    /**
     * Renders component.
     * @return void
     */
    public function render()
    {
        $this->template->Staty = json_encode(\Spisovka\Subjekt::stat());

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}