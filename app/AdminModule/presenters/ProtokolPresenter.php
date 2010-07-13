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

        $this->template->seznam = $LogModel->seznamPristupu($limit,$offset,$user_id);


    }



}
