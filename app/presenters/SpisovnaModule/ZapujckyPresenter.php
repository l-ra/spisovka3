<?php

class Spisovna_ZapujckyPresenter extends BasePresenter
{

    private $filtr;
    private $typ_evidence = null;
    private $oddelovac_poradi = null;

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Zapujcka', 'vytvorit');
    }

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
        $this->template->Oddelovac_poradi = $this->oddelovac_poradi;
        $this->template->Typ_evidence = $this->typ_evidence;

        parent::startup();
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
            $this->template->no_items = 3; // indikator pri nenalezeni zypujcky pri hledani
        }

        if (!$this->user->isAllowed('Zapujcka', 'schvalit'))
            $args = $Zapujcka->osobni($args);

        $result = $Zapujcka->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }

        $this->template->seznam = $seznam;
    }

    public function createComponentBulkAction()
    {
        $BA = new Spisovka\Components\BulkAction();

        if ($this->user->isAllowed('Zapujcka', 'schvalit')) {
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
                    $this->redirect('default');
                }
                break;

            /* Vraceni vybranych zapujcek  */
            case 'vratit':
                $count_ok = $count_failed = 0;
                foreach ($selection as $zapujcka_id) {
                    try {
                        $Zapujcka->vratit($zapujcka_id);
                        $count_ok++;
                    } catch (Exception $e) {
                        $e->getMessage();
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
                    $this->redirect('default');
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
                    $this->redirect('default');
                }
                break;

            default:
                break;
        }
    }

    public function renderDetail($id)
    {
        $Zapujcka = new Zapujcka();
        $zapujcka = $Zapujcka->getInfo($id);
        if ($zapujcka) {
            $this->template->Opravnen_schvalit_zapujcku = $this->user->isAllowed('Zapujcka',
                    'schvalit');
            $this->template->Zapujcka = $zapujcka;
        } else {
            // zapujcka neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
    }

    public function actionSchvalit($id)
    {
        $zapujcka_id = $id;
        if (!empty($zapujcka_id) && is_numeric($zapujcka_id)) {
            $Zapujcka = new Zapujcka();
            if ($Zapujcka->schvalit($zapujcka_id))
                $this->flashMessage('Zápůjčka byla schválena.');
            else
                $this->flashMessage('Zápůjčku se nepodařilo schválit!.', 'error');
        } else
            $this->flashMessage('Zápůjčku nelze schválit! Neplatná zápůjčka.', 'error');

        $this->redirect('default');
    }

    public function actionOdmitnout($id)
    {
        $zapujcka_id = $id;
        $Zapujcka = new Zapujcka();
        if ($Zapujcka->odmitnout($zapujcka_id))
            $this->flashMessage('Zápůjčka byla odmítnuta.');
        else
            $this->flashMessage('Zápůjčku se nepodařilo odmítnout!.', 'error');
        $this->redirect('default');
    }

    public function actionVratit($id, $back = null)
    {
        $zapujcka_id = $id;
        $Zapujcka = new Zapujcka();
        try {
            $Zapujcka->vratit($zapujcka_id);
            $this->flashMessage('Zapůjčený dokument byl vrácen.');
        } catch (Exception $e) {
            $this->flashMessage('Dokument se nepodařilo vrátit!', 'error');
            $this->flashMessage($e->getMessage(), 'error');
        }
        $this->redirect($back ?: 'default');
    }

    public function renderNova()
    {
        
    }

    protected function createComponentNovyForm()
    {
        $form = new Spisovka\Form();

        $dokument_id = $this->getParameter('dokument_id');

        if ($dokument_id) {
            $Zapujcka = new Zapujcka();
            $zapujcky = $Zapujcka->seznamZapujcenychDokumentu();

            if (isset($zapujcky[$dokument_id])) {
                $dokument_text = '';
                $dokument_id = null;
                $this->flashMessage('Vybraný dokument nelze zapůjčit! Je již zapůjčen jiným zaměstnancem.',
                        'warning');
                $this->redirect('default');
            } else {
                $doc = new Document($dokument_id);
                if ($doc->stav >= DocumentStates::STAV_SKARTACNI_RIZENI) {
                    $dokument_text = '';
                    $dokument_id = null;
                    $this->flashMessage('Vybraný dokument nelze zapůjčit! Dokument prochází nebo již prošel skartačním řízením.',
                            'warning');
                    $this->redirect('default');
                } else {
                    if ($this->typ_evidence != 'priorace') {
                        $dokument_text = $doc->cislo_jednaci . "" . $this->oddelovac_poradi . "" . $doc->poradi . " - " . $doc->nazev;
                    } else {
                        $dokument_text = $doc->cislo_jednaci . " - " . $doc->nazev;
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

        $pracovnik_spisovny = $this->user->isAllowed('Zapujcka', 'schvalit');
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
            $this->redirect('default');
        } catch (DibiException $e) {
            $this->flashMessage('Zápůjčku se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $zapujcka_id = $data['id'];
        $this->redirect('detail', array('id' => $zapujcka_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect('default');
    }

    protected function createComponentFiltrForm()
    {
        if ($this->user->isAllowed('Zapujcka', 'schvalit')) {
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

    public function renderSeznamAjax($term)
    {
        $Zapujcka = new Zapujcka();
        $zapujcky = $Zapujcka->seznamZapujcenychDokumentu();

        $Dokument = new Dokument();
        $args = $term ? $Dokument->hledat($term) : null;
        $args = $Dokument->filtrSpisovnaLzeZapujcit($args);
        $args['cols'] = ['nazev', 'cislo_jednaci'];
        $result = $Dokument->seznam($args);
        $seznam_dok = $result->fetchAll();

        $result = array();
        if ($seznam_dok)
            foreach ($seznam_dok as $dok) {
                if (isset($zapujcky[$dok->id]))
                    continue; // na dokument již existuje žádanka

                $result[] = ["id" => $dok->id,
                    "type" => 'item',
                    "value" => "$dok->cislo_jednaci - $dok->nazev",
                    "nazev" => "$dok->cislo_jednaci - $dok->nazev"
                ];
            }

        $this->sendJson($result);
    }

}
