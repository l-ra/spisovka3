<?php

namespace Spisovka;

use Nette;

/**
 * Description of Form
 *
 * @author Pavel
 */
class Form extends Nette\Application\UI\Form
{
    public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
        $this->onError[] = [$this, 'validationFailed'];
        $this->setupRendering();
    }
    
    public function validationFailed($form)
    {
        $id = $form->getValues()->id;
        $presenter = $this->getPresenter();
        $presenter->flashMessage('Validace formuláře selhala.', 'warning');
        $errors = $form->getErrors();
        foreach ($errors as $error)
            $presenter->flashMessage($error, 'warning');
        
        $presenter->redirect('this', ['id' => $id]);
    }
    
    public function addDatePicker($name, $label)
    {
        return $this[$name] = new \DatePicker($label);        
    }

    /* Není v aplikaci použito
    public function addDateTimePicker($name, $label)
    {
        return $this[$name] = new \DateTimePicker($label);
    } */
    
    protected function setupRendering()
    {
        $renderer = $this->getRenderer();
        
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';        
    }
}
