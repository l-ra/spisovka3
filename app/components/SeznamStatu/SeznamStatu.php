<?php

class SeznamStatu extends Control
{

    /**
     * Renders paginator.
     * @return void
     */
    public function render()
    {
        $this->template->Staty = json_encode(Subjekt::stat());

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}