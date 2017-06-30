<?php

namespace Spisovka\Components;

class PrintControl extends \Nette\Application\UI\Control
{
    /**
     * Renders component.
     * @return void
     */
    public function render()
    {
        $this->template->publicUrl = \Spisovka\GlobalVariables::get('publicUrl');;

        $this->template->setFile(dirname(__FILE__) . '/template.latte');
        $this->template->render();
    }

}
