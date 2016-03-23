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
        $client_config = GlobalVariables::get('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
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

                $app_info = new VersionInformation();
                $app_name = $app_info->name;
                $mpdf->SetCreator($app_name);
                $person_name = $this->user->displayName;
                $mpdf->SetAuthor($person_name);
                $mpdf->SetTitle('Spisová služba - Zápůjčky');

                $mpdf->defaultheaderfontsize = 10; /* in pts */
                $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                $mpdf->defaultfooterfontsize = 9; /* in pts */
                $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */
                $mpdf->SetHeader('Zápůjčky||' . $this->template->Urad->nazev);
                $mpdf->SetFooter("{DATE j.n.Y}/" . $person_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */



                $mpdf->WriteHTML($content);
                $mpdf->Output('zapujcky.pdf', 'I');
            }
        }
    }

    public function renderDefault($hledat)
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $Zapujcka = new Zapujcka();

        $this->template->no_items = 1; // indikator pri nenalezeni zapujcky

        $this->filtr = UserSettings::get('spisovna_zapujcky_filtr', 'vse');
        if ($this->filtr != 'vse')
            $this->template->no_items = 2; // indikator pri nenalezeni zapujcky po filtraci

        $args = $Zapujcka->filtr($this->filtr);

        if (isset($hledat)) {
            // rychle hledani = string
            $args = $Zapujcka->hledat($hledat, $args);
            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni zypujcky pri hledani
        }

        /* $Zapujcka->seradit($args, $seradit);
          $this->template->seradit = $seradit; */

        if (!$this->user->inheritsFromRole('spisovna') && !$this->user->isInRole('superadmin'))
            $args = $Zapujcka->osobni($args);

        $result = $Zapujcka->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setView('print');
        } elseif ($pdf) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }

        $this->template->seznam = $seznam;
    }

    public function createComponentBulkAction()
    {
        $BA = new Spisovka\Components\BulkAction();

        if ($this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin')) {
            $actions = ['vratit' => 'Vrátit dokumenty',
                'schvalit' => 'Schválit žádosti',
                'odmitnout' => 'Odmítnout žádosti'
            ];
            switch ($this->filtr) {
                case 'ke_schvaleni':
                    unset($actions['vratit']);
                    break;
                case 'zapujcene':
                    unset($actions['schvalit']);
                    unset($actions['odmitnout']);
                    break;

                case 'odmitnute':
                case 'vracene':
                    $actions = [];
                    break;
            }
        } else {
            $actions = ['vratit' => 'Vrátit vybrané zápůjčky'];
        }

        $BA->setActions($actions);
        $BA->setCallback([$this, 'bulkAction']);
        $BA->text_checkbox_title = 'Vybrat tuto zápůjčku';

        return $BA;
    }

    public function bulkAction($action, $selection)
    {
        $Zapujcka = new Zapujcka();
        switch ($action) {
            /* Schvaleni vybranych zapujcek  */
            case 'schvalit':
                $count_ok = $count_failed = 0;
                foreach ($selection as $zapujcka_id) {
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
                break;

            /* Vraceni vybranych zapujcek  */
            case 'vratit':
                $count_ok = $count_failed = 0;
                $dnes = new DateTime();
                foreach ($selection as $zapujcka_id) {
                    $stav = $Zapujcka->vraceno($zapujcka_id, $dnes);
                    if ($stav) {
                        $count_ok++;
                    } else {
                        $count_failed++;
                    }
                }
                if ($count_ok > 0) {
                    $this->flashMessage('Úspěšně jste vrátil ' . $count_ok . ' dokument(ů).');
                }
                if ($count_failed > 0) {
                    $this->flashMessage($count_failed . ' dokumentů k zapůjčení se nepodařilo vrátit!',
                            'warning');
                }
                if ($count_ok > 0 && $count_failed > 0) {
                    $this->redirect(':Spisovna:Zapujcky:default');
                }
                break;

            /* Odmitnuti vybranych zapujcek  */
            case 'odmitnout':
                $count_ok = $count_failed = 0;
                foreach ($selection as $zapujcka_id) {
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
                break;

            default:
                break;
        }
    }

    public function actionDetail()
    {
        $Zapujcka = new Zapujcka();

        // Nacteni parametru
        $zapujcka_id = $this->getParameter('id', null);

        $this->template->Zapujcka = null;
        $zapujcka = $Zapujcka->getInfo($zapujcka_id);
        if ($zapujcka) {
            $this->template->Opravnen_schvalit_zapujcku = $this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin');
            $this->template->Zapujcka = $zapujcka;
        } else {
            // zapujcka neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
    }

    public function actionSchvalit()
    {

        $zapujcka_id = $this->getParameter('id');
        if (!empty($zapujcka_id) && is_numeric($zapujcka_id)) {
            if ($this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin')) {

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
            if ($this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin')) {

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
        
    }

    protected function createComponentNovyForm()
    {
        $form = new Spisovka\Form();

        $dokument_id = $this->getParameter('dokument_id');

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

        $form->addText('dokument_text', '', 80)
                ->setValue($dokument_text);
        $form->addText('dokument_id', 'Zapůjčený dokument:')
                ->setValue($dokument_id)
                ->setRequired('Musí být vybrán dokument k zapůjčení!');

        $pracovnik_spisovny = $this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin');
        if ($pracovnik_spisovny) {
            $form->addText('user_text', '', 80);
            $form->addText('user_id', 'Zapůjčeno komu:')
                    ->setRequired('Musí být vybrána osoba, které se bude zapůjčovat!');
        }

        $form->addTextArea('duvod', "Důvod zapůjčení:", 80, 5);

        $datum_od = date('d.m.Y');
        $form->addDatePicker('date_od', 'Datum výpůjčky:')
                ->setValue($datum_od)
                ->setRequired('Datum výpůjčky musí být vyplněné!');
        $form->addDatePicker('date_do', 'Datum vrácení:')
                ->setRequired('Datum vrácení musí být vyplněné! Zadejte alespoň předpokládané datum vrácení.')
                ->forbidPastDates();

        $submit = $form->addSubmit('novy', 'Vytvořit zápůjčku');
        $submit->onClick[] = array($this, 'vytvoritClicked');

        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

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

        $this->redirect('this', array('hledat' => $data['dotaz']));
    }

    protected function createComponentFiltrForm()
    {

        if ($this->user->inheritsFromRole('spisovna') || $this->user->isInRole('superadmin')) {
            $filtr = !is_null($this->filtr) ? $this->filtr : 'vse';
            $select = [
                'ke_schvaleni' => 'Žádosti čekající na schválení',
                'zapujcene' => 'Aktuálně zapůjčené dokumenty',
                'aktualni' => 'Ke schválení + zapůjčené',
                'odmitnute' => 'Odmítnuté žádosti',
                'vracene' => 'Vrácené dokumenty',
                'vse' => 'Zobrazit vše',
            ];
        } else {
            $filtr = !is_null($this->filtr) ? $this->filtr : '';
            $select = array(
                '' => 'Zobrazit vše',
            );
        }

        $form = new Nette\Application\UI\Form();
        $form->addSelect('filtr', 'Filtr:', $select)
                // ->setValue($filtr)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        if (count($select) > 1)
            $form['filtr']->setValue($filtr);
        else
            $form['filtr']->setDisabled();

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
        UserSettings::set('spisovna_zapujcky_filtr', $form_data['filtr']);
        $this->redirect('this');
    }

    public function actionSeznamAjax()
    {
        $Dokument = new Dokument();

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $zapujcky = $Zapujcka->aktivniSeznam();

        $term = $this->getParameter('term');
        $args = $term ? $Dokument->hledat($term) : null;
        $args = $Dokument->filtrSpisovna($args);
        $result = $Dokument->seznam($args);
        $seznam_dok = $result->fetchAll();

        $seznam = array();
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
