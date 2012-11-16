<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of vlink
 *
 * @author tomik
 */
class MyMacros extends Control {
    //put your code here




    public static function vlink($__param,$__link) {

        //P.L. zrus tuto funkci, protoze:
        // 1. pristup se kontroluje na cilove adrese odkazu
        // 2. a pristup se kontroluje v hlavni sablone jiz v momente, kdy se rozhoduje, zda link vubec zobrazit
        return $__link;
        
        /* //$__param = "";
        if ( strpos($__link, "error:")!==false ) {
            return "#";
        } else {
            $__array = explode(":",$__param);
            $__resource = $__array[1] ."_". $__array[2] ."Presenter";
            $__privilege = isset($__array[3])?$__array[3]:null;
            if ( @Environment::getUser()->isAllowed($__resource, $__privilege) ) {
                return $__link;
            } else {
                return "#";
            }
        } */
    }

    public static function alink($param) {

        //$param = self::toParam($param);
        $param = explode(",",$param);
        $name = $param[0];
        $destination = $param[1];
        unset($param[0],$param[1]);
        $argumenty = implode(",", $param);
        $argumenty = !empty($argumenty)?', array('.$argumenty.')':'';
        $__array = explode(":",$destination);
        $__resource = $__array[1] ."_". $__array[2] ."Presenter";
        $__privilege = isset($__array[3])?$__array[3]:null;

        echo "<pre>";
        echo "\n name:        "; print_r($name);
        echo "\n destination: "; print_r($destination);
        echo "\n argumenty:   "; print_r($argumenty);
        echo "\n __resource:  "; print_r($__resource);
        echo "\n __privilege: "; print_r($__privilege);
        echo "</pre>";


        if ( @Environment::getUser()->isAllowed($__resource, $__privilege) ) {
            return "<a href=\"<?php echo \$control->link('".$destination."'".$argumenty.")) ?>\">".$name.'</a>';
        } else {
            return "";
        }
    }

    public static function toParam($param) {

        $__array = explode(":",$param);
        $__resource = $__array[1] ."_". $__array[2] ."Presenter";
        $__privilege = isset($__array[3])?trim($__array[3]):"";

        if ( !empty($__privilege) ) {
            return "'". $__resource ."','". $__privilege ."'";
        } else {
            return "'". $__resource ."'";
        }

        
    }


}

