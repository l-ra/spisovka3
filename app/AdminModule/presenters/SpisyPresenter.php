<?php

class Admin_SpisyPresenter extends BasePresenter
{

    private $typ_evidence = null;
    private $oddelovac_poradi = null;    
    private $spis_plan;
    private $hledat;
    private $pdf_output = 0;

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
    
    protected function shutdown($response) {
        
        if ($this->pdf_output == 1 || $this->pdf_output == 2) {

            function handlePDFError($errno, $errstr, $errfile, $errline, array $errcontext)
            {
                if (0 === error_reporting()) {
                    return;
                }
                //if ( $errno == 8 ) {
                if ( strpos($errstr,'Undefined') === false ) {    
                    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
                }
                
                
            }
            set_error_handler('handlePDFError');
            
            try {                
        
            ob_start();
            $response->send();
            $content = ob_get_clean();
            if ($content) {
        
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                
                if ($this->pdf_output == 2) {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s','', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s','', $content);
                
                    $mpdf = new mPDF('iso-8859-2', 'A4',9,'Helvetica');
                
                    $app_info = Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor(Environment::getUser()->getIdentity()->name);
                    $mpdf->SetTitle('Spisová služba - Detail spisu');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader('||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('dokument.pdf', 'I');                    
                } else {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s','', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s','', $content);
                
                    $mpdf = new mPDF('iso-8859-2', 'A4-L',9,'Helvetica');
                
                    $app_info = Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor(Environment::getUser()->getIdentity()->name);
                    $mpdf->SetTitle('Spisová služba - Tisk');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader('Seznam spisů||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('spisova_sluzba.pdf', 'I');
                }
            }
            
            } catch (Exception $e) {
                $location = str_replace("pdfprint=1","",Environment::getHttpRequest()->getUri());

                echo "<h1>Nelze vygenerovat PDF výstup.</h1>";
                echo "<p>Generovaný obsah obsahuje příliš mnoho dat, které není možné zpracovat.</p>";
                echo "<p><a href=".$location.">Přejít na předchozí stránku.</a></p>";
                echo "<p>".$e->getMessage()."</p>";
                exit;
            }
            
        }
        
    }    
    
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
            
            // Volba vystupu - web/tisk/pdf
            $tisk = $this->getParam('print');
            $pdf = $this->getParam('pdfprint');
            if ( $tisk ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
                $seznam = $result->fetchAll();
                if ( count($seznam)>0 ) {
                    $spis_ids = array();
                    foreach($seznam as $spis) {
                        $spis_ids[] = $spis->id;
                    }
                    $this->template->seznam_dokumentu = $Spisy->seznamDokumentu($spis_ids);
                } else {
                    $this->template->seznam_dokumentu = array();
                }               
                $this->setLayout(false);
                $this->setView('print');
            } elseif ( $pdf ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $this->pdf_output = 1;
                //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
                $seznam = $result->fetchAll();
                if ( count($seznam)>0 ) {
                    $spis_ids = array();
                    foreach($seznam as $spis) {
                        $spis_ids[] = $spis->id;
                    }
                    $this->template->seznam_dokumentu = $Spisy->seznamDokumentu($spis_ids);
                } else {
                    $this->template->seznam_dokumentu = array();
                }                
                $this->setLayout(false);
                $this->setView('print');
            } else {
                $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            }               
            
            $this->template->seznam = $seznam;

            $SpisovyZnak = new SpisovyZnak();
            $spisove_znaky = $SpisovyZnak->select(11);
            $this->template->SpisoveZnaky = $spisove_znaky;            

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
        $spisove_znaky = $SpisovyZnak->select(11);
        $this->template->SpisoveZnaky = $spisove_znaky;
        
        if ( isset($spisove_znaky[ @$spis->spisovy_znak_id ]) ) {
            $this->template->SpisZnak_popis = $spisove_znaky[ $spis->spisovy_znak_id ]->popis;
            $this->template->SpisZnak_nazev = $spisove_znaky[ $spis->spisovy_znak_id ]->nazev;
        } else {
            $this->template->SpisZnak_popis = "";
            $this->template->SpisZnak_nazev = "";
        }

        $DokumentSpis = new DokumentSpis();
        $result = $DokumentSpis->dokumenty($spis_id,1);
        $this->template->seznam = $result;        
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParam('print');
        $pdf = $this->getParam('pdfprint');
        if ( $tisk ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->setLayout(false);
            $this->setView('printdetail');
        } elseif ( $pdf ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->pdf_output = 2;
            $this->setLayout(false);
            $this->setView('printdetail');
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
        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->seznam(null);
        $this->template->SpisoveZnaky = $spisove_znaky;
        $this->template->spisForm = $this['upravitSpisovyPlanForm'];
    }

    public function actionOdebrat1()
    {

        $spis_id = $this->getParam('id',null);
        $Spis = new Spis();
        if ( is_numeric($spis_id) ) {
            try {
                $res = $Spis->odstranit($spis_id, 1);
                if ( $res == 0 ) {
                    $this->flashMessage('Spis byl úspěšně odstraněn.');
                } else if ( $res == -1 ) {
                    $this->flashMessage('Některý ze spisů je využíván v aplikaci.<br>Z toho důvodu není možné spisy odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spis se nepodařilo odstranit.','warning');
                }
            } catch (Exception $e) {
                if ( $e->getCode() == 1451 ) {
                    $this->flashMessage('Některý ze spisů je využíván v aplikaci.<br>Z toho důvodu není možné spisy odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spis se nepodařilo odstranit.','warning');
                    $this->flashMessage($e->getMessage(),'warning');
                }
            }
        }
        $this->redirect(':Admin:Spisy:seznam');

    }

    public function actionOdebrat2()
    {

        $spis_id = $this->getParam('id',null);
        $Spis = new Spis();
        if ( is_numeric($spis_id) ) {
            try {
                $res = $Spis->odstranit($spis_id, 2);
                if ( $res !== false ) {
                    $this->flashMessage('Spis byl úspěšně odstraněn.');
                } else if ( $res == -1 ) {
                    $this->flashMessage('Spis je využíván v aplikaci.<br>Z toho důvodu není možné spis odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spis se nepodařilo odstranit.','warning');
                }
            } catch (Exception $e) {
                if ( $e->getCode() == 1451 ) {
                    $this->flashMessage('Spis je využíván v aplikaci.<br>Z toho důvodu není možné spis odstranit.','warning_ext');
                } else {
                    $this->flashMessage('Spis se nepodařilo odstranit.','warning');
                    $this->flashMessage($e->getMessage(),'warning');
                }
            }
        }
        $this->redirect(':Admin:Spisy:seznam');

    }    
    
    public function actionStav()
    {

        $spis_id = $this->getParam('id');
        $stav = $this->getParam('stav');

        $Spis = new Spis();

        switch ($stav) {
            case 'uzavrit':
                $stav = $Spis->zmenitStav($spis_id, 0);
                if ( $stav === -1 ) {
                    $this->flashMessage('Spis nelze uzavřít. Jeden nebo více dokumentů nejsou vyřízeny.','warning');
                } else if ( $stav ) {
                    $this->flashMessage('Spis byl uzavřen.');
                } else {
                    $this->flashMessage('Spis se nepodařilo uzavřit.','error');
                }
                break;
            case 'otevrit':
                if ( $Spis->zmenitStav($spis_id, 1) ) {
                    $this->flashMessage('Spis byl otevřen.');
                } else {
                    $this->flashMessage('Spis se nepodařilo otevřít.','error');
                }
                break;
            default:
                break;
        }

        $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));

    }    
    
    public function renderNovy()
    {
        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->seznam(null);
        $this->template->SpisoveZnaky = $spisove_znaky;
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
        
        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);

        $session_spisplan = Environment::getSession('s3_spisplan');
        if ( empty($session_spisplan->spis_id) ) {
            $session_spisplan->spis_id = 1;
        }
        $params = array('where'=> array("tb.typ = 'VS'") );
        //$spisy = $Spisy->select(11, null, $session_spisplan->spis_id, $params);
        $spisy = $Spisy->select(1, @$spis->id, $session_spisplan->spis_id, $params);
        

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$spis->id);
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu)
                ->setValue(@$spis->typ);
        $form1->addText('nazev', 'Název spisu:', 50, 80)
                ->setValue(@$spis->nazev)
                ->addRule(Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200)
                ->setValue(@$spis->popis);
        $form1->addSelect('parent_id', 'Složka:', $spisy)
                ->setValue(@$spis->parent_id);
        $form1->addHidden('parent_id_old')
                ->setValue(@$spis->parent_id);        

        
        $form1->addSelect('spisovy_znak_id', 'Spisový znak:', $spisznak_seznam)
                ->setValue(@$spis->spisovy_znak_id)
                ->controlPrototype->onchange("vybratSpisovyZnak();");
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$spis->skartacni_znak)
                ->controlPrototype->readonly = TRUE;
        $form1->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$spis->skartacni_lhuta)
                ->controlPrototype->readonly = TRUE;
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci)
                ->setValue(@$spis->spousteci_udalost_id)
                ->controlPrototype->readonly = TRUE;

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
        if ( empty($session_spisplan->spis_id) ) {
            $session_spisplan->spis_id = 1;
        }
        $params = array('where'=> array("tb.typ = 'VS'") );
        $spisy = $Spisy->select(11, null, $session_spisplan->spis_id, $params);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);
        //$spisovy_znak_max = $Spisy->maxSpisovyZnak( $session_spisplan->spis_id );

        $form1 = new AppForm();
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu);
        $form1->addText('nazev', 'Název spisu/složky:', 50, 80)
                ->addRule(Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('parent_id', 'Složka:', $spisy)
                ->getControlPrototype()->onchange("return zmenitSpisovyZnak('novy');");

        //$form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
        //        ->setValue($spisovy_znak_max)
        //        ->getControlPrototype()->onblur("return kontrolaSpisovyZnak('novy');");
        $form1->addSelect('spisovy_znak_id', 'Spisový znak:', $spisznak_seznam)
                ->controlPrototype->onchange("vybratSpisovyZnak();");
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
            if ( is_object($spis_id) ) {
                $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','error');
                $this->flashMessage('Error: '. $spis_id->getMessage(),'error');
            } else {
                $this->flashMessage('Spis "'. $data['nazev'] .'"  byl vytvořen.');
                $this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
            }
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
        //$form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
        //        ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!')
        //        ->setValue($spisovy_znak_max);
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
        //$form1->addText('spisovy_znak', 'Spisový znak:', 10, 10)
        //        ->addRule(Form::FILLED, 'Spisový znak musí být vyplněn!')
        //        ->setValue($spisovy_znak_max);
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
