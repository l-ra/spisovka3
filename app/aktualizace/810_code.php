<?php

function revision_810_after()
{
    $res = dibi::query('SELECT * FROM [:PREFIX:sestava]');
    
    foreach ($res as $sestava) {
        
        $sloupce = array(
            '-1' => 'smer',
            '0' => 'cislo_jednaci',
            '1' => 'spis',
            '2' => 'datum_vzniku',
            '3' => 'subjekty',
            '4' => 'cislo_jednaci_odesilatele',
            '5' => 'pocet_listu',
            '6' => 'pocet_priloh',
            '7' => 'pocet_nelistu',
            '8' => 'nazev',
            '9' => 'vyridil',
            '10' => 'zpusob_vyrizeni',
            '11' => 'datum_odeslani',
            '12' => 'spisovy_znak',
            '13' => 'skartacni_znak',
            '14' => 'skartacni_lhuta',
            '15' => 'zaznam_vyrazeni',
            '16' => 'popis',
            '17' => 'poznamka_predani',
            '18' => 'prazdny_sloupec'
        );

        $zobr = isset($sestava->zobrazeni_dat) ? unserialize($sestava->zobrazeni_dat) : false;
        if ($zobr === false)
            $zobr = array();
        
        // nastav vychozi hodnoty
        if (!isset($zobr['sloupce_poznamka']))
            $zobr['sloupce_poznamka'] = false;
        if (!isset($zobr['sloupce_poznamka_predani']))
            $zobr['sloupce_poznamka_predani'] = false;
        if (!isset($zobr['sloupce_smer_dokumentu']))
            $zobr['sloupce_smer_dokumentu'] = true;
        if (!isset($zobr['sloupce_prazdny']))
            $zobr['sloupce_prazdny'] = false;

        // vyber sloupce dle stare definice sestavy
        if (!$zobr['sloupce_poznamka'])
            unset($sloupce[16]);
        if (!$zobr['sloupce_smer_dokumentu'])
            unset($sloupce[-1]);
        if (!$zobr['sloupce_poznamka_predani'])
            unset($sloupce[17]);
        if (!$zobr['sloupce_prazdny'])
            unset($sloupce[18]);
        
        $sloupce_string = implode(',', $sloupce);
        
        unset($zobr['sloupce_poznamka']);
        unset($zobr['sloupce_poznamka_predani']);
        unset($zobr['sloupce_smer_dokumentu']);
        unset($zobr['sloupce_prazdny']);
        
        $res = dibi::query('UPDATE [:PREFIX:sestava] SET [sloupce] = %s, [zobrazeni_dat] = %s WHERE [id] = %i',
                $sloupce_string, serialize($zobr), $sestava->id);
        
    }
    
}