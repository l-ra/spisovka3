<?php

// Trida NESMI dedit z BasePresenteru (kvuli autentizaci)

class Spisovka_CronPresenter extends Nette\Application\UI\Presenter
{

    public function renderDefault()
    {
    }

    public function actionSpustit()
    {
        
        /* Kontrola novych zprav z webu */
        UpdateAgent::update(UpdateAgent::CHECK_NOTICES);

        /* Kontrola nove verze */
        UpdateAgent::update(UpdateAgent::CHECK_NEW_VERSION);
        
        // Zjisti, kdy naposledy byly odeslany informace o uzivateli a po uplynuti urciteho intervalu je odesli znovu
        //BonzAgent::bonzuj();
        
        exit;
    }   
    
}