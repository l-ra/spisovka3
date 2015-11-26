<?php

namespace Spisovka\Controls;

/**
 * DatePicker input control.
 */
class DatePicker extends \Nette\Forms\Controls\TextInput
{

    protected $forbidPastDates = false;

    /**
     * @param  string  label
     */
    public function __construct($label)
    {
        // 10 characters are enough for a date
        parent::__construct($label, 10);
    }

    /**
     * Returns control's value.
     * @return mixed 
     */
    public function getValue()
    {
        $value = parent::getValue();
        if (empty($value))
            return $value;

        try {
            $value = trim($value);
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            $e->getMessage();
            throw new \Exception("Neplatné datum: {$value}");
        }
    }

    /**
     * Sets control's value.
     * @param  string
     * @return void
     */
    public function setValue($value)
    {
        if (!empty($value)) {
            try {
                $datetime = new \DateTime($value);
            } catch (\Exception $e) {
                $e->getMessage();
                throw new \Exception("Neplatné datum: $value");
            }
            $value = $datetime->format('j.n.Y');
        }
        parent::setValue($value);

        return $this;
    }

    /**
     * Generates control's HTML element.
     * @return Nette\Utils\Html
     */
    public function getControl()
    {
        $control = parent::getControl();
        $control->class = $this->forbidPastDates ? 'datepicker DPNoPast' : 'datepicker';
        $control->size = 10;
        return $control;
    }

    public function forbidPastDates()
    {
        $this->forbidPastDates = true;
        return $this;
    }

    /**
     * Vyzaduje, aby control byl vyplnen.
     */
    public static function validateValid(\Nette\Forms\IControl $control)
    {
        $value = $control->getValue();
        if (is_null($value))
            return false;
        if (!$control->forbidPastDates)
            return true;

        $today = date('Y-m-d');
        return strcmp($value, $today) >= 0;
    }

}
