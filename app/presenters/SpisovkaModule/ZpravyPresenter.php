<?php

namespace Spisovka;

use Nette;

class Spisovka_ZpravyPresenter extends BasePresenter
{

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
        /* $client_config = Environment::getVariable('client_config');
          $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
          $paginator = $vp->getPaginator();
          $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek)?$client_config->nastaveni->pocet_polozek:20;

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
        if ($id) {
            // $Zpravy = new Zpravy();
            // $Zpravy->precteno($id);
            echo 'OK';
        }
        exit;
    }

}
