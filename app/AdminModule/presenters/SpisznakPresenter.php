<?php

class Admin_SpisznakPresenter extends BasePresenter
{

    private $spisznak;

    public function renderSeznam()
    {
        $this->template->title = " - Seznam spisových znaků";

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $where = null;// array( array('ciselna_rada LIKE %s','ORG_12%') );

        $SpisovyZnak = new SpisovyZnak();
        $result = $SpisovyZnak->seznam($where,5);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nový spisový znak";

    }


    public function actionDetail()
    {
        

        $this->template->FormUpravit = $this->getParam('upravit',null);
        $id = $this->getParam('id',null);

        $SpisovyZnak = new SpisovyZnak();
        $this->spisznak = $SpisovyZnak->getInfo($id);
        $this->template->SpisZnak = $this->spisznak;

        $this->template->title = " - Detail spisového znaku";

    }

    public function actionOdebrat1()
    {

        $spisznak_id = $this->getParam('id',null);
        $SpisovyZnak = new SpisovyZnak();
        if ( is_numeric($spisznak_id) ) {
            try {
                $res = $SpisovyZnak->odstranit($spisznak_id, 1);
                if ( $res == 0 ) {
                    $this->flashMessage('Spisový znak byl úspěšně odstraněn.');
                } else if ( $res == -1 ) {
                    $this->flashMessage('Některý ze spisových znaků je využíván v aplikaci.<br>Z toho důvodu není možné spisové znaky odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spisový znak se nepodařilo odstranit.','warning');
                }
            } catch (Exception $e) {
                if ( $e->getCode() == 1451 ) {
                    $this->flashMessage('Některý ze spisových znaků je využíván v aplikaci.<br>Z toho důvodu není možné spisové znaky odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spisový znak se nepodařilo odstranit.','warning');
                    $this->flashMessage($e->getMessage(),'warning');
                }
            }
        }
        $this->redirect(':Admin:Spisznak:seznam');

    }

    public function actionOdebrat2()
    {

        $spisznak_id = $this->getParam('id',null);
        $SpisovyZnak = new SpisovyZnak();
        if ( is_numeric($spisznak_id) ) {
            try {
                $res = $SpisovyZnak->odstranit($spisznak_id, 2);
                if ( $res !== false ) {
                    $this->flashMessage('Spisový znak byl úspěšně odstraněn.');
                } else if ( $res == -1 ) {
                    $this->flashMessage('Spisový znak je využíván v aplikaci.<br>Z toho důvodu není možné spisový znak odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spisový znak se nepodařilo odstranit.','warning');
                }
            } catch (Exception $e) {
                if ( $e->getCode() == 1451 ) {
                    $this->flashMessage('Spisový znak je využíván v aplikaci.<br>Z toho důvodu není možné spisový znak odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spisový znak se nepodařilo odstranit.','warning');
                    $this->flashMessage($e->getMessage(),'warning');
                }
            }
        }
        $this->redirect(':Admin:Spisznak:seznam');

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
        if ( empty($this->spisznak) ) {
            $spisznak = $SpisovyZnak->getInfo($this->getParam('id',null));
        } else {
            $spisznak = $this->spisznak;
        }
        $spisznak_seznam = $SpisovyZnak->select(1, @$spisznak->id);
        $stav_select = SpisovyZnak::stav();
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');


        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$spisznak->id);

        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->setValue(@$spisznak->nazev)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Věcná skupina:', 50, 200)
                ->setValue(@$spisznak->popis);
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$spisznak->skartacni_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta:', 5, 5)
                ->setValue(@$spisznak->skartacni_lhuta);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci)
                ->setValue(@$spisznak->spousteci_udalost_id);

        $form1->addSelect('parent_id', 'Připojit k:', $spisznak_seznam)
                ->setValue(@$spisznak->parent_id);
        $form1->addHidden('parent_id_old')
                ->setValue(@$spisznak->parent_id);
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

        $SpisovyZnak = new SpisovyZnak();

        try {
            $res = $SpisovyZnak->upravit($data, $spisznak_id);
            if ( is_object($res) ) {
                $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
                $this->flashMessage($res->getMessage(),'warning');
                $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
            } else {
                $this->flashMessage('Spisový znak  "'. $data['nazev'] .'"  byl upraven.');
                $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
            }            
        } catch (DibiException $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            //Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $this->redirect('this',array('id'=>$data['id']));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Spisznak:seznam');
    }

    protected function createComponentNovyForm()
    {

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(1);
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $form1 = new AppForm();
        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Věcná skupina:', 50, 200);
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta:', 5, 5);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci);
        $form1->addSelect('parent_id', 'Připojit k:', $spisznak_seznam);
        $form1->addSubmit('vytvorit', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('vytvorit_a_novy', 'Vytvořit spisový znak a založit nový')
                 ->onClick[] = array($this, 'vytvoritanovyClicked');
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

        try {
            $spisznak_id = $SpisovyZnak->vytvorit($data);
            if ( is_object($spisznak_id) ) {
                $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
                $this->flashMessage($spisznak_id->getMessage(),'warning');
                //$this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
            } else {
                $this->flashMessage('Spisový znak  "'. $data['nazev'] .'" byl vytvořen.');
                $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
            }              
        } catch (DibiException $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }
    }
    
    public function vytvoritanovyClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $SpisovyZnak = new SpisovyZnak();

        try {
            $spisznak_id = $SpisovyZnak->vytvorit($data);
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'"  byl vytvořen.');
            $this->redirect(':Admin:Spisznak:novy');
        } catch (DibiException $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }
    }    

}
