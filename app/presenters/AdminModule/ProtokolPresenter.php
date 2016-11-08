<?php

class Admin_ProtokolPresenter extends BasePresenter
{

    public function renderDefault()
    {
        $this->template->title = " - Protokoly";
    }

    public function renderPrihlaseni()
    {
        $this->template->title = " - Protokol přihlášených uživatelů";

        $limit = 50;
        $offset = 0;
        $user_id = null;
        $LogModel = new LogModel();

        $this->template->seznam = $LogModel->seznamPristupu($limit, $offset, $user_id);
    }

    public function renderIsds($delete = false)
    {
        $filename = ISDS_Logger::getFilename();
        if ($delete) {
            if (is_file($filename))
                unlink($filename);
            $this->flashMessage('Protokol byl smazán.');
            $this->redirect('isds');
        }
        
        $data = null;
        if (is_file($filename))
            $data = file_get_contents($filename);
        if (empty($data))
            $data = 'Protokol je prázdný.';
        
        // Toto je důležité v případě, kdyby protokol obsahoval binární data
        $data = htmlSpecialChars($data, ENT_COMPAT, 'ISO-8859-1');
        $this->template->protocol = $data;
    }
}
