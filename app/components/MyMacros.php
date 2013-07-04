<?php

// Pomocna trida pro uzivatelska Latte makra

class MyMacros extends Control {

    public static function vlink($__param,$__link) {

        //P.L. zrus tuto funkci, protoze:
        // 1. pristup se kontroluje na cilove adrese odkazu
        // 2. a pristup se kontroluje v hlavni sablone jiz v momente, kdy se rozhoduje, zda link vubec zobrazit
        return $__link;        
    }

    // Volano pouze z Latte makra Access
    // Ignoruj pripadnou polozku view (__array[3] ) v parametru makra, protoze v aplikaci se prideluje pristup pouze na urovni presenteru
    public static function toParam($param) {

        $__array = explode(":",$param);
        $__resource = $__array[1] ."_". $__array[2] ."Presenter";
        return "'". $__resource ."'";        
    }
}

