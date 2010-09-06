<?php

class Spisovka_SpojitPresenter extends BasePresenter
{

    public function renderVyber()
    {
        $this->template->dokument_id = $this->getParam('id',null);
    }

    public function renderNacti()
    {
        $dokument_id = $this->getParam('id',null);
        $query = $this->getParam('q',null);

        $Dokument = new Dokument();
        $args = $Dokument->hledat($query,'dokument');
        $args['order'] = array('podaci_denik_rok','podaci_denik_poradi');

        $seznam = $Dokument->seznam($args);

        if ( count($seznam)>0 ) {
            $tmp = array();
            foreach ( $seznam as $dokument_id ) {

                if (is_object($dokument_id) ) { $dokument_id = $dokument_id->dokument_id; }

                $dok = $Dokument->getBasicInfo($dokument_id);
                $tmp[ $dok->id ]['dokument_id'] = $dok->id;
                $tmp[ $dok->id ]['cislo_jednaci'] = $dok->cislo_jednaci;
                $tmp[ $dok->id ]['jid'] = $dok->jid;
                $tmp[ $dok->id ]['nazev'] = $dok->nazev;
            }
            echo json_encode($tmp);

        } else {
            echo "";
        }

        $this->terminate();
    }

    public function renderVybrano()
    {

        $dokument_id = $this->getParam('id',null);
        $dokument_spojit = $this->getParam('spojit_s',null);

        $Dokument = new Dokument();

        $dok_in = $Dokument->getBasicInfo($dokument_id);
        $dok_out = $Dokument->getBasicInfo($dokument_spojit);
        if ( $dok_in && $dok_out ) {

            // spojit s dokumentem
            $SouvisejiciDokument = new SouvisejiciDokument();
            $SouvisejiciDokument->spojit($dokument_id, $dokument_spojit);

            echo '###vybrano###'. $dok_out->cislo_jednaci .' ('. $dok_out->jid .')';//. $spis->nazev;
            $this->terminate();

        } else {
            // chyba
            $this->template->chyba = 1;
            $this->template->render('vyber');
        }
    }

    public function renderOdebrat()
    {
        $dokument_id = $this->getParam('id',null);
        $spojit_s = $this->getParam('spojeny',null);

        $Souvisejici = new SouvisejiciDokument();
        $param = array( array('dokument_id=%i',$dokument_id),array('spojit_s=%i',$spojit_s) );

        if ( $Souvisejici->odebrat($param) ) {
            $this->flashMessage('Spojený dokument byl odebrán z dokumentu.');
        } else {
            $this->flashMessage('Spojený dokument se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }




}
