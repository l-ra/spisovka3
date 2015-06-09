<?php

class Admin_SpisznakPresenter extends BasePresenter
{

    private $spisznak;

    public function renderSeznam()
    {
        $this->template->title = " - Seznam spisových znaků";

        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $where = null;// array( array('ciselna_rada LIKE %s','ORG_12%') );

        $SpisovyZnak = new SpisovyZnak();
        $result = $SpisovyZnak->seznam($where,5);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        
/*        $seznam = $result->fetchAll();//($paginator->offset, $paginator->itemsPerPage);
        //natsort($seznam);
        echo "<pre>";
        //print_r($seznam);
        foreach ( $seznam as $s ) {
            echo $s->nazev ."\n";
        }
        echo "</pre>";
        exit;
*/        
        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nový spisový znak";

    }


    public function renderDetail()
    {
        $this->template->FormUpravit = $this->getParameter('upravit',null);
        $id = $this->getParameter('id',null);

        $SpisovyZnak = new SpisovyZnak();
        $this->spisznak = $SpisovyZnak->getInfo($id);
        $this->template->SpisZnak = $this->spisznak;
        
        $this->template->has_children = $SpisovyZnak->ma_podrizene_spisove_znaky($id);

        $this->template->title = " - Detail spisového znaku";
        $this->template->upravitForm = $this['upravitForm'];
    }

    protected function _odebrat($odebrat_strom)
    {
        $spisznak_id = $this->getParameter('id',null);
        $SpisovyZnak = new SpisovyZnak();
        $res = $SpisovyZnak->odstranit($spisznak_id, $odebrat_strom);
        if ( !$res )
            $this->flashMessage('Spisový znak je využíván v aplikaci.<br>Z toho důvodu není možné spisový znak odstranit.','warning_ext');                    
        else 
            $this->flashMessage('Spisový znak byl úspěšně odstraněn.');
            
        $this->redirect(':Admin:Spisznak:seznam');
    }

    public function actionOdebrat()
    {   
        $this->_odebrat(false);
    }

    public function actionOdebratstrom()
    {
        $this->_odebrat(true);
    }
    
    public function renderNovy()
    {
        $this->template->novyForm = $this['novyForm'];
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
            
            $SpisovyZnak = new SpisovyZnak();
            $args = null;
            if ( $post_data['export_co'] == 2 ) {
                // pouze aktivni
                $args['where'] = array( array('stav=1') );
            }
            
            $seznam = $SpisovyZnak->seznam($args,5)->fetchAll();
            
            if ( $seznam ) {
                
                if ( $post_data['export_do'] == "csv" ) {
                    // export do CSV
                    $ignore_cols = array("date_created","user_created","date_modified","user_modified",
                                         "sekvence_string");
                    $export_data = CsvExport::csv(
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
                    $httpResponse->setHeader('Content-Disposition', 'attachment; filename="export_subjektu.csv"');
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
 * Formular a zpracovani pro zmenu spisoveho znaku
 *
 */

    protected function createComponentUpravitForm()
    {

        $SpisovyZnak = new SpisovyZnak();
        if ( empty($this->spisznak) ) {
            $id = $this->getParameter('id',null);
            if ($id !== null)
                $spisznak = $SpisovyZnak->getInfo($id);
            else
                $spisznak = array();
        } else {
            $spisznak = $this->spisznak;
        }
        $spisznak_seznam = $SpisovyZnak->selectBox(1, @$spisznak->id);
        $stav_select = SpisovyZnak::stav();
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');


        $form1 = new Spisovka\Form();
        $form1->addHidden('id')
                ->setValue(@$spisznak->id);

        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->setValue(@$spisznak->nazev)
                ->addRule(Nette\Forms\Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200)
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
        $form1->addSelect('selected', 'Možnost vybrat:', array("1"=>"ano","0"=>"ne"))
                ->setValue(@$spisznak->selected);        
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select)
                ->setValue(@$spisznak->stav);

        $submit = $form1->addSubmit('upravit', 'Upravit');
        $submit->onClick[] = array($this, 'upravitClicked');
        
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
    
    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $spisznak_id = $data['id'];
        unset($data['id']);

        $SpisovyZnak = new SpisovyZnak();

        try {
            $SpisovyZnak->upravit($data, $spisznak_id);
            $this->flashMessage('Spisový znak  "'. $data['nazev'] .'"  byl upraven.');
            
        } catch (Exception $e) {
            $this->flashMessage('Spisový znak "'. $data['nazev'] .'" se nepodařilo upravit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }

        $this->redirect(':Admin:Spisznak:detail',array('id'=>$spisznak_id));
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $this->redirect('this',array('id'=>$data['id']));
    }

    public function stornoNovyClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $this->redirect(':Admin:Spisznak:seznam');
    }

    protected function createComponentNovyForm()
    {

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->selectBox(1);
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $form1 = new Nette\Application\UI\Form();
        $form1->addText('nazev', 'Spisový znak:', 50, 80)
                ->addRule(Nette\Forms\Form::FILLED, 'Spisový znak musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta:', 5, 5);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci)
            ->setValue(3); // Standardni udalost je "Skartační lhůta začíná plynout po uzavření dokumentu."
        $form1->addSelect('parent_id', 'Připojit k:', $spisznak_seznam);
        $form1->addSelect('selected', 'Možnost vybrat:', array("1"=>"ano","0"=>"ne"));
        $form1->addSubmit('vytvorit', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('vytvorit_a_novy', 'Vytvořit spisový znak a založit nový')
                 ->onClick[] = array($this, 'vytvoritanovyClicked');
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

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
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
    
    public function vytvoritanovyClicked(Nette\Forms\Controls\SubmitButton $button)
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
