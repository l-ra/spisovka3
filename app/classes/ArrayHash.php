<?php

namespace Spisovka;

class ArrayHash extends \Nette\Utils\ArrayHash {
    
    public function toArray() {
        
        $a = array();
        foreach ($this as $key => $value) {
                if (is_object($value)) {
                    $a[$key] = $value->toArray();
                } else {
                    $a[$key] = $value;
                }
        }
        
        return $a;
    }
}
