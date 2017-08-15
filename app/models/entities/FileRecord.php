<?php

namespace Spisovka;

class FileRecord extends DBEntity
{

    const TBL_NAME = 'file';

    /**
     * @param Document $doc
     * @return \static[]
     */
    public static function getDocumentFiles(Document $doc)
    {
        $result = dibi::query("SELECT f.* FROM %n AS df JOIN %n AS f ON df.[file_id] = f.[id]"
                        . " WHERE df.[dokument_id] = $doc->id", 'dokument_to_file',
                        FileRecord::TBL_NAME);

        return self::_createObjectsFromDibiResult($result);
    }

}
