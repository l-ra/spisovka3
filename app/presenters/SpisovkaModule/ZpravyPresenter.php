<?php

class Spisovka_ZpravyPresenter extends BasePresenter {
   
    // Zobrazi neprectene zpravy
    public function renderDefault()
    {
        $this->zobrazZpravy(true);
    }
    
    // Zobrazi vsechny, to je i prectene zpravy
    public function renderVsechny()
    {
        $this->zobrazZpravy(false);

        // Pouzij spolecnou sablonu
        $this->setView('default');
    }

    protected function zobrazZpravy($jen_neprectene)
    {
        /* $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $result = Zpravy::nacti();
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        */

        $seznam = array();
        $this->template->seznam = $seznam;
    }
    
    // Melo by byt implementovano jako Ajax akce
    public function actionPrecteno()
    {
        $id = $this->getParameter('id');
        if ( $id ) {
            // $Zpravy = new Zpravy();
            // $Zpravy->precteno($id);
            echo 'OK';
        }
        exit;
    }

}
