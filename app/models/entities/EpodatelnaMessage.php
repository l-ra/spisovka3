<?php

class EpodatelnaMessage extends DBEntity
{

    const TBL_NAME = 'epodatelna';

    /**
     *  Pro e-maily vrátí odkaz na soubor s e-mailem,
     *  u ISDS zpráv na soubor se serializovaným objektem s informacemi o zprávě.
     * @param Storage_Basic $storage
     * @return string  filename
     */
    public function getMessageSource($storage)
    {
        $FileModel = new FileModel();
        $file = $FileModel->getInfo($this->file_id);
        $path = $storage->getFilePath($file);

        return $path;
    }
}
