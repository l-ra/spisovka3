<?php

/**
 * ABC filter control.
 * @author Pavel Laštovička
 */
class AbcFilter extends Nette\Application\UI\Control
{

    /**
     * Renders component.
     * @return void
     */
    public function render()
    {
        $this->template->current_letter = $this->presenter->getParameter('abc');

        $this->template->js_function = false;
        $request = Nette\Environment::getHttpRequest();
        if ($request->isAjax())
            $this->template->js_function = 'reloadDialog';

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}
