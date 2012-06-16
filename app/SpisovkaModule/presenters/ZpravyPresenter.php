<?php

class Spisovka_ZpravyPresenter extends BasePresenter {

    public $backlink = '';
    
    public function renderDefault()
    {

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;
        $Zprava = new Zprava();
        $result = $Zprava->nacti();
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        
        $this->template->seznam = $seznam;
        
    }
    
    public function actionPrectena($backlink)
    {
        
        $id = $this->getParam('id');
        if ( $id ) {
            $Zprava = new Zprava();
            $Zprava->precteno($id);
        }
        
        $this->backlink = $backlink;
        $this->redirect(':Spisovka:Dokumenty:default');
    }
    
    public function actionPrecteno($backlink)
    {
        
        $id = $this->getParam('id');
        if ( $id ) {
            $Zprava = new Zprava();
            $Zprava->precteno($id);
        }
        
        $this->backlink = $backlink;
        $this->redirect(':Spisovka:Zpravy:default');
    }    
    
    public function actionSkryt($backlink)
    {
        
        $id = $this->getParam('id');
        if ( $id ) {
            $Zprava = new Zprava();
            $Zprava->skryt($id);
        }
        
        $this->backlink = $backlink;
        $this->redirect(':Spisovka:Zpravy:default');
    }    

    public function actionNacti()
    {
        
        /* Kontrola novych zprav z webu pres ajax */
        Zprava::informace_z_webu();
        exit;
        
    }
    
}

