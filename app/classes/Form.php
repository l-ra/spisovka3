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
            // Nedělej nic a doufej, že Javascript kód vykreslí formulář
            // znovu do dialogového okna.
            // Následující řádek by zobrazil zprávu pouze v automaticky
            // vykreslených formulářích, proto je lépe jej nepoužít.
            // Uživatel není tak jako tak slepý.
            //$this->addError('Validace formuláře selhala.');
        } else {
            // Upozorni uživatele, aby nepřehlédl, že něco zadal do formuláře chybně
            $presenter->flashMessage('Validace formuláře selhala.', 'warning');

            /* [P.L.] Přepsal jsem všechny formuláře v aplikaci, u validovaných
             * prvků by se nyní měla zobrazit zpráva přímo vedle nich.
             * Následující kód je už zbytečný
              $errors = $form->getErrors();
              foreach ($errors as $error)
              $presenter->flashMessage($error, 'warning');
             */
            /* Redirect už neprovádíme */
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @return Controls\DatePicker
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
