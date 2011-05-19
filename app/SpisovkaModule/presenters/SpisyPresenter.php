<?php

class Spisovka_SpisyPresenter extends BasePresenter
{

    private $dokument_id;
    private $typ_evidence = null;
    private $oddelovac_poradi = null;

    public function startup()
    {
        $user_config = Environment::getVariable('user_config');
        $this->typ_evidence = 0;
        if ( isset($user_config->cislo_jednaci->typ_evidence) ) {
            $this->typ_evidence = $user_config->cislo_jednaci->typ_evidence;
        } else {
            $this->typ_evidence = 'priorace';
        }
        $this->template->Typ_evidence = $this->typ_evidence;
        
        if ( isset($user_config->cislo_jednaci->oddelovac) ) {
            $this->oddelovac_poradi = $user_config->cislo_jednaci->oddelovac;
        } else {
            $this->oddelovac_poradi = '/';
        }
        $this->template->Oddelovac_poradi = $this->oddelovac_poradi;
        parent::startup();
    }

    public function actionVyber()
    {
        $this->dokument_id = $this->getParam('id',null);
        if ( empty($this->dokument_iddokument_id) ) {
            if ( isset($_POST['dokument_id']) ) {
                $this->dokument_id = $_POST['dokument_id'];
            }
        }
    }

    public function renderVyber()
    {

        $Spisy = new Spis();
        $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
        $seznam = $Spisy->seznam($args);
        $this->template->seznam = $seznam;
        $this->template->dokument_id = $this->dokument_id;

    }

    public function renderVybrano()
    {

        $spis_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);
        $Spisy = new Spis();

        $spis = $Spisy->getInfo($spis_id);
        if ( $spis ) {

            // Propojit s dokumentem
            $DokumentSpis = new DokumentSpis();
            $DokumentSpis->pripojit($dokument_id, $spis_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::SPIS_DOK_PRIPOJEN,'Dokument přidán do spisu "'. $spis->nazev .'"');

            echo '###vybrano###'. $spis->nazev;
            $this->terminate();

        } else {
            // chyba
            

            $Spisy = new Spis();
            $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
            $seznam = $Spisy->seznam($args);
            $this->template->seznam = $seznam;
            $this->template->dokument_id = $dokument_id;

            $this->template->chyba = 1;
            
            $this->template->render('vyber');
        }
        
    }


    public function renderDefault()
    {
        $Spisy = new Spis();
        $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
        $seznam = $Spisy->seznam($args);
        $this->template->seznam = $seznam;

    }

    public function actionDetail()
    {
        
        $spis_id = $this->getParam('id',null);
        // Info o spisu
        $Spisy = new Spis();
        $this->template->Spis = $spis = $Spisy->getInfo($spis_id);

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->seznam(null);
        $this->template->SpisoveZnaky = $spisove_znaky;

        if ( isset($spisove_znaky[ @$spis->spisovy_znak ]) ) {
            $this->template->SpisZnak_popis = $spisove_znaky[ $spis->spisovy_znak ]->popis;
            $this->template->SpisZnak_nazev = $spisove_znaky[ $spis->spisovy_znak ]->nazev;
        } else {
            $this->template->SpisZnak_popis = "";
            $this->template->SpisZnak_nazev = "";
        }

        $DokumentSpis = new DokumentSpis();
        //$user_config = Environment::getVariable('user_config');
        //$vp = new VisualPaginator($this, 'vp');
        //$paginator = $vp->getPaginator();
        //$paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;
        $result = $DokumentSpis->dokumenty($spis_id,1);
        //$paginator->itemCount = count($result);
        //$seznam = $DokumentSpis->fetchPart($result, $paginator->offset, $paginator->itemsPerPage);
        //$this->template->seznam = $seznam;
        $this->template->seznam = $result;

        $this->template->FormUpravit = $this->getParam('upravit',null);

    }

    public function renderDetail()
    {
        $this->template->upravitForm = $this['upravitForm'];
    }

    protected function createComponentUpravitForm()
    {

        $Spisy = new Spis();

        $spis = @$this->template->Spis;
        $typ_spisu = Spis::typSpisu();
        $stav_select = Spis::stav();

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->seznam(null,1);

        $spisy = $Spisy->seznam(null,1);
        $spisy_pod = $Spisy->seznam_pod(@$spis->id);
        $spisy_pod[ @$spis->id ] = @$spis->id;
        foreach ($spisy_pod as $spi => $sp) {
            if ( array_key_exists($spi, $spisy) ) {
                unset( $spisy[ $spi ] );
            }
        }

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$spis->id);
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu)
                ->setValue(@$spis->typ);
        $form1->addText('nazev', 'Spisová značka / název:', 50, 80)
                ->setValue(@$spis->nazev)
                ->addRule(Form::FILLED, 'Spisová značka musí být vyplněna!');
        $form1->addText('popis', 'Popis:', 50, 200)
                ->setValue(@$spis->popis);
        $form1->addSelect('spis_parent', 'Připojit k:', $spisy)
                ->setValue(@$spis->spis_parent);
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select)
                ->setValue(@$spis->stav);

        $form1->addSelect('spisovy_znak', 'Spisový znak:', $spisznak_seznam)
                ->setValue(@$spis->spisovy_znak)
                ->controlPrototype->onchange("vybratSpisovyZnak();");
        $form1->addText('skartacni_znak','Skartační znak: ', 3, 3)
                ->setValue(@$spis->skartacni_znak)
                ->controlPrototype->readonly = TRUE;
        $form1->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$spis->skartacni_lhuta)
                ->controlPrototype->readonly = TRUE;
        $form1->addTextArea('spousteci_udalost','Spouštěcí událost: ', 80, 3)
                ->setValue(@$spis->spousteci_udalost)
                ->controlPrototype->readonly = TRUE;

        $form1->addSubmit('upravit', 'Upravit')
                 ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function upravitClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $spis_id = $data['id'];
        unset($data['id']);
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->id;


        $Spisy = new Spis();

        try {
            $Spisy->upravit($data, $spis_id);
            $this->flashMessage('Spis  "'. $data['nazev'] .'"  byl upraven.');
            $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $data['id'];
        $this->redirect('this',array('id'=>$spis_id));
    }


    protected function createComponentNovyForm()
    {

        $Spisy = new Spis();

        $typ_spisu = Spis::typSpisu();
        $spisy = $Spisy->seznam(null,1);

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('spis-vytvorit');
        $form1->addHidden('dokument_id')
                ->setValue($this->dokument_id);
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu);
        $form1->addText('nazev', 'Spisová značka / název:', 50, 80)
                ->addRule(Form::FILLED, 'Spisová značka musí být vyplněna!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('spis_parent', 'Připojit k:', $spisy);

        $form1->addSubmit('vytvorit', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Spisy = new Spis();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_created'] = Environment::getUser()->getIdentity()->id;
        unset($data['dokument_id']);
        
        try {
            $spis_id = $Spisy->vytvorit($data);

            if ( $spis_id ) {
                $this->flashMessage('Spis "'. $data['nazev'] .'"  byl vytvořen.');
                
                if (!$this->isAjax()) {
                    //$this->redirect('this');
                } else {
                    $this->invalidateControl('dokspis');
                }
            } else {
                throw new Exception;
            }
            
            //$this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
        }
    }


}

