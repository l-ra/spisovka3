<?php

namespace Spisovka;

/**
 * Entita, kde je kesovana tabulka jako celek
 * (pouze v pripade, ze neni pouzito omezeni WHERE, ORDER BY, LIMIT a OFFSET)
 * Urceno pro data, ktera se zridka meni.
 *
 * @author Pavel Laštovička
 */
abstract class TableCachedDBEntity extends DBEntity
{

    public static function getAll(array $params = [])
    {
        if ($params !== [])
            return parent::getAll($params);

        $result = DbCache::get(static::_getCacheEntryName());
        if ($result !== null)
            return $result;

        $result = parent::getAll();
        DbCache::set(static::_getCacheEntryName(), $result);
        return $result;
    }

    protected static function _getCacheEntryName()
    {
        return 'table_' . static::TBL_NAME;
    }
    
    protected static function _invalidateCache()
    {
        DbCache::delete(static::_getCacheEntryName());        
    }

    public static function create(array $data)
    {
        $res = parent::create($data);
        self::_invalidateCache();
        return $res;
    }

    protected function _load()
    {
        $cached_data = DbCache::get(static::_getCacheEntryName());
        if ($cached_data === null) {
            parent::_load();
            return;
        }
        
        $this->_setData($cached_data[$this->id]->_data);
    }
    
    public function save()
    {
        $data_changed = $this->_data_changed;
        parent::save();
        if ($data_changed)
            self::_invalidateCache();
    }
    
    public function delete()
    {
        parent::delete();
        self::_invalidateCache();        
    }
}
