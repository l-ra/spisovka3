<?php

namespace Spisovka\Controls;

/**
 * DatePicker input control.
 */
class DatePicker extends \Nette\Forms\Controls\TextInput
{
	const NO_PAST_DATE = ':noPastDate';

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
     * @return string|null  2015-12-30
     */
    public function getValue()
    {
        $value = parent::getValue();
        return $value === '' ? null : $value;
    }

    /**
     * Sets control's value.
     * @param  string
     * @return self
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
            $value = $datetime->format('Y-m-d');
        }
        parent::setValue($value);

        return $this;
    }

    /**
     * Generates control's HTML element. Displays date in local format.
     * @return Nette\Utils\Html
     */
    public function getControl()
    {
        $input = parent::getControl();
        $input->class = $this->forbidPastDates ? 'datepicker DPNoPast' : 'datepicker';
        $input->size = 10;
        $value = $this->getValue();
        if ($value !== null) {
            $datetime = new \DateTime($value);
            $input->value = $datetime->format('j.n.Y');
        }
        
        return $input;
    }

    public function forbidPastDates()
    {
        $this->forbidPastDates = true;
        $this->addRule(self::NO_PAST_DATE, 'Zadané datum nemůže být v minulosti: %label');
        return $this;
    }

    public static function validateNoPastDate(\Nette\Forms\IControl $control)
    {
        $value = $control->getValue();
        if ($value === null)
            return true;
        
        $today = date('Y-m-d');
        return strcmp($value, $today) >= 0;
    }
}
