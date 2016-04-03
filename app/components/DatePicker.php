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
        $this->addCondition(\Nette\Forms\Form::FILLED)
                ->addRule([$this, 'validateDate'], 'Zadané datum je neplatné.');
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
                $value = $datetime->format('Y-m-d');
            } catch (\Exception $e) {
                $e->getMessage();
                $form = $this->getForm();
                // Nehazej vyjimku pri submitu formulare, chybu uzivatele osetri validaci
                if (!$form->isSubmitted())
                    throw new \Exception("Neplatné datum: $value");
            }
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
            try {
                $datetime = new \DateTime($value);
                $input->value = $datetime->format('j.n.Y');
            } catch (\Exception $e) {
                // nic nedelej, zobraz neplatny udaj uzivateli, aby jej opravil
                $e->getMessage();
            }
        }

        return $input;
    }

    public function forbidPastDates()
    {
        $this->forbidPastDates = true;
        $this->addRule([$this, 'validateNoPastDate'], 'Zadané datum nemůže být v minulosti.');
        return $this;
    }

    public static function validateNoPastDate(DatePicker $control)
    {
        $value = $control->getValue();
        if ($value === null)
            return true;

        $today = date('Y-m-d');
        return strcmp($value, $today) >= 0;
    }

    public static function validateDate(DatePicker $control)
    {
        $value = $control->getValue();
        try {
            $ok = false;
            new \DateTime($value);
            $ok = true;
        } catch (\Exception $e) {
            $e->getMessage();
        }

        return $ok;
    }

}
