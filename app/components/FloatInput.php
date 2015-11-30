<?php

namespace Spisovka\Controls;

/**
 * Control pro vstup čísel. Používá napevno desetinnou čárku.
 *
 * @author Pavel Laštovička
 */
class FloatInput extends \Nette\Forms\Controls\TextInput
{
    public function __construct($label = NULL, $maxLength = NULL)
    {
        parent::__construct($label, $maxLength);
        $this->addCondition(\Nette\Forms\Form::FILLED)
            ->addRule(\Nette\Forms\Form::FLOAT);
    }
    
    /**
     * Generates control's HTML element.
     * @return Nette\Utils\Html
     */
    public function getControl()
    {
        $input = parent::getControl();
        $value = $this->getValue();
        if ($value !== null) {
            $input->value = str_replace('.', ',', $value);
        }
        
        return $input;
    }
    
	/**
	 * Returns control's value.
	 * @return float|null
	 */
    public function getValue()
    {
        $value = parent::getValue();
        return $value === '' ? null : $value;
    }
}
