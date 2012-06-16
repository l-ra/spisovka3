<?php

class Spisovka_CronPresenter extends BasePresenter
{


    public function renderDefault()
    {
    }

    public function actionAjax()
    {
        
        /* Kontrola novych zprav z webu */
        Zprava::informace_z_webu();

        /* Kontrola nove verze */
        Zprava::aktualni_verze();
        
        exit;
    }

    
    
}