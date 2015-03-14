<?php

class Spisovka_SpojitPresenter extends BasePresenter
{

    private $typ_evidence = 'priorace';

    public function startup()
    {
        $user_config = Nette\Environment::getVariable('user_config');
        $this->typ_evidence = 0;
        if ( isset($user_config->cislo_jednaci->typ_evidence) ) {
            $this->typ_evidence = $user_config->cislo_jednaci->typ_evidence;
        } else {
            $this->typ_evidence = 'priorace';
        }

        parent::startup();
    }

    public function renderVyber()
    {
        $this->template->dokument_id = $this->getParam('id',null);
    }

    public function renderNacti()
    {
        $query = $this->getParam('q',null);

        $Dokument = new Dokument();
        $args = $Dokument->hledat($query,'dokument');
        $args['order'] = array('d.podaci_denik_rok','d.podaci_denik_poradi','d.poradi');

        // nehledej mezi dokumenty ve spisovně
        $args = $Dokument->spisovka($args);
        $seznam = $Dokument->seznam($args);

        if ( count($seznam)>0 ) {
            
            if ( count($seznam) > 200 ) {
                echo "prilis_mnoho";
                $this->terminate();
            }
            
            $tmp = array();
            foreach ( $seznam as $dokument_id ) {

                if (is_object($dokument_id) ) { $dokument_id = $dokument_id->dokument_id; }

                $dok = $Dokument->getBasicInfo($dokument_id);

                // Tomášovina - kód je použit též u sběrného archu pro zařazení dokumentu do spisu
                if ($this->typ_evidence == "sberny_arch") {
                    if ($dok->poradi != 1 || empty($dok->cislo_jednaci))
                        continue;
                }

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

    public function actionVybrano()
    {
        $dokument_id = $this->getParam('id',null);
        $dokument_spojit = $this->getParam('spojit_s',null);

        $Dokument = new Dokument();

        try {
            $dok_in = $Dokument->getBasicInfo($dokument_id);
            $dok_out = $Dokument->getBasicInfo($dokument_spojit);
            if (!$dok_in || !$dok_out)
                throw new Exception('Neplatný parametr');

            // spojit s dokumentem
            $SouvisejiciDokument = new SouvisejiciDokument();
            $SouvisejiciDokument->spojit($dokument_id, $dokument_spojit);

            echo '###vybrano###'. $dok_out->cislo_jednaci .' ('. $dok_out->jid .')';//. $spis->nazev;
        } 
        catch (Exception $e) {
            echo 'Při pokusu o spojení dokumentů došlo k chybě - ' . $e->getMessage();
        }
        $this->terminate();
    }

    public function renderOdebrat()
    {
        $dokument_id = $this->getParam('id',null);
        $spojit_s = $this->getParam('spojeny',null);
        $zpetne_spojeny = $this->getParam('zpetne_spojeny',null);

        $Souvisejici = new SouvisejiciDokument();
        
        if ( $zpetne_spojeny ) {
            $param = array( array('spojit_s_id=%i',$dokument_id),array('dokument_id=%i',$zpetne_spojeny) );
        } else {
            $param = array( array('dokument_id=%i',$dokument_id),array('spojit_s_id=%i',$spojit_s) );
        }
        

        if ( $Souvisejici->odebrat($param) ) {
            $this->flashMessage('Spojený dokument byl odebrán z dokumentu.');
        } else {
            $this->flashMessage('Spojený dokument se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }




}
