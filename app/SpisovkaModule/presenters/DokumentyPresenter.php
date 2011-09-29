<?php

class Spisovka_DokumentyPresenter extends BasePresenter
{

    private $filtr;
    private $filtr_bezvyrizenych;
    private $hledat;
    private $seradit;
    private $odpoved = null;
    private $typ_evidence = null;
    private $oddelovac_poradi = null;
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
        if ( isset($user_config->cislo_jednaci->oddelovac) ) {
            $this->oddelovac_poradi = $user_config->cislo_jednaci->oddelovac;
        } else {
            $this->oddelovac_poradi = '/';
        }
        $this->template->Oddelovac_poradi = $this->oddelovac_poradi;

        parent::startup();
    }

    public function renderDefault($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $this->template->Typ_evidence = $this->typ_evidence;

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $Dokument = new Dokument();

        $this->template->no_items = 1; // indikator pri nenalezeni dokumentu
        if ( isset($filtr) ) {
            // zjisten filtr
            $this->getHttpResponse()->setCookie('s3_filtr', serialize($filtr), strtotime('90 day'));
            $args = $Dokument->filtr($filtr['filtr'],null,$filtr['bez_vyrizenych']);
            $this->filtr = $filtr['filtr'];
            $this->filtr_bezvyrizenych = $filtr['bez_vyrizenych'];
            $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci
        } else {
            // filtr nezjisten - pouzijeme default
            $cookie_filtr = $this->getHttpRequest()->getCookie('s3_filtr');
            if ( $cookie_filtr ) {
                // zjisten filtr v cookie, tak vezmeme z nej
                $filtr = unserialize($cookie_filtr);
                $args = $Dokument->filtr($filtr['filtr'],null,$filtr['bez_vyrizenych']);
                $this->filtr = $filtr['filtr'];
                $this->filtr_bezvyrizenych = $filtr['bez_vyrizenych'];
                $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci
            } else {
                $args = $Dokument->filtr('moje');
                $this->filtr = 'moje';
                $this->filtr_bezvyrizenych = false;
            }

        }        
        
        if ( isset($hledat) ) {
            if (is_array($hledat) ) {
                // podrobne hledani = array
                $args = $Dokument->filtr(null,$hledat);
                $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
            } else {
                // rychle hledani = string
                $args = $Dokument->hledat($hledat);
                $this->hledat = $hledat;
                $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
            }
            $this->getHttpResponse()->setCookie('s3_hledat', serialize($hledat), strtotime('90 day'));
        } else {
            $cookie_hledat = $this->getHttpRequest()->getCookie('s3_hledat');            
            if ( $cookie_hledat ) {
                // zjisteno hladaci filtr v cookie, tak vezmeme z nej
                $hledat = unserialize($cookie_hledat);
                if (is_array($hledat) ) {
                    // podrobne hledani = array
                    $args = $Dokument->filtr(null,$hledat);
                    $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
                } else {
                    // rychle hledani = string
                    $args = $Dokument->hledat($hledat);
                    $this->hledat = $hledat;
                    $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
                }
            }
        }
        $this->template->s3_hledat = $hledat;
        
        if ( isset($seradit) ) {
            $Dokument->seradit($args, $seradit);
            $this->getHttpResponse()->setCookie('s3_seradit', $seradit, strtotime('90 day'));
        } else {
            $seradit = $this->getHttpRequest()->getCookie('s3_seradit');            
            if ( $seradit ) {
                // zjisteno razeni v cookie, tak vezmeme z nej
                $Dokument->seradit($args, $seradit);
            }           
        }
        $this->seradit = $seradit;
        $this->template->s3_seradit = $seradit;        
        $this->template->seradit = $seradit;

        $args = $Dokument->spisovka($args);
        $result = $Dokument->seznam($args);
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

            $dataplus = array();

            $dokument_ids = array();
            foreach ($seznam as $row) {
                $dokument_ids[] = $row->id;
            }

            $DokSubjekty = new DokumentSubjekt();
            $dataplus['subjekty'] = $DokSubjekty->subjekty($dokument_ids);
            $Dokrilohy = new DokumentPrilohy();
            $dataplus['prilohy'] = $Dokrilohy->prilohy($dokument_ids);
            $DokOdeslani = new DokumentOdeslani();
            $dataplus['odeslani'] = array( '0'=> null );//$DokOdeslani->odeslaneZpravy($dokument_ids);

            foreach ($seznam as $index => $row) {
                $dok = $Dokument->getInfo($row->id,null, $dataplus);
                if ( empty($dok->stav_dokumentu) ) continue;
                $seznam[$index] = $dok;
            }
        } 

        $this->template->seznam = $seznam;

        $this->template->filtrForm = $this['filtrForm'];
        $this->template->seraditForm = $this['seraditForm'];

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
                    $mpdf->SetTitle('Spisová služba - Detail dokumentu');                
                
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
                    $mpdf->SetHeader('Seznam dokumentů||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('spisova_sluzba.pdf', 'I');
                }
            }
            
            } catch (Exception $e) {
                $location = str_replace("pdfprint=1","",Environment::getHttpRequest()->getUri());

                echo "<h1>Nelze vygenerovat PDF výstup.</h1>";
                echo "<p>Generovaný obsah obsahuje příliš mnoho dat, které není možné zpracovat.<br />Zkuste omezit celkový počet dokumentů.</p>";
                echo "<p><a href=".$location.">Přejít na předchozí stránku.</a></p>";
                echo "<p>".$e->getMessage()."</p>";
                exit;
            }
            
        }
        
    }    
    
    public function actionDetail()
    {

        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $this->getParam('id',null);

        $dokument = $Dokument->getInfo($dokument_id, 1);
        if ( $dokument ) {
            // dokument zobrazime
            $this->template->Dok = $dokument;

            $user = Environment::getUser();
            //Debug::dump($user);

            $user_id = $user->getIdentity()->id;

            $this->template->Pridelen = 0;
            $this->template->Predan = 0;
            $this->template->AccessEdit = 0;
            $this->template->AccessView = 0;
            $formUpravit = null;
            if ( count($dokument->workflow)>0 ) {
                // uzivatel na dokumentu nekdy pracoval, tak mu dame moznost aspon nahlizet
                foreach ($dokument->workflow as $wf) {
                    if ( ($wf->prideleno_id == $user_id) && ($wf->stav_osoby < 100 || $wf->stav_osoby !=0) ) {
                        $this->template->AccessView = 1;
                    }
                }
            }      
            
            // Prideleny nebo predany uzivatel
            if ( @$dokument->prideleno->prideleno_id == $user_id ) {
                // prideleny
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            }
            if ( @$dokument->predano->prideleno_id == $user_id ) {
                // predany
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            }
            if ( empty($dokument->prideleno->prideleno_id)
                        && Orgjednotka::isInOrg(@$dokument->prideleno->orgjednotka_id, 'vedouci') ) {
                // prideleno organizacni jednotce
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            }
            if ( empty($dokument->predano->prideleno_id)
                        && Orgjednotka::isInOrg(@$dokument->predano->orgjednotka_id, 'vedouci') ) {
                // predano organizacni jednotce
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            }

            // Dokument se vyrizuje
            if ( $dokument->stav_dokumentu >= 3 ) {
                $this->template->Vyrizovani = 1;
            } else {
                $this->template->Vyrizovani = 0;
            }
            // Dokument je vyrizeny, ale nespusten
            if ( $dokument->stav_dokumentu == 4 || $dokument->stav_dokumentu == 5) {
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 0;
                $this->template->Predan = 0;
                $this->template->SpousteciUdalost = 1;
                $formUpravit = null;
            }
            // Dokument je vyrizeny a spusteny
            if ( $dokument->stav_dokumentu == 5) {
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 0;
                $this->template->Predan = 0;
                $this->template->SpousteciUdalost = 0;
                $formUpravit = null;
            }

            // Dokument je zapujcen
            if ( $dokument->stav_dokumentu == 11 ) {
                $this->template->Pridelen = 0;
                $this->template->Predan = 0;                
                $this->template->Vyrizovani = 0;
                $this->template->AccessEdit = 0;
                $Zapujcka = new Zapujcka();
                $this->template->Zapujcka = $Zapujcka->getDokument($dokument_id);
            } else {
                $this->template->Zapujcka = null;
            }            
            
            $this->template->Skartacni_dohled = 0;
            $this->template->Skartacni_komise = 0;
            
            $datum_skartace = new DateTime($dokument->skartacni_rok);
            $datum_aktualni = new DateTime();
            $DateDiff = new DateDiff();
            $skartacni_rozdil = $DateDiff->diff($datum_skartace);            
            
            // Dokument je ve skartacnim obodbi
            if ( $skartacni_rozdil > 0 && $dokument->stav_dokumentu == 7
                    && ($user->isInRole('skartacni_dohled') || $user->isInRole('superadmin')) ) {
                $this->template->AccessView = 1;
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 1;
                $this->template->Skartacni_dohled = 1;
            }
            // Dokument je ve skartacnim rezimu
            if ( $dokument->stav_dokumentu == 8
                    && ($user->isInRole('skartacni_komise') || $user->isInRole('superadmin')) ) {
                $this->template->AccessView = 1;
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 1;
                $this->template->Skartacni_komise = 1;
            }

            // SuperAdmin - moznost zasahovat do dokumentu
            if ( $user->isInRole('superadmin') ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            }

            if ( $dokument->stav_dokumentu == 1 ) {
                $this->template->isRozdelany = true;
            } else {
                $this->template->isRozdelany = false;
            }   
            if ( $user->isInRole('superadmin') ) {
                // muze editovat vse
                $this->template->isRozdelany = true;
            }
            
            $this->template->FormUpravit = $formUpravit;

            if ( $this->getParam('udalost1',false) && $dokument->stav_dokumentu == 4) {
                $this->template->FormUdalost = 3;
            } else if ( $this->getParam('udalost',false) && $dokument->stav_dokumentu <= 3 ) {
                $this->template->FormUdalost = 2;
            } else if ( $dokument->stav_dokumentu == 4 ) {
                $this->template->FormUdalost = 1;
            } else {
                $this->template->FormUdalost = 0;
            }

            $SpisovyZnak = new SpisovyZnak();
            $this->template->SpisoveZnaky = $SpisovyZnak->seznam(null);

            $this->template->Typ_evidence = $this->typ_evidence;
            if ( $this->typ_evidence == 'priorace' ) {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument_id);
            }

            // Kontrola lhuty a skartace
            if ( $dokument->lhuta_stav==2 && $dokument->stav_dokumentu < 4 ) {
                $this->flashMessage('Vypršela lhůta k vyřízení! Vyřiďte neprodleně tento dokument.','warning');
            } else if ( $dokument->lhuta_stav==1 && $dokument->stav_dokumentu < 4 ) {
                $this->flashMessage('Za pár dní vyprší lhůta k vyřízení! Vyřiďte co nejrychleji tento dokument.');
            }

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
            
            $this->invalidateControl('dokspis');
        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
        
    }

    public function renderDetail()
    {
        $this->template->metadataForm = $this['metadataForm'];
        $this->template->vyrizovaniForm = $this['vyrizovaniForm'];
        $this->template->udalostForm = $this['udalostForm'];
    }

    public function actionAkce($data)
    {

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        if ( isset($data['hromadna_akce']) ) {
            $Workflow = new Workflow();
            $user = Environment::getUser()->getIdentity();
            switch ($data['hromadna_akce']) {
                /* Prevzeti vybranych dokumentu */
                case 'prevzit':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['dokument_vyber'] as $dokument_id ) {
                            if ( $Workflow->predany($dokument_id) ) {
                                if ( $Workflow->prevzit($dokument_id, $user->id) ) {
                                    $count_ok++;
                                } else {
                                    $count_failed++;
                                }
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste převzal '.$count_ok.' dokumentů.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage('U '.$count_failed.' dokumentů se nepodařilo převzít dokument!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                /* Predani vybranych dokumentu do spisovny  */
                /*case 'predat_spisovna':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['dokument_vyber'] as $dokument_id ) {
                            $stav = $Workflow->predatDoSpisovny($dokument_id);
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
                            $this->flashMessage('Úspěšně jste předal '.$count_ok.' dokumentů do spisovny.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' dokumentů se nepodařilo předat do spisovny!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;*/
                default:
                    break;
            }
            
            
        }
       
    }

    public function renderPrevzit()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->predany($dokument_id) ) {
            if ( $Workflow->prevzit($dokument_id, $user_id, $orgjednotka_id) ) {
                $this->flashMessage('Úspěšně jste si převzal tento dokument.');
            } else {
                $this->flashMessage('Převzetí dokumentu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění k převzetí dokumentu.','warning');
        }

        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderZrusitprevzeti()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            if ( $Workflow->zrusit_prevzeti($dokument_id) ) {
               $this->flashMessage('Zrušil jste převzetí dokumentu.');
            } else {
                $this->flashMessage('Zrušení převzetí se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění ke zrušení převzetí dokumentu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }
    
    
    public function renderKvyrizeni()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            if ( $Workflow->vyrizuje($dokument_id, $user_id, $orgjednotka_id) ) {
               $Workflow->zrusit_prevzeti($dokument_id);

               $DokumentSpis = new DokumentSpis();
               $spisy = $DokumentSpis->spisy($dokument_id);
               if ( count($spisy)>0 ) {
                   $Dokument = new Dokument();
                   foreach ( $spisy as $spis ) {
                       $data = array(
                            "spisovy_znak_id" => $spis->spisovy_znak_id,
                            "skartacni_znak" => $spis->skartacni_znak,
                            "skartacni_lhuta" => $spis->skartacni_lhuta,
                            "spousteci_udalost_id" => $spis->spousteci_udalost_id
                       );
                       $dokument = $Dokument->ulozit($data,$dokument_id);
                       unset($data);
                   }
               }


               $this->flashMessage('Převzal jste tento dokument k vyřízení.');
            } else {
                $this->flashMessage('Označení dokumentu k vyřízení se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění označit dokument k vyřízení.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderKeskartaci()
    {

        $dokument_id = $this->getParam('id',null);
        $user = Environment::getUser();
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $user->isInRole('skartacni_dohled') || $user->isInRole('superadmin') ) {
            if ( $Workflow->keskartaci($dokument_id, $user_id, $orgjednotka_id) ) {
               $this->flashMessage('Dokument byl přidán do skartačního řízení.');
            } else {
               $this->flashMessage('Dokument se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění manipulovat s tímto dokumentem.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderArchivovat()
    {

        $dokument_id = $this->getParam('id',null);
        $user = Environment::getUser();
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {
            if ( $Workflow->archivovat($dokument_id, $user_id, $orgjednotka_id) ) {
               $this->flashMessage('Dokument byl archivován.');
            } else {
               $this->flashMessage('Dokument se nepodařilo zařadit do archivu. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění manipulovat s tímto dokumentem.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderSkartovat()
    {

        $dokument_id = $this->getParam('id',null);
        $user = Environment::getUser();
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {
            if ( $Workflow->skartovat($dokument_id, $user_id, $orgjednotka_id) ) {
               $this->flashMessage('Dokument byl skartován.');
            } else {
               $this->flashMessage('Dokument se nepodařilo skartovat. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění manipulovat s tímto dokumentem.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }



    public function renderVyrizeno()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            $ret = $Workflow->vyrizeno($dokument_id, $user_id, $orgjednotka_id);
            if ( $ret === "udalost" ) {
                // manualni vyrizeni
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id,'udalost'=>1));
            } else if ( $ret === "neprideleno" ) {
                // neprideleno
                $this->flashMessage('Nemáte oprávnění označit dokument za vyřízený.','warning');
            } else if ( $ret === true ) {
                // automaticke vyrizeni
                $Workflow->zrusit_prevzeti($dokument_id);
                $this->flashMessage('Označil jste tento dokument za vyřízený!');
            } else {
                $this->flashMessage('Označení dokumentu za vyřízený se nepodařilo. Zkuste to znovu.','warning');
                
            }
        } else {
            $this->flashMessage('Nemáte oprávnění označit dokument za vyřízený.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderCjednaci()
    {
        $this->template->dokument_id = $this->getParam('id',null);
        $this->template->evidence = $this->getParam('evidence',0);
    }

    public function renderCjednaciadd()
    {
        $dokument_id = $this->getParam('id',null);
        $dokument_spojit = $this->getParam('spojit_s',null);
        $evidence = $this->getParam('evidence',null);

        $Dokument = new Dokument();

        $dok_in = $Dokument->getBasicInfo($dokument_id);
        $dok_out = $Dokument->getBasicInfo($dokument_spojit);

        if ( $dok_in && $dok_out ) {

            // spojit s dokumentem
            $poradi = $Dokument->getMaxPoradi($dok_out->cislo_jednaci_id);

            if ( $evidence == 1 ) {

                $CJ = new CisloJednaci();

                if ( !empty($dok_out->cislo_jednaci_id) ) {
                    $cjednaci = $CJ->nacti($dok_out->cislo_jednaci_id);
                } else {
                    $cjednaci = $CJ->generuj(1);
                }

                $poradi = $Dokument->getMaxPoradi($dok_out->cislo_jednaci_id);

                $data = array();
                $data['cislo_jednaci_id'] = $cjednaci->id;
                $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
                $data['poradi'] = $poradi;
                $data['podaci_denik'] = $cjednaci->podaci_denik;
                $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
                $data['podaci_denik_rok'] = $cjednaci->rok;

                $dokument = $Dokument->update($data, array(array('id=%i',$dokument_id)));//   array('dokument_id'=>0);// $Dokument->ulozit($data);
                if ( $dokument ) {

                    $this->flashMessage('Dokument připojen do evidence.');

                    $Log = new LogModel();
                    $Log->logDokument($dokument_id, LogModel::DOK_UNDEFINED,'Dokument připojen do evidence. Přiděleno číslo jednací: '.$cjednaci->cislo_jednaci);

                    $Spis = new Spis();
                    $spis = $Spis->getInfo($cjednaci->cislo_jednaci);
                    if ( !$spis ) {
                        // vytvorime spis
                        //$session_spisplan = Environment::getSession('s3_spisplan');
                        //if ( !empty($session_spisplan->spis_id) ) {
                        //    $spisplan_id = $session_spisplan->spis_id;
                        //} else {
                            $spisplan_id = $Spis->getSpisovyPlan();
                        //}                    
                    
                        $spis_new = array(
                            'nazev' => $cjednaci->cislo_jednaci,
                            'popis' => "",
                            'spousteci_udalost_id' => 3,
                            'skartacni_znak' => 'S',                            
                            'skartacni_lhuta' => '10',                            
                            'typ' => 'S',
                            'stav' => 1
                        );
                        if ( !empty($spisplan_id) ) {
                            $spis_new['parent_id'] = (int) $spisplan_id;
                        }
                        $spis_id = $Spis->vytvorit($spis_new);
                        $spis = $Spis->getInfo($spis_id);
                    }

                    // pripojime
                    if ( $spis ) {
                        $DokumentSpis = new DokumentSpis();
                        $DokumentSpis->pripojit($dokument_id, $spis->id);
                    }
                }

                echo '###zaevidovano###'. $this->link('detail',array('id'=>$dokument_id));
                $this->terminate();

            } else {
                echo '###vybrano###'. $dok_out->cislo_jednaci .'#'. $poradi .'#'. $dok_out->cislo_jednaci_id;//. $spis->nazev;
                $this->terminate();
            }

        } else {
            // chyba
            $this->template->chyba = 1;
            $this->template->render('cjednaci');
        }
    }

    public function renderPridelitcj()
    {

        $dokument_id = $this->getParam('id',null);
        $cjednaci_id = $this->getParam('cislo_jednaci_id',null);
        $user_id = $this->getParam('user',null);

        $Dokument = new Dokument();
        //$dokument_info = $Dokument->getInfo($dokument_id);

        $CJ = new CisloJednaci();

        if ( !empty($cjednaci_id) ) {
            $cjednaci = $CJ->nacti($cjednaci_id);
            unset($cjednaci_id);
        } else {
            $cjednaci = $CJ->generuj(1);
        }

        $poradi = $Dokument->getMaxPoradi($cjednaci_id);

        $data = array();
        $data['cislo_jednaci_id'] = $cjednaci->id;
        $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
        $data['poradi'] = $poradi;
        $data['podaci_denik'] = $cjednaci->podaci_denik;
        $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
        $data['podaci_denik_rok'] = $cjednaci->rok;

        $dokument = $Dokument->update($data, array(array('id=%i',$dokument_id)));//   array('dokument_id'=>0);// $Dokument->ulozit($data);
        if ( $dokument ) {

            $this->flashMessage('Dokument připojen do evidence.');

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_UNDEFINED,'Dokument připojen do evidence. Přiděleno číslo jednací: '.$cjednaci->cislo_jednaci);

            if ( $this->typ_evidence == 'sberny_arch' ) {

                $Spis = new Spis();
                $spis = $Spis->getInfo($cjednaci->cislo_jednaci);
                if ( !$spis ) {
                    // vytvorime spis
                    
                    //$session_spisplan = Environment::getSession('s3_spisplan');
                    //if ( !empty($session_spisplan->spis_id) ) {
                    //    $spisplan_id = $session_spisplan->spis_id;
                    //} else {
                        $spisplan_id = $Spis->getSpisovyPlan();
                    //}                    
                    
                    $spis_new = array(
                        'nazev' => $cjednaci->cislo_jednaci,
                        'popis' => "",
                        'spousteci_udalost_id' => 3,
                        'skartacni_znak' => 'S',                            
                        'skartacni_lhuta' => '10',                          
                        'typ' => 'S',
                        'stav' => 1
                    );
                    if ( !empty($spisplan_id) ) {
                        $spis_new['parent_id'] = (int) $spisplan_id;
                    }
                    $spis_id = $Spis->vytvorit($spis_new);
                    $spis = $Spis->getInfo($spis_id);
                }

                // pripojime
                if ( $spis ) {
                    $DokumentSpis = new DokumentSpis();
                    $DokumentSpis->pripojit($dokument_id, $spis->id);
                }

            }
        }

        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

    public function renderNovy()
    {

        $Dokumenty = new Dokument();

        $args_rozd = array();
        $args_rozd['where'] = array(
                array('stav=%i',0),
                array('user_created=%i',Environment::getUser()->getIdentity()->id),
        );
        
        $args_rozd['order'] = array('date_created'=>'DESC');

        $this->template->Typ_evidence = $this->typ_evidence;

        $cisty = $this->getParam('cisty',0);
        $spis_id = $this->getParam('spis_id',null);
        $rozdelany_dokument = null;
        
        if ( $cisty == 1 ) {
            $Dokumenty->odstranit_rozepsane();
            $this->redirect(':Spisovka:Dokumenty:novy');
            //$rozdelany_dokument = null;
        } else if ( $spis_id )  {
            $Dokumenty->odstranit_rozepsane();
        } else {
            $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);
        }

        if ( count($rozdelany_dokument)>0 ) {
            $dokument = $rozdelany_dokument[0];

            $this->flashMessage('Byl detekován a načten rozepsaný dokument.<p>Pokud chcete založit úplně nový dokument, klikněte na následující odkaz. <a href="'. $this->link(':Spisovka:Dokumenty:novy',array('cisty'=>1)) .'">Vytvořit nový nerozepsaný dokument.</a>','info_ext');

            $DokumentSpis = new DokumentSpis();
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentPrilohy = new DokumentPrilohy();

            $spisy = $DokumentSpis->spisy($dokument->id);
            $this->template->Spisy = $spisy;

            $subjekty = $DokumentSubjekt->subjekty($dokument->id);
            $this->template->Subjekty = $subjekty;

            $prilohy  = $DokumentPrilohy->prilohy($dokument->id,null,1);
            $this->template->Prilohy = $prilohy;

            if ( $this->typ_evidence == 'priorace' ) {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument->id);
            }

        } else {

            if ( $this->getUser()->isInRole('podatelna') ) {
                $dokument_typ_id = 1;
            } else {
                $dokument_typ_id = 2;
            }

            $pred_priprava = array(
                "nazev" => "",
                "popis" => "",
                "stav" => 0,
                "dokument_typ_id" => $dokument_typ_id,
                "zpusob_doruceni_id" => null,
                "zpusob_vyrizeni_id" => null,
                "spousteci_udalost_id" => null,
                "cislo_jednaci_odesilatele" => "",
                "datum_vzniku" => date('Y-m-d H:i:s'),
                "lhuta" => "30",
                "poznamka" => "",
            );
            $dokument = $Dokumenty->ulozit($pred_priprava);

            if ( $spis_id ) {
                $DokumentSpis = new DokumentSpis();
                $DokumentSpis->pripojit($dokument->id, $spis_id);
                $spisy = $DokumentSpis->spisy($dokument->id);
                $this->template->Spisy = $spisy;
            } else {
                $this->template->Spisy = null;
            }
            
            
            $this->template->Subjekty = null;
            $this->template->Prilohy = null;
            $this->template->SouvisejiciDokumenty = null;
            $this->template->Typ_evidence = $this->typ_evidence;

        }

        $UserModel = new UserModel();
        $user = $UserModel->getUser(Environment::getUser()->getIdentity()->id, 1);
        $this->template->Prideleno = Osoba::displayName($user->identity);

        $CJ = new CisloJednaci();
        $this->template->cjednaci = $CJ->generuj();


        if ( $dokument ) {
            $this->template->Dok = $dokument;
        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument není připraven k vytvoření','warning');
        }
        
        $this->template->novyForm = $this['novyForm'];
    }

    public function renderOdpoved()
    {

        $Dokumenty = new Dokument();
        
        $dokument_id = $this->getParam('id',null);
        $dok = $Dokumenty->getInfo($dokument_id);

        if ( $dok ) {

            $args_rozd = array();
            $args_rozd['where'] = array(
                array('stav=%i',0),
                array('dokument_typ_id=%i',2),
                array('cislo_jednaci=%s',$dok->cislo_jednaci),
                array('user_created=%i',Environment::getUser()->getIdentity()->id)
            );
            $args_rozd['order'] = array('date_created'=>'DESC');

            $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);

            if ( count($rozdelany_dokument)>0 ) {
                $dok_odpoved = $rozdelany_dokument[0];
                // odpoved jiz existuje, tak ji nacteme
                $DokumentSpis = new DokumentSpis();
                $DokumentSubjekt = new DokumentSubjekt();
                $DokumentPrilohy = new DokumentPrilohy();

                $spisy = $DokumentSpis->spisy($dok_odpoved->id);
                $this->template->Spisy = $spisy;

                $subjekty = $DokumentSubjekt->subjekty($dok_odpoved->id);
                $this->template->Subjekty = $subjekty;

                $prilohy  = $DokumentPrilohy->prilohy($dok_odpoved->id,null,1);
                $this->template->Prilohy = $prilohy;

                $UserModel = new UserModel();
                $user = $UserModel->getUser(Environment::getUser()->getIdentity()->id, 1);
                $this->template->Prideleno = Osoba::displayName($user->identity);

                $CJ = new CisloJednaci();
                $this->template->Typ_evidence = $this->typ_evidence;
                if ( $this->typ_evidence == 'priorace' ) {
                    // Nacteni souvisejicicho dokumentu
                    $Souvisejici = new SouvisejiciDokument();
                    $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dok_odpoved->id);

                    $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);
                } else if ( $this->typ_evidence == 'sberny_arch' ) {
                    // sberny arch
                    //$dok_odpoved->poradi = $dok_odpoved->poradi;
                    $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);
                } else {
                    $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);
                }

                $this->template->Dok = $dok_odpoved;


            } else {
                // totozna odpoved neexistuje
    
		// nalezeni nejvyssiho cisla poradi v ramci spisu
		$poradi = $Dokumenty->getMaxPoradi($dok->cislo_jednaci_id);

                $pred_priprava = array(
                    "nazev" => $dok->nazev,
                    "popis" => $dok->popis,
                    "stav" => 0,
                    "dokument_typ_id" => 2,
                    "zpusob_doruceni_id" => null,
                    "cislo_jednaci_id" => $dok->cislo_jednaci_id,
                    "cislo_jednaci" => $dok->cislo_jednaci,
                    "podaci_denik" => $dok->podaci_denik,
                    "podaci_denik_poradi" => $dok->podaci_denik_poradi,
                    "podaci_denik_rok" => $dok->podaci_denik_rok,
                    "poradi" => ($poradi),
                    "cislo_jednaci_odesilatele" => $dok->cislo_jednaci_odesilatele,
                    "datum_vzniku" => date('Y-m-d H:i:s'),
                    "lhuta" => "30",
                    "poznamka" => $dok->poznamka,
                    "zmocneni_id" => null
                );
                $dok_odpoved = $Dokumenty->ulozit($pred_priprava);

                if ( $dok_odpoved ) {
                
                    $DokumentSpis = new DokumentSpis();
                    $DokumentSubjekt = new DokumentSubjekt();
                    $DokumentPrilohy = new DokumentPrilohy();

                    // kopirovani spisu
                    $spisy_old = $DokumentSpis->spisy($dokument_id);
                    if ( count($spisy_old)>0 ) {
                        foreach ( $spisy_old as $spis ) {
                            $DokumentSpis->pripojit($dok_odpoved->id, $spis->id, $stav = 1);
                        }
                    }
                    $spisy_new = $DokumentSpis->spisy($dok_odpoved->id);
                    $this->template->Spisy = $spisy_new;

                    // kopirovani subjektu
                    $subjekty_old = $DokumentSubjekt->subjekty($dokument_id);
                    if ( count($subjekty_old)>0 ) {
                        foreach ( $subjekty_old as $subjekt ) {
                            if ( $subjekt->type != 'O' ) {
                                $DokumentSubjekt->pripojit($dok_odpoved->id, $subjekt->id, $subjekt->rezim_subjektu);
                            }
                        }
                    }
                    $subjekty_new = $DokumentSubjekt->subjekty($dokument_id);
                    $this->template->Subjekty = $subjekty_new;

                    // kopirovani prilohy
                    $prilohy_old  = $DokumentPrilohy->prilohy($dokument_id,null,1);

                    if ( count($prilohy_old)>0 ) {
                        foreach ( $prilohy_old as $priloha ) {
                            if ( $priloha->typ == 1 || $priloha->typ == 2 || $priloha->typ == 3 ) {
                                $DokumentPrilohy->pripojit($dok_odpoved->id, $priloha->id);
                            }
                        }
                    }
                    $prilohy_new  = $DokumentPrilohy->prilohy($dok_odpoved->id,1);
                    $this->template->Prilohy = $prilohy_new;

                    $UserModel = new UserModel();
                    $user = $UserModel->getUser(Environment::getUser()->getIdentity()->id, 1);
                    $this->template->Prideleno = Osoba::displayName($user->identity);

                    $CJ = new CisloJednaci();
                    $this->template->Typ_evidence = $this->typ_evidence;
                    $this->template->SouvisejiciDokumenty = null;
                    if ( $this->typ_evidence == 'priorace' ) {
                        // priorace - Nacteni souvisejicicho dokumentu
                        $Souvisejici = new SouvisejiciDokument();
                        $Souvisejici->spojit($dok_odpoved->id,$dokument_id);
                        $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dok_odpoved->id);
                        
                        $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);
                    } else if ( $this->typ_evidence == 'sberny_arch' ) {
                        // sberny arch
                        //$dok_odpoved->poradi = $dok_odpoved->poradi;
                        $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);

                    } else {
                        $this->template->cjednaci = $CJ->nacti($dok->cislo_jednaci_id);
                    }

                    $this->template->Dok = $dok_odpoved;
                } else {
                    $this->template->Dok = null;
                    $this->flashMessage('Dokument není připraven k vytvoření','warning');
                }
            }

            $this->odpoved = $dok->cislo_jednaci_id;

            $this->template->novyForm = $this['novyForm'];
            $this->setView('novy');

        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument neexistuje','warning');
            $this->redirect(':Spisovka:Dokumenty:default');
        }

    }


    public function renderDownload() 
    {

        $dokument_id = $this->getParam('id',null);
        $file_id = $this->getParam('file',null);
        
        $DokumentPrilohy = new DokumentPrilohy();
        $prilohy = $DokumentPrilohy->prilohy($dokument_id);
        if ( key_exists($file_id, $prilohy) ) {

            $storage_conf = Environment::getConfig('storage');
            eval("\$DownloadFile = new ".$storage_conf->type."();");
            $FileModel = new FileModel();
            $file = $FileModel->getInfo($file_id);
            $res = $DownloadFile->download($file);
            if ( $res == 0 ) {
                $this->terminate();
            } else if ( $res == 1 ) {
                // not found
                $this->flashMessage('Požadovaný soubor nenalezen!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 2 ) {
                $this->flashMessage('Chyba při stahování!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 3 ) {
                $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout zmíněný soubor!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            }
        } else {
            $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout cizí soubor!','warning');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        }
        
    }

    public function renderZmocneni()
    {
        
    }

    public function renderHistorie()
    {

        $dokument_id = $this->getParam('id',null);

        $Log = new LogModel();
        $historie = $Log->historieDokumentu($dokument_id,1000);

        $this->template->historie = $historie;

    }

    public function actionOdeslat()
    {

        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $this->getParam('id',null);
        $dokument = $Dokument->getInfo($dokument_id, 1);

        if ( $dokument ) {
            // dokument zobrazime
            $this->template->Dok = $dokument;

            $user = Environment::getUser();
            //Debug::dump($user);

            $user_id = $user->getIdentity()->id;
            $this->template->Pridelen = 0;
            $this->template->Predan = 0;
            // Prideleny nebo predany uzivatel
            if ( @$dokument->prideleno->prideleno_id == $user_id ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
            } else if ( @$dokument->predano->prideleno_id == $user_id ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
            } else {
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 0;
                if ( count($dokument->workflow)>0 ) {
                    foreach ($dokument->workflow as $wf) {
                        if ( ($wf->prideleno_id == $user_id) && ($wf->stav_osoby < 100) ) {
                            $this->template->AccessView = 1;
                        }
                    }
                }
            }

            // Prilohy
            $prilohy_celkem = 0;
            if ( count($dokument->prilohy) > 0 ) {
                foreach ($dokument->prilohy as $p) {
                    $prilohy_celkem = $prilohy_celkem + $p->size;
                }
            }
            $this->template->PrilohyCelkovaVelikost = $prilohy_celkem;

            $this->template->ChybaPredOdeslani = 0;


            // Dokument se vyrizuje
            if ( $dokument->stav_dokumentu == 3 ) {
                $this->template->Vyrizovani = 1;
            } else {
                $this->template->Vyrizovani = 0;
            }

            // SuperAdmin - moznost zasahovat do dokumentu
            if ( $user->isInRole('superadmin') ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
            }

            $this->template->FormUpravit = $this->getParam('upravit',null);

            $SpisovyZnak = new SpisovyZnak();
            $this->template->SpisoveZnaky = $SpisovyZnak->seznam(null);

            $this->template->DruhZasilky = DruhZasilky::get(null,1);
            

            $this->invalidateControl('dokspis');
        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }

    }

    public function renderOdeslat()
    {
        $this->template->odeslatForm = $this['odeslatForm'];
    }

    protected function createComponentNovyForm()
    {

        $dok = null;
        if( isset($this->template->Dok) ) {
            $dokument_id = isset($this->template->Dok->id)?$this->template->Dok->id:0;
            $dok = $this->template->Dok;
        } else {
            $dokument_id = 0;
        }

        if ( Acl::isInRole('podatelna') ) {
            $typ_dokumentu = Dokument::typDokumentu(null,2);
            $this->template->isPodatelna = true;
        } else {
            $typ_dokumentu = Dokument::typDokumentu(null,1);
            $this->template->isPodatelna = false;
        }

        $zpusob_doruceni = Dokument::zpusobDoruceni(null,2);
        
        $form = new AppForm();
        $form->addHidden('id')
                ->setValue($dokument_id);
        $form->addHidden('odpoved')
                ->setValue($this->odpoved);
        $form->addHidden('predano_user');
        $form->addHidden('predano_org');
        $form->addHidden('predano_poznamka');

        if ( $this->typ_evidence == 'sberny_arch' ) {
            $form->addText('poradi', 'Pořadí dokumentu ve sberném archu:', 4, 4)
                    ->setValue(@$dok->poradi)
                    ->controlPrototype->readonly = TRUE;
        }

        if ( isset($dok->nazev) && $dok->nazev == "(bez názvu)" ) $dok->nazev = "";
        if ( $this->template->isPodatelna ) {
            $form->addText('nazev', 'Věc:', 80, 100)
                    ->setValue(@$dok->nazev);
            
        } else {
            $form->addText('nazev', 'Věc:', 80, 100)
                    ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!')
                    ->setValue(@$dok->nazev);
        }
        $form->addTextArea('popis', 'Stručný popis:', 80, 3)
                ->setValue(@$dok->popis);
        $form->addSelect('dokument_typ_id', 'Typ Dokumentu:', $typ_dokumentu)
                ->setValue(@$dok->dokument_typ->id);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$dok->cislo_jednaci_odesilatele);

        $datum = date('d.m.Y');
        $cas = date('H:i:s');

        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10)
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);

        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:',$zpusob_doruceni);
        
        $form->addText('cislo_doporuceneho_dopisu', 'Číslo jdoporučeného dopisu:', 50, 50)
                ->setValue(@$dok->cislo_doporuceneho_dopisu);        
        
        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->addRule(Form::FILLED, 'Lhůta k vyřízení musí být vyplněna!')
                ->setValue('30');
        $form->addTextArea('poznamka', 'Poznámka:', 80, 6)
                ->setValue(@$dok->poznamka);

        $form->addTextArea('predani_poznamka', 'Poznámka:', 80, 3);
        
        $form->addHidden('zmocneni')->setValue(0);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$dok->pocet_listu);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$dok->pocet_priloh);
        $form->addText('typ_prilohy', 'Typ přílohy:', 20, 50)
                ->setValue(@$dok->typ_prilohy);        


        $form->addSubmit('novy', 'Vytvořit dokument')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form->addSubmit('novy_pridat', 'Vytvořit dokument a založit nový')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoSeznamClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Dokument = new Dokument();

        $dokument_id = $data['id'];
        $data['stav'] = 1;

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] ." ". $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        // predani
        $predani_poznamka = $data['predani_poznamka'];

        unset($data['predani_poznamka'],$data['id'],$data['version']);

        try {

            //Debug::dump($dokument_id); exit;
            $CJ = new CisloJednaci();

            if ( !empty($data['odpoved']) ) {
                $cjednaci = $CJ->nacti($data['odpoved'],0);
                unset($data['odpoved']);
            } else {
                $cjednaci = $CJ->generuj(); // 1 - generuj, 0 - negeneruj
            }

            $data['jid'] = $cjednaci->app_id.'-ESS-'.$dokument_id;
            //$data['cislo_jednaci_id'] = (int) $cjednaci->id;
            //$data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
            $data['podaci_denik'] = $cjednaci->podaci_denik;
            //$data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
            $data['podaci_denik_rok'] = $cjednaci->rok;

            //Debug::dump($data); exit;

            $dokument = $Dokument->ulozit($data, $dokument_id);//   array('dokument_id'=>0);// $Dokument->ulozit($data);

            if ( $dokument ) {
                $Workflow = new Workflow();
                $Workflow->vytvorit($dokument_id,$predani_poznamka);

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

                // Vytvoreni spisu noveho archu
                if ( $this->typ_evidence == 'sberny_arch' ) {

                    $Spis = new Spis();
                    $spis = $Spis->getInfo($data['cislo_jednaci']);
                    if ( !$spis ) {
                        // zjistime aktualni spisovy plan
                        $spisovy_plan = $Spis->getSpisovyPlan();
                        // vytvorime spis
                        $spis_new = array(
                            'parent_id' => $spisovy_plan,
                            'nazev' => $data['cislo_jednaci'],
                            'popis' => $data['popis'],
                            'spousteci_udalost_id' => 3,
                            'skartacni_znak' => 'S',                            
                            'skartacni_lhuta' => '10',                              
                            'typ' => 'S',
                            'stav' => 1
                        );
                        $spis_id = $Spis->vytvorit($spis_new);
                        $spis = $Spis->getInfo($spis_id);
                    }

                    // pripojime
                    if ( $spis ) {
                        $DokumentSpis = new DokumentSpis();
                        $DokumentSpis->pripojit($dokument_id, $spis->id);
                    }

                }

                $this->flashMessage('Dokument byl vytvořen.');

                if ( !empty($data['predano_user']) || !empty($data['predano_org']) ) {
                    /* Dokument predan */
                    $Workflow->priradit($dokument_id, $data['predano_user'], $data['predano_org'], $data['predano_poznamka']);
                    $this->flashMessage('Dokument předán zaměstnanci nebo organizační jendotce.');
                }

                $name = $button->getName();
                if ( $name == "novy_pridat" ) {
                    $this->redirect(':Spisovka:Dokumenty:novy');
                } else {
                    $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));    
                }
                
            } else {
                $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            }
        } catch (DibiException $e) {
            $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
            //Debug::dump($e); exit;
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['id'];
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Spisovka:Dokumenty:default');
    }

    protected function createComponentMetadataForm()
    {
        
        $Dok = @$this->template->Dok;

        if ( Acl::isInRole('podatelna') ) {
            $typ_dokumentu = Dokument::typDokumentu(null,2);
            $this->template->isPodatelna = true;
        } else {
            $typ_dokumentu = Dokument::typDokumentu(null,1);
            $this->template->isPodatelna = false;
        }  
        
        $zpusob_doruceni = Dokument::zpusobDoruceni(null,2);

        $form = new AppForm();
        $form->addHidden('id')
                ->setValue(@$Dok->id);
        
        $nazev = (@$Dok->nazev=="(bez názvu)")?"":$Dok->nazev;
        if ( $this->template->isPodatelna ) {
            $form->addText('nazev', 'Věc:', 80, 100)
                    ->setValue($nazev);
        } else {
            $form->addText('nazev', 'Věc:', 80, 100)
                    ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!')
                    ->setValue($nazev);
        }        
        
        $form->addTextArea('popis', 'Stručný popis:', 80, 3)
                ->setValue(@$Dok->popis);
        $form->addSelect('dokument_typ_id', 'Typ Dokumentu:', $typ_dokumentu)
                ->setValue(@$Dok->dokument_typ_id);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$Dok->cislo_jednaci_odesilatele);

        $unixtime = strtotime(@$Dok->datum_vzniku);
        if ( $unixtime == 0 ) {
            $datum = date('d.m.Y');
            $cas = date('H:i:s');
        } else {
            $datum = date('d.m.Y',$unixtime);
            $cas = date('H:i:s',$unixtime);
        }

        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10)
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);
        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:',$zpusob_doruceni)
                ->setValue(@$Dok->zpusob_doruceni_id);
        
        $form->addText('cislo_doporuceneho_dopisu', 'Číslo jdoporučeného dopisu:', 50, 50)
                ->setValue(@$Dok->cislo_doporuceneho_dopisu);          
        
        $form->addTextArea('poznamka', 'Poznámka:', 80, 6)
                ->setValue(@$Dok->poznamka);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$Dok->pocet_listu);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$Dok->pocet_priloh);
        $form->addText('typ_prilohy', 'Typ přílohy:', 20, 50)
                ->setValue(@$Dok->typ_prilohy);  
        
        $form->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'upravitMetadataClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function upravitMetadataClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        
        $Dokument = new Dokument();
        $dokument_id = $data['id'];
        $dok = $Dokument->getInfo($dokument_id);        

        //Debug::dump($data);
        
        if ( !($dok->stav_dokumentu == 1 || Environment::getUser()->isInRole('superadmin')) ) {
            // needitovatelne skryte polozky
            $data['datum_vzniku'] = $dok->datum_vzniku;
            $data['dokument_typ_id'] = $dok->typ_dokumentu->id;
            $data['zpusob_doruceni_id'] = $dok->zpusob_doruceni_id;
            $data['cislo_doporuceneho_dopisu'] = $dok->cislo_doporuceneho_dopisu;
            unset($data['datum_vzniku_cas']);
        } else {
            // uprava casu
            if ( isset($data['datum_vzniku']) ) {
                $data['datum_vzniku'] = $data['datum_vzniku'] ." ". $data['datum_vzniku_cas'];
            }
            unset($data['datum_vzniku_cas']);            
        }
        
        //Debug::dump($data); exit;
        //Debug::dump($dok); exit;

        try {

            $dokument = $Dokument->ulozit($data,$dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upravena metadata dokumentu.');

            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'"  byl upraven.');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'" se nepodařilo upravit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }

    protected function createComponentVyrizovaniForm()
    {

        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(null, 1);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);
        $spousteci_udalost = $SpisovyZnak->spousteci_udalost(null, 1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $Dok = @$this->template->Dok;

        $form = new AppForm();
        $form->addHidden('id')
                ->setValue(@$Dok->id);
        $form->addSelect('zpusob_vyrizeni_id', 'Způsob vyřízení:', $zpusob_vyrizeni)
                ->setValue(@$Dok->zpusob_vyrizeni_id);

        $unixtime = strtotime(@$Dok->datum_vyrizeni);
        if ( $unixtime == 0 ) {
            $datum = date('d.m.Y');
            $cas = date('H:i:s');
        } else {
            $datum = date('d.m.Y',$unixtime);
            $cas = date('H:i:s',$unixtime);
        }
        
        $form->addDatePicker('datum_vyrizeni', 'Datum vyřízení:', 10)
                ->setValue($datum);
        $form->addText('datum_vyrizeni_cas', 'Čas vyřízení:', 10, 15)
                ->setValue($cas);

        $form->addSelect('spisovy_znak_id', 'spisový znak:', $spisznak_seznam)
                ->setValue(@$Dok->spisovy_znak_id)
                ->controlPrototype->onchange("vybratSpisovyZnak();");
        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6)
                ->setValue(@$Dok->ulozeni_dokumentu);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 6)
                ->setValue(@$Dok->poznamka_vyrizeni);

        //$form->addText('skartacni_znak','Skartační znak: ', 3, 3)
        //        ->setValue(@$Dok->skartacni_znak)
        //        ->controlPrototype->readonly = TRUE;
        $form->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$Dok->skartacni_znak)
                ->controlPrototype->readonly = TRUE;        
        $form->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$Dok->skartacni_lhuta)
                ->controlPrototype->readonly = TRUE;
        $form->addSelect('spousteci_udalost_id','Spouštěcí událost: ', $spousteci_udalost)
                ->setValue( empty($Dok->spousteci_udalost_id)?3:@$Dok->spousteci_udalost_id )
                ->controlPrototype->readonly = TRUE;

        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_listu);
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_priloh);
        $form->addText('vyrizeni_typ_prilohy', 'Typ přílohy:', 20, 50)
                ->setValue(@$Dok->vyrizeni_typ_prilohy);          


//                ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!');

        $form->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'upravitVyrizeniClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function upravitVyrizeniClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $data['id'];

        // uprava casu
        $data['datum_vyrizeni'] = $data['datum_vyrizeni'] ." ". $data['datum_vyrizeni_cas'];
        unset($data['datum_vzniku_cas']);

        // spisovy znak


        //Debug::dump($data); exit;

        $Dokument = new Dokument();

        $dok = $Dokument->getInfo($dokument_id);

        try {

            $dokument = $Dokument->ulozit($data,$dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upravena data vyřízení.');

            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'"  byl upraven.');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'" se nepodařilo upravit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');

            Debug::dump($e);
            exit;
            //$this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        }

    }

    protected function createComponentUdalostForm()
    {

        $Dok = @$this->template->Dok;

        $form = new AppForm();
        $form->addHidden('id')
                ->setValue(@$Dok->id);

        $options = array(
            '1'=>'Spustit událost od data (vyplňte datum '. @$Dok->spisovy_znak_udalost_dtext .')',
            '2'=>'Spustit událost ručně (událost se spustí v pozdějším čase ručně)',
            '3'=>'Spustit událost okamžitě (po odeslání se událost spustí)'
        );
        $form->addRadioList('udalost_typ', 'Jak spustit událost:', $options);

        $form->addDatePicker('datum_spousteci_udalosti', 'Datum spuštění události:');

        $form->addSubmit('vyridit', 'Vyřídit dokument')
                 ->onClick[] = array($this, 'udalostClicked');
        $form->addSubmit('storno', 'Zrušit vyřízení')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function udalostClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        //Debug::dump($data); exit;

        $dokument_id = $data['id'];
        $UserModel = new UserModel();
        $user_id = Environment::getUser()->getIdentity()->id;
        $orgjednotka_id = @$UserModel->getOrg(Environment::getUser()->getIdentity())->id;
        if ( $data['udalost_typ'] == 1 && !empty($data['datum_spousteci_udalosti']) ) {
            // spusteni udalosti dle datumu
            $add = array('stav'=>5, 'datum'=>$data['datum_spousteci_udalosti']);
        } else if ( $data['udalost_typ'] == 2 ) {
            // rucni spusteni udalosti
            $add = array('stav'=>4);
        } else if ( $data['udalost_typ'] == 3 ) {
            // okamzite spusteni
            $add = array('stav'=>5, 'datum'=>date('Y-m-d'));
        } else {
            $this->flashMessage('Nebyla vybrána spouštěcí událost. Vyberte událost nebo zrušte vyřízení.','warning');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id, 'udalost'=>1));
        }

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            $ret = $Workflow->vyrizeno($dokument_id, $user_id, $orgjednotka_id, $add);
            if ( $ret == "udalost" ) {
                // manualni vyrizeni
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id,'udalost'=>1));
            } else if ( $ret == true ) {
                // automaticke vyrizeni
                $Workflow->zrusit_prevzeti($dokument_id);
                $this->flashMessage('Označil jste tento dokument za vyřízený!');
            } else {
                $this->flashMessage('Označení dokumentu za vyřízený se nepodařilo. Zkuste to znovu.','warning');

            }
        } else {
            $this->flashMessage('Nemáte oprávnění označit dokument za vyřízený.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }


    protected function createComponentOdeslatForm()
    {

        $typ_dokumentu = Dokument::typDokumentu(null,1);
        $Dok = @$this->template->Dok;


        $zprava = "";


        $sznacka = "";
        if ( isset( $this->template->Dok->spisy ) ) {
            $sznacka_A = array();
            foreach ($this->template->Dok->spisy as $spis) {
                $sznacka_A[] = $spis->nazev;
            }
            $sznacka = implode(", ",$sznacka_A);
        }
        $this->template->SpisovaZnacka = $sznacka;

        // odesilatele
        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();
        $odesilatele = array();

        if ( count($ep['odeslani'])>0 ) {
            foreach ($ep['odeslani'] as $odes_id => $odes) {
                if ( $odes['aktivni']==1 ) {
                    if ( empty($odes['jmeno']) ) {
                        $odesilatele['epod'.$odes_id] = $odes['email'] ."[".$odes['ucet']."]";
                    } else {
                        $odesilatele['epod'.$odes_id] = $odes['jmeno'] ." <".$odes['email']."> [".$odes['ucet']."]";
                    }
                }
            }
        }
        $user_info = Environment::getUser()->getIdentity();
        if ( !empty($user_info->identity->email) ) {
            $key = "user#". Osoba::displayName($user_info->identity, 'jmeno') ."#". $user_info->identity->email;
            $odesilatele[$key] = Osoba::displayName($user_info->identity, 'jmeno') ." <". $user_info->identity->email ."> [zaměstnanec]";
        }

        $this->template->odesilatele = $odesilatele;
        
        $form = new AppForm();
        $form->addHidden('id')
                ->setValue(@$Dok->id);
        $form->addSelect('email_from', 'Odesílatel:', $odesilatele);
        $form->addText('email_predmet', 'Předmět zprávy:', 80, 100)
                ->setValue(@$Dok->nazev);
        $form->addTextArea('email_text', 'Text:', 80, 15)
                ->setValue($zprava);

        $form->addText('isds_predmet', 'Předmět zprávy:', 80, 100)
                ->setValue(@$Dok->nazev);
        $form->addText('isds_cjednaci_odes', 'Číslo jendací odesílatele:', 40, 100)
                ->setValue(@$Dok->cislo_jednaci);
        $form->addText('isds_spis_odes', 'Spisová značka odesílatele:', 40, 100)
                ->setValue($sznacka);
        $form->addText('isds_cjednaci_adres', 'Číslo jendací adresáta:', 40, 100)
                ->setValue(@$Dok->cislo_jednaci_odesilatele);
        $form->addText('isds_spis_adres', 'Spisová značka adresáta:', 40, 100);
        $form->addCheckbox('isds_dvr', 'Do vlastních rukou? :')
                ->setValue(false);
        $form->addCheckbox('isds_fikce', 'Doručit fikcí? :')
                ->setValue(true);



        $form->addSubmit('odeslat', 'Odeslat zprávu adresátům nebo předat do podatelny k odeslání')
                 ->onClick[] = array($this, 'odeslatClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function odeslatClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        //Debug::dump($data); exit;

        $dokument_id = $data['id'];
        $Dokument = new Dokument();
        $Subjekt = new Subjekt();
        $File = new FileModel();
        //$dok = $Dokument->getInfo($dokument_id, $dokument_version);

        $post_data = Environment::getHttpRequest()->getPost();

        //Debug::dump($post_data);
        //Debug::dump($data);

        //exit;

        //echo "<pre>";
        //echo "\n\nPřílohy:\n\n";

        $prilohy = array();
        if ( isset($post_data['prilohy'])) {
            if ( count($post_data['prilohy'])>0 ) {

                $storage_conf = Environment::getConfig('storage');
                eval("\$DownloadFile = new ".$storage_conf->type."();");

                foreach ($post_data['prilohy'] as $file_id => $status) {
                    $priloha = $File->getInfo($file_id);
                    $priloha->tmp_file = $DownloadFile->download($priloha,2);

                    $prilohy[ $file_id ] = $priloha;
                }
            } else {
                // zadne prilohy
            }
        } else {
            // zadne prilohy
        }


        //echo "\n\nAdresati:\n\n";

        if ( isset($post_data['subjekt'])) {
            if ( count($post_data['subjekt'])>0 ) {
                foreach ($post_data['subjekt'] as $subjekt_id => $metoda_odeslani) {
                    $adresat = $Subjekt->getInfo($subjekt_id);
                    //echo Subjekt::displayName($adresat) ."\n";
                    //Debug::dump($adresat);

                    $datum_odeslani = new DateTime();
                    $epodatelna_id = null;
                    $zprava_odes = '';                    
                    $cena = null;
                    $hmotnost = null;
                    $druh_zasilky = null;
                    $cislo_faxu = '';
                    $stav = 0;

                    if ( $metoda_odeslani == 0 ) {
                        // neodesilat - nebudeme delat nic
                        //echo "  => neodesilat";
                        continue;
                    } elseif ( $metoda_odeslani == 1 ) {
                        // emailem
                        //echo "  => emailem";
                        if ( !empty($adresat->email) ) {
                            
                            $data = array(
                                'email_from' => $post_data['email_from'][$subjekt_id],
                                'email_predmet' => $post_data['email_predmet'][$subjekt_id],
                                'email_text' => $post_data['email_text'][$subjekt_id],
                            );
                            
                            if ( $zprava = $this->odeslatEmailem($adresat, $data, $prilohy) ) {
                                $Log = new LogModel();
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán emailem na adresu "'. Subjekt::displayName($adresat,'email') .'".');
                                $this->flashMessage('Zpráva na emailovou adresu "'. Subjekt::displayName($adresat,'email') .'" byla úspěšně odeslána.');
                                $stav = 2;
                            } else {
                                $this->flashMessage('Zprávu na emailovou adresu "'. Subjekt::displayName($adresat,'email') .'" se nepodařilo odeslat!','warning');
                                $stav = 0;
                                continue;
                            }
                            
                            if ( isset($zprava['epodatelna_id']) ) {
                                $epodatelna_id = $zprava['epodatelna_id'];
                            }
                            if ( isset($zprava['zprava']) ) {
                                $zprava_odes = $zprava['zprava'];
                            }                            
                        } else {
                            $this->flashMessage('Subjekt "'. Subjekt::displayName($adresat,'email') .'" nemá emailovou adresu. Zprávu tomuto adresátovi nelze poslat přes email!','warning');
                            continue;
                        }
                    } elseif ( $metoda_odeslani == 2 ) {
                        // isds
                        //echo "  => isds";
                        if ( !empty($adresat->id_isds) ) {
                            
                            $data = array(
                                'isds_cjednaci_odes' => $post_data['email_from'][$subjekt_id],
                                'isds_spis_odes' => $post_data['email_from'][$subjekt_id],
                                'isds_cjednaci_adres' => $post_data['email_from'][$subjekt_id],
                                'isds_spis_adres' => $post_data['email_from'][$subjekt_id],
                                'isds_dvr' => isset($post_data['email_from'][$subjekt_id])?true:false,
                                'isds_fikce' => isset($post_data['email_from'][$subjekt_id])?true:false,
                            );
                            
                            if ( $zprava = $this->odeslatISDS($adresat, $data, $prilohy) ) {
                                $Log = new LogModel();
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán datovou zprávou na adresu "'. Subjekt::displayName($adresat,'isds') .'".');
                                $this->flashMessage('Datová zpráva pro "'. Subjekt::displayName($adresat,'isds') .'" byla úspěšně odeslána do systému ISDS.');
                                $stav = 2;
                            } else {
                                $this->flashMessage('Datoovu zprávu pro "'. Subjekt::displayName($adresat,'isds') .'" se nepodařilo odeslat do systému ISDS!','warning');
                                $stav = 0;
                                continue;
                            }
                            
                            if ( isset($zprava['epodatelna_id']) ) {
                                $epodatelna_id = $zprava['epodatelna_id'];
                            }
                            if ( isset($zprava['zprava']) ) {
                                $zprava_odes = $zprava['zprava'];
                            }                            
                            
                        } else {
                            $this->flashMessage('Subjekt "'. Subjekt::displayName($adresat,'jmeno') .'" nemá ID datové schránky. Zprávu tomuto adresátovi nelze poslat přes datovou schránku!','warning');
                            continue;
                        }

                    } else if ( $metoda_odeslani == 3 ) {
                        // postou
                        if ( isset($post_data['datum_odeslani_postou'][$subjekt_id]) ) {
                            $datum_odeslani = new DateTime( $post_data['datum_odeslani_postou'][$subjekt_id] );
                        }
                        
                        $druh_zasilky_form = $post_data['druh_zasilky'][$subjekt_id];
                        if ( count($druh_zasilky_form)>0 ) {
                            $druh_zasilky_a = array();
                            foreach( $druh_zasilky_form as $druh_id=>$druh_status ) {
                                $druh_zasilky_a[] = $druh_id;
                            }
                            $druh_zasilky = serialize($druh_zasilky_a);
                        }
                        
                        $cena = floatval($post_data['cena_zasilky'][$subjekt_id]);
                        $hmotnost = floatval($post_data['hmotnost_zasilky'][$subjekt_id]);
                        $stav = 1;
                        
                        $this->flashMessage('Dokument předán na podatelnu k odeslání poštou na adresu "'. Subjekt::displayName($adresat) .'".');
                        
                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_PREDODESLAN,'Dokument předán na podatelnu k odeslání poštou na adresu "'. Subjekt::displayName($adresat) .'".');
                        
                    } else if ( $metoda_odeslani == 4 ) {
                        // faxem
                        if ( isset($post_data['datum_odeslani_faxu'][$subjekt_id]) ) {
                            $datum_odeslani = new DateTime( $post_data['datum_odeslani_faxu'][$subjekt_id] );
                        }
                        
                        $cislo_faxu = $post_data['cislo_faxu'][$subjekt_id];
                        $zprava_odes = $post_data['zprava_faxu'][$subjekt_id];
                        $stav = 1;
                        
                        $this->flashMessage('Dokument předán na podatelnu k odeslání faxem na číslo "'. $cislo_faxu .'".');
                        
                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_PREDODESLAN,'Dokument předán na podatelnu k odeslání faxem na číslo "'. $cislo_faxu .'".');
                        
                    } else {
                        // jinak - externe (osobne, ...)
                        //echo "  => jinak";

                        if ( isset($post_data['datum_odeslani'][$subjekt_id]) ) {
                            $datum_odeslani = new DateTime( $post_data['datum_odeslani'][$subjekt_id] );
                        }

                        switch ($metoda_odeslani) {
                            case 1:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán emailem na adresu "'. Subjekt::displayName($adresat,'email') .'".');
                                break;
                            case 2:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán datovou zprávou na adresu "'. Subjekt::displayName($adresat,'isds') .'".');
                                break;
                            case 3:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán poštou na adresu "'. Subjekt::displayName($adresat) .'".');
                                break;
                            case 4:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán faxem na číslo "'. Subjekt::displayName($adresat,'telefon') .'".');
                                break;
                            case 5:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán osobně "'. Subjekt::displayName($adresat,'jmeno') .'".');
                                break;
                            case 6:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán telefonicky na číslo "'. Subjekt::displayName($adresat,'telefon') .'".');
                                break;
                            default: break;
                        }

                    }


                    // Zaznam do DB (dokument_odeslani)
                    $DokumentOdeslani = new DokumentOdeslani();
                    $row = array(
                        'dokument_id' => $dokument_id,
                        'subjekt_id' => $adresat->id,
                        'zpusob_odeslani_id' => (int) $metoda_odeslani,
                        'epodatelna_id' => $epodatelna_id,
                        'datum_odeslani' => $datum_odeslani,
                        'zprava' => $zprava_odes,
                        'druh_zasilky' => $druh_zasilky,
                        'cena' => $cena,
                        'hmotnost' => $hmotnost,
                        'cislo_faxu' => $cislo_faxu,
                        'stav' => $stav
                    );
                    $DokumentOdeslani->ulozit($row);
                    
                }

                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else {
                // zadni adresati
            }
        } else {
            // zadni adresati
        }

    }


    protected function odeslatEmailem($adresat, $data, $prilohy)
    {

        $mail = new ESSMail;
        $mail->signed(1);

        if ( !empty($data['email_from']) ) {

            if ( strpos($data['email_from'],"epod")!==false ) {
                $id_odes = substr($data['email_from'],4);
                $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
                $ep = $ep_config->toArray();
                if ( isset( $ep['odeslani'][$id_odes] ) ) {
                    $mail->setFromConfig($ep['odeslani'][$id_odes]);
                } else {
                    $mail->setFromConfig();
                }
            } else if ( strpos($data['email_from'],"user")!==false ) {

                $user_part = explode("#",$data['email_from']);
                $mail->setFrom($user_part[2],$user_part[1]);

            } else {
                $mail->setFromConfig();
            }
        } else {
            $mail->setFromConfig();
        }

        if ( strpos($adresat->email,';') !== false ) {
            $email_parse = explode(';',$adresat->email);
            foreach($email_parse as $emp) {
                $email = trim($emp);
                $mail->addTo($email);
            }
        } elseif ( strpos($adresat->email,',') !== false ) {
            $email_parse = explode(',',$adresat->email);
            foreach($email_parse as $emp) {
                $email = trim($emp);
                $mail->addTo($email);
            }
        } else {
            $mail->addTo($adresat->email);
        }

        $mail->setSubject($data['email_predmet']);
        $mail->setBody($data['email_text']);

        if ( count($prilohy)>0 ) {
            foreach ($prilohy as $p) {
                $mail->addAttachment($p->tmp_file);
            }
        }

        try {
            $mail->send();
        } catch (Exception $e) {
            $this->flashMessage('Chyba při odesilání emailu! '. $e->getMessage(),'error_ext');
            return false;
        }

            $source = "";
            if ( file_exists(CLIENT_DIR .'/temp/tmp_email.eml') ) {
                //if ( $fp = @fopen(WWW_DIR .'/files/tmp_email.eml','rb') ) {
                //    $source = fread($fp, filesize(WWW_DIR .'/files/tmp_email.eml') );
                //    @fclose($fp);
                //}
                $source = CLIENT_DIR .'/temp/tmp_email.eml';
            }

            // Do epodatelny
            $storage_conf = Environment::getConfig('storage');
            eval("\$UploadFile = new ".$storage_conf->type."();");

            // nacist email z ImapClient
            $imap = new ImapClientFile();
            if ( $imap->open($source) ) {
                $email_mess = $imap->get_head_message(0);
            } else {
                $email_mess = new stdClass();
                $email_mess->from_address = @$user_part[2];
                $mid = sha1(@$data['email_predmet'] ."#". time() ."#". @$user_part[2] ."#". @$adresat->email);
                $email_mess->message_id =  "<$mid@mail>";
                $email_mess->subject = $data['email_predmet'];
                $email_mess->to_address = $adresat->email;
            }

            //echo $source;
            //Debug::dump($email_mess);
            //exit;

            
            $email_config = $mail->getFromConfig();
            if ( is_null($email_config) ) {
                if ( isset($user_part) ) {
                    $email_config['ucet'] = $user_part[1];
                    $email_config['email'] = $user_part[2];
                } else {
                    $email_config['ucet'] = "uživatel";
                    $email_config['email'] = $email_mess->from_address;
                }
            }

            $user = Environment::getUser()->getIdentity();


            // zapis do epodatelny
            $Epodatelna = new Epodatelna();
            $zprava = array();
            $zprava['epodatelna_typ'] = 1;
            $zprava['poradi'] = $Epodatelna->getMax(1);
            $zprava['rok'] = date('Y');
            $zprava['email_signature'] = $email_mess->message_id;
            $zprava['predmet'] = empty($email_mess->subject)?$data['email_predmet']:$email_mess->subject;
            if ( empty($zprava['predmet']) ) $zprava['predmet'] = "(bez předmětu)";
            $zprava['popis'] = $data['email_text'];
            $zprava['odesilatel'] = $email_mess->to_address;
            $zprava['odesilatel_id'] = $adresat->id;
            $zprava['adresat'] = $email_config['ucet'] .' ['. $email_config['email'] .']';
            $zprava['prijato_dne'] = new DateTime();
            $zprava['doruceno_dne'] = new DateTime();
            $zprava['prijal_kdo'] = $user->id;
            $zprava['prijal_info'] = serialize($user->identity);

            $zprava['sha1_hash'] = sha1_file($source);

            $prilohy = array();
            if( isset($email_mess->attachments) ) {
                foreach ($email_mess->attachments as $ipr => $pr) {

                    $base_name = basename($pr['DataFile']);
                    //echo $base_name ."<br>";

                    $prilohy[] = array(
                        'name'=> $pr['FileName'],
                        'size'=> filesize($pr['DataFile']),
                        'mimetype'=> FileModel::mimeType($pr['FileName']),
                        'id'=>$base_name
                    );
                }
            }
            $zprava['prilohy'] = serialize($prilohy);

            $zprava['evidence'] = 'spisovka';
            $zprava['dokument_id'] = $data['id'];
            $zprava['stav'] = 0;
            $zprava['stav_info'] = '';
            //$zprava['source'] = $z;
            //unset($mess->source);
            //$zprava['source'] = $mess;
            $zprava['file_id'] = null;

                        if ( $epod_id = $Epodatelna->insert($zprava) ) {

                            $data_file = array(
                                'filename'=>'ep_email_'.$epod_id .'.eml',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Emailová zpráva z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            $mess_source = "";
                            if ( $fp = @fopen($source ,'rb') ) {
                                $mess_source = fread($fp, filesize($source) );
                                @fclose($fp);
                            }

                            if ( $file = $UploadFile->uploadEpodatelna($mess_source, $data_file) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id;
                                $Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'file_id'=>$file->id
                                            ),
                                        array(array('id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }
                        } else {
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }




            return array(
                'source'=>$source,
                'epodatelna_id'=>$epod_id,
                'email_signature'=>$email_mess->message_id,
                'zprava'=>$data['email_text']
            );
        //} else {
        //    return false;
        //}
    }

    protected function odeslatISDS($adresat, $data, $prilohy)
    {

        //$isds_debug = 1;
        try {


        $isds = new ISDS_Spisovka();
        if ( $ISDSBox = $isds->pripojit() ) {

            $dmEnvelope = array(
                "dbIDRecipient"=>$adresat->id_isds,
                "cislo_jednaci"=>$data['isds_cjednaci_odes'],
                "spisovy_znak"=>$data['isds_spis_odes'],
                "vase_cj"=>$data['isds_cjednaci_adres'],
                "vase_sznak"=>$data['isds_spis_adres'],
                "k_rukam"=>$data['isds_dvr'],
                "anotace"=>$data['isds_predmet'],
                //"zmocneni_law"=>$_POST['dok_zmo_law'],
                //"zmocneni_year"=>$_POST['dok_zmo_year'],
                //"zmocneni_sect"=>$_POST['dok_zmo_sect'],
                //"zmocneni_par"=>$_POST['dok_zmo_par'],
                //"zmocneni_point"=>$_POST['dok_zmo_point'],
                "do_vlastnich"=>($data['isds_dvr']==true)?1:0,
                "doruceni_fikci"=>($data['isds_fikce']==true)?0:1
            );

            if ( $id_mess = $isds->odeslatZpravu($dmEnvelope, $prilohy) ) {

                sleep(3);
                $odchozi_zpravy = $isds->seznamOdeslanychZprav( time()-3600 , time() );
                //Debug::dump($odchozi_zpravy);
                $mess = null;
                if ( count($odchozi_zpravy)>0 ) {
                    foreach ($odchozi_zpravy as $oz) {
                        if ( $oz->dmID == $id_mess ) {
                            $mess = $oz;
                            break;
                        }
                    }
                }
                if ( is_null($mess) ) {
                    return false;
                }

                $popis  = '';
                $popis .= "ID datové zprávy    : ". $mess->dmID ."\n";// = 342682
                $popis .= "Věc, předmět zprávy : ". $mess->dmAnnotation ."\n";//  = Vaše datová zpráva byla přijata
                $popis .= "\n";
                $popis .= "Číslo jednací odeslatele   : ". $mess->dmSenderRefNumber ."\n";//  = AB-44656
                $popis .= "Spisová značka odesílatele : ". $mess->dmSenderIdent ."\n";//  = ZN-161
                $popis .= "Číslo jednací příjemce     : ". $mess->dmRecipientRefNumber ."\n";//  = KAV-34/06-ŘKAV/2010
                $popis .= "Spisová značka příjemce    : ". $mess->dmRecipientIdent ."\n";//  = 0.06.00
                $popis .= "\n";
                $popis .= "Do vlastních rukou? : ". (!empty($mess->dmPersonalDelivery)?"ano":"ne") ."\n";//  =
                $popis .= "Doručeno fikcí?     : ". (!empty($mess->dmAllowSubstDelivery)?"ano":"ne") ."\n";//  =
                $popis .= "Zpráva určena pro   : ". $mess->dmToHands ."\n";//  =
                $popis .= "\n";
                $popis .= "Odesílatel:\n";
                $popis .= "            ". $mess->dbIDSender ."\n";//  = hjyaavk
                $popis .= "            ". $mess->dmSender ."\n";//  = Město Milotice
                $popis .= "            ". $mess->dmSenderAddress ."\n";//  = Kovářská 14/1, 37612 Milotice, CZ
                $popis .= "            ". $mess->dmSenderType ." - ". ISDS_Spisovka::typDS($mess->dmSenderType) ."\n";//  = 10
                $popis .= "            org.jednotka: ". $mess->dmSenderOrgUnit ." [". $mess->dmSenderOrgUnitNum ."]\n";//  =
                $popis .= "\n";
                $popis .= "Příjemce:\n";
                $popis .= "            ". $mess->dbIDRecipient ."\n";//  = pksakua
                $popis .= "            ". $mess->dmRecipient ."\n";//  = Společnost pro výzkum a podporu OpenSource
                $popis .= "            ". $mess->dmRecipientAddress ."\n";//  = 40501 Děčín, CZ
                        //$popis .= "Je příjemce ne-OVM povýšený na OVM: ". $mess->dmDm->dmAmbiguousRecipient ."\n";//  =
                $popis .= "            org.jednotka: ". $mess->dmRecipientOrgUnit ." [". $mess->dmRecipientOrgUnitNum ."]\n";//  =
                $popis .= "\n";
                $popis .= "Status: ". $mess->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) ."\n";
                $dt_dodani = strtotime($mess->dmDeliveryTime);
                $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                $popis .= "Datum a čas dodání   : ". date("j.n.Y G:i:s",$dt_dodani) ." (". $mess->dmDeliveryTime .")\n";//  =
                if ( $dt_doruceni == 0) {
                    $popis .= "Datum a čas doručení : (příjemce zprávu zatím nepřijal)\n";//  =    
                } else {
                    $popis .= "Datum a čas doručení : ". date("j.n.Y G:i:s",$dt_doruceni) ." (". $mess->dmAcceptanceTime .")\n";//  =                    
                }
                $popis .= "Přiblížná velikost všech příloh : ". $mess->dmAttachmentSize ."kB\n";//  =


                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleLaw ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleYear ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleSect ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePar ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePoint ."\n";//  =

                // Do epodatelny
                $storage_conf = Environment::getConfig('storage');
                eval("\$UploadFile = new ".$storage_conf->type."();");

                $Epodatelna = new Epodatelna();
                $config = $isds->getConfig();
                $user = Environment::getUser()->getIdentity();

                $zprava = array();
                $zprava['epodatelna_typ'] = 1;
                $zprava['poradi'] = $Epodatelna->getMax(1);
                $zprava['rok'] = date('Y');
                $zprava['isds_signature'] = $mess->dmID;
                $zprava['predmet'] = $mess->dmAnnotation;
                $zprava['popis'] = $popis;
                $zprava['odesilatel'] = $mess->dmRecipient .', '. $mess->dmRecipientAddress;
                $zprava['odesilatel_id'] = $adresat->id;
                $zprava['adresat'] = $config['ucet'] .' ['. $config['idbox'] .']';
                $zprava['prijato_dne'] = new DateTime();

                $zprava['doruceno_dne'] = new DateTime($mess->dmAcceptanceTime);

                $zprava['prijal_kdo'] = $user->id;
                $zprava['prijal_info'] = serialize($user->identity);

                $zprava['sha1_hash'] = '';

                $aprilohy = array();
                if ( count($prilohy)>0 ) {
                    foreach( $prilohy as $index => $file ) {
                        $aprilohy[] = array(
                                    'name'=>$file->real_name,
                                    'size'=>$file->size,
                                    'mimetype'=> $file->mime_type,
                                    'id'=>$index
                        );
                    }
                }
                $zprava['prilohy'] = serialize($aprilohy);

                $zprava['evidence'] = 'spisovka';
                $zprava['dokument_id'] = $data['id'];
                $zprava['stav'] = 0;
                $zprava['stav_info'] = '';

                        //print_r($zprava);
                        //exit;

                        if ( $epod_id = $Epodatelna->insert($zprava) ) {

                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.zfo',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Podepsaný originál ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            $signedmess = $isds->SignedSentMessageDownload($id_mess);

                            if ( $file_o = $UploadFile->uploadEpodatelna($signedmess, $data) ) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.bsr',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>' Byte-stream reprezentace ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            if ( $file = $UploadFile->uploadEpodatelna(serialize($mess), $data) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id ."-". $file_o->id;
                                $Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'file_id'=>$file->id ."-". $file_o->id
                                            ),
                                        array(array('id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }

                        } else {
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }
                
                return array(
                    'source'=>$mess,
                    'epodatelna_id'=>$epod_id,
                    'isds_signature' => $zprava,
                    'zprava'=>$popis
                );
            } else {
                return false;
            }
        } else {
            return false;
        }

        } catch (Exception $e) {
            $this->flashMessage('Chyba ISDS: '. $e->getMessage(),'warning_ext');
        }
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        
        $cookie_hledat = $this->getHttpRequest()->getCookie('s3_hledat');
        $s3_hledat = unserialize($cookie_hledat);
        if ( is_array($s3_hledat) ) {
            $controlPrototype = $form['dotaz']->getControlPrototype();
            $controlPrototype->style(array('background-color' => '#ccffcc','border'=>'1px #c0c0c0 solid'));
            $controlPrototype->title = "Aplikováno pokročilé vyhledávání. Pro detail klikněte na odkaz \"Pokročilé vyhledávání\". Zadáním hodnoty do tohoto pole, se pokročilé vyhledávání zruší a aplikuje se rychlé vyhledávání.";  
        } else if ( !empty($hledat) ) {
            $controlPrototype = $form['dotaz']->getControlPrototype();
            //$controlPrototype->style(array('background-color' => '#ccffcc','border'=>'1px #c0c0c0 solid'));
            $controlPrototype->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";  
        } else {
            $form['dotaz']->getControlPrototype()->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";  
        }
        
        
        

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

        //$this->redirect('this', array('hledat'=>$data['dotaz']));
        $this->redirect(':Spisovka:Dokumenty:default',array('hledat'=>$data['dotaz']));

    }

    protected function createComponentFiltrForm()
    {

        //$args = $Dokument->filtr('moje');
        //$args = $Dokument->filtr('predane');
        //$args = $Dokument->filtr('pracoval');
        //$args = $Dokument->filtr('moje_nove');
        //$args = $Dokument->filtr('vsichni_nove');
        //$args = $Dokument->filtr('moje_vyrizuje');
        //$args = $Dokument->filtr('vsichni_vyrizuji');

        if ( Environment::getUser()->isAllowed(null, 'is_vedouci') ) {
            $filtr =  !is_null($this->filtr)?$this->filtr:'moje';
            $select = array(
                'Vlastní' => array(
                    'moje'=>'Přidělené',
                    'predane'=>'K převzetí',
                    'moje_nove'=>'Nové / nepředané',
                    'moje_vyrizuje'=>'K vyřízení',
                    'moje_vyrizene'=>'Vyřízené',
                    'pracoval'=>'Na kterých jsem kdy pracoval',
                ),
                'Společné' => array(
                    'vsichni_nove'=>'Všechny nepředané',
                    'vsichni_vyrizuji'=>'Všechny k vyřízení',
                    'vsichni_vyrizene'=>'Všechny vyřízené',
                    'vse'=>'Všechny',
                    'org'=>'Všechny včetně podřízených',
                ),
                
                
            );
        } else {
            $filtr =  !is_null($this->filtr)?$this->filtr:'moje';
            $select = array(
                'Vlastní' => array(
                    'moje'=>'Přidělené',
                    'predane'=>'K převzetí',
                    'moje_nove'=>'Nové / nepředané',
                    'moje_vyrizuje'=>'K vyřízení',
                    'moje_vyrizene'=>'Vyřízené',
                    'pracoval'=>'na kterých jsem kdy pracoval',
                ),
                'Společné' => array(
                    /*'vsichni_nove'=>'Všechny nové dokumenty, které nebyly ještě předány',
                    'vsichni_vyrizuji'=>'Všechny dokumenty, které se vyřizují',
                    'vsichni_vyrizene'=>'Všechny dokumenty, které jsou vyřízené',*/
                    'vse'=>'Všechny'
                )
            );
        }

        $filtr_bezvyrizenych =  !is_null($this->filtr_bezvyrizenych)?$this->filtr_bezvyrizenych:false;

        $form = new AppForm();
        $form->addSelect('filtr', 'Filtr:', $select)
                ->setValue($filtr)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        $form->addCheckbox('bez_vyrizenych','Nezobrazovat vyřízené nebo archivované dokumenty')
                ->setValue($filtr_bezvyrizenych)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        $form->addSubmit('go_filtr', 'Filtrovat')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'filtrClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function filtrClicked(SubmitButton $button)
    {
        $form_data = $button->getForm()->getValues();

        $data = array('filtr'=>$form_data['filtr'],'bez_vyrizenych'=>$form_data['bez_vyrizenych']);

        $this->getHttpResponse()->setCookie('s3_filtr', serialize($data), strtotime('90 day'));

        //$this->forward('this', array('filtr'=>$data) );
        $this->forward(':Spisovka:Dokumenty:default', array('filtr'=>$data) );

    }

    protected function createComponentSeraditForm()
    {

        $select = array(
            'stav'=>'stavu dokumentu (sestupně)',
            'stav_desc'=>'stavu dokumentu (vzestupně)',
            'cj'=>'čísla jednacího (sestupně)',
            'cj_desc'=>'čísla jednacího (vzestupně)',
            'jid'=>'JID (sestupně)',
            'jid_desc'=>'JID (vzestupně)',
            'dvzniku'=>'data přijetí/vzniku (sestupně)',
            'dvzniku_desc'=>'data přijetí/vzniku (vzestupně)',
            'vec'=>'věci (sestupně)',
            'vec_desc'=>'věci (vzestupně)',
            'prideleno'=>'přidělené osoby (sestupně)',
            'prideleno_desc'=>'přidělené osoby (vzestupně)',
        );

        $seradit =  !is_null($this->seradit)?$this->seradit:null;
        
        $form = new AppForm();
        $form->addSelect('seradit', 'Seřadit podle:', $select)
                ->setValue($seradit)
                ->getControlPrototype()->onchange("return document.forms['frm-seraditForm'].submit();");
        $form->addSubmit('go_seradit', 'Seřadit')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'seraditClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function seraditClicked(SubmitButton $button)
    {
        $form_data = $button->getForm()->getValues();
        $this->getHttpResponse()->setCookie('s3_seradit', $form_data['seradit'], strtotime('90 day'));
        $this->forward(':Spisovka:Dokumenty:default', array('seradit'=>$form_data['seradit']) );
    }
    
    
}

