<?php

namespace Spisovka;

/**
 * Description of Form
 *
 * @author Pavel
 */
class Form extends \Nette\Application\UI\Form
{
    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
        $this->onError[] = [$this, 'validationFailed'];
        $this->setupRendering();
    }
    
    public function validationFailed($form)
    {
        $presenter = $this->getPresenter();
        $presenter->flashMessage('Validace formuláře selhala.', 'warning');
        $errors = $form->getErrors();
        foreach ($errors as $error)
            $presenter->flashMessage($error, 'warning');
        
        $values = $form->getValues();
        if (isset($values->id))
            $presenter->redirect('this', ['id' => $values->id]);
        else
            $presenter->redirect('this');
    }
    
    public function addDatePicker($name, $label)
    {
        return $this[$name] = new Controls\DatePicker($label);        
    }

    /* Není v aplikaci použito
    public function addDateTimePicker($name, $label)
    {
        return $this[$name] = new \DateTimePicker($label);
    } */
    
	/**
	 * Adds single-line text input control to the form. Its content should be 
     * floating point numbers.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int  width of the control (deprecated)
	 * @param  int  maximum number of characters the user may enter
	 * @return Nette\Forms\Controls\TextInput
	 */
    public function addFloat($name, $label = NULL, $cols = NULL, $maxLength = NULL)
    {
        $control = new Controls\FloatInput($label, $maxLength);        
		$control->setAttribute('size', $cols);
		return $this[$name] = $control;
    }
    
    protected function setupRendering()
    {
        $renderer = $this->getRenderer();
        
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';        
    }
}
