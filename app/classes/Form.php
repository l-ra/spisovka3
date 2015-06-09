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
    
    public function addDatePicker($name, $label, $cols = NULL, $maxLength = NULL)
    {
        return $this[$name] = new \DatePicker($label, $cols, $maxLength);        
    }

    public function addDateTimePicker($name, $label, $cols = NULL, $maxLength = NULL)
    {
        return $this[$name] = new \DateTimePicker($label, $cols, $maxLength);        
    }
}
