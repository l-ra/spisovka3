<?php

class Admin_SubjektyPresenter extends SubjektyPresenter
{

    private $hledat;

    // Nejpreve prepsane metody z parent tridy
    
    protected function createComponentNovyForm()
    {
        $form1 = parent::createComponentNovyForm();

        // Tento formular se neodesila pres Ajax
        $form1->getElementPrototype()->onsubmit('');

        $form1['novy']->onClick[] = array($this, 'vytvoritClicked');
        $form1['storno']->onClick[] = array($this, 'stornoSeznamClicked');

        return $form1;
    }

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Subjekt = new Subjekt();

        try {
            $subjekt_id = $Subjekt->ulozit($data);
            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl vytvořen.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } 
        catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo vytvořit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
            $this->redirect(':Admin:Subjekty:novy');
        }
    }

    protected function createComponentUpravitForm()
    {
        $form1 = parent::createComponentUpravitForm();

        $form1['upravit']->onClick[] = array($this, 'upravitClicked');
        $form1['storno']->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    // ------------------------------------------------------------------------
    
    
    
    public function renderSeznam($hledat = null)
    {

        // paginator
        $abcPaginator = new AbcPaginator($this, 'abc');
        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        // hledani
        $this->hledat = "";
        $this->template->no_items = 0;
        $args = null;
        if ( isset($hledat) ) {
            $args = array( 'where'=>array(array(
                    'nazev_subjektu LIKE %s OR','%'.$hledat.'%',
                    'ic LIKE %s OR','%'.$hledat.'%',
                    'email LIKE %s OR','%'.$hledat.'%',
                    'telefon LIKE %s OR','%'.$hledat.'%',
                    'id_isds LIKE %s OR','%'.$hledat.'%',
                    'adresa_mesto LIKE %s OR','%'.$hledat.'%',
                    'adresa_psc LIKE %s OR','%'.$hledat.'%',
                    "CONCAT(jmeno,' ',prijmeni) LIKE %s OR",'%'.$hledat.'%',
                    "CONCAT(prijmeni,' ',jmeno) LIKE %s",'%'.$hledat.'%'
                ))
            );

            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }
        
        // zobrazit podle pismena
        $abc = $abcPaginator->getParam('abc');
        if ( !empty($abc) ) {
            if ( isset($args['where']) ) {
                $args['where'][] = array("nazev_subjektu LIKE %s OR prijmeni LIKE %s",$abc.'%',$abc.'%');
            } else {
                $args = array('where'=>array(array("nazev_subjektu LIKE %s OR prijmeni LIKE %s",$abc.'%',$abc.'%')));
            }
        }

        // nesmysl, jakmile by uzivatel oznacil subjekt za neaktivni, uz by jej v administraci nikdy normalne nevidel
        // if ( isset($args['where']) ) {
            // $args['where'][] = array('stav=1');
        // } else {
            // $args = array('where'=>array('stav=1'));
        // }

        // nacteni
        $Subjekt = new Subjekt();
        $result = $Subjekt->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;
    }


    public function renderDetail()
    {
        $this->template->title = " - Detail subjektu";

        $this->template->FormUpravit = $this->getParam('upravit',null);

        $subjekt_id = $this->getParam('id',null);

        $Subjekt = new Subjekt();
        $subjekt = $Subjekt->getInfo($subjekt_id);
        $this->template->Subjekt = $subjekt;
        
        $this->template->upravitForm = $this['upravitForm'];
    }

    
    public function renderIsdsid()
    {
        $id = $this->getParam('id',null);
        
        if ( is_null($id) ) {
            exit;
        }
        
        $isds = new ISDS_Spisovka();
        try {
            $isds->pripojit();
            $filtr['dbID'] = $id;
            $prijemci = $isds->FindDataBoxEx($filtr);
            if ( isset($prijemci->dbOwnerInfo) ) {
                
                $info = $prijemci->dbOwnerInfo[0];
                
                /*echo "<pre>";
                print_r($info);
                echo "</pre>";*/
                
                echo json_encode($info);
            } else {
                echo json_encode(array("error"=>$isds->error()));
            }
            
            exit;
                                    
        } catch (Exception $e) {
            echo json_encode(array("error"=>$e->getMessage()));
            exit;
        }
    }
    
    public function renderImport()
    {
    }    
    
    public function renderExport()
    {
        
        if ( $this->getHttpRequest()->isPost() ) {
            // Exportovani
            $post_data = $this->getHttpRequest()->getPost();
            //Debug::dump($post_data);
            
            $Subjekt = new Subjekt();
            $args = null;
            if ( $post_data['export_co'] == 2 ) {
                // pouze aktivni
                $args['where'] = array( array('stav=1') );
            }
            $seznam = $Subjekt->seznam($args)->fetchAll();
            if ( $seznam ) {
                
                if ( $post_data['export_do'] == "csv" ) {
                    // export do CSV
                    $ignore_cols = array("date_created","user_created","date_modified","user_modified");
                    $export_data = Export::csv(
                                    $seznam, 
                                    $ignore_cols, 
                                    $post_data['csv_code'], 
                                    $post_data['csv_radek'], 
                                    $post_data['csv_sloupce'], 
                                    $post_data['csv_hodnoty']);
                    
                    //echo "<pre>"; echo $export_data; echo "</pre>"; exit;
                
                    $httpResponse = Environment::getHttpResponse();
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
    

    public function upravitClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];
        
        if (empty($data['stat_narozeni']))
            $data['stat_narozeni'] = null;
        if (empty($data['adresa_stat']))
            $data['adresa_stat'] = null;

        $Subjekt = new Subjekt();

        try {
            $Subjekt->ulozit($data, $subjekt_id);
            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl upraven.');
            
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        }
        catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo upravit.','warning');
            $this->flashMessage($e->getMessage(), 'warning');
            
            $this->redirect(':Admin:Subjekty:seznam');
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];
        $this->redirect('this',array('id'=>$subjekt_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Subjekty:seznam');
    }

    protected function createComponentStavForm()
    {

        $subjekt = $this->template->Subjekt;
        $stav_select = Subjekt::stav();

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select)
                ->setValue(@$subjekt->stav);
        $form1->addSubmit('zmenit_stav', 'Změnit stav')
                 ->onClick[] = array($this, 'zmenitStavClicked');
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

    public function zmenitStavClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $subjekt_id = $data['id'];
        $Subjekt = new Subjekt();

        try {
            $Subjekt->zmenitStav($data);
            $this->flashMessage('Stav subjektu byl změněn.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Stav subjektu se nepodařilo změnit.','warning');
        }
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze názvu subjektu, jména, IČ, emailu, telefonu, ISDS, města, PSČ";

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
