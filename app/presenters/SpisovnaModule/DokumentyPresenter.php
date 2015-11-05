<?php

class Spisovna_DokumentyPresenter extends BasePresenter
{

    private $filtr;
    private $hledat;
    private $seradit;
    private $odpoved = null;
    private $typ_evidence = null;
    private $pdf_output = 0;

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Spisovna', 'cist_dokumenty');
    }

    public function startup()
    {
        $client_config = Nette\Environment::getVariable('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
        $this->template->Typ_evidence = $this->typ_evidence;

        parent::startup();
    }

    protected function shutdown($response)
    {

        if ($this->pdf_output == 1 || $this->pdf_output == 2) {

            ob_start();
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
            $content = ob_get_clean();
            if ($content) {

                if ($this->pdf_output == 2) {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s', '', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s', '',
                            $content);

                    $mpdf = new mPDF('iso-8859-2', 'A4', 9, 'Helvetica');

                    $app_info = Nette\Environment::getVariable('app_info');
                    $app_info = explode("#", $app_info);
                    $app_name = (isset($app_info[2])) ? $app_info[2] : 'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                    $mpdf->SetTitle('Spisová služba - Detail dokumentu');

                    $mpdf->defaultheaderfontsize = 10; /* in pts */
                    $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9; /* in pts */
                    $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->SetHeader('||' . $this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/" . $this->user->getIdentity()->display_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

                    $mpdf->WriteHTML($content);

                    $mpdf->Output('dokument.pdf', 'I');
                } else {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s', '', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s', '',
                            $content);

                    $mpdf = new mPDF('iso-8859-2', 'A4-L', 9, 'Helvetica');

                    $app_info = Nette\Environment::getVariable('app_info');
                    $app_info = explode("#", $app_info);
                    $app_name = (isset($app_info[2])) ? $app_info[2] : 'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                    $mpdf->SetTitle('Spisová služba - Spisovna - Tisk');

                    $mpdf->defaultheaderfontsize = 10; /* in pts */
                    $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9; /* in pts */
                    $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */
                    $mpdf->SetHeader($this->template->title . '||' . $this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/" . $this->user->getIdentity()->display_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

                    $mpdf->WriteHTML($content);
                    $mpdf->Output('spisovna.pdf', 'I');
                }
            }
        }
    }

    protected function seznam($typ = 0)
    {
        $client_config = Nette\Environment::getVariable('client_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $Dokument = new Dokument();

        $filtr = UserSettings::get('spisovna_dokumenty_filtr');
        if ($filtr) {
            $filtr = unserialize($filtr);
        } else {
            // filtr nezjisten - pouzijeme filtr Vse
            $filtr = array();
            $filtr['filtr'] = 'stav_77';
        }

        $this->filtr = $filtr['filtr'];
        if ($this->view != 'default' && strpos($filtr['filtr'], 'stav_') === 0)
            $filtr['filtr'] = 'stav_77';

        $this->template->no_items = ($filtr['filtr'] == 'stav_77') ? 1 : 2; // indikator pri nenalezeni dokumentu
        $args_f = $Dokument->spisovnaFiltr($filtr['filtr']);

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

        if ($typ == 1) {
            // prijem
            $args = $Dokument->spisovna_prijem($args);
        } else if ($typ == 2) {
            // ke skartaci
            $args = $Dokument->spisovna_keskartaci($args);
        } else if ($typ == 3) {
            // skartacni rizeni
            $args = $Dokument->spisovna_skartace($args);
        } else {
            // seznam
            $args = $Dokument->spisovna($args);
        }

        if ($typ == 2) {
            $result = $Dokument->seznamKeSkartaci($args);
        } else {
            $result = $Dokument->seznam($args);
        }

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
            $this->setView('default');
        }

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

        $this->template->filtrForm = $this['filtrForm'];
        $this->template->seraditForm = $this['seraditForm'];

        // Pripojit aktivni zapujcky
        $Zapujcka = new Zapujcka();
        $this->template->zapujcky = $Zapujcka->aktivniSeznam();
    }

    public function renderDefault()
    {

        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array();

        $this->template->title = "Seznam dokumentů ve spisovně";
        $this->seznam(0);
    }

    public function renderPrijem()
    {
        if (!$this->user->isAllowed('Spisovna', 'prijem_dokumentu'))
            $this->forward(':NoAccess:default');

        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'prevzit_spisovna' => 'převzetí dokumentů do spisovny'
        );

        $this->template->title = "Seznam dokumentů pro příjem do spisovny";
        $this->seznam(1);
    }

    public function renderSkartacniNavrh()
    {
        if (!$this->user->isAllowed('Spisovna', 'skartacni_navrh'))
            $this->forward(':NoAccess:default');

        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'ke_skartaci' => 'předat do skartačního řízení'
        );
        $this->template->title = "Seznam dokumentů, kterým uplynula skartační lhůta";
        $this->seznam(2);
    }

    public function renderSkartacniRizeni()
    {
        if (!$this->user->isAllowed('Spisovna', 'skartacni_rizeni'))
            $this->forward(':NoAccess:default');

        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $this->template->akce_select = array(
            'archivovat' => 'archivovat vybrané dokumenty',
            'skartovat' => 'skartovat vybrané dokumenty',
        );
        $this->template->title = "Seznam dokumentů ve skartačním řízení";
        $this->seznam(3);
    }

    public function renderDetail()
    {
        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $this->getParameter('id', null);

        $dokument = $Dokument->getInfo($dokument_id, "subjekty,soubory,odeslani,workflow");
        if ($dokument) {
            // dokument zobrazime
            if (!empty($dokument->identifikator)) {
                $Epodatelna = new Epodatelna();
                $dokument->identifikator = $Epodatelna->identifikator(unserialize($dokument->identifikator));
            }

            $this->template->Dok = $dokument;

            $Zapujcka = new Zapujcka();
            if (in_array($dokument->stav_dokumentu, [7])) {
                // dokument musi byt prevzat do spisovny a nesmi byt zahajeno skartacni rizeni
                $this->template->Zapujcka = $Zapujcka->getDokument($dokument_id);
                // lze zapujcit, pokud uz neni zapujcen
                $this->template->Lze_zapujcit = $this->template->Zapujcka === null;
            } else {
                $this->template->Zapujcka = null;
                $this->template->Lze_zapujcit = false;
            }

            $user = $this->user;

            $this->template->Lze_menit_skartacni_rezim = $dokument->stav_dokumentu == 7 && $user->isAllowed('Spisovna',
                            'zmenit_skartacni_rezim');
            $this->template->Upravit_param = $this->getParameter('upravit', null);

            $uplynula_skart_lhuta = !empty($dokument->skartacni_rok) && date('Y') >= $dokument->skartacni_rok;
            $this->template->Lze_zaradit_do_skartacniho_rizeni = $uplynula_skart_lhuta && $dokument->stav_dokumentu == 7 && $user->isAllowed('Spisovna',
                            'skartacni_navrh');

            $this->template->Lze_provest_skartacni_rizeni = $dokument->stav_dokumentu == 8 && $user->isAllowed('Spisovna',
                            'skartacni_rizeni');

            $SpisovyZnak = new SpisovyZnak();
            $this->template->SpisoveZnaky = $SpisovyZnak->seznam(null);

            $this->template->Typ_evidence = $this->typ_evidence;
            if ($this->typ_evidence == 'priorace') {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument_id);
            }

            // Volba vystupu - web/tisk/pdf
            $tisk = $this->getParameter('print');
            $pdf = $this->getParameter('pdfprint');
            if ($tisk) {
                @ini_set("memory_limit", PDF_MEMORY_LIMIT);
                $this->setLayout(false);
                $this->setView('printdetail');
            } elseif ($pdf) {
                @ini_set("memory_limit", PDF_MEMORY_LIMIT);
                $this->pdf_output = 2;
                $this->setLayout(false);
                $this->setView('printdetail');
            }

            if ($this->template->Lze_menit_skartacni_rezim)
                $this->template->vyrizovaniForm = $this['vyrizovaniForm'];

            $this->invalidateControl('dokspis');
        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
    }

    public function actionAkce($data)
    {
        if (!isset($data['hromadna_akce']))
            return;

        $Workflow = new Workflow();
        $user = $this->user;
        switch ($data['hromadna_akce']) {
            /* Prevzeti vybranych dokumentu */
            case 'prevzit_spisovna':
                if (!isset($data['dokument_vyber']))
                    return;

                $count_ok = $count_failed = 0;
                foreach ($data['dokument_vyber'] as $dokument_id) {
                    $stav = $Workflow->prevzitDoSpisovny($dokument_id, true);
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
                    $this->flashMessage($count_failed . ' dokumentů se nepodařilo příjmout do spisovny!',
                            'warning');
                }
                if ($count_ok > 0 && $count_failed > 0) {
                    $this->redirect('this');
                }
                break;

            case 'ke_skartaci':
                if (isset($data['dokument_vyber'])) {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_navrh')) {
                        foreach ($data['dokument_vyber'] as $dokument_id) {
                            if ($Workflow->keskartaci($dokument_id, $user->getIdentity()->id)) {
                                //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                $count_ok++;
                            } else {
                                $count_failed++;
                                //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
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
            case 'archivovat':
                if (isset($data['dokument_vyber'])) {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
                        foreach ($data['dokument_vyber'] as $dokument_id) {
                            if ($Workflow->archivovat($dokument_id)) {
                                //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                $count_ok++;
                            } else {
                                $count_failed++;
                                //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
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
            case 'skartovat':
                if (isset($data['dokument_vyber'])) {
                    $count_ok = $count_failed = 0;
                    if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
                        foreach ($data['dokument_vyber'] as $dokument_id) {
                            if ($Workflow->skartovat($dokument_id)) {
                                //$this->flashMessage('Dokument byl přidán do skartačního řízení.');
                                $count_ok++;
                            } else {
                                $count_failed++;
                                //$this->flashMessage('Dokument  se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.','warning');
                            }
                        }
                        if ($count_ok > 0) {
                            $this->flashMessage($count_ok . ' dokumentů bylo úspěšně skartováno.');
                        }
                        if ($count_failed > 0) {
                            $this->flashMessage($count_failed . ' dokumentů se nepodařilo skartovat. Zkuste to znovu.',
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
            default:
                break;
        }
    }

    public function actionKeskartaci()
    {

        $dokument_id = $this->getParameter('id', null);
        $user = $this->user;

        $Workflow = new Workflow();
        if ($user->isAllowed('Spisovna', 'skartacni_navrh')) {
            if ($Workflow->keskartaci($dokument_id)) {
                $this->flashMessage('Dokument byl přidán do skartačního řízení.');
            } else {
                $this->flashMessage('Dokument se nepodařilo zařadit do skartačního řízení. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění přidávat dokumenty do skartačního zřízení.',
                    'warning');
        }
        $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
    }

    public function actionArchivovat()
    {
        $dokument_id = $this->getParameter('id', null);
        $user = $this->user;

        $Workflow = new Workflow();
        if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
            if ($Workflow->archivovat($dokument_id)) {
                $this->flashMessage('Dokument byl archivován.');
            } else {
                $this->flashMessage('Dokument se nepodařilo zařadit do archivu. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění provést operaci.', 'warning');
        }
        $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
    }

    public function actionSkartovat()
    {
        $dokument_id = $this->getParameter('id', null);
        $user = $this->user;

        $Workflow = new Workflow();
        if ($user->isAllowed('Spisovna', 'skartacni_rizeni')) {
            if ($Workflow->skartovat($dokument_id)) {
                $this->flashMessage('Dokument byl skartován.');
            } else {
                $this->flashMessage('Dokument se nepodařilo skartovat. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění provést operaci.', 'warning');
        }
        $this->redirect(':Spisovna:Dokumenty:detail', array('id' => $dokument_id));
    }

    public function renderDownload()
    {

        $dokument_id = $this->getParameter('id', null);
        $file_id = $this->getParameter('file', null);

        $DokumentPrilohy = new DokumentPrilohy();
        $prilohy = $DokumentPrilohy->prilohy($dokument_id);
        if (array_key_exists($file_id, $prilohy)) {

            $DownloadFile = $this->storage;
            $FileModel = new FileModel();
            $file = $FileModel->getInfo($file_id);
            $res = $DownloadFile->download($file);
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

    public function renderHistorie()
    {

        $dokument_id = $this->getParameter('id', null);

        $Log = new LogModel();
        $historie = $Log->historieDokumentu($dokument_id, 1000);

        $this->template->historie = $historie;
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
        // pracovnik spisovny
        $filtr = !is_null($this->filtr) ? $this->filtr : 'stav_77';
        $select = array(
            'stav_77' => 'Zobrazit vše',
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
            $form['filtr']->setValue($filtr);
        } catch (Exception $e) {
            $e->getMessage();
            // stavy "archivován" a "skartován" neplatí na stránce příjmu dokumentů
            $form['filtr']->setValue('stav_77');
        }

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
        UserSettings::set('spisovna_dokumenty_filtr', serialize($data));

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

}
