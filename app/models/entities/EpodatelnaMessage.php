<?php

class EpodatelnaMessage extends DBEntity
{

    const TBL_NAME = 'epodatelna';

    /**
     *  Vrátí odkaz na soubor s e-mailem.
     * @param Storage_Basic $storage
     * @return string  filename
     */
    public function getEmailFile($storage)
    {
        if ($this->typ != 'E')
            throw new LogicException(__METHOD__);
        
        if (!$this->file_id)
            return null;
        
        $FileModel = new FileModel();
        $file = $FileModel->getInfo($this->file_id);
        $path = $storage->getFilePath($file);

        return $path;
    }
    
    /**
     * Vrátí soubor se serializovaným objektem s informacemi o zprávě.
     * @param Storage_Basic $storage
     * @return string  filename
     */
    public function getIsdsFile($storage)
    {
        if ($this->typ != 'I')
            throw new LogicException(__METHOD__);

        if (!$this->file_id)
            return null;
        
        $FileModel = new FileModel();
        $file = $FileModel->getInfo($this->file_id);
        $path = $storage->getFilePath($file);

        return $path;
    }
    
    /**
     * @param Storage_Basic $storage
     * @param boolean $download  Download file or return it?
     * @return string  data
     */
    public function getZfoFile($storage, $download)
    {
        if ($this->typ != 'I')
            throw new LogicException(__METHOD__);
        
        $FileModel = new FileModel();
        $file = $FileModel->select([["nazev = %s", "ep-isds-{$this->id}.zfo"]])->fetch();
        if (!$file)
            return null;
        
        $zfo = $storage->download($file->id, !$download);
        return $zfo;        
    }
}
