<?php

/**
 * SpisovyZnakComponent - formularovy prvek pro spisovy znak se Select2 widgetem pro opakovane pouziti
 *
 * @author Petr Slavicek
 */
class SpisovyZnakComponent extends SelectBox implements IComponent
{
    public function __construct($label = NULL, array $items = NULL, $size = NULL)
    {
        parent::__construct($label, $items, $size);
        $this->controlPrototype->attrs['data-widget-select2'] = 1; //pouzit widget Select2
        $this->controlPrototype->attrs['data-widget-select2-options'] = json_encode(array('width' => 'resolve'));//Select2 options
        $this->controlPrototype->onchange("vybratSpisovyZnak();");
    }
    
   
    
}
