<?php

class Spisovna_DokumentyPresenter extends BasePresenter
{

    private $filtr;
    private $hledat;
    private $seradit;
    private $typ_evidence = null;

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Spisovna', 'cist_dokumenty');
    }

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
        $this->template->Typ_evidence = $this->typ_evidence;

        parent::startup();
    }

    protected function seznam()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $Dokument = new Dokument();

        $filtr = UserSettings::get('spisovna_dokumenty_filtr', '');
        $this->filtr = $filtr;
        if ($this->view != 'default' && strpos($filtr, 'stav_') === 0)
            $filtr = '';
        $this->template->no_items = $filtr ? 2 : 1; // indikator pri nenalezeni dokumentu
        $args_f = $Dokument->spisovnaFiltr($filtr);

        switch ($this->view) {
            case 'prijem':
                $args_f = $Dokument->filtrSpisovnaPrijem($args_f);
                break;
            case 'skartacniNavrh':
                $args_f = $Dokument->filtrSpisovnaKeskartaci($args_f);
                break;
            case 'skartacniRizeni':
                $args_f = $Dokument->filtrSpisovnaSkartace($args_f);
                break;
            default:
                $args_f = $Dokument->filtrSpisovna($args_f);
                break;
        }

        $args_h = array();
        $hledat = UserSettings::get('spisovna_dokumenty_hledat');
        if ($hledat)
            $hledat = unserialize($hledat);
        try {
            if (isset($hledat))
                if (is_array($hledat)) {
                    // podrobne hledani = array
                    $args_h = $Dokument->paramsFiltr($hledat);
                    $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
                } else {
                    // rychle hledani = string
                    $args_h = $Dokument->hledat($hledat);
                    $this->hledat = $hledat;
                    $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
                }
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage() . " Hledání bylo zrušeno.", 'warning');
            $this->forward(':Spisovna:Vyhledat:reset');
        }
        $this->template->s3_hledat = $hledat;

        $args = $Dokument->spojitAgrs(@$args_f, @$args_h);

        $seradit = UserSettings::get('spisovna_dokumenty_seradit', 'cj');
        $Dokument->seradit($args, $seradit);
        $this->seradit = $seradit;
        $this->template->s3_seradit = $seradit;
        $this->template->seradit = $seradit;

        if ($this->view == 'skartacniNavrh') {
            $result = $Dokument->seznamKeSkartaci($args);
        } else {
            $result = $Dokument->seznam($args);
        }

        $paginator->itemCount = count($result);

        $this->template->dokument_view = $this->view;

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }
        $this->setView('default');

        if (count($seznam) > 0) {
            $dokument_ids = array();
            foreach ($seznam as $row) {
                $dokument_ids[] = $row->id;
            }

            $DokSubjekty = new DokumentSubjekt();
            $subjekty = $DokSubjekty->subjekty($dokument_ids);
            $pocty_souboru = DokumentPrilohy::pocet_priloh($dokument_ids);

            foreach ($seznam as $index => $row) {
                $dok = $Dokument->getInfo($row->id, '');
                $id = $dok->id;
                $dok->subjekty = isset($subjekty[$id]) ? $subjekty[$id] : null;
                $dok->pocet_souboru = isset($pocty_souboru[$id]) ? $pocty_souboru[$id] : 0;
                $seznam[$index] = $dok;
            }
        }

        $this->template->seznam = $seznam;

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $this->template->zapujcky = $Zapujcka->seznamZapujcenychDokumentu();
    }

    public function renderDefault()
    {
        $this->template->title = "Seznam dokumentů ve spisovně";
        $this->seznam();
    }

    public function renderPrijem()
    {
        if (!$this->user->isAllowed('Spisovna', 'prijem_dokumentu'))
            $this->forward(':NoAccess:default');

        $this->template->title = "Seznam dokumentů pro příjem do spisovny";
        $this->seznam();
    }

    public function renderSkartacniNavrh()
    {
        if (!$this->user->isAllowed('Spisovna', 'skartacni_navrh'))
            $this->forward(':NoAccess:default');

        $this->template->title = "Seznam dokumentů, kterým uplynula skartační lhůta";
        $this->seznam();
    }

    public function renderSkartacniRizeni()
    {
        if (!$this->user->isAllowed('Spisovna', 'skartacni_rizeni'))
            $this->forward(':NoAccess:default');

        $this->template->title = "Seznam dokumentů ve skartačním řízení";
        $this->seznam();
    }

    public function renderDetail($id)
    {
        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $id;

        $dokument = $Dokument->getInfo($dokument_id, "subjekty,soubory,odeslani");
        if ($dokument) {
            // dokument zobrazime
            $this->template->Dok = $dokument;
            $this->template->dokument_id = $dokument_id;

            $Zapujcka = new Zapujcka();
            if (in_array($dokument->stav_dokumentu, [7])) {
                // dokument musi byt prevzat do spisovny a nesmi byt zahajeno skartacni rizeni
                $this->template->Zapujcka = $Zapujcka->getFromDokumentId($dokument_id);
                // lze zapujcit, pokud uz neni zapujcen
                $this->template->Lze_zapujcit = $this->template->Zapujcka === null;
            } else {
                $this->template->Zapujcka = null;
                $this->template->Lze_zapujcit = false;
            }

            $user = $this->user;

            // nektere sablony jsou sdilene s modulem Spisovka
            $this->template->AccessEdit = false;
            $this->template->AccessView = true;

            $this->template->Lze_menit_skartacni_rezim = $dokument->stav_dokumentu == 7 && $user->isAllowed('Spisovna',
                            'zmenit_skartacni_rezim');
            $this->template->Upravit_param = $this->getParameter('upravit', null);

            $uplynula_skart_lhuta = !empty($dokument->skartacni_rok) && date('Y') >= $dokument->skartacni_rok;
            $this->template->Lze_zaradit_do_skartacniho_rizeni = $uplynula_skart_lhuta && $dokument->stav_dokumentu == 7 && $user->isAllowed('Spisovna',
                            'skartacni_navrh');

            $this->template->Lze_provest_skartacni_rizeni = $dokument->stav_dokumentu == 8 && $user->isAllowed('Spisovna',
                            'skartacni_rizeni');

            $this->template->Typ_evidence = $this->typ_evidence;
            if ($this->typ_evidence == 'priorace') {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument_id);
            }

            // Volba vystupu - web/tisk/pdf
            $tisk = $this->getParameter('print');
            $pdf = $this->getParameter('pdfprint');
            if ($tisk || $pdf) {
                $this->template->AccessEdit = false;
            }
            
            $Log = new LogModel();
            $historie = $Log->historieDokumentu($dokument_id, $tisk || $pdf);
            $this->template->historie = $historie;

        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
    }

    public function actionKeskartaci($id)
    {
        $user = $this->user;
        $doc = new DocumentWorkflow($id);
        if ($user->isAllowed('Spisovna', 'skartacni_navrh')) {
            if ($doc->shredProcessing()) {
                $this->flashMessage('Dokument byl přidán do skartačního řízení.');
            } else {
                $this->flashMessage('Dokument se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění přidávat dokumenty do skartačního zřízení.',
                    'warning');
        }
        $this->redirect('detail', array('id' => $id));
    }

    public function actionArchivovat($id)
    {
        $user = $this->user;

        $workflow = new DocumentWorkflow($id);
        if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
            if ($workflow->archive()) {
                $this->flashMessage('Dokument byl archivován.');
            } else {
                $this->flashMessage('Dokument se nepodařilo zařadit do archivu. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění provést operaci.', 'warning');
        }
        $this->redirect('detail', array('id' => $id));
    }

    public function actionSkartovat($id)
    {
        $user = $this->user;

        $Workflow = new DocumentWorkflow($id);
        if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
            if ($Workflow->shred()) {
                $this->flashMessage('Dokument byl skartován.');
            } else {
                $this->flashMessage('Dokument se nepodařilo skartovat. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění provést operaci.', 'warning');
        }
        $this->redirect('detail', array('id' => $id));
    }

    public function renderDownload($id, $file)
    {
        $dokument_id = $id;
        $file_id = $file;

        $DokumentPrilohy = new DokumentPrilohy();
        $prilohy = $DokumentPrilohy->prilohy($dokument_id);
        if (array_key_exists($file_id, $prilohy)) {
            $res = $this->storage->download($file_id);
            if ($res == 0) {
                $this->terminate();
            } else if ($res == 1) {
                // not found
                $this->flashMessage('Požadovaný soubor nenalezen!', 'warning');
                $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
            } else if ($res == 2) {
                $this->flashMessage('Chyba při stahování!', 'warning');
                $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
            } else if ($res == 3) {
                $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout zmíněný soubor!',
                        'warning');
                $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
            }
        } else {
            $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnout cizí soubor!',
                    'warning');
            $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
        }
    }

    protected function createComponentVyrizovaniForm()
    {
        $skar_znak = array('A' => 'A', 'S' => 'S', 'V' => 'V');

        $Dok = @$this->template->Dok;

        $form = new Spisovka\Form();
        $form->addHidden('id')
                ->setValue(@$Dok->id);

        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6)
                ->setValue(@$Dok->ulozeni_dokumentu);

        $form->addComponent(new SpisovyZnakComponent(), 'spisovy_znak_id');
        $form->getComponent('spisovy_znak_id')->setValue(@$Dok->spisovy_znak_id);

        $form->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak)
                ->setValue(@$Dok->skartacni_znak);
        $form->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
                ->addRule(Spisovka\Form::INTEGER, 'Skartační lhůta musí být celé číslo.')
                ->setValue(@$Dok->skartacni_lhuta);

        $form->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'upravitVyrizeniClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitVyrizeniClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        if (!$this->user->isAllowed('Spisovna', 'zmenit_skartacni_rezim')) {
            $this->forward(':NoAccess:default');
        }

        $data = $button->getForm()->getValues();

        $dokument_id = $data['id'];

        //Nette\Diagnostics\Debugger::dump($data); exit;

        $Dokument = new Dokument();

        $dok = $Dokument->getInfo($dokument_id);

        try {
            $Dokument->ulozit($data, $dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upraven skartační režim.');

            $this->flashMessage('Dokument "' . $dok->cislo_jednaci . '"  byl upraven.');
            $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "' . $dok->cislo_jednaci . '" se nepodařilo upravit.',
                    'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
            $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['id'];
        $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Spisovna:Dokumenty:default');
    }

    protected function createComponentSearchForm()
    {

        $hledat = !is_null($this->hledat) ? $this->hledat : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);

        $s3_hledat = UserSettings::get('spisovna_dokumenty_hledat');
        $s3_hledat = unserialize($s3_hledat);
        if (is_array($s3_hledat) && !empty($s3_hledat)) {
            $controlPrototype = $form['dotaz']->getControlPrototype();
            $controlPrototype->style(array('background-color' => '#ccffcc', 'border' => '1px #c0c0c0 solid'));
            $controlPrototype->title = "Aplikováno pokročilé vyhledávání. Pro detail klikněte na odkaz \"Pokročilé vyhledávání\". Zadáním hodnoty do tohoto pole, se pokročilé vyhledávání zruší a aplikuje se rychlé vyhledávání.";
        } else if (!empty($hledat)) {
            $controlPrototype = $form['dotaz']->getControlPrototype();
            //$controlPrototype->style(array('background-color' => '#ccffcc','border'=>'1px #c0c0c0 solid'));
            $controlPrototype->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";
        } else {
            $form['dotaz']->getControlPrototype()->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";
        }

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

        UserSettings::set('spisovna_dokumenty_hledat', serialize($data['dotaz']));
        $this->redirect($this->view);
    }

    protected function createComponentFiltrForm()
    {
        $select = array(
            '' => 'Zobrazit vše',
            'Podle stavu' => array(
                'stav_9' => 'archivován',
                'stav_10' => 'skartován',
            ),
            'Podle skartačního znaku' => array(
                'skartacni_znak_A' => 'A',
                'skartacni_znak_V' => 'V',
                'skartacni_znak_S' => 'S',
            ),
            'Podle způsobu vyřízení' => Dokument::zpusobVyrizeni(4)
        );
        if (isset($this->template->view) && $this->template->view != 'default')
            unset($select['Podle stavu']);

        $form = new Nette\Application\UI\Form();
        $form->addSelect('filtr', 'Filtr:', $select)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        try {
            $form['filtr']->setValue($this->filtr);
        } catch (Exception $e) {
            $e->getMessage();
            // stavy "archivován" a "skartován" neplatí na stránce příjmu dokumentů
            $form['filtr']->setValue('');
        }

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
        UserSettings::set('spisovna_dokumenty_filtr', $form_data['filtr']);

        $this->redirect($this->view);
    }

    protected function createComponentSeraditForm()
    {

        $select = array(
            'cj' => 'čísla jednacího (vzestupně)',
            'cj_desc' => 'čísla jednacího (sestupně)',
            'jid' => 'JID (vzestupně)',
            'jid_desc' => 'JID (sestupně)',
            'dvzniku' => 'data přijetí/vzniku (vzestupně)',
            'dvzniku_desc' => 'data přijetí/vzniku (sestupně)',
            'vec' => 'věci (vzestupně)',
            'vec_desc' => 'věci (sestupně)',
            'prideleno' => 'přidělené osoby (vzestupně)',
            'prideleno_desc' => 'přidělené osoby (sestupně)',
            'skartacni_znak' => 'skartačního znaku (vzestupně)',
            'skartacni_znak_desc' => 'skartačního znaku (sestupně)',
            'spisovy_znak' => 'spisového znaku (vzestupně)',
            'spisovy_znak_desc' => 'spisového znaku (sestupně)',
        );

        $seradit = !is_null($this->seradit) ? $this->seradit : null;

        $form = new Nette\Application\UI\Form();
        $form->addSelect('seradit', 'Seřadit podle:', $select)
                ->setValue($seradit)
                ->getControlPrototype()->onchange("return document.forms['frm-seraditForm'].submit();");

        $form->onSuccess[] = array($this, 'seraditFormSucceeded');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function seraditFormSucceeded(Nette\Application\UI\Form $form, $form_data)
    {
        UserSettings::set('spisovna_dokumenty_seradit', $form_data['seradit']);
        $this->redirect('default');
    }

    public function createComponentBulkAction()
    {
        $BA = new Spisovka\Components\BulkAction();

        $actions = [];
        if (isset($this->template->dokument_view))
            switch ($this->template->dokument_view) {
                case 'prijem':
                    $actions = ['prevzit_spisovna' => 'převzetí dokumentů do spisovny',
                        'vratit' => 'vrátit (nepřevzít) dokumenty'
                    ];
                    $BA->setDefaultAction('prevzit_spisovna');
                    break;
                case 'skartacniNavrh':
                    $actions = ['ke_skartaci' => 'předat do skartačního řízení'];
                    break;
                case 'skartacniRizeni':
                    $actions = ['archivovat' => 'archivovat vybrané dokumenty',
                        'skartovat' => 'skartovat vybrané dokumenty'
                    ];
                default:
                    break;
            }

        $BA->setActions($actions);
        $BA->setCallback([$this, 'bulkAction']);

        return $BA;
    }

    public function bulkAction($action, $documents)
    {
        $user = $this->user;

        switch ($action) {
            case 'vratit':
                foreach ($documents as $dokument_id) {
                    $doc = new DocumentWorkflow($dokument_id);
                    $doc->returnFromSpisovna();
                }
                if (count($documents) == 1)
                    $msg = 'Dokument byl vrácen.';
                else
                    $msg = 'Dokumenty byly vráceny.';
                $this->flashMessage($msg);
                break;

            /* Prevzeti vybranych dokumentu */
            case 'prevzit_spisovna':                
                $count_ok = $count_failed = 0;
                foreach ($documents as $dokument_id) {
                    $doc = new DocumentWorkflow($dokument_id);
                    $stav = $doc->receiveIntoSpisovna(true);
                    if ($stav === true) {
                        $count_ok++;
                    } else {
                        if (is_string($stav)) {
                            $this->flashMessage($stav, 'warning');
                        }
                        $count_failed++;
                    }
                }
                if ($count_ok > 0) {
                    $this->flashMessage('Úspěšně jste přijal ' . $count_ok . ' dokumentů do spisovny.');
                }
                if ($count_failed > 0) {
                    $this->flashMessage($count_failed . ' dokumentů se nepodařilo přijmout do spisovny!',
                            'warning');
                }
                if ($count_ok > 0 && $count_failed > 0) {
                    $this->redirect('this');
                }
                break;

            case 'ke_skartaci': {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_navrh')) {
                        foreach ($documents as $dokument_id) {
                            $doc = new DocumentWorkflow($dokument_id);
                            if ($doc->shredProcessing()) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage('Úspěšně jste předal ' . $count_ok . ' dokumentů do skartačního řízení.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' dokumentů se nepodařilo předat do skartačního řízení!',
                                    'warning');
                        }
                    } else {
                        $this->flashMessage('Nemáte oprávnění převádět dokumenty do skartačního řízení.',
                                'warning');
                        $count_failed++;
                    }

                    if ($count_ok > 0 && $count_failed > 0) {
                        $this->redirect('this');
                    }
                }
                break;

            case 'archivovat': {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
                        foreach ($documents as $dokument_id) {
                            $doc = new DocumentWorkflow($dokument_id);
                            if ($doc->archive()) {
                                $count_ok++;
                            } else {
                                $count_failed++;
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage($count_ok . ' dokumentů bylo úspěšně archivováno.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' dokumentů se nepodařilo zařadit do archivu. Zkuste to znovu.',
                                    'warning');
                        }
                    } else {
                        $this->flashMessage('Nemáte oprávnění rozhodovat o skartačním řízení.',
                                'warning');
                        $count_failed++;
                    }

                    if ($count_ok > 0 && $count_failed > 0) {
                        $this->redirect('this');
                    }
                }
                break;

            case 'skartovat': {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
                        foreach ($documents as $dokument_id) {
                            $doc = new DocumentWorkflow($dokument_id);
                            if ($doc->shred()) {
                                $count_ok++;
                            } else
                                $count_failed++;
                        }
                        if ($count_ok > 0)
                            $this->flashMessage($count_ok . ' dokumentů bylo úspěšně skartováno.');
                        if ($count_failed > 0)
                            $this->flashMessage($count_failed . ' dokumentů se nepodařilo skartovat. Zkuste to znovu.',
                                    'warning');
                    } else {
                        $this->flashMessage('Nemáte oprávnění rozhodovat o skartačním řízení.',
                                'warning');
                        $count_failed++;
                    }

                    if ($count_ok > 0 && $count_failed > 0) {
                        $this->redirect('this');
                    }
                }
                break;

            default:
                $this->flashMessage('Neznámá akce.', 'warning');
                break;
        }
    }

}
