<?php

/**
 * DatePicker input control.
 *
 * @author     Tomáš Kraina, Roman Sklenář
 *             Pavel Laštovička - upraveno pro spisovku
 * @copyright  Copyright (c) 2009
 * @license    New BSD License
 */
class DatePicker extends Nette\Forms\Controls\TextInput
{

    protected $forbidPastDates = false;

    /**
     * @param  string  label
     * @param  int  width of the control
     * @param  int  maximum number of characters the user may enter
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
        if (strlen($this->value)) {
            $tmp = preg_replace('~([[:space:]])~', '', $this->value);

            try {
                $tmp = new DateTime($this->value);
                return $tmp->format('Y-m-d');
            } catch (Exception $e) {
                $e->getMessage();
                throw new Exception("Neplatné datum: {$this->value}");
            }
        }

        return $this->value;
    }

    /**
     * Sets control's value.
     * @param  string
     * @return void
     */
    public function setValue($value)
    {
        try {
            $datetime = new DateTime($value);
        } catch (Exception $e) {
            $e->getMessage();
            throw new Exception("Neplatné datum: $value");
        }
        $value = $datetime->format('j.n.Y');
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
    public static function validateValid(Nette\Forms\IControl $control)
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
