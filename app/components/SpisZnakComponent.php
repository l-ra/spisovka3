<?php

class SpisovyZnakComponent extends Nette\Forms\Controls\SelectBox
{
    public function __construct()
    {
        $m = new SpisovyZnak();
        $items = $m->selectBox(2);
        parent::__construct('Spisový znak:', $items);
        
        $proto = $this->controlPrototype;
        $proto->attrs['class'] = 'widget_spisovy_znak';
        $proto->onchange('vybratSpisovyZnak(this);');
    }
    
	public function isFilled()
	{
        $value = $this->getValue();
		return $value !== NULL && $value != 0;
	}

}
