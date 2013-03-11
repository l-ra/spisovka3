<?php

class DruhZasilky extends BaseModel 
{

    protected $name = 'druh_zasilky';
    
    public static function get( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_druh_zasilky = $prefix .'druh_zasilky';

        $result = dibi::query('SELECT * FROM %n', $tb_druh_zasilky )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else if ( $select == 3 ) {
               $tmp = array();
                $tmp[0] = 'jakýkoli druh zásilky';
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else if ( $select == 4 ) {
               $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ 'druh_zasilky_'. $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( array_key_exists($kod, $result) )?$result[ $kod ]:null;
        }

    }    
    
    public static function vypis( $data, $podaci_arch = false ) {

        static $ciselnik = array();
        
        /* Odfiltruj polozky, ktere nejsou doplnkovymi sluzbami Ceske Posty
           - obycejne
           - doporucene - vsechno, na co se pouziva podaci arch je doporucene
           - balik - pro jistotu, mel by byt odfiltrovan uz pri vyberu polozek pro p. arch
           - cizina
        */
        static $filtr_arch = array(1, 2, 3, 7);
        
        if (empty($ciselnik)) {
            $prefix = Environment::getConfig('database')->prefix;
            $tb_druh_zasilky = $prefix .'druh_zasilky';
            $ciselnik = dibi::query('SELECT * FROM %n', $tb_druh_zasilky )->fetchAssoc('id');
        }

        if ( empty($data) || !is_array($data) )
            return '';

        $druh_a = array();
        foreach( $data as $druh_zasilky_id ) {
            if ($podaci_arch && in_array($druh_zasilky_id, $filtr_arch))
                continue;
            $druh_a[] = $ciselnik[ $druh_zasilky_id ]->nazev;
        }
        
        return empty($druh_a) ? '' : implode(", ", $druh_a);            
    }    

}
