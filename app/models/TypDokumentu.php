<?php

class TypDokumentu extends BaseModel
{
    /**
     * Vrátí typy dokumentů, které má uživatel povolené v administraci
     * @return array pole názvů
     */
    public static function dostupneUzivateli()
    {
        $typy = dibi::query('SELECT * FROM [:PREFIX:dokument_typ] WHERE [stav] = 1')
                ->fetchAssoc('id');

        $user = self::getUser();
        if ($user->inheritsFromRole('admin,superadmin')) {
            $referent = true;
            $podatelna = true;
        } else if ($user->inheritsFromRole('podatelna')) {
            $referent = false;
            $podatelna = true;
        } else {
            $referent = true;
            $podatelna = false;
        }
        
        $vysledek = [];
        foreach ($typy as $typ) {
            if ($typ->podatelna && $podatelna || $typ->referent && $referent)
                $vysledek[$typ->id] = $typ->nazev;
        }
        
        return $vysledek;
    }

    /** Vrací všechny, i neaktivní
     * 
     * @return array  pole názvů
     */
    public static function vsechny()
    {
        $typy = dibi::query('SELECT [id], [nazev] FROM [:PREFIX:dokument_typ]')
                ->fetchPairs('id', 'nazev');
        
        return $typy;
    }

    /** Vrací všechny, i neaktivní
     * 
     * @return array  pole DibiRow
     */
    public static function vsechnyJakoTabulku()
    {
        $typy = dibi::query('SELECT * FROM [:PREFIX:dokument_typ]')
                ->fetchAssoc('id');
        
        return $typy;
    }
}
