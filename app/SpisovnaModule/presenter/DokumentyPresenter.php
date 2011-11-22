<?php

class Spisovna_DokumentyPresenter extends BasePresenter
{

    private $filtr;
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
        $this->template->Typ_evidence = $this->typ_evidence;

        parent::startup();
    }

    protected function shutdown($response) {
        
        if ($this->pdf_output == 1 || $this->pdf_output == 2) {
            
            function handlePDFError($errno, $errstr, $errfile, $errline, array $errcontext)
            {
                if (0 === error_reporting()) {
                    return;
                }
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
                    $mpdf->SetTitle('Spisová služba - Spisovna - Tisk');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader($this->template->title .'||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                    $mpdf->Output('spisovna.pdf', 'I');
                }
            }
            
            } catch (Exception $e) {
                $location = str_replace("pdfprint=1","",Environment::getHttpRequest()->getUri());

                echo "<h1>Nelze vygenerovat PDF výstup.</h1>";
                echo "<p>Generovaný obsah obsahuje příliš mnoho dat, které není možné zpracovat.<br />Zkuste omezit celkový počet dokumentů.</p>";
                echo "<p><a href=".$location.">Přejít na předchozí stránku.</a></p>";
                exit;
            }            
        }
        
    }    
    
    
    protected function seznam($typ = 0, $filtr = null, $hledat = null, $seradit = null)
    {

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $Dokument = new Dokument();

        $this->template->no_items = 1; // indikator pri nenalezeni dokumentu
        if ( isset($filtr) ) {
            // zjisten filtr
            $args_f = $Dokument->filtr('spisovna', $filtr['filtr']);
            $this->filtr = $filtr['filtr'];
            $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci
        } else {
            // filtr nezjisten - pouzijeme default
            $cookie_filtr = $this->getHttpRequest()->getCookie('s3_spisovna_filtr');
            if ( $cookie_filtr ) {
                // zjisten filtr v cookie, tak vezmeme z nej
                $filtr = unserialize($cookie_filtr);
                $args_f = $Dokument->filtr('spisovna', $filtr['filtr']);
                $this->filtr = $filtr['filtr'];
                $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci
            } else {
                $args_f = null;// $Dokument->filtr('');
                $this->filtr = '';
            }

        }

        if ( isset($hledat) ) {
            if (is_array($hledat) ) {
                // podrobne hledani = array
                $args_h = $Dokument->filtr(null,$hledat);
                $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
            } else {
                // rychle hledani = string
                $args_h = $Dokument->hledat($hledat);
                $this->hledat = $hledat;
                $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
            }
            $this->getHttpResponse()->setCookie('s3_spisovna_hledat', serialize($hledat), strtotime('90 day'));
        } else {
            $cookie_hledat = $this->getHttpRequest()->getCookie('s3_spisovna_hledat');            
            if ( $cookie_hledat ) {
                // zjisteno hladaci filtr v cookie, tak vezmeme z nej
                $hledat = unserialize($cookie_hledat);
                if (is_array($hledat) ) {
                    // podrobne hledani = array
                    $args_h = $Dokument->filtr(null,$hledat);
                    $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
                } else {
                    // rychle hledani = string
                    $args_h = $Dokument->hledat($hledat);
                    $this->hledat = $hledat;
                    $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
                }
            }
        }
        $this->template->s3_hledat = $hledat;        

        $args = $Dokument->spojitAgrs(@$args_f, @$args_h);
        
        if ( isset($seradit) ) {
            $Dokument->seradit($args, $seradit);
            $this->getHttpResponse()->setCookie('s3_spisovna_seradit', $seradit, strtotime('90 day'));
        } else {
            $seradit = $this->getHttpRequest()->getCookie('s3_spisovna_seradit');            
            if ( $seradit ) {
                // zjisteno razeni v cookie, tak vezmeme z nej
                $Dokument->seradit($args, $seradit);
            }           
        }
        $this->seradit = $seradit;
        $this->template->s3_seradit = $seradit;        
        $this->template->seradit = $seradit;

        if ( $typ == 1 ) {
            // prijem
            $args = $Dokument->spisovna_prijem($args);
        } else if ( $typ == 2 ) {
            // ke skartaci
            $args = $Dokument->spisovna_keskartaci($args);
        } else if ( $typ == 3 ) {
            // skartacni rizeni
            $args = $Dokument->spisovna_skartace($args);
        } else {
            // seznam
            $args = $Dokument->spisovna($args);
        }
        
        if ( $typ == 2 ) {
            $result = $Dokument->seznamKeSkartaci($args);
        } else {
            $result = $Dokument->seznam($args);
        }
        
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
            //$SpisoveZnaky = new SpisovyZnak();
            //$dataplus['spisovy_znak'] = array( '0'=> null );//$DokOdeslani->odeslaneZpravy($dokument_ids);


            foreach ($seznam as $index => $row) {
                $dok = $Dokument->getInfo($row->id,null, $dataplus);
                $seznam[$index] = $dok;
            }
        }

        $this->template->seznam = $seznam;

        $this->template->filtrForm = $this['filtrForm'];
        $this->template->seraditForm = $this['seraditForm'];

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $this->template->zapujcky = $Zapujcka->aktivniSeznam();
        

    }

    public function renderDefault($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        if ( Acl::isInRole('skartacni_dohled') || Environment::getUser()->isInRole('superadmin') ) {
            $this->template->akce_select = array(
                'zapujcka'=>'Zápůjčka'
            );            
        } else {
            $this->template->akce_select = array(
                'zapujcka'=>'Zápůjčka'
            );              
        }

        $this->template->title = "Seznam dokumentů ve spisovně";
        $this->seznam(0, $filtr, $hledat, $seradit);
    }

    public function renderPrijem($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'prevzit_spisovna'=>'převzetí seznamu dokumentů do spisovny'
        );
        $this->template->title = "Seznam dokumentů pro příjem do spisovny";
        $this->template->is_prijem = 1;
        $this->setView('default');
        $this->seznam(1, $filtr, $hledat, $seradit);
    }

    public function renderKeskartaciseznam($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'ke_skartaci'=>'předat do skartačního řízení'
        );
        $this->template->title = "Seznam dokumentů určených ke skartaci";
        $this->setView('default');
        $this->seznam(2, $filtr, $hledat, $seradit);
    }

    public function renderSkartace($filtr = null, $hledat = null, $seradit = null)
    {

        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'archivovat'=>'archivovat vybrané dokumenty',
            'skartovat'=>'skartovat vybrané dokumenty',
        );
        $this->template->title = "Seznam dokumentů ve skartačním řízení";
        $this->setView('default');
        $this->seznam(3, $filtr, $hledat, $seradit);
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

            // Zapujcka
            $Zapujcka = new Zapujcka();
            $this->template->Zapujcka = $Zapujcka->getDokument($dokument_id);
            
            $user = Environment::getUser();
            //Debug::dump($user);

            $user_id = $user->getIdentity()->id;

            $this->template->Pridelen = 0;
            $this->template->Predan = 0;
            $formUpravit = null;
            // Prideleny nebo predany uzivatel
            if ( Acl::isInRole('skartacni_dohled,superadmin') ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);                
            } else if ( @$dokument->prideleno->prideleno_id == $user_id ) {
                // prideleny
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( @$dokument->predano->prideleno_id == $user_id ) {
                // predany
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( empty($dokument->prideleno->prideleno_id)
                        && Orgjednotka::isInOrg(@$dokument->prideleno->orgjednotka_id, 'vedouci') ) {
                // prideleno organizacni jednotce
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( empty($dokument->predano->prideleno_id)
                        && Orgjednotka::isInOrg(@$dokument->predano->orgjednotka_id, 'vedouci') ) {
                // predano organizacni jednotce
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else {
                // byvaly muze aspon prohlednout
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 0;
                $formUpravit = null;
                if ( count($dokument->workflow)>0 ) {
                    foreach ($dokument->workflow as $wf) {
                        if ( ($wf->prideleno_id == $user_id) && ($wf->stav_osoby < 100 || $wf->stav_osoby !=0) ) {
                            $this->template->AccessView = 1;
                        }
                    }
                }
            }

            $this->template->Vyrizovani = 0;
            $this->template->Skartacni_dohled = 0;
            $this->template->Skartacni_komise = 0;
            // Dokument je ve skartacnim obodbi

            $datum_skartace = new DateTime($dokument->skartacni_rok);
            $datum_aktualni = new DateTime();
            $DateDiff = new DateDiff();
            $skartacni_rozdil = $DateDiff->diff($datum_skartace);
            
            if ( $skartacni_rozdil > 0 && $dokument->stav_dokumentu == 7
                    && (Acl::isInRole('skartacni_dohled') || $user->isInRole('superadmin')) ) {
                $this->template->AccessView = 1;
                $this->template->AccessEdit = 1;
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
            
            if ( $dokument->stav_dokumentu == 9 || $dokument->stav_dokumentu == 10 ) {
                $this->template->AccessEdit = 0;
                $zapujcka = new stdClass();
                $zapujcka->id = 1;
                $this->template->Zapujcka = $zapujcka;
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
        $this->template->vyrizovaniForm = $this['vyrizovaniForm'];
    }    
    
    public function actionAkce($data)
    {

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        if ( isset($data['hromadna_akce']) ) {
            $Workflow = new Workflow();
            $Dokument = new Dokument();
            $user = Environment::getUser();
            switch ($data['hromadna_akce']) {
                /* Prevzeti vybranych dokumentu */
                case 'prevzit_spisovna':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['dokument_vyber'] as $dokument_id ) {
                            $stav = $Workflow->pripojitDoSpisovny($dokument_id, 1);
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
                            $this->flashMessage('Úspěšně jste přijal '.$count_ok.' dokumentů do spisovny.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' dokumentů se nepodařilo příjmout do spisovny!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                case 'ke_skartaci':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        if ( Acl::isInRole('skartacni_dohled') || $user->isInRole('superadmin') ) {
                            foreach ( $data['dokument_vyber'] as $dokument_id ) {
                                if ( $Workflow->keskartaci($dokument_id, $user->getIdentity()->id) ) {
                                    //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                    $count_ok++;
                                } else {
                                    $count_failed++;
                                    //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
                                }
                            }
                            if ( $count_ok > 0 ) {
                                $this->flashMessage('Úspěšně jste předal '.$count_ok.' dokumentů do skartačního řízení.');
                            }
                            if ( $count_failed > 0 ) {
                                $this->flashMessage($count_failed.' dokumentů se nepodařilo předat do skartačního řízení!','warning');
                            }
                        } else {
                            $this->flashMessage('Nemáte oprávnění převádět dokumenty do skartačního řízení.','warning');
                            $count_failed++;
                        }

                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                case 'archivovat':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {
                            foreach ( $data['dokument_vyber'] as $dokument_id ) {
                                if ( $Workflow->archivovat($dokument_id, $user->getIdentity()->id) ) {
                                    //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                    $count_ok++;
                                } else {
                                    $count_failed++;
                                    //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
                                }
                            }
                            if ( $count_ok > 0 ) {
                                $this->flashMessage($count_ok.' dokumentů bylo úspěšně archivováno.');
                            }
                            if ( $count_failed > 0 ) {
                                $this->flashMessage($count_failed.' dokumentů se nepodařilo zařadit do archivu. Zkuste to znovu.','warning');
                            }
                        } else {
                            $this->flashMessage('Nemáte oprávnění rozhodovat o skartačním řízení.','warning');
                            $count_failed++;
                        }

                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                case 'skartovat':
                    if ( isset($data['dokument_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {
                            foreach ( $data['dokument_vyber'] as $dokument_id ) {
                                if ( $Workflow->skartovat($dokument_id, $user->getIdentity()->id) ) {
                                    //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                    $count_ok++;
                                } else {
                                    $count_failed++;
                                    //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
                                }
                            }
                            if ( $count_ok > 0 ) {
                                $this->flashMessage($count_ok.' dokumentů bylo úspěšně skartováno.');
                            }
                            if ( $count_failed > 0 ) {
                                $this->flashMessage($count_failed.' dokumentů se nepodařilo skartovat. Zkuste to znovu.','warning');
                            }
                        } else {
                            $this->flashMessage('Nemáte oprávnění rozhodovat o skartačním řízení.','warning');
                            $count_failed++;
                        }

                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect('this');
                        }
                    }
                    break;
                case 'zapujcka':
                    if ( isset($data['dokument_vyber']) ) {
                        $user = Environment::getUser();
                        foreach ( $data['dokument_vyber'] as $dokument_id ) {
                            
                            if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {
                                $this->redirect(':Spisovna:Zapujcky:nova',array('dokument_id'=>$dokument_id));
                            } else {
                                $this->redirect(':Spisovna:Zapujcky:nova',array('dokument_id'=>$dokument_id,'user_id'=>$user->getIdentity()->id));
                            }
                            
                            break;
                        }
                    }
                    break;                    
                default:
                    break;
            }


        }

    }


    public function renderKeskartaci()
    {

        $dokument_id = $this->getParam('id',null);
        $user = Environment::getUser();
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( Acl::isInRole('skartacni_dohled') || $user->isInRole('superadmin') ) {
            if ( $Workflow->keskartaci($dokument_id, $user_id, $orgjednotka_id) ) {
               $this->flashMessage('Dokument byl přidán do skartačního řízení.');
            } else {
               $this->flashMessage('Dokument se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění manipulovat s tímto dokumentem.','warning');
        }
        $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));

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
        $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));

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
        $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));

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
                $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 2 ) {
                $this->flashMessage('Chyba při stahování!','warning');
                $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 3 ) {
                $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout zmíněný soubor!','warning');
                $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
            }
        } else {
            $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout cizí soubor!','warning');
            $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
        }
        
    }

    public function renderHistorie()
    {

        $dokument_id = $this->getParam('id',null);

        $Log = new LogModel();
        $historie = $Log->historieDokumentu($dokument_id,1000);

        $this->template->historie = $historie;

    }

protected function createComponentVyrizovaniForm()
    {

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->select(2);
        $spousteci_udalost = $SpisovyZnak->spousteci_udalost(null, 1);
        $skar_znak = array('A'=>'A','S'=>'S','V'=>'V');

        $Dok = @$this->template->Dok;

        $form = new AppForm();
        $form->addHidden('id')
                ->setValue(@$Dok->id);

        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6)
                ->setValue(@$Dok->ulozeni_dokumentu);

        $form->addSelect('spisovy_znak_id', 'spisový znak:', $spisznak_seznam)
                ->setValue(@$Dok->spisovy_znak_id)
                ->controlPrototype->onchange("vybratSpisovyZnak();");        
        $form->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$Dok->skartacni_znak);
        $form->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$Dok->skartacni_lhuta);

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

        //Debug::dump($data); exit;

        $Dokument = new Dokument();

        $dok = $Dokument->getInfo($dokument_id);

        try {

            $dokument = $Dokument->ulozit($data,$dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upraven skartační režim.');

            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'"  byl upraven.');
            $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'" se nepodařilo upravit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
            $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
            //Debug::dump($e);
            //exit;
            //$this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        }

    }    
    
    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['id'];
        $this->redirect(':Spisovna:Dokumenty:detail',array('id'=>$dokument_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Spisovna:Dokumenty:default');
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);

        $cookie_hledat = $this->getHttpRequest()->getCookie('s3_spisovna_hledat');
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

        //$this->forward('this', array('hledat'=>$data['dotaz']));
        $this->redirect(':Spisovna:Dokumenty:'.$this->view,array('hledat'=>$data['dotaz']));

    }

    protected function createComponentFiltrForm()
    {

        if ( Acl::isInRole('skartacni_dohled') || Environment::getUser()->isInRole('superadmin') ) {
            // pracovnik spisovny
            $filtr =  !is_null($this->filtr)?$this->filtr:'stav_77';
            $select = array(
                'stav_77'=>'Zobrazit vše',
                'Podle stavu' => array(
                    'stav_6'=>'předáno do spisovny',
                    'stav_7'=>'ve spisovně (probíhá skartační lhůta)',
                    'stav_8'=>'ke skartaci (probíhá skartační řízení)',
                    'stav_9'=>'archivován',
                    'stav_10'=>'skartován',
                ),
                'Podle skartačního znaku' => array(
                    'skartacni_znak_A'=>'A',
                    'skartacni_znak_V'=>'V',
                    'skartacni_znak_S'=>'S',
                ),
                'Podle způsobu vyřízení' => Dokument::zpusobVyrizeni(null,4)
                
                
            );            
        } else {
            // ostatni
            $filtr =  !is_null($this->filtr)?$this->filtr:'stav_77';
            $select = array(
                'stav_77'=>'Zobrazit vše',
                'Podle skartačního znaku' => array(
                    'skartacni_znak_A'=>'A',
                    'skartacni_znak_V'=>'V',
                    'skartacni_znak_S'=>'S',
                ),
                'Podle způsobu vyřízení' => Dokument::zpusobVyrizeni(null,4)
                
                
            );            
        }

        $form = new AppForm();
        $form->addSelect('filtr', 'Filtr:', $select)
                ->setValue($filtr)
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
        $data = array('filtr'=>$form_data['filtr']);
        $this->getHttpResponse()->setCookie('s3_spisovna_filtr', serialize($data), strtotime('90 day'));
        
        
        
        $this->redirect(':Spisovna:Dokumenty:'.$this->view, array('filtr'=>$data) );
    }
    
    protected function createComponentSeraditForm()
    {

        $select = array(
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
            'skartacni_znak'=>'skartačního znaku (sestupně)',
            'skartacni_znak_desc'=>'skartačního znaku (vzestupně)',
            'spisovy_znak'=>'spisového znaku (sestupně)',
            'spisovy_znak_desc'=>'spisového znaku (vzestupně)',
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
        $this->getHttpResponse()->setCookie('s3_spisovna_seradit', $form_data['seradit'], strtotime('90 day'));
        $this->redirect(':Spisovna:Dokumenty:default', array('seradit'=>$form_data['seradit']) );
    }
    
    
    public function actionSeznamAjax()
    {
        
        $Dokument = new Dokument();
        
        $args = null;
        $seznam = array();

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $zapujcky = $Zapujcka->aktivniSeznam();        
        
        
        $term = $this->getParam('term');

        if ( !empty($term) ) {
            $args = $Dokument->hledat($term);
            $args = $Dokument->spisovna($args);            
            $result = $Dokument->seznam($args);
            $seznam_dok = $result->fetchAll();
        } else {
            $args = $Dokument->spisovna($args);            
            $result = $Dokument->seznam($args);
            $seznam_dok = $result->fetchAll();
        }

        if ( count($seznam_dok)>0 ) {
            foreach( $seznam_dok as $row ) {
                if ( isset($zapujcky[$row->id]) ) continue; // je zapujcen
                $dok = $Dokument->getBasicInfo($row->id);
                
                //if ( $dok->stav_dokumentu > 7 ) continue; // vyradime dokumenty po skartacnim rizeni
                
                $seznam[ ] = array(
                    "id"=> $dok->id,
                    "type" => 'item',
                    "value"=> '<strong>'.$dok->cislo_jednaci.'</strong> - '.$dok->nazev,
                    "nazev"=> $dok->cislo_jednaci ." - ". $dok->nazev
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }
    

}

