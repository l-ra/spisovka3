<?php

namespace Spisovka\Components;

use Nette;

/**
 * ABC filter control.
 * @author Pavel Laštovička
 */
class AbcFilter extends Nette\Application\UI\Control
{

    /**
     *
     * @var Nette\Http\Request 
     */
    protected $httpRequest;
    
    public function __construct($parent, $name, Nette\Http\Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        parent::__construct($parent, $name);
    }
    
    /**
     * Renders component.
     * @return void
     */
    public function render()
    {
        $this->template->current_letter = $this->presenter->getParameter('abc');

        $this->template->js_function = false;
        if ($this->httpRequest->isAjax())
            $this->template->js_function = 'reloadDialog';

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}
