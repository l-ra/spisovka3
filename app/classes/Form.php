<?php

namespace Spisovka;

/**
 * Description of Form
 *
 * @author Pavel
 */
class Form extends \Nette\Application\UI\Form
{
    public function addDatePicker($name, $label, $cols = NULL, $maxLength = NULL)
    {
        return $this[$name] = new \DatePicker($label, $cols, $maxLength);        
    }

    public function addDateTimePicker($name, $label, $cols = NULL, $maxLength = NULL)
    {
        return $this[$name] = new \DateTimePicker($label, $cols, $maxLength);        
    }
}
