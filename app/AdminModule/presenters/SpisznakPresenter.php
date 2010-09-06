<?php

class Admin_SpisznakPresenter extends BasePresenter
{

    public function renderSeznam()
    {
        $this->template->title = " - Seznam spisových znaků";

        $SpisovyZnak = new SpisovyZnak();

        $args = null;// array( 'where'=>array("nazev like %s",'%blue%') );

        $seznam = $SpisovyZnak->seznam($args);

        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nový spisový znak";

    }


    public function actionDetail()
    {
        

        $this->template->FormUpravit = $this->getParam('upravit',null);

        $spisznak_id = $this->getParam('id',null);
        $SpisovyZnak = new SpisovyZnak();

        $spisznak = $SpisovyZnak->getInfo($spisznak_id);
        $this->template->SpisZnak = $spisznak;

        //$this->template->SpisovyZnakNad = $SpisovyZnak->seznam_nad($spisznak_id,1);
        //$this->template->SpisovyZnakPod = $SpisovyZnak->seznam_pod($spisznak_id,1);

        $this->template->title = " - Detail spisového znaku";

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
 * Formular a zpracovani pro zmenu spisoveho znaku
 *
 */

    protected function createComponentUpravitForm()
    {

        $SpisovyZnak = new SpisovyZnak();

        $spisznak = $this->template->SpisZnak;
        $stav_select = SpisovyZnak::stav();
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');


        $spisznak_seznam = $SpisovyZnak->seznam(null,1);
        $spisznak_seznam[0] = '(hlavní větev)';
        $spisznak_seznam_pod = $SpisovyZnak->seznam_pod(@$spisznak->id);
        $spisznak_seznam_pod[] = @$spisznak->id;
        foreach ($spisznak_seznam_pod as $sp) {
            if ( array_key_exists($sp, $spisznak_seznam) ) {
                unset( $spisznak_seznam[ $sp ] );
            }
        }

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$spisznak->id);
        $form1->addHidden('spisznak_parent_old')
                ->setValue(@$spisznak->spisznak_parent);

        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->setValue(@$spisznak->nazev)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200)
                ->setValue(@$spisznak->popis);
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$spisznak->skartacni_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta:', 5, 5)
                ->setValue(@$spisznak->skartacni_lhuta);
        $form1->addSelect('spousteci_udalost', 'Spouštěcí událost:', $spousteci)
                ->setValue(@$spisznak->spousteci_udalost);

        $form1->addSelect('spisznak_parent', 'Připojit k:', $spisznak_seznam)
                ->setValue(@$spisznak->spisznak_parent);
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select)
                ->setValue(@$spisznak->stav);

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

        $spisznak_id = $data['id'];
        unset($data['id']);
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->id;


        $SpisovyZnak = new SpisovyZnak();

        try {
            $SpisovyZnak->upravit($data, $spisznak_id);
            $this->flashMessage('Spisový znak  "'. $data['nazev'] .'"  byl upraven.');
            $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $this->redirect('this',array('id'=>$data['spisznak_id']));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Spisznak:seznam');
    }

    protected function createComponentNovyForm()
    {

        $SpisovyZnak = new SpisovyZnak();

        $spisznak_seznam = $SpisovyZnak->seznam(null,1);
        $spisznak_seznam[''] = '(hlavní větev)';

        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $form1 = new AppForm();
        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta:', 5, 5);
        $form1->addSelect('spousteci_udalost', 'Spouštěcí událost:', $spousteci);

        $form1->addSelect('spisznak_parent', 'Připojit k:', $spisznak_seznam);

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

        $SpisovyZnak = new SpisovyZnak();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_created'] = Environment::getUser()->getIdentity()->user_id;

        try {
            $spisznak_id = $SpisovyZnak->vytvorit($data);
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'"  byl vytvořen.');
            $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
        }
    }


}
