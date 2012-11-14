<?php

// Trida NESMI dedit z BasePresenteru (kvuli autentizaci)

class Spisovka_CronPresenter extends Presenter
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