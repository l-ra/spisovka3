<?php

class Spisovka_SpisyPresenter extends BasePresenter
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
    
    public function actionVyber()
    {
        $this->template->dokument_id = $this->getParam('id',$this->getParam('dokument_id',null));
        if ( empty($this->template->dokument_id) ) {
            if ( isset($_POST['dokument_id']) ) {
                $this->template->dokument_id = $_POST['dokument_id'];
            }
        }
    }
    
    public function renderVyber()
    {

        $Spisy = new Spis();
        $spis_id = null;

        $args = null;
        if ( !empty($hledat) ) {
            $args = array( 'where'=>array(array("tb.nazev LIKE %s",'%'.$hledat.'%')));
        }

        /*$user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;*/

        $args = $Spisy->spisovka($args);
        /*$result = $Spisy->seznam($args, 5, $spis_id);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        $this->template->seznam = $seznam;*/

        $result = $Spisy->seznam($args, 5, $spis_id);
        $this->template->seznam = $result->fetchAll();

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

    public function actionOdebratspis()
    {

        $spis_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);
        $Spisy = new Spis();

        $spis = $Spisy->getInfo($spis_id);
        if ( $spis && $dokument_id ) {

            // Propojit s dokumentem
            $DokumentSpis = new DokumentSpis();
            $where = array(array('dokument_id=%i',$dokument_id),array('spis_id=%i',$spis_id));
            $DokumentSpis->odebrat($where);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::SPIS_DOK_ODEBRAN,'Dokument vyjmut ze spisu "'. $spis->nazev .'"');
            $Log->logSpis($spis_id, LogModel::SPIS_DOK_ODEBRAN,'Dokument "'. $dokument_id .'" odebran ze spisu');
            
            $this->flashMessage('Dokument byl úspěšně vyjmut ze spisu "'. $spis->nazev .'"');
        } else {
            $this->flashMessage('Dokument se nepodařilo vyjmout ze spisu.','warning');
        }
        
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));            
        
    }

    public function actionDefault()
    {
    }

    public function renderDefault($hledat = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $Spisy = new Spis();
        $spis_id = null;

        $args = null;
        if ( !empty($hledat) ) {
            $args = array( 'where'=>array(array("tb.nazev LIKE %s",'%'.$hledat.'%')));
        }

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $args = $Spisy->spisovka($args);
        $result = $Spisy->seznam($args, 5, $spis_id);
        $paginator->itemCount = count($result);
            
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParam('print');
        $pdf = $this->getParam('pdfprint');
        if ( $tisk ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            
            $this->setLayout(false);
            $this->setView('print');
        } elseif ( $pdf ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
              
            $this->setLayout(false);
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }            
            
        if ( count($seznam)>0 ) {
            $spis_ids = array();
            foreach($seznam as $spis) {
                $spis_ids[] = $spis->id;
            }
            $this->template->seznam_dokumentu = $Spisy->seznamDokumentu($spis_ids);
        } else {
            $this->template->seznam_dokumentu = array();
        }               
            
        $this->template->seznam = $seznam;

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->select(11);
        $this->template->SpisoveZnaky = $spisove_znaky;

    }
    
    public function actionDetail()
    {
        
        $spis_id = $this->getParam('id',null);
        // Info o spisu
        $Spisy = new Spis();
        $this->template->Spis = $spis = $Spisy->getInfo($spis_id, true);
        
        if ( $spis ) {
            
            $SpisovyZnak = new SpisovyZnak();
            $spisove_znaky = $SpisovyZnak->select(11);
            $this->template->SpisoveZnaky = $spisove_znaky;
        
            if ( isset($spisove_znaky[ $spis->spisovy_znak_id ]) ) {
                $this->template->SpisZnak_popis = $spisove_znaky[ $spis->spisovy_znak_id ]->popis;
                $this->template->SpisZnak_nazev = $spisove_znaky[ $spis->spisovy_znak_id ]->nazev;
            } else {
                $this->template->SpisZnak_popis = "";
                $this->template->SpisZnak_nazev = "";
            }        
            
            $user = Environment::getUser();
            $user_id = $user->getIdentity()->id;  
            $pridelen = $predan = $accessview = false;
            $formUpravit = null;
            
            
            // prideleno
            if ( Orgjednotka::isInOrg($spis->orgjednotka_id, null, $user_id) ) {
                $pridelen = true;
                $accessview = true;
            }
            // predano
            if ( Orgjednotka::isInOrg($spis->orgjednotka_id_predano, null, $user_id) ) {
                $predan = true;
                $accessview = true;
            }
            
            if ( $user->isInRole('superadmin') ) {
                $accessview = 1;
                $pridelen = 1;
            }                
            
            if ( count($spis->workflow)>0 ) {
                $prideleno = $predano = 0;
                $wf_orgjednotka_prideleno = $spis->orgjednotka_id;
                $wf_orgjednotka_predano = $spis->orgjednotka_id_predano;
                $org_cache = array();
                foreach ( $spis->workflow as $wf ) {
                    
                    if ( isset( $org_cache[$wf->orgjednotka_id] ) ) {
                        $orgjednotka_expr = $org_cache[$wf->orgjednotka_id];
                    } else {
                        $orgjednotka_expr = Orgjednotka::isInOrg($wf->orgjednotka_id, null, $user_id);
                        $org_cache[$wf->orgjednotka_id] = $orgjednotka_expr;
                    }
                    
                    if ( !$accessview ) {
                        if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                             && ($wf->stav_osoby < 100 || $wf->stav_osoby !=0) ) {
                            $accessview = 1;
                        }   
                    }
                    
                    if ( !$pridelen ) {
                        if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                            && ($wf->stav_osoby == 1 && $wf->aktivni == 1 ) ) {
                            $pridelen = 1;
                            $wf_orgjednotka_prideleno = $wf->orgjednotka_id;
                        }   
                    }
                    if ( !$predan ) {
                        if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                            && ($wf->stav_osoby == 0 && $wf->aktivni == 1 ) ) {
                            $predan = 1;
                            $wf_orgjednotka_predano = $wf->orgjednotka_id;
                        }   
                    }
                    
                    if ( $predan && $pridelen && $accessview ) {
                        break;
                    }
                }
            }
            
            $this->template->Pridelen = $pridelen;
            if ( $pridelen ) {
                $this->template->AccessEdit = $pridelen;
                $formUpravit = $this->getParam('upravit',null);
                if ( empty($spis->orgjednotka_id) ) {
                    
                }
            }
            $this->template->Predan = $predan;
            $this->template->AccessView = $accessview;    
            
            $Orgjednotka = new Orgjednotka();
            if ( empty($spis->orgjednotka_id) && !empty($wf_orgjednotka_prideleno) ) {
                $spis->orgjendotka_id = $wf_orgjednotka_prideleno;
                $spis->orgjendotka_prideleno = $Orgjednotka->getInfo($wf_orgjednotka_prideleno);
            }
            if ( empty($spis->orgjednotka_id_predano) && !empty($wf_orgjednotka_predano) ) {
                $spis->orgjendotka_id_predano = $wf_orgjednotka_predano;
                $spis->orgjendotka_predano = $Orgjednotka->getInfo($wf_orgjednotka_predano);
            }
            
            if ( $accessview ) {
                $DokumentSpis = new DokumentSpis();
                
                //$user_config = Environment::getVariable('user_config');
                //$vp = new VisualPaginator($this, 'vp');
                //$paginator = $vp->getPaginator();
                //$paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;
                //$result = $DokumentSpis->dokumenty($spis_id, 1, $paginator);
                
                $result = $DokumentSpis->dokumenty($spis_id, 1);
                
                $this->template->seznam = $result;
            } else {
                $this->template->seznam = null;
            }
        
            if ( $spis->stav == 2 && !$user->isInRole('superadmin') ) {
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 0;
                $formUpravit = null;
            }
            

            $this->template->FormUpravit = $formUpravit;
        
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
            
        } else {
            // spis neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');            
        }

    }

    public function renderDetail()
    {
        $this->template->upravitForm = $this['upravitForm'];
    }

    public function renderPrevzit()
    {

        $spis_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);
        if ( empty($user_id) ) {
            $user = Environment::getUser();
            $user_id = $user->getIdentity()->id;          
        }        

        $DokSpis = new DokumentSpis();
        $dokumenty = $DokSpis->dokumenty($spis_id);        
        
        if ( count($dokumenty)>0 ) {
            // obsahuje dokumenty - predame i dokumenty
            $dokument = current($dokumenty);   

            $Workflow = new Workflow();
            if ( $Workflow->predany($dokument->id) ) {
                if ( $Workflow->prevzit($dokument->id, $user_id, $orgjednotka_id) ) {
                    $this->flashMessage('Úspěšně jste si převzal tento spis.');
                } else {
                    $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
                }
            } else {
                $this->flashMessage('Nemáte oprávnění k převzetí spisu.','warning');
            }
        } else {
            $Spis = new Spis;
            if ( $Spis->zmenitOrg($spis_id, $orgjednotka_id) ) {
                $this->flashMessage('Úspěšně jste si převzal tento spis.');
            } else {
                $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
            }               
        }

        $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));

    }

    public function renderPrevzitspis()
    {

        $spis_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);
        if ( empty($user_id) ) {
            $user = Environment::getUser();
            $user_id = $user->getIdentity()->id;          
        }        

        $DokSpis = new DokumentSpis();
        $dokumenty = $DokSpis->dokumenty($spis_id);        
        
        if ( count($dokumenty)>0 ) {
            // obsahuje dokumenty - predame i dokumenty
            $dokument = current($dokumenty);   

            $Workflow = new Workflow();
            if ( $Workflow->priradit($dokument->id, $user_id, $orgjednotka_id, "") ) {
                if ( $Workflow->prevzit($dokument->id, $user_id, $orgjednotka_id) ) {
                    $this->flashMessage('Úspěšně jste si převzal tento spis.');
                } else {
                    $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
                }
            } else {
                $this->flashMessage('Přiřazení spisu se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $Spis = new Spis;
            if ( $Spis->zmenitOrg($spis_id, $orgjednotka_id) ) {
                $this->flashMessage('Úspěšně jste si převzal tento spis.');
            } else {
                $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
            }               
        }

        $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));

    }
    
    
    public function renderZrusitprevzeti()
    {

        $spis_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);
        if ( empty($user_id) ) {
            $user = Environment::getUser();
            $user_id = $user->getIdentity()->id;          
        }        

        $DokSpis = new DokumentSpis();
        $dokumenty = $DokSpis->dokumenty($spis_id);        
        
        if ( count($dokumenty)>0 ) {
            // obsahuje dokumenty - predame i dokumenty
            $dokument = current($dokumenty);    

            $Workflow = new Workflow();
            if ( $Workflow->prirazeny($dokument->id) ) {
                if ( $Workflow->zrusit_prevzeti($dokument->id) ) {
                    $this->flashMessage('Zrušil jste převzetí spisu.');
                } else {
                    $this->flashMessage('Zrušení převzetí se nepodařilo. Zkuste to znovu.','warning');
                }
            } else {
                $this->flashMessage('Nemáte oprávnění ke zrušení převzetí spisu.','warning');
            }
        } else {
            $Spis = new Spis;
            if ( $Spis->zrusitPredani($spis_id) ) {
                $this->flashMessage('Zrušil jste převzetí spisu.');
            } else {
                $this->flashMessage('Zrušení převzetí se nepodařilo. Zkuste to znovu.','warning');
            }               
        }
            
        $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));

    }
    
    public function renderOdmitnoutprevzeti()
    {

        $spis_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);
        if ( empty($user_id) ) {
            $user = Environment::getUser();
            $user_id = $user->getIdentity()->id;          
        }        

        $DokSpis = new DokumentSpis();
        $dokumenty = $DokSpis->dokumenty($spis_id);        
        
        if ( count($dokumenty)>0 ) {
            // obsahuje dokumenty - predame i dokumenty
            $dokument = current($dokumenty);        
        
            $Workflow = new Workflow();
            if ( $Workflow->predany($dokument->id) ) {
                if ( $Workflow->zrusit_prevzeti($dokument->id) ) {
                    $this->flashMessage('Odmítl jste převzetí spisu.');
                } else {
                    $this->flashMessage('Odmítnutí převzetí se nepodařilo. Zkuste to znovu.','warning');
                }
            } else {
                $this->flashMessage('Nemáte oprávnění k odmítnutí převzetí spisu.','warning');
            }
        } else {
            $Spis = new Spis;
            if ( $Spis->zrusitPredani($spis_id) ) {
                $this->flashMessage('Odmítl jste převzetí spisu.');
            } else {
                $this->flashMessage('Odmítnutí převzetí se nepodařilo. Zkuste to znovu.','warning');
            }              
        }
        
        $this->redirect(':Spisovka:spisy:detail',array('id'=>$spis_id));

    }      
    
    public function renderNovy()
    {
        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->seznam(null);
        $this->template->SpisoveZnaky = $spisove_znaky;
        $this->template->spisForm = $this['novyForm'];
    }  
    
    public function actionAkce($data)
    {

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        if ( isset($data['hromadna_akce']) ) {
            $Spis = new Spis();
            $user = Environment::getUser()->getIdentity();
            switch ($data['hromadna_akce']) {
                /* Predani vybranych spisu do spisovny  */
                case 'predat_spisovna':
                    if ( isset($data['spis_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['spis_vyber'] as $spis_id ) {
                            $stav = $Spis->predatDoSpisovny($spis_id);
                            if ( $stav === true ) {
                                $count_ok++;
                            } else {
                                if ( is_string($stav) ) {
                                    $this->flashMessage($stav,'warning');
                                }
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste předal '.$count_ok.' spisů do spisovny.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' spisů se nepodařilo předat do spisovny!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                default:
                    break;
            }


        }

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

        $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));

    }

    protected function createComponentUpravitForm()
    {

        $Spisy = new Spis();

        $spis = @$this->template->Spis;
        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);
        
        $params = array('where'=> array("tb.typ = 'VS'") );
        $spisy = $Spisy->select(1, @$spis->id, 1, $params);

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$spis->id);
        $form1->addHidden('typ')
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
                ->controlPrototype->disabled = TRUE;
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
                $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));
            } else {
                $this->flashMessage('Spis  "'. $data['nazev'] .'"  byl upraven.');
                $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));
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
        if ( $spis_id ) {
            $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));
        } else {
            $this->redirect(':Spisovka:Spisy:default',array('id'=>$spis_id));
        }
        
    }

    protected function createComponentNovyForm()
    {

        $Spisy = new Spis();

        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $params = array('where'=> array("tb.typ = 'VS'") );
        $spisy = $Spisy->select(11, null, 1, $params);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);
        //$spisovy_znak_max = $Spisy->maxSpisovyZnak( $session_spisplan->spis_id );

        $form1 = new AppForm();
        $form1->addHidden('typ')
                ->setValue('S');
        $form1->addText('nazev', 'Název spisu:', 50, 80)
                ->addRule(Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('parent_id', 'Složka:', $spisy)
                ->getControlPrototype()->onchange("return zmenitSpisovyZnak('novy');");
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
        
        $Spisy = new Spis();

        $dokument_id = @$data['dokument_id'];
        $this->template->dokument_id = $dokument_id;
        unset($data['dokument_id']);
        
        try {
            $spis_id = $Spisy->vytvorit($data);
            if ( is_object($spis_id) ) {
                if ( $dokument_id ) {
                    echo '<div class="flash_message flash_error">Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.</div>';
                    echo '<div class="flash_message flash_error">Error: '. $spis_id->getMessage() .'</div>';
                } else {
                    $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','error');
                    $this->flashMessage('Error: '. $spis_id->getMessage(),'error');
                }
            } else {
                if ( $dokument_id ) {
                    echo '<div class="flash_message flash_info">Spis "'. $data['nazev'] .'"  byl vytvořen.</div>';                
                } else {
                    $this->flashMessage('Spis "'. $data['nazev'] .'"  byl vytvořen.');
                }
                if (!$this->isAjax()) {
                    $this->redirect(':Spisovka:Spisy:detail',array('id'=>$spis_id));
                } else {
                    $this->invalidateControl('dokspis');
                }                
            }
        } catch (DibiException $e) {
            if ( $dokument_id ) {
                echo '<div class="flash_message flash_error">Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.</div>';
            } else {
                $this->flashMessage('Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.','warning');
            }
            
            
        }
    }

    protected function createComponentNovyajaxForm()
    {

        $Spisy = new Spis();

        $spousteci = SpisovyZnak::spousteci_udalost(null,1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $params = array('where'=> array("tb.typ = 'VS'") );
        $spisy = $Spisy->select(11, null, 1, $params);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);

        $dokument_id = $this->getParam('id',$this->getParam('dokument_id',null));
        if ( empty($dokument_id) ) {
            if ( isset($_POST['dokument_id']) ) {
                $dokument_id = $_POST['dokument_id'];
            }
        }        
        
        $form1 = new AppForm();
        $form1->getElementPrototype()->id('spis-vytvorit');
        $form1->getElementPrototype()->onsubmit('return false;');        
        
        $form1->addHidden('dokument_id')
                ->setValue($dokument_id);
        $form1->addHidden('typ')
                ->setValue('S');
        $form1->addText('nazev', 'Název spisu:', 50, 80)
                ->addRule(Form::FILLED, 'Název spisu musí být vyplněn!');
        $form1->addText('popis', 'Popis:', 50, 200);
        $form1->addSelect('parent_id', 'Složka:', $spisy);
                //->getControlPrototype()->onchange("return zmenitSpisovyZnak('novy');");
        $form1->addSelect('spisovy_znak_id', 'Spisový znak:', $spisznak_seznam)
                ->controlPrototype->onchange("vybratSpisovyZnak('frm-novyajaxForm');");
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5);
        $form1->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci);
        $form1->addDatePicker('datum_otevreni', 'Datum otevření:', 10)
                ->setValue( date('d.m.Y') );
        $form1->addDatePicker('datum_uzavreni', 'Datum uzavření:', 10);

        $form1->addSubmit('vytvorit', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritAjaxClicked');
        $form1['vytvorit']->controlPrototype->onclick("return spisVytvoritSubmit();");
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->controlPrototype->onclick("return spisVytvoritStorno('$dokument_id');");

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function vytvoritAjaxClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        
        $Spisy = new Spis();

        $dokument_id = @$data['dokument_id'];
        $this->template->dokument_id = $dokument_id;
        unset($data['dokument_id']);
        
        try {
            $spis_id = $Spisy->vytvorit($data);
            if ( is_object($spis_id) ) {
                echo '<div class="flash_message flash_error">Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.</div>';
                echo '<div class="flash_message flash_error">Error: '. $spis_id->getMessage() .'</div>';
            } else {
                echo '<div class="flash_message flash_info">Spis "'. $data['nazev'] .'"  byl vytvořen.</div>';                
            }
        } catch (DibiException $e) {
            echo '<div class="flash_message flash_error">Spis "'. $data['nazev'] .'" se nepodařilo vytvořit.</div>';
        }
        
        $this->setLayout(false);
    }

    public function stornoAjaxClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = @$data['dokument_id'];
        $this->redirect(':Spisovka:Spisy:vyber',array('id'=>$dokument_id));
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

    
    public function renderPrideleni()
    {

        $user = Environment::getUser();
            
        if ( !$user->isInRole('superadmin','admin') ) {
            $this->flashMessage('Nemáte oprávnění k provádění této činnosti!','warning');
            $this->redirect(':Spisovka:Spisy:default');
        }         
        
        
        
        $Spisy = new Spis();
        $spis_id = null;

        $post = $this->getRequest()->getPost();
        if ( isset($post['spisorg_pridelit']) ) {
            if ( isset($post['orgvybran']) ) {
                $Spis = new Spis;
                foreach( $post['orgvybran'] as $orgvybran_spis => $orgvybran_org ) {
                    if ( $Spis->zmenitOrg($orgvybran_spis, $orgvybran_org) ) {
                        $this->flashMessage('Úspěšně jste si přidělil spis číslo '.$orgvybran_spis);
                    } else {
                        $this->flashMessage('Přidělení spisu číslo '.$orgvybran_spis.' se nepodařilo. Zkuste to znovu.','warning');
                    }                     
                }
            }
            $this->redirect(':Spisovka:Spisy:default');
        }
        
            $args = null;
            if ( !empty($hledat) ) {
                $args = array( 'where'=>array(array("tb.nazev LIKE %s",'%'.$hledat.'%')));
            }

            $args = $Spisy->spisovka($args);
            $result = $Spisy->seznam($args, 5, $spis_id);
            
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
            
            $this->template->seznam = $seznam;

            $SpisovyZnak = new SpisovyZnak();
            $spisove_znaky = $SpisovyZnak->select(11);
            $this->template->SpisoveZnaky = $spisove_znaky;

    }
    
    

}

