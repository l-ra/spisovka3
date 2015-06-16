<?php

class VyberPostovniZasilky extends Nette\Application\UI\Control
{
    protected $value = array();
    protected $input_name = "druh_zasilky";
    
    public function __construct($init = null)
    {
        parent::__construct();

        if ($init !== null)
            $this->setValue($init);
    }

    public function render($input_name = null)
    {
        $this->template->DruhyZasilek = DruhZasilky::get();
        $this->template->Checked = $this->value;
        $this->template->InputName = $input_name ? $input_name : $this->input_name;

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

    public function setInputName($name)
    {
        $this->input_name = $name;
    }
    
    public function setValue($value)
    {
        if (is_string($value))
            $value = unserialize($value);
        if (is_array($value))
            $this->value = $value;
    }
}