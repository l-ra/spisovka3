<?php

/**
 * Description of Role
 *
 * @author Pavel Laštovička
 */
class Role extends TableCachedDBEntity
{

    const TBL_NAME = 'user_role';

    protected static function _invalidateCache()
    {
        parent::_invalidateCache();
        DbCache::delete('s3_Permission');
    }

    public static function create(array $data)
    {
        $m = new RoleModel();
        $id = $m->vlozitH($data);
        self::_invalidateCache();
        return new self($id);
    }

    public function delete()
    {
        parent::delete();
        self::_invalidateCache();
        DbCache::delete('s3_Permission');
    }

    public function save()
    {
        if ($this->_data_changed) {
            $m = new RoleModel();
            $res = $m->upravitH($this->_data, $this->id);
            self::_invalidateCache();
            return $res;
        }
    }

}