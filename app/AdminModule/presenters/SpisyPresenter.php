<?php

class Admin_SpisyPresenter extends BasePresenter
{

    public function renderSeznam()
    {

        $Spisy = new Spis();
        $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
        $seznam = $Spisy->seznam($args);
        $this->template->seznam = $seznam;

    }


    public function actionDetail()
    {
        

        $this->template->FormUpravit = $this->getParam('upravit',null);

        $spis_id = $this->getParam('id',null);
        $Spisy = new Spis();

        $spis = $Spisy->getInfo($spis_id);
        $this->template->Spis = $spis;

        $this->template->SpisyNad = $Spisy->seznam_nad($spis_id,1);
        $this->template->SpisyPod = $Spisy->seznam_pod($spis_id,1);

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

    }

    public function renderDetail()
    {
        $this->template->upravitForm = $this['upravitForm'];
    }

    public function renderNovy()
    {

        $this->template->novyForm = $this['novyForm'];
    }
/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    protected function createComponentUpravitForm()
    {

        $Spisy = new Spis();

        $spis = $this->template->Spis;
        $typ_spisu = Spis::typSpisu();
        $stav_select = Spis::stav();

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = array();
        $spisznak_seznam[0] = 'vyberte z nabídky ...';
        $spisznak_seznam = @array_merge($spisznak_seznam, $SpisovyZnak->seznam(null,1));


        $spisy = $Spisy->seznam(null,1);
        $spisy_pod = $Spisy->seznam_pod(@$spis->spis_id);
        $spisy_pod[] = @$spis->spis_id;
        foreach ($spisy_pod as $sp) {
            if ( array_key_exists($sp, $spisy) ) {
                unset( $spisy[ $sp ] );
            }
        }

        $form1 = new AppForm();
        $form1->addHidden('spis_id')
                ->setValue(@$spis->spis_id);
        $form1->addHidden('spis_parent_old')
                ->setValue(@$spis->spis_parent);

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

        $spis_id = $data['spis_id'];
        unset($data['spis_id']);
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->user_id;


        $Spisy = new Spis();

        try {
            $Spisy->upravit($data, $spis_id);
            $this->flashMessage('Spis  "'. $data['nazev'] .'"  byl upraven.');
            $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $data['spis_id'];
        $this->redirect('this',array('id'=>$spis_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Spisy:seznam');
    }

    protected function createComponentNovyForm()
    {

        $Spisy = new Spis();

        $typ_spisu = Spis::typSpisu();
        $spisy = $Spisy->seznam(null,1);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = array();
        $spisznak_seznam[0] = 'vyberte z nabídky ...';
        $spisznak_seznam = @array_merge($spisznak_seznam, $SpisovyZnak->seznam(null,1));

        $form1 = new AppForm();
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu);
        $form1->addText('nazev', 'Spisová značka / název:', 50, 80)
                ->addRule(Form::FILLED, 'Spisová značka musí být vyplněna!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('spis_parent', 'Připojit k:', $spisy);

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

        $form1->addSubmit('vytvorit', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
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

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Spisy = new Spis();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_created'] = Environment::getUser()->getIdentity()->user_id;

        try {
            $spis_id = $Spisy->vytvorit($data);
            $this->flashMessage('Spis "'. $data['nazev'] .'"  byl vytvořen.');
            $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
        }
    }


}
