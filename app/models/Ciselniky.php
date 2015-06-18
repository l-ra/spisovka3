<?php

class ZpusobOdeslani extends BaseModel
{

    protected static function getTableName()
    {

        return self::getDbPrefix() . "zpusob_odeslani";
    }

    public static function getName($id)
    {

        return dibi::query('SELECT [nazev] FROM %n', self::getTableName(), "WHERE [id] = %i",
                        $id)->fetchSingle();
    }

    // Vrati vsechny aktivni zpusoby
    public static function getZpusoby()
    {

        return dibi::query('SELECT * FROM %n', self::getTableName(), "WHERE stav = 1")->fetchAll();
    }

}

?>