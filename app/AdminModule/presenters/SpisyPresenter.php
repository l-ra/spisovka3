<?php

class Admin_SpisyPresenter extends SpisyPresenter
{

    private $typ_evidence = null;
    private $oddelovac_poradi = null;    
    private $spis_plan;
    private $hledat;
    private $pdf_output = 0;

    public function startup()
    {
        $user_config = Nette\Environment::getVariable('user_config');
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
                
                    $app_info = Nette\Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                    $mpdf->SetTitle('Spisová služba - Detail spisu');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader('||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".$this->user->getIdentity()->display_name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('dokument.pdf', 'I');                    
                } else {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s','', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s','', $content);
                
                    $mpdf = new mPDF('iso-8859-2', 'A4-L',9,'Helvetica');
                
                    $app_info = Nette\Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                    $mpdf->SetTitle('Spisová služba - Tisk');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader('Seznam spisů||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".$this->user->getIdentity()->display_name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('spisova_sluzba.pdf', 'I');
                }
            }            
        }
        
    }    
    
    public function actionSeznam()
    {
    }

    public function renderSeznam($hledat = null)
    {

        $Spisy = new Spis();
        $spis_id = null;

        $args = null;
        if ( !empty($hledat) ) {
            $args = array( 'where'=>array(array("tb.nazev LIKE %s",'%'.$hledat.'%')));
        }

        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $result = $Spisy->seznam($args, 5, $spis_id);
        $paginator->itemCount = count($result);
            
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
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
        $spisove_znaky = $SpisovyZnak->selectBox(11);
        $this->template->SpisoveZnaky = $spisove_znaky;            

    }


    public function renderDetail()
    {
        $this->template->FormUpravit = $this->getParameter('upravit',null);

        $spis_id = $this->getParameter('id',null);
        $Spisy = new Spis();

        $spis = $Spisy->getInfo($spis_id);
        $this->template->Spis = $spis;

        $this->template->SpisyNad = null;// $Spisy->seznam_nad($spis_id,1);
        $this->template->SpisyPod = null;//$Spisy->seznam_pod($spis_id,1);

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->selectBox(11);
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

        $this->template->spisForm = $this['upravitForm'];
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
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

    public function actionUpravit()
    {
        $session_spisplan = Nette\Environment::getSession('s3_spisplan');
        $spis_id = $this->getParameter('id',null);
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

    /* P.L. Pro pripad pridani funkce mazani spisu viz kod v SpisznakPresenter. */
    
    public function actionStav()
    {

        $spis_id = $this->getParameter('id');
        $stav = $this->getParameter('stav');

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

    public function renderImport()
    {
    }    
    
    public function renderExport()
    {
        
        if ( $this->getHttpRequest()->isPost() ) {
            // Exportovani
            $post_data = $this->getHttpRequest()->getPost();
            //Nette\Diagnostics\Debugger::dump($post_data);
            
            $Spis = new Spis();
            $args = null;
            if ( $post_data['export_co'] == 2 ) {
                // pouze aktivni
                $args['where'] = array( array('stav=1') );
            }
            
            $seznam = $Spis->seznam($args,5)->fetchAll();
            
            if ( $seznam ) {
                
                if ( $post_data['export_do'] == "csv" ) {
                    // export do CSV
                    $ignore_cols = array("date_created","user_created","date_modified","user_modified",
                                         "sekvence_string");
                    $export_data = Export::csv(
                                    $seznam, 
                                    $ignore_cols, 
                                    $post_data['csv_code'], 
                                    $post_data['csv_radek'], 
                                    $post_data['csv_sloupce'], 
                                    $post_data['csv_hodnoty']);
                    
                    //echo "<pre>"; echo $export_data; echo "</pre>"; exit;
                
                    $httpResponse = $this->getHttpResponse();
                    $httpResponse->setContentType('application/octetstream');
                    $httpResponse->setHeader('Content-Description', 'File Transfer');
                    $httpResponse->setHeader('Content-Disposition', 'attachment; filename="export_spisu.csv"');
                    $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                    $httpResponse->setHeader('Expires', '0');
                    $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
                    $httpResponse->setHeader('Pragma', 'public');
                    $httpResponse->setHeader('Content-Length', strlen($export_data));
                    echo $export_data;  
                    exit;
                
                }
                
            } else {
                $this->flashMessage('Nebyly nalezany žádné data k exportu!', 'warning');
            }
        }
        
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
        $spisznak_seznam = $SpisovyZnak->selectBox(2);

        $session_spisplan = Nette\Environment::getSession('s3_spisplan');
        if ( empty($session_spisplan->spis_id) ) {
            $session_spisplan->spis_id = 1;
        }
        $params = array('where'=> array("tb.typ = 'VS'") );
        //$spisy = $Spisy->selectBox(11, null, $session_spisplan->spis_id, $params);
        $spisy = $Spisy->selectBox(1, @$spis->id, $session_spisplan->spis_id, $params);
        

        $form1 = new Nette\Application\UI\Form();
        $form1->addHidden('id')
                ->setValue(@$spis->id);
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu)
                ->setValue(@$spis->typ);
        $form1->addText('nazev', 'Název spisu:', 50, 80)
                ->setValue(@$spis->nazev)
                ->addRule(Nette\Forms\Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200)
                ->setValue(@$spis->popis);
        $form1->addSelect('parent_id', 'Složka:', $spisy)
                ->setValue(@$spis->parent_id);
        $form1->addHidden('parent_id_old')
                ->setValue(@$spis->parent_id);    

        $form1->addComponent( new Select2Component('Spisový znak:', $spisznak_seznam), 'spisovy_znak_id');
        $form1->getComponent('spisovy_znak_id')->setValue(@$spis->spisovy_znak_id)
            ->controlPrototype->onchange("vybratSpisovyZnak(this);");
        
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

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $data['id'];
        $this->redirect('this',array('id'=>$spis_id));
    }

    public function stornoNovyClicked(Nette\Forms\Controls\SubmitButton $button)
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

        $session_spisplan = Nette\Environment::getSession('s3_spisplan');
        if ( empty($session_spisplan->spis_id) ) {
            $session_spisplan->spis_id = 1;
        }
        $params = array('where'=> array("tb.typ = 'VS'") );
        $spisy = $Spisy->selectBox(11, null, $session_spisplan->spis_id, $params);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->selectBox(2);

        $form1 = new Nette\Application\UI\Form();
        $form1->addSelect('typ', 'Typ spisu:', $typ_spisu);
        $form1->addText('nazev', 'Název spisu/složky:', 50, 80)
                ->addRule(Nette\Forms\Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('parent_id', 'Složka:', $spisy)
                ->getControlPrototype()->onchange("return zmenitSpisovyZnak('novy');");

        $form1->addComponent( new Select2Component('Spisový znak:', $spisznak_seznam), 'spisovy_znak_id');
        $form1->getComponent('spisovy_znak_id')->controlPrototype->onchange("vybratSpisovyZnak(this);");

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
                 ->onClick[] = array($this, 'stornoNovyClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new Nette\Application\UI\Form();
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

    public function hledatSimpleClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->forward('this', array('hledat'=>$data['dotaz']));

    }

}
