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
        $__resource = $__array[1] ."_". $__array[2] ."Nette\Application\UI\Presenter";
        
        return "Environment::getUser()->isAllowed('$__resource')";        
    }

    public static function isAllowed($content)
    {
        $resource = Nette\Latte\Engine::fetchToken($content); // resource [,] [privilege]
        $params = "'$resource'";
        $privilege = Nette\Latte\Engine::fetchToken($content);
        if ($privilege !== null)
            $params .= ", '$privilege'";
            
        return "Environment::getUser()->isAllowed($params)";
    }
    
    public static function CSS($content, $publicUrl) {
    
		$filename = Nette\Latte\Engine::fetchToken($content); // filename [,] [media]
        $media = Nette\Latte\Engine::fetchToken($content);

        $filename .= '.css';
        if (empty($media))
            $media = 'screen';
        $href = "{$publicUrl}css/$filename?" . @filemtime(APP_DIR . "/../public/css/$filename");
        $res = "<link rel=\"stylesheet\" type=\"text/css\" media=\"$media\" href=\"$href\" />";

        return $res;   
    }

    public static function JavaScript($content, $publicUrl) {
    
		$filename = Nette\Latte\Engine::fetchToken($content); // filename

        $filename .= '.js';
        $href = "{$publicUrl}js/$filename?" . @filemtime(APP_DIR . "/../public/js/$filename");
        $res = "<script type=\"text/javascript\" src=\"$href\"></script>";

        return $res;   
    }
    
    // Vykresli standardni prvek formulare
    public static function input($form, $name) {
    
        return "<dl class=\"detail_item\">
            <dt>{$form[$name]->caption}</dt>
            <dd>{$form[$name]->control}</dd>
        </dl>";
    }
}

