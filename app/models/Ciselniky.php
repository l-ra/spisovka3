<?php

class ZpusobOdeslani extends BaseModel
{

    public static function getName($id)
    {
        return dibi::query('SELECT [nazev] FROM [:PREFIX:zpusob_odeslani] WHERE [id] = %i',
                        $id)->fetchSingle();
    }

    // Vrati vsechny aktivni zpusoby
    public static function getZpusoby()
    {
        return dibi::query('SELECT * FROM [:PREFIX:zpusob_odeslani] WHERE stav = 1')->fetchAll();
    }

}
