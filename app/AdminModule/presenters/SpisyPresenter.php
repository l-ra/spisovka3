<?php

class Admin_SpisyPresenter extends BasePresenter
{

    private $spis_plan;
    private $hledat;

    public function actionSeznam()
    {
        $Spisy = new Spis();
        $this->spis_plan = $Spisy->seznamSpisovychPlanu();
    }

    public function renderSeznam($hledat = null)
    {

        $Spisy = new Spis();

        $session_spisplan = Environment::getSession('s3_spisplan');
        $spis_id = $this->getParam('id',null);

        if ( !is_null($spis_id) ) {
            // spis_id
        } else if ( !empty($session_spisplan->spis_id) ) {
            $spis_id = $session_spisplan->spis_id;
        } else if ( count($this->spis_plan)>0 ) {
            reset($this->spis_plan);
            $spis_id = key($this->spis_plan);
        } else {
            $spis_id = null;
        }

        if ( !empty($spis_id) ) {

            $this->template->SpisovyPlan = $Spisy->getInfo($spis_id);

            $args = null;
            if ( !empty($hledat) ) {
                $args = array( 'where'=>array(array("tb.nazev LIKE %s",'%'.$hledat.'%')));
            }

            $user_config = Environment::getVariable('user_config');
            $vp = new VisualPaginator($this, 'vp');
            $paginator = $vp->getPaginator();
            $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

            $result = $Spisy->seznam($args, 5, $spis_id);
            $paginator->itemCount = count($result);
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $this->template->seznam = $seznam;


            //$seznam = $Spisy->seznam(null, 0, $spis_id);
            //$this->template->seznam = $seznam;

            $session_spisplan->spis_id = $spis_id;
        } else {
            $this->template->seznam = null;
        }

        $this->template->spisplanForm = $this['spisplanForm'];

    }


    public function actionDetail()
    {
        

        $this->template->FormUpravit = $this->getParam('upravit',null);

        $spis_id = $this->getParam('id',null);
        $Spisy = new Spis();

        $spis = $Spisy->getInfo($spis_id);
        $this->template->Spis = $spis;

        $this->template->SpisyNad = null;// $Spisy->seznam_nad($spis_id,1);
        $this->template->SpisyPod = null;//$Spisy->seznam_pod($spis_id,1);

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
        $this->template->spisForm = $this['upravitForm'];
    }

    public function actionUpravit()
    {
        $session_spisplan = Environment::getSession('s3_spisplan');
        $spis_id = $this->getParam('id',null);
        if ( !is_null($spis_id) ) {
            // spis_id
        } else if ( !empty($session_spisplan->spis_id) ) {
            $spis_id = $session_spisplan->spis_id;
        } else {
            $this->flashMessage('Spisový plán nenalezen!','warning');
            $this->redirect(':Admin:Spisy:seznam');
        }
        $this->spis_plan = $spis_id;

    }

    public function renderUpravit()
    {
        $this->template->spisForm = $this['upravitSpisovyPlanForm'];
    }

    public function renderNovy()
    {
        //$SpisovyZnak = new SpisovyZnak();
        //$spisove_znaky = $SpisovyZnak->seznam(null);
        //$this->template->SpisoveZnaky = $spisove_znaky;
        $this->template->spisForm = $this['novyForm'];
    }

    public function renderNovyplan()
    {
        $this->template->spisForm = $this['novySpisovyPlanForm'];
    }

    /**
     * Select formular se seznamem spisovych planu
     *
     * @return AppForm
     */
    protected function createComponentSpisplanForm()
    {

        $session_spisplan = Environment::getSession('s3_spisplan');

        //Debug::dump($session_spisplan->spis_id);

        $form = new AppForm();
        $form->addSelect('spisplan', 'Zobrazit spisový plán:', $this->spis_plan)
                ->setValue($session_spisplan->spis_id)
                ->getControlPrototype()->onchange("return document.forms['frm-spisplanForm'].submit();");
        $form->addSubmit('go_spisplan', 'Zobrazit')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'spisplanClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function spisplanClicked(SubmitButton $button)
    {
        $form_data = $button->getForm()->getValues();
        $session_spisplan = Environment::getSession('s3_spisplan');
        $session_spisplan->spis_id = $form_data['spisplan'];

        //Debug::dump($form_data['spisplan']);
        //Debug::dump($session_spisplan->spis_id);
        $this->forward('seznam', array('id'=>$form_data['spisplan']) );
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
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        if ( empty($spis->spisovy_znak_index) ) {
            $spisovy_znak_max = $Spisy->maxSpisovyZnak( @$spis->id );
        } else {
            $spisovy_znak_max = $spis->spisovy_znak_index;
        }

        //$spisy = $Spisy->select(1,@$spis->id);

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

        $form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
                ->setValue($spisovy_znak_max)
                ->getControlPrototype()->onblur("return kontrolaSpisovyZnak('upravit');");

        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$spis->skartacni_znak);

        $form1->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$spis->skartacni_lhuta);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci)
                ->setValue(@$spis->spousteci_udalost_id);

        $unixtime = strtotime(@$spis->datum_otevreni);
        if ( $unixtime == 0 ) {
            $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10);
        } else {
            $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10)
                ->setValue( date('d.m.Y',$unixtime) );
        }

        $unixtime = strtotime(@$spis->datum_uzavreni);
        if ( $unixtime == 0 ) {
            $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10);
        } else {
            $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10)
                ->setValue( date('d.m.Y',$unixtime) );
        }


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

        $Spisy = new Spis();

        try {
            $res = $Spisy->upravit($data, $spis_id);
            if ( is_object($res) ) {
                $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
                $this->flashMessage($res->getMessage(),'warning');
                $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
            } else {
                $this->flashMessage('Spis  "'. $data['nazev'] .'"  byl upraven.');
                $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
            }
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $data['id'];
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
        $stav_select = Spis::stav();
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $session_spisplan = Environment::getSession('s3_spisplan');

        $spisy = $Spisy->select(11, null, $session_spisplan->spis_id);

        $spisovy_znak_max = $Spisy->maxSpisovyZnak( $session_spisplan->spis_id );

        $form1 = new AppForm();
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu);
        $form1->addText('nazev', 'Spisová značka / název:', 50, 80)
                ->addRule(Form::FILLED, 'Spisová značka musí být vyplněna!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('parent_id', 'Mateřská entita:', $spisy)
                ->getControlPrototype()->onchange("return zmenitSpisovyZnak('novy');");

        $form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
                ->setValue($spisovy_znak_max)
                ->getControlPrototype()->onblur("return kontrolaSpisovyZnak('novy');");
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci);
        $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10)
                ->setValue( date('d.m.Y') );
        $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10);

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

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        $Spisy = new Spis();

        try {
            $spis_id = $Spisy->vytvorit($data);
            $this->flashMessage('Spis "'. $data['nazev'] .'"  byl vytvořen.');
            $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
        }
    }

    protected function createComponentNovySpisovyPlanForm()
    {

        $Spisy = new Spis();

        $spisovy_znak_max = $Spisy->maxSpisovyZnak();


        $form1 = new AppForm();
        $form1->addText('nazev', 'Název:', 50, 80)
                ->addRule(Form::FILLED, 'Název spisového plánu musí být vyplněn!');
        $form1->addTextArea('popis', 'Popis:', 80, 5);
        $form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!')
                ->setValue($spisovy_znak_max);
                //->getControlPrototype()->onblur("return kontrolaSpisovyZnak('novyplan');");
        $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10)
                ->setValue( date('d.m.Y') );

        $form1->addSubmit('vytvorit', 'Vytvořit spisový plán')
                 ->onClick[] = array($this, 'vytvoritNovyPlanClicked');
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

    public function vytvoritNovyPlanClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Spisy = new Spis();
        $data['typ'] = 'SP';
        $data['parent_id'] = null;

        try {
            $spis_id = $Spisy->vytvorit($data);
            $this->flashMessage('Spisový plán "'. $data['nazev'] .'"  byl vytvořen.');
            $this->redirect(':Admin:Spisy:seznam',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
        }
    }

    protected function createComponentUpravitSpisovyPlanForm()
    {

        $Spis = new Spis();
        $SpisovyPlan = $Spis->getInfo($this->spis_plan);

        if ( empty($SpisovyPlan->spisovy_znak_index) ) {
            $spisovy_znak_max = $Spis->maxSpisovyZnak( $SpisovyPlan->id );
        } else {
            $spisovy_znak_max = $SpisovyPlan->spisovy_znak_index;
        }

        $form1 = new AppForm();
        $form1->addHidden('spisovy_plan_id')
                ->setValue($SpisovyPlan->id);
        $form1->addText('nazev', 'Název:', 50, 80)
                ->addRule(Form::FILLED, 'Název spisového plánu musí být vyplněn!')
                ->setValue($SpisovyPlan->nazev);
        $form1->addTextArea('popis', 'Popis:', 80, 5)
                ->setValue($SpisovyPlan->popis);
        $form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
                ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!')
                ->setValue($spisovy_znak_max);
                //->getControlPrototype()->onblur("return kontrolaSpisovyZnak('upravitSpisovyPlan');");

        $unixtime = strtotime($SpisovyPlan->datum_otevreni);
        if ( $unixtime == 0 ) {
            $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10);
        } else {
            $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10)
                ->setValue( date('d.m.Y',$unixtime) );
        }

        $unixtime = strtotime($SpisovyPlan->datum_uzavreni);
        if ( $unixtime == 0 ) {
            $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10);
        } else {
            $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10)
                ->setValue( date('d.m.Y',$unixtime) );
        }


        $form1->addSubmit('upravit', 'Upravit spisový plán')
                 ->onClick[] = array($this, 'upravitSpisovyPlanClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function upravitSpisovyPlanClicked(SubmitButton $button)
    {
        $form_data = $button->getForm()->getValues();

        $spisovy_plan_id = $form_data['spisovy_plan_id'];
        unset($form_data['spisovy_plan_id']);

        $Spis = new Spis();
        $form_data['parent_id'] = null;
        $form_data['parent_id_old'] = null;
        $Spis->upravit($form_data, $spisovy_plan_id);

        $this->redirect('seznam');
    }

    public function actionZmenitspisovyznak()
    {
        $Spis = new Spis();
        $spisovy_znak_max = $Spis->maxSpisovyZnak( $this->getParam('id',null) );
        echo $spisovy_znak_max;
        exit;
    }

    public function actionKontrolaspisovyznak()
    {
        $Spis = new Spis();
        $spisovy_znak = $this->getParam('id',null);

        if (is_numeric($spisovy_znak) ) {
            $spisovy_znak_bool = $Spis->kontrolaSpisovyZnak( $spisovy_znak, $this->getParam('spis',null) );
            if ( $spisovy_znak_bool ) {
                echo '1';
            } else {
                echo '0';
            }
        } else {
            echo '-1';
        }

        exit;
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle názvu spisu";

        $form->addSubmit('hledat', 'Hledat')
                 ->onClick[] = array($this, 'hledatSimpleClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function hledatSimpleClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->forward('this', array('hledat'=>$data['dotaz']));

    }

}
