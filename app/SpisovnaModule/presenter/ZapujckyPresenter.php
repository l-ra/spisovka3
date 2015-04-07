<?php

class Spisovna_ZapujckyPresenter extends BasePresenter
{
    
    private $filtr;
    private $hledat;
    private $typ_evidence = null;
    private $oddelovac_poradi = null;
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
        
        if ($this->pdf_output == 1) {
            
            ob_start();
            $response->send();
            $content = ob_get_clean();
            if ($content) {
                
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
                $mpdf->SetAuthor(Nette\Environment::getUser()->getIdentity()->display_name);
                $mpdf->SetTitle('Spisová služba - Zápůjčky');                
                
                $mpdf->defaultheaderfontsize = 10;	/* in pts */
                $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                $mpdf->defaultfooterfontsize = 9;	/* in pts */
                $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                $mpdf->SetHeader('Zápůjčky||'.$this->template->Urad->nazev);
                $mpdf->SetFooter("{DATE j.n.Y}/".Nette\Environment::getUser()->getIdentity()->display_name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                
                
                $mpdf->WriteHTML($content);
                $mpdf->Output('zapujcky.pdf', 'I');
         
            }
        }
        
    }    
    
    
    public function renderDefault()
    {
        
        $post = $this->getRequest()->getPost();
        if ( isset($post['hromadna_submit']) ) {
            $this->actionAkce($post);
        }        
        
        $filtr = $this->getParameter('filtr');
        $hledat = $this->getParameter('hledat');
        $seradit = $this->getParameter('seradit');         
        
        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $Zapujcka = new Zapujcka();

        $this->template->no_items = 1; // indikator pri nenalezeni dokumentu
        if ( isset($filtr) ) {
            // zjisten filtr
            $args = $Zapujcka->filtr($filtr['filtr']);
            $this->filtr = $filtr['filtr'];
            $this->template->no_items = 2; // indikator pri nenalezeni zapujcky po filtraci
        } else {
            // filtr nezjisten - pouzijeme default
            $cookie_filtr = $this->getHttpRequest()->getCookie('s3_zapujcka_filtr');
            if ( $cookie_filtr ) {
                // zjisten filtr v cookie, tak vezmeme z nej
                $filtr = unserialize($cookie_filtr);
                $args = $Zapujcka->filtr($filtr['filtr']);
                $this->filtr = $filtr['filtr'];
                $this->template->no_items = 2; // indikator pri nenalezeni zapujcky po filtraci
            } else {
                $args = null;// $Zapujcka->filtr('');
                $this->filtr = 'aktualni';
            }
        }        

        if ( isset($hledat) ) {
            if (is_array($hledat) ) {
                // podrobne hledani = array
                $args = $hledat;
                $this->template->no_items = 4; // indikator pri nenalezeni zapujcky pri pokorčilem hledani
            } else {
                // rychle hledani = string
                $args = $Zapujcka->hledat($hledat);
                $this->hledat = $hledat;
                $this->template->no_items = 3; // indikator pri nenalezeni zypujcky pri hledani
            }
        }

        if ( isset($seradit) ) {
            $Zapujcka->seradit($args, $seradit);
        }
        $this->template->seradit = $seradit;        
        
        if ( Acl::isInRole('spisovna') || Nette\Environment::getUser()->isInRole('superadmin') ) {
            $this->template->akce_select = array(
                'vratit' => 'Vrátit vybrané zápůjčky',
                'schvalit' => 'Schválit vybrané zápůjčky',
                'odmitnout' => 'Odmítnout vybrané zápůjčky'
            );
        } else {
            $args = $Zapujcka->osobni($args);
            $this->template->akce_select = array(
                'vratit' => 'Vrátit vybrané zápůjčky'
            );
        }
        
        $result = $Zapujcka->seznam($args);
        $paginator->itemCount = count($result);
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
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

        $this->template->seznam = $seznam;
        $this->template->filtrForm = $this['filtrForm'];

    }
    
    public function actionDetail()
    {

        $Zapujcka = new Zapujcka();

        // Nacteni parametru
        $zapujcka_id = $this->getParameter('id',null);

        $this->template->Zapujcka = null;
        $zapujcka = $Zapujcka->getInfo($zapujcka_id);
        if ( $zapujcka ) {
            $this->template->Opravnen_schvalit_zapujcku = 
                Acl::isInRole('spisovna') || Nette\Environment::getUser()->isInRole('superadmin');
                
            $this->template->Zapujcka = $zapujcka;

        } else {
            // zapujcka neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
        
    }

    public function actionAkce($data)
    {

        //echo "<pre>"; print_r($data); echo "</pre>"; exit;

        if ( isset($data['hromadna_akce']) ) {
            $Zapujcka = new Zapujcka();
            $user = Nette\Environment::getUser();
            switch ($data['hromadna_akce']) {
                /* Schvaleni vybranych zapujcek  */
                case 'schvalit':
                    if ( isset($data['zapujcka_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['zapujcka_vyber'] as $zapujcka_id ) {
                            $stav = $Zapujcka->schvalit($zapujcka_id);
                            if ( $stav ) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste schválil '.$count_ok.' zápůjček.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' zápůjček se nepodařilo schválit!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect(':Spisovna:Zapujcky:default');
                        }
                    }
                    break;
                /* Vraceni vybranych zapujcek  */
                case 'vratit':
                    if ( isset($data['zapujcka_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        $dnes = new DateTime();
                        foreach ( $data['zapujcka_vyber'] as $zapujcka_id ) {
                            $stav = $Zapujcka->vraceno($zapujcka_id, $dnes);
                            if ( $stav ) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste vrátil '.$count_ok.' dokumentů k zapůjčení.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' dokumentů k zapůjčení se nepodařilo vrátit!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect(':Spisovna:Zapujcky:default');
                        }
                    }
                    break;
                /* Odmitnuti vybranych zapujcek  */
                case 'odmitnout':
                    if ( isset($data['zapujcka_vyber']) ) {
                        $count_ok = $count_failed = 0;
                        foreach ( $data['zapujcka_vyber'] as $zapujcka_id ) {
                            $stav = $Zapujcka->odmitnout($zapujcka_id);
                            if ( $stav ) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ( $count_ok > 0 ) {
                            $this->flashMessage('Úspěšně jste odmítnul '.$count_ok.' zápůjček.');
                        }
                        if ( $count_failed > 0 ) {
                            $this->flashMessage($count_failed.' zápůjček se nepodařilo odmítnout!','warning');
                        }
                        if ( $count_ok > 0 && $count_failed > 0 ) {
                            $this->redirect(':Spisovna:Zapujcky:default');
                        }
                    }
                    break;
                    
                default:
                    break;
            }
        }
    }
    
    public function actionSchvalit()
    {
        
        $zapujcka_id = $this->getParameter('id');
        if ( !empty($zapujcka_id) && is_numeric($zapujcka_id) ) {
            if ( Acl::isInRole('spisovna') || Nette\Environment::getUser()->isInRole('superadmin') ) {
                
                $Zapujcka = new Zapujcka();
                if ( $Zapujcka->schvalit($zapujcka_id) ) {
                    $this->flashMessage('Zápůjčka byla schválena.');
                } else {
                    $this->flashMessage('Zápůjčku se nepodařilo schválit!.', 'error');
                }
            } else {
                $this->flashMessage('Nemáte oprávnění schválit zápůjčku!.', 'warning');
            }
        } else {
            $this->flashMessage('Zápůjčku nelze schválit! Neplatná zápůjčka.', 'error');
        }
        $this->redirect(':Spisovna:Zapujcky:default');
        
    }
    
    public function actionOdmitnout()
    {
        
        $zapujcka_id = $this->getParameter('id');
        if ( !empty($zapujcka_id) && is_numeric($zapujcka_id) ) {
            if ( Acl::isInRole('spisovna') || Nette\Environment::getUser()->isInRole('superadmin') ) {
                
                $Zapujcka = new Zapujcka();
                if ( $Zapujcka->odmitnout($zapujcka_id) ) {
                    $this->flashMessage('Zápůjčka byla odmítnuta.');
                } else {
                    $this->flashMessage('Zápůjčku se nepodařilo odmítnout!.', 'error');
                }
            } else {
                $this->flashMessage('Nemáte oprávnění odmítnout zápůjčku!.', 'warning');
            }
        } else {
            $this->flashMessage('Zápůjčku nelze odmítnout! Neplatná zápůjčka.', 'error');
        }
        $this->redirect(':Spisovna:Zapujcky:default');
        
    } 
    
    public function actionVratit()
    {
        
        $zapujcka_id = $this->getParameter('id');
        if ( !empty($zapujcka_id) && is_numeric($zapujcka_id) ) {
            $Zapujcka = new Zapujcka();
            if ( $Zapujcka->vraceno($zapujcka_id, new Datetime()) ) {
                $this->flashMessage('Zápůjčka byla vrácena.');
            } else {
                $this->flashMessage('Zápůjčku se nepodařilo vrátit!.', 'error');
            }
        } else {
            $this->flashMessage('Zápůjčku nelze vrátit! Neplatná zápůjčka.', 'error');
        }
        $this->redirect(':Spisovna:Zapujcky:default');
        
    }      
    
    
    public function renderNova()
    {
        $this->template->novyForm = $this['novyForm'];
    }    
    
    protected function createComponentNovyForm()
    {
        
        $form = new Nette\Application\UI\Form();
        
        $dokument_id = $this->getParameter('dokument_id');
        $user_id = $this->getParameter('user_id');
        
        if ( $dokument_id ) {
            $Dokument = new Dokument();
            $Zapujcka = new Zapujcka();
            $zapujcky = $Zapujcka->aktivniSeznam();             
            
            if ( isset($zapujcky[$dokument_id]) ) {
                $dokument_text = '';
                $dokument_id = null;                
                $this->flashMessage('Vybraný dokument nelze zapůjčit! Je již zapůjčen jiným zaměstnancem.','warning');
            } else {
                $dokument_info = $Dokument->getInfo($dokument_id);
                if ( $dokument_info->stav_dokumentu > 7 ) {
                    $dokument_text = '';
                    $dokument_id = null;
                    $this->flashMessage('Vybraný dokument nelze zapůjčit! Dokument prochází nebo již prošel skartačním řízením a je tudíž nedostupný.','warning');
                } else {
                    if ( $this->typ_evidence != 'priorace' ) {
                        $dokument_text = $dokument_info->cislo_jednaci ."". $this->oddelovac_poradi ."". $dokument_info->poradi ." - ". $dokument_info->nazev;
                    } else {
                        $dokument_text = $dokument_info->cislo_jednaci ." - ". $dokument_info->nazev;
                    }
                }
            }
        } else {
            $dokument_text = "";
        }
        
        if ( $user_id ) {
            $user_info = UserModel::getIdentity($user_id);
            $osoba = Osoba::displayName($user_info);
        } else {
            $user = Nette\Environment::getUser();
            if ( Acl::isInRole('spisovna') || $user->isInRole('superadmin') ) {
                $osoba = "";
                $user_id = null;
                $form->addHidden('is_in_role')->setValue(1);
            } else {
                $osoba = Osoba::displayName($user->getIdentity()->identity);
                $user_id = $user->getIdentity()->id;
            }
        }
        
        $form->addText('dokument_text', 'Zapůjčený dokument:',80)
                ->setValue($dokument_text);
        $form->addText('dokument_id')
                ->setValue($dokument_id)
                ->setRequired('Musí být vybrán dokument k zapůjčení!');
        
        
        $form->addText('user_text', 'Zapůjčeno komu:',80)
                ->setValue($osoba);
        $form->addText('user_id')
                ->setValue($user_id)
                ->setRequired('Musí být vybrána osoba, které se bude zapůjčovat!');
        
        $form->addTextArea('duvod', "Důvod zapůjčení:", 80, 5);
        
        $datum_od = date('d.m.Y');
        $form->addDatePicker('date_od', 'Datum výpůjčky:', 10)
                ->setValue($datum_od)
                ->setRequired('Datum výpůjčky musí být vyplněné!');
        $datum_do = date('d.m.Y');
        $form->addDatePicker('date_do', 'Datum vrácení:', 10)
                ->setRequired('Datum vrácení musí být vyplněné! Zadejte alespoň předpokládané datum vrácení.')
                ->forbidPastDates()
                ->addRule(Nette\Forms\Form::VALID, 'Datum vrácení nemůže být v minulosti.');

        
        $submit = $form->addSubmit('novy', 'Vytvořit zápůjčku');
        $submit->onClick[] = array($this, 'vytvoritClicked');
        $submit->onInvalidClick[] = array($this, 'vytvoritClickedChyba');
                 
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoSeznamClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;           

    }
    
    public function vytvoritClickedChyba(Nette\Forms\Controls\SubmitButton $button)
    {
        $errors = $button->getForm()->getErrors();
        foreach($errors as $error)
            $this->flashMessage($error, 'warning');
        // Neni treba provadet redirect, formular se vykresli nyni znovu
    }
    
    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        
        //Nette\Diagnostics\Debugger::dump($data);
        //Nette\Diagnostics\Debugger::dump($this->getHttpRequest()->getPost());
        //exit;
        
        $Zapujcka = new Zapujcka();

        try {

            $zapujcka_id = $Zapujcka->ulozit($data);

            $this->flashMessage('Zápůjčka byla vytvořena.');
            $this->redirect(':Spisovna:Zapujcky:default');
        } catch (DibiException $e) {
            $this->flashMessage('Zápůjčku se nepodařilo vytvořit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }
    
    
    
    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $zapujcka_id = $data['id'];
        $this->redirect(':Spisovna:Zapujcky:detail',array('id'=>$zapujcka_id));
    }

    public function stornoSeznamClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $this->redirect(':Spisovna:Zapujcky:default');
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";

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

    protected function createComponentFiltrForm()
    {

        if ( Acl::isInRole('spisovna') || Nette\Environment::getUser()->isInRole('superadmin') ) {
            $filtr =  !is_null($this->filtr)?$this->filtr:'vse';
            $select = array(
                'aktualni'=>'Zobrazit aktuální zápůjčky',
                'zapujcene'=>'Zobrazit zapůjčené zápůjčky',
                'ke_schvaleni'=>'Zobrazit zápůjčky ke schválení',
                'vracene'=>'Zobrazit historii zápůjček (již vracené)',
                'odmitnute'=>'Zobrazit odmítnuté zápůjčky',
                'vse'=>'Zobrazit vše',
            );
            $this->template->zobrazit_filtr = 1;
        } else {
            $filtr =  !is_null($this->filtr)?$this->filtr:'';
            $select = array(
                ''=>'Zobrazit vše',
            );
            $this->template->zobrazit_filtr = 0;
        }

        $form = new Nette\Application\UI\Form();
        $form->addSelect('filtr', 'Filtr:', $select)
                // ->setValue($filtr)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        if ($this->template->zobrazit_filtr)
            $form['filtr']->setValue($filtr);
        
        $form->addSubmit('go_filtr', 'Filtrovat');

        $form->onSuccess[] = array($this, 'filtrClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function filtrClicked(Nette\Application\UI\Form $form, $form_data)
    {
        $data = array('filtr'=>$form_data['filtr']);
        $this->getHttpResponse()->setCookie('s3_zapujcka_filtr', serialize($data), strtotime('90 day'));
        $this->forward(':Spisovna:Zapujcky:default', array('filtr'=>$data) );
    }
    
    
}