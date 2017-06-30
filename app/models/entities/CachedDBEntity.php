<?php

namespace Spisovka;

/**
 * Description of CachedDBEntity
 *
 * @author Pavel Laštovička
 */
abstract class CachedDBEntity extends DBEntity
{

    private function _getCacheEntryName()
    {
        $id = $this->id;
        return 'ent_' . $this::TBL_NAME . "_$id";
    }

    private function _invalidateCacheEntry()
    {
        DbCache::delete($this->_getCacheEntryName());        
    }
    
    protected function _load()
    {
        $result = DbCache::get($this->_getCacheEntryName());
        if ($result !== null) {
            $this->_data = $result;
            return;
        }

        parent::_load();
        DbCache::set($this->_getCacheEntryName(), $this->_data);
    }

    public function save()
    {
        $data_changed = $this->_data_changed;
        parent::save();
        if ($data_changed)
            $this->_invalidateCacheEntry();
    }
    
    public function delete()
    {
        parent::delete();
        $this->_invalidateCacheEntry();        
    }
}
