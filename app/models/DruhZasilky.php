<?php

namespace Spisovka;

class DruhZasilky
{

    const OBYCEJNE = 1;
    const DOPORUCENE = 2;
    
    public static function get()
    {

        static $result = null;

        if ($result === null)
            $result = dibi::query('SELECT id, nazev, [order] FROM :PREFIX:druh_zasilky WHERE stav = 1 ORDER BY [order]')->fetchAssoc('id');

        return $result;
    }

    public static function vypis($data, $podaci_arch = false)
    {
        static $ciselnik = array();

        /* Pro tisk podaciho archu odfiltruj polozky, ktere nejsou doplnkovymi sluzbami Ceske Posty
          - obycejne
          - doporucene - vsechno, na co se pouziva podaci arch je doporucene
          - balik - pro jistotu, mel by byt odfiltrovan uz pri vyberu polozek pro p. arch
          - cizina
         */
        static $filtr_arch = array(1, 2, 3, 7);

        if (empty($ciselnik)) {
            $ciselnik = dibi::query('SELECT * FROM [:PREFIX:druh_zasilky]')->fetchAssoc('id');
        }

        if (empty($data) || !is_array($data))
            return '';

        $druh_a = array();
        foreach ($data as $druh_zasilky_id) {
            if ($podaci_arch && in_array($druh_zasilky_id, $filtr_arch))
                continue;
            if (!isset($ciselnik[$druh_zasilky_id]))
                continue;
            $druh_a[] = $ciselnik[$druh_zasilky_id]->nazev;
        }

        return empty($druh_a) ? '' : implode(", ", $druh_a);
    }

}
