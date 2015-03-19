<?php

// Pomocna trida pro uzivatelska Latte makra

class MyMacros extends Nette\Application\UI\Control {

    public static function vlink($__param,$__link) {

        //P.L. zrus tuto funkci, protoze:
        // 1. pristup se kontroluje na cilove adrese odkazu
        // 2. a pristup se kontroluje v hlavni sablone jiz v momente, kdy se rozhoduje, zda link vubec zobrazit
        return $__link;        
    }

    // Ignoruj pripadnou polozku view (__array[3] ) v parametru makra, protoze v aplikaci se prideluje pristup pouze na urovni presenteru
    public static function access($param) {

        $__array = explode(":",$param);
        $__resource = $__array[1] ."_". $__array[2] . "Presenter";
        
        return Nette\Environment::getUser()->isAllowed($__resource);        
    }

    public static function isAllowed($resource, $privilege)
    {
        return Nette\Environment::getUser()->isAllowed($resource, $privilege);
    }
    
    public static function CSS($publicUrl, $filename, $media = 'screen') {

        // $filename = Nette\Latte\Engine::fetchToken($content); // filename [,] [media]
        // $media = Nette\Latte\Engine::fetchToken($content);

        $filename .= '.css';
        $href = "{$publicUrl}css/$filename?" . @filemtime(dirname(APP_DIR) . "/public/css/$filename");
        $res = "<link rel=\"stylesheet\" type=\"text/css\" media=\"$media\" href=\"$href\" />";

        return $res;   
    }

    public static function JavaScript($filename, $publicUrl) {

        $filename .= '.js';
        $href = "{$publicUrl}js/$filename?" . @filemtime(dirname(APP_DIR) . "/public/js/$filename");
        $res = "<script type=\"text/javascript\" src=\"$href\"></script>";

        return $res;   
    }
    
    // Vykresli standardni prvek formulare
    public static function input($form, $name) {
    
        $caption = $form[$name]->caption;
        $control = isset($form[$name]->controlPart) ? $form[$name]->controlPart : $form[$name]->control;
        return "<dl class=\"detail_item\">
            <dt>$caption</dt>
            <dd>$control</dd>
        </dl>";
    }
}

