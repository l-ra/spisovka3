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
        if (isset($user_config->cislo_jednaci->typ_evidence)) {
            $this->typ_evidence = $user_config->cislo_jednaci->typ_evidence;
        } else {
            $this->typ_evidence = 'priorace';
        }
        if (isset($user_config->cislo_jednaci->oddelovac)) {
            $this->oddelovac_poradi = $user_config->cislo_jednaci->oddelovac;
        } else {
            $this->oddelovac_poradi = '/';
        }
        $this->template->Oddelovac_poradi = $this->oddelovac_poradi;
        $this->template->Typ_evidence = $this->typ_evidence;

        parent::startup();
    }

    protected function shutdown($response)
    {

        if ($this->pdf_output == 1) {

            ob_start();
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
            $content = ob_get_clean();
            if ($content) {

                $content = str_replace("<td", "<td valign='top'", $content);
                $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s', '', $content);
                $content = preg_replace('#<table id="table_top">.*?</table>#s', '', $content);

                $mpdf = new mPDF('iso-8859-2', 'A4', 9, 'Helvetica');

                $app_info = Nette\Environment::getVariable('app_info');
                $app_info = explode("#", $app_info);
                $app_name = (isset($app_info[2])) ? $app_info[2] : 'OSS Spisová služba v3';
                $mpdf->SetCreator($app_name);
                $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                $mpdf->SetTitle('Spisová služba - Zápůjčky');

                $mpdf->defaultheaderfontsize = 10; /* in pts */
                $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                $mpdf->defaultfooterfontsize = 9; /* in pts */
                $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */
                $mpdf->SetHeader('Zápůjčky||' . $this->template->Urad->nazev);
                $mpdf->SetFooter("{DATE j.n.Y}/" . $this->user->getIdentity()->display_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */



                $mpdf->WriteHTML($content);
                $mpdf->Output('zapujcky.pdf', 'I');
            }
        }
    }

    public function renderDefault()
    {

        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $filtr = $this->getParameter('filtr');
        $hledat = $this->getParameter('hledat');
        $seradit = $this->getParameter('seradit');

        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek) ? $user_config->nastaveni->pocet_polozek
                    : 20;

        $Zapujcka = new Zapujcka();

        $this->template->no_items = 1; // indikator pri nenalezeni dokumentu
        if (isset($filtr)) {
            // zjisten filtr
            $this->filtr = $filtr['filtr'];
            $this->template->no_items = 2; // indikator pri nenalezeni zapujcky po filtraci
        } else {
            // filtr nezjisten - pouzijeme default
            $cookie_filtr = $this->getHttpRequest()->getCookie('s3_zapujcka_filtr');
            if ($cookie_filtr) {
                // zjisten filtr v cookie, tak vezmeme z nej
                $filtr = unserialize($cookie_filtr);
                $this->filtr = $filtr['filtr'];
                $this->template->no_items = 2; // indikator pri nenalezeni zapujcky po filtraci
            } else {
                $this->filtr = 'vse';
            }
        }
        $args = $Zapujcka->filtr($this->filtr);

        if (isset($hledat)) {
            if (is_array($hledat)) {
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

        if (isset($seradit)) {
            $Zapujcka->seradit($args, $seradit);
        }
        $this->template->seradit = $seradit;

        if (Acl::isInRole('spisovna') || $this->user->isInRole('superadmin')) {
            $akce = ['vratit' => 'Vrátit dokumenty',
                'schvalit' => 'Schválit žádosti',
                'odmitnout' => 'Odmítnout žádosti'
            ];
            switch ($this->filtr) {
                case 'ke_schvaleni':
                    unset($akce['vratit']);
                    break;
                case 'zapujcene':
                    unset($akce['schvalit']);
                    unset($akce['odmitnout']);
                    break;

                case 'odmitnute':
                case 'vracene':
                    $akce = [];
                    break;
            }
                
            $this->template->akce_select = $akce;
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
        if ($tisk) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('print');
        } elseif ($pdf) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
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
        $zapujcka_id = $this->getParameter('id', null);

        $this->template->Zapujcka = null;
        $zapujcka = $Zapujcka->getInfo($zapujcka_id);
        if ($zapujcka) {
            $this->template->Opravnen_schvalit_zapujcku = Acl::isInRole('spisovna') || $this->user->isInRole('superadmin');

            $this->template->Zapujcka = $zapujcka;
        } else {
            // zapujcka neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
    }

    public function actionAkce($data)
    {
        if (isset($data['hromadna_akce'])) {
            $Zapujcka = new Zapujcka();
            switch ($data['hromadna_akce']) {
                /* Schvaleni vybranych zapujcek  */
                case 'schvalit':
                    if (isset($data['zapujcka_vyber'])) {
                        $count_ok = $count_failed = 0;
                        foreach ($data['zapujcka_vyber'] as $zapujcka_id) {
                            $stav = $Zapujcka->schvalit($zapujcka_id);
                            if ($stav) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage('Úspěšně jste schválil ' . $count_ok . ' zápůjček.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' zápůjček se nepodařilo schválit!',
                                    'warning');
                        }
                        if ($count_ok > 0 && $count_failed > 0) {
                            $this->redirect(':Spisovna:Zapujcky:default');
                        }
                    }
                    break;
                /* Vraceni vybranych zapujcek  */
                case 'vratit':
                    if (isset($data['zapujcka_vyber'])) {
                        $count_ok = $count_failed = 0;
                        $dnes = new DateTime();
                        foreach ($data['zapujcka_vyber'] as $zapujcka_id) {
                            $stav = $Zapujcka->vraceno($zapujcka_id, $dnes);
                            if ($stav) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage('Úspěšně jste vrátil ' . $count_ok . ' dokumentů k zapůjčení.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' dokumentů k zapůjčení se nepodařilo vrátit!',
                                    'warning');
                        }
                        if ($count_ok > 0 && $count_failed > 0) {
                            $this->redirect(':Spisovna:Zapujcky:default');
                        }
                    }
                    break;
                /* Odmitnuti vybranych zapujcek  */
                case 'odmitnout':
                    if (isset($data['zapujcka_vyber'])) {
                        $count_ok = $count_failed = 0;
                        foreach ($data['zapujcka_vyber'] as $zapujcka_id) {
                            $stav = $Zapujcka->odmitnout($zapujcka_id);
                            if ($stav) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage('Úspěšně jste odmítnul ' . $count_ok . ' zápůjček.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' zápůjček se nepodařilo odmítnout!',
                                    'warning');
                        }
                        if ($count_ok > 0 && $count_failed > 0) {
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
        if (!empty($zapujcka_id) && is_numeric($zapujcka_id)) {
            if (Acl::isInRole('spisovna') || $this->user->isInRole('superadmin')) {

                $Zapujcka = new Zapujcka();
                if ($Zapujcka->schvalit($zapujcka_id)) {
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
        if (!empty($zapujcka_id) && is_numeric($zapujcka_id)) {
            if (Acl::isInRole('spisovna') || $this->user->isInRole('superadmin')) {

                $Zapujcka = new Zapujcka();
                if ($Zapujcka->odmitnout($zapujcka_id)) {
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
        if (!empty($zapujcka_id) && is_numeric($zapujcka_id)) {
            $Zapujcka = new Zapujcka();
            if ($Zapujcka->vraceno($zapujcka_id, new Datetime())) {
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

        $form = new Spisovka\Form();

        $dokument_id = $this->getParameter('dokument_id');
        $user_id = $this->getParameter('user_id');

        if ($dokument_id) {
            $Dokument = new Dokument();
            $Zapujcka = new Zapujcka();
            $zapujcky = $Zapujcka->aktivniSeznam();

            if (isset($zapujcky[$dokument_id])) {
                $dokument_text = '';
                $dokument_id = null;
                $this->flashMessage('Vybraný dokument nelze zapůjčit! Je již zapůjčen jiným zaměstnancem.',
                        'warning');
                $this->redirect('default');
            } else {
                $dokument_info = $Dokument->getInfo($dokument_id);
                if ($dokument_info->stav_dokumentu > 7) {
                    $dokument_text = '';
                    $dokument_id = null;
                    $this->flashMessage('Vybraný dokument nelze zapůjčit! Dokument prochází nebo již prošel skartačním řízením a je tudíž nedostupný.',
                            'warning');
                    $this->redirect('default');
                } else {
                    if ($this->typ_evidence != 'priorace') {
                        $dokument_text = $dokument_info->cislo_jednaci . "" . $this->oddelovac_poradi . "" . $dokument_info->poradi . " - " . $dokument_info->nazev;
                    } else {
                        $dokument_text = $dokument_info->cislo_jednaci . " - " . $dokument_info->nazev;
                    }
                }
            }
        } else {
            $dokument_text = "";
        }

        if ($user_id) {
            $user_info = UserModel::getIdentity($user_id);
            $osoba = Osoba::displayName($user_info);
        } else {
            $user = $this->user;
            if (Acl::isInRole('spisovna') || $user->isInRole('superadmin')) {
                $osoba = "";
                $user_id = null;
                $form->addHidden('is_in_role')->setValue(1);
            } else {
                $osoba = Osoba::displayName($user->getIdentity()->identity);
                $user_id = $user->getIdentity()->id;
            }
        }

        $form->addText('dokument_text', 'Zapůjčený dokument:', 80)
                ->setValue($dokument_text);
        $form->addText('dokument_id')
                ->setValue($dokument_id)
                ->setRequired('Musí být vybrán dokument k zapůjčení!');


        $form->addText('user_text', 'Zapůjčeno komu:', 80)
                ->setValue($osoba);
        $form->addText('user_id')
                ->setValue($user_id)
                ->setRequired('Musí být vybrána osoba, které se bude zapůjčovat!');

        $form->addTextArea('duvod', "Důvod zapůjčení:", 80, 5);

        $datum_od = date('d.m.Y');
        $form->addDatePicker('date_od', 'Datum výpůjčky:', 10)
                ->setValue($datum_od)
                ->setRequired('Datum výpůjčky musí být vyplněné!');
        $form->addDatePicker('date_do', 'Datum vrácení:', 10)
                ->setRequired('Datum vrácení musí být vyplněné! Zadejte alespoň předpokládané datum vrácení.')
                ->forbidPastDates()
                ->addRule(Nette\Forms\Form::VALID, 'Datum vrácení nemůže být v minulosti.');


        $submit = $form->addSubmit('novy', 'Vytvořit zápůjčku');
        $submit->onClick[] = array($this, 'vytvoritClicked');

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

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        //Nette\Diagnostics\Debugger::dump($data);
        //Nette\Diagnostics\Debugger::dump($this->getHttpRequest()->getPost());
        //exit;

        $Zapujcka = new Zapujcka();

        try {
            $Zapujcka->vytvorit($data);
            $this->flashMessage('Zápůjčka byla vytvořena.');
            $this->redirect(':Spisovna:Zapujcky:default');
        } catch (DibiException $e) {
            $this->flashMessage('Zápůjčku se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $zapujcka_id = $data['id'];
        $this->redirect(':Spisovna:Zapujcky:detail', array('id' => $zapujcka_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Spisovna:Zapujcky:default');
    }

    protected function createComponentSearchForm()
    {

        $hledat = !is_null($this->hledat) ? $this->hledat : '';

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

        $this->forward('this', array('hledat' => $data['dotaz']));
    }

    protected function createComponentFiltrForm()
    {

        if (Acl::isInRole('spisovna') || $this->user->isInRole('superadmin')) {
            $filtr = !is_null($this->filtr) ? $this->filtr : 'vse';
            $select = [
                'ke_schvaleni' => 'Žádosti čekající na schválení',
                'zapujcene' => 'Aktuálně zapůjčené dokumenty',
                'aktualni' => 'Ke schválení + zapůjčené',
                'odmitnute' => 'Odmítnuté žádosti',
                'vracene' => 'Vrácené dokumenty',
                'vse' => 'Zobrazit vše',
                ];
            $this->template->zobrazit_filtr = 1;
        } else {
            $filtr = !is_null($this->filtr) ? $this->filtr : '';
            $select = array(
                '' => 'Zobrazit vše',
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
        $data = array('filtr' => $form_data['filtr']);
        $this->getHttpResponse()->setCookie('s3_zapujcka_filtr', serialize($data),
                strtotime('90 day'));
        $this->forward(':Spisovna:Zapujcky:default', array('filtr' => $data));
    }

    public function actionSeznamAjax()
    {

        $Dokument = new Dokument();

        $args = null;
        $seznam = array();

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $zapujcky = $Zapujcka->aktivniSeznam();


        $term = $this->getParameter('term');

        if (!empty($term)) {
            $args = $Dokument->hledat($term);
            $args = $Dokument->spisovna($args);
            $result = $Dokument->seznam($args);
            $seznam_dok = $result->fetchAll();
        } else {
            $args = $Dokument->spisovna($args);
            $result = $Dokument->seznam($args);
            $seznam_dok = $result->fetchAll();
        }

        if (count($seznam_dok) > 0) {
            foreach ($seznam_dok as $row) {
                if (isset($zapujcky[$row->id]))
                    continue; // je zapujcen
                $dok = $Dokument->getBasicInfo($row->id);

                //if ( $dok->stav_dokumentu > 7 ) continue; // vyradime dokumenty po skartacnim rizeni

                $seznam[] = array(
                    "id" => $dok->id,
                    "type" => 'item',
                    "value" => $dok->cislo_jednaci . ' - ' . $dok->nazev,
                    "nazev" => $dok->cislo_jednaci . " - " . $dok->nazev
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }

}
