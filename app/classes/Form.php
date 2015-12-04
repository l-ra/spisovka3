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
        // Zpracování při Ajaxu musí být odlišné
        // Redirect by vrátil JSON s URL v atributu redirect
        if ($presenter->isAjax()) {
            // Nedělej nic a doufej, že se formulář vykreslí znovu.
            // U formulářů renderovaných Nette by se mělo objevit hlášení
            // vedle prvku, který neprošel validací
        } else {
            $presenter->flashMessage('Validace formuláře selhala.', 'warning');
            $errors = $form->getErrors();
            foreach ($errors as $error)
                $presenter->flashMessage($error, 'warning');

            /* Zkouska - NEprovadej redirect
            $values = $form->getValues();
            if (isset($values->id))
                $presenter->redirect('this', ['id' => $values->id]);
            else
                $presenter->redirect('this'); */
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Spisovka\Controls\DatePicker
     */
    public function addDatePicker($name, $label)
    {
        return $this[$name] = new Controls\DatePicker($label);        
    }
    
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
        // tato změna by vyžadovala enormní množství práce, přepsat všechny formuláře
        // $renderer->wrappers['label']['suffix'] = ':';
        $renderer->wrappers['control']['container'] = 'dd';
        
        $renderer->wrappers['error']['container'] = null;
        $renderer->wrappers['error']['item'] = 'div class="flash_message flash_warning"';                
    }
}
