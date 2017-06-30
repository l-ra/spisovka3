<?php

namespace Spisovka;

use Nette;

class Spisovka_DokumentyPresenter extends BasePresenter
{

    private $filtr;
    private $filtr_bezvyrizenych;
    private $filtr_moje;
    private $zakaz_filtr = false;
    private $hledat;
    private $seradit;
    private $odpoved = false;
    private $typ_evidence = null;

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;

        parent::startup();
    }

    public function renderDefault()
    {
        $this->template->Typ_evidence = $this->typ_evidence;

        $client_config = GlobalVariables::get('client_config');
        $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $Dokument = new Dokument();

        $filtr = UserSettings::get('spisovka_dokumenty_filtr');
        if ($filtr) {
            $filtr = unserialize($filtr);
        } else {
            // filtr nezjisten - pouzijeme nejaky
            $filtr = array();
            $filtr['filtr'] = 'vse';
            $filtr['bez_vyrizenych'] = false;
            $filtr['jen_moje'] = false;
        }

        $args_f = $Dokument->fixedFiltr($filtr['filtr'], $filtr['bez_vyrizenych'],
                $filtr['jen_moje']);
        $this->filtr = $filtr['filtr'];
        $this->filtr_bezvyrizenych = $filtr['bez_vyrizenych'];
        $this->filtr_moje = $filtr['jen_moje'];
        $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci

        $args_h = array();
        $hledat = UserSettings::get('spisovka_dokumenty_hledat');
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
            $this->forward(':Spisovka:Vyhledat:reset');
        }
        $this->template->s3_hledat = $hledat;

        /* [P.L.] Pokud uzivatel zvoli pokrocile hledani a hleda dokumenty pridelene/predane uzivateli ci jednotce,
          ignoruj filtr, ktery uzivatel nastavil a pouzij filtr "Vsechny" */
        if (is_array($hledat) && (isset($hledat['prideleno']) || isset($hledat['predano']) || isset($hledat['prideleno_org']) || isset($hledat['predano_org'])
                )) {
            $bez_vyrizenych = false;
            if (isset($filtr['bez_vyrizenych']))
                $bez_vyrizenych = $filtr['bez_vyrizenych'];
            $args_f = $Dokument->fixedFiltr('vse', $bez_vyrizenych, false);
            $this->zakaz_filtr = true;
        }

        $vybrane_dokumenty = $this->getParameter('vybrane_dokumenty', false);
        if (!$vybrane_dokumenty)
            $args = $Dokument->spojitAgrs(@$args_f, @$args_h);
        else {
            $args = ['where' => [['d.[id] IN (%i)', explode('-', $vybrane_dokumenty)]]];
        }

        $seradit = UserSettings::get('spisovka_dokumenty_seradit');
        if ($seradit) {
            $Dokument->seradit($args, $seradit);
        }

        $this->seradit = $seradit;
        $this->template->s3_seradit = $seradit;
        $this->template->seradit = $seradit;

        $args = $Dokument->filtrSpisovka($args);
        $result = $Dokument->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }

        if (count($seznam) > 0) {
            $dokument_ids = array();
            foreach ($seznam as $row)
                $dokument_ids[] = $row->id;

            $DokSubjekty = new DokumentSubjekt();
            $subjekty = $DokSubjekty->subjekty3($dokument_ids);
            $pocty_souboru = DokumentPrilohy::pocet_priloh($dokument_ids);

            foreach ($seznam as $index => $row) {
                $dok = $Dokument->getInfo($row->id, '');
                if (empty($dok->stav)) {
                    // toto má myslím zajistit, aby se v seznamu nezobrazovaly rozepsané dokumenty
                    unset($seznam[$index]);
                    continue;
                }
                $id = $dok->id;
                $dok->subjekty = isset($subjekty[$id]) ? $subjekty[$id] : null;
                // $dok->prilohy = isset($prilohy[$id]) ? $prilohy[$id] : null;
                $dok->pocet_souboru = isset($pocty_souboru[$id]) ? $pocty_souboru[$id] : 0;
                $seznam[$index] = $dok;
            }
        }

        $this->template->seznam = $seznam;
    }

    public function actionDetail($id, $upravit = null, $udalost = null)
    {
        $dokument_id = $id;
        $old_model = new Dokument();
        $dokument = $old_model->getInfo($dokument_id, "subjekty,soubory,odeslani");
        if (!$dokument) {
            $this->setView('neexistuje');
            return;
        }
        $doc = new DocumentWorkflow($id);

        if ($doc->stav === 0) {
            $this->flashMessage("Pokoušel jste se zobrazit dokument, který je rozepsaný. V menu zvolte \"Nový dokument\" a vytvoření dokumentu prosím dokončete.",
                    'warning');
            $this->redirect('default');
        }

        $user = $this->user;

        $permissions = $doc->getUserPermissions();
        $AccessEdit = $permissions['edit'];

        $this->template->LzeVratit = false;
        if ($doc->stav == DocumentWorkflow::STAV_ZAPUJCEN) {
            $Zapujcka = new Zapujcka();
            $zapujcka = $Zapujcka->getFromDokumentId($dokument_id);
            $this->template->Zapujcka = $zapujcka;
            if (isset($zapujcka->id) && $doc->owner_user_id == $user->id) {
                $this->template->LzeVratit = true;
            }
        }

        $this->template->FormUpravit = $upravit;

        // Kontrola chybejiciho nazvu dokumentu
        if (empty($doc->nazev) && $AccessEdit && $doc->stav == DocumentStates::STAV_VYRIZUJE_SE) {
            $this->template->nutnyNadpis = 1;
            // vyvolej zobrazeni formulare editace metadat
            $this->template->FormUpravit = 'metadata';
        }

        $this->template->SouvisejiciDokumenty = array();
        $this->template->povolitOdpoved = false;
        if ($this->typ_evidence == 'priorace') {
            // Nacteni souvisejicicho dokumentu
            $Souvisejici = new SouvisejiciDokument();
            $linkedDocuments = $Souvisejici->souvisejici($dokument_id);
            $this->template->SouvisejiciDokumenty = $linkedDocuments;
            if (!empty($doc->cislo_jednaci) && $dokument->typ_dokumentu->smer == 0
                    && $this->user->isAllowed('Dokument', 'vytvorit')) {
                $this->template->povolitOdpoved = true;
                if ($doc->doesReplyExist()) {
                    // odpoved jiz existuje - stejné číslo jednací mohou mít maximálně dva dokumenty
                    $this->template->povolitOdpoved = false;
                }
            }
        }

        $this->template->Dok = $dokument;
        $this->template->Typ_evidence = $this->typ_evidence;
        $this->template->FormUdalost = $udalost && $doc->stav == DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA;
        $this->template->AccessEdit = $AccessEdit;
        $this->template->LzePriraditCj = $permissions['assign_cj'];
        $this->template->LzePrevzit = $permissions['take_over'];
        $this->template->LzeZrusitPredani = $permissions['cancel_forwarding'];
        $this->template->LzePredatDokument = $doc->canUserForward();
        $this->template->LzeZnovuOtevrit = $doc->canUserReopen();

        if (!$permissions['view'])
            $this->setView('noaccess');
    }

    public function renderDetail($id)
    {
        $dokument = $this->template->Dok;

        $this->template->dokument_id = $id;
        $this->template->typy_dokumentu = TypDokumentu::vsechnyJakoTabulku();
        $this->template->dokument_nebo_spis = isset($dokument->spis) ? 'spis' : 'dokument';

        // Kontrola lhuty a skartace
        if ($dokument->lhuta_stav == 2 && $dokument->stav < 4) {
            $this->flashMessage('Vypršela lhůta k vyřízení! Vyřiďte neprodleně tento dokument.',
                    'warning');
        } else if ($dokument->lhuta_stav == 1 && $dokument->stav < 4) {
            $this->flashMessage('Za pár dní vyprší lhůta k vyřízení! Vyřiďte co nejrychleji tento dokument.');
        }

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $this->template->AccessEdit = false;
        }

        $Log = new LogModel();
        $historie = $Log->getDocumentsHistory($id, $tisk || $pdf);
        $this->template->historie = $historie;
    }

    public function renderDetailSpojeni($id)
    {
        // Napln promenne sablony daty
        $this->actionDetail($id);
    }

    public function createComponentBulkAction()
    {
        $BA = new Components\BulkAction();

        $actions = [
            'prevzit' => 'převzít',
            'predat_spisovna' => 'předat do spisovny',
            'tisk' => 'tisknout'
        ];

        $BA->setActions($actions);
        $BA->setCallback([$this, 'bulkAction']);

        return $BA;
    }

    public function bulkAction($action, $documents)
    {
        switch ($action) {
            case 'tisk':
                $this->redirect('this',
                        ['print' => 1,
                    'vybrane_dokumenty' => implode('-', $documents)]);

            /* Prevzeti vybranych dokumentu */
            case 'prevzit':
                $count_ok = $count_failed = 0;
                foreach ($documents as $dokument_id) {
                    $doc = new Document($dokument_id);
                    if ($doc->canUserTakeOver()) {
                        if ($doc->takeOver())
                            $count_ok++;
                        else
                            $count_failed++;
                    }
                }
                if ($count_ok > 0)
                    $this->flashMessage('Úspěšně jste převzal ' . $count_ok . ' dokumentů.');
                if ($count_failed > 0)
                    $this->flashMessage('U ' . $count_failed . ' dokumentů se nepodařilo převzít dokument!',
                            'warning');
                break;

            /* Predani vybranych dokumentu do spisovny  */
            case 'predat_spisovna':
                $count_ok = $count_failed = 0;
                foreach ($documents as $dokument_id) {
                    $doc = new DocumentWorkflow($dokument_id);
                    $stav = $doc->transferToSpisovna(false);
                    if ($stav === true) {
                        $count_ok++;
                    } else {
                        if (is_string($stav))
                            $this->flashMessage($stav, 'warning');
                        $count_failed++;
                    }
                }
                if ($count_ok > 0) {
                    $this->flashMessage('Úspěšně jste předal ' . $count_ok . ' dokumentů do spisovny.');
                }
                if ($count_failed > 0) {
                    $this->flashMessage($count_failed . ' dokumentů se nepodařilo předat do spisovny!',
                            'warning');
                }
                if ($count_ok > 0 && $count_failed > 0) {
                    $this->redirect('this');
                }
                break;
        }
    }

    public function actionPredatDoSpisovny($id)
    {
        $w = new DocumentWorkflow($id);
        $res = $w->transferToSpisovna(false);
        if ($res === true)
            $this->flashMessage('Dokument byl předán do spisovny.');
        else {
            if (is_string($res))
                $this->flashMessage($res, 'warning');
            $this->flashMessage('Dokument se nepodařilo předat do spisovny.');
        }
        $this->redirect('default');
    }

    public function actionPrevzit($id)
    {
        $doc = new Document($id);
        if ($doc->canUserTakeOver()) {
            $doc->takeOver();
            $this->flashMessage('Úspěšně jste si převzal tento dokument.');
        } else
            $this->flashMessage('Nemáte oprávnění k převzetí dokumentu.', 'warning');

        $this->redirect('detail', array('id' => $id));
    }

    public function renderPredatVyber($id)
    {
        $doc = new Document($id);
        $this->template->document_is_in_spis = (boolean) $doc->getSpis();
        $this->template->dokument_id = $id;

        $Zamestnanci = new Osoba();
        $this->template->user_list = $Zamestnanci->seznamOsobSUcty();

        $OrgJednotky = new OrgJednotka();
        $this->template->orgunit_list = $OrgJednotky->linearniSeznam();

        $this->template->called_from_spis = (boolean) $this->getParameter('from_spis');
    }

    public function actionPredat($id, $user = null, $orgunit = null, $note = null, $from_spis = false)
    {
        $doc = new Document($id);
        try {
            $doc->forward($user, $orgunit, $note);
            $this->flashMessage('Předání proběhlo v pořádku.');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }
        if (!$from_spis)
            $this->redirect('detail', $id);

        $this->redirect('Spisy:detail', $doc->getSpis()->id);
    }

    public function actionZrusitPredani($id)
    {
        $doc = new Document($id);
        if ($doc->canUserModify()) {
            if ($doc->cancelForwarding()) {
                $this->flashMessage('Zrušil jste předání dokumentu.');
            } else {
                $this->flashMessage('Zrušení předání se nepodařilo.', 'warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění ke zrušení předání dokumentu.', 'warning');
        }
        $this->redirect('detail', array('id' => $id));
    }

    public function actionOdmitnoutPrevzeti($id)
    {
        $doc = new Document($id);
        if ($doc->canUserTakeOver()) {
            if ($doc->reject()) {
                $this->flashMessage('Odmítl jste převzetí dokumentu.');
                $this->redirect('default');
            } else {
                $this->flashMessage('Odmítnutí převzetí se nepodařilo. Zkuste to znovu.',
                        'warning');
            }
        } else {
            $this->flashMessage('Nemůžete odmítnout převzetí dokumentu, který Vám nebyl předán.',
                    'warning');
        }

        $this->redirect('detail', array('id' => $id));
    }

    public function actionKvyrizeni($id)
    {
        $workflow = new DocumentWorkflow($id);
        if (!$workflow->canUserModify())
            $this->flashMessage('Nemáte oprávnění označit dokument k vyřízení.', 'warning');
        else {
            if ($workflow->markForProcessing())
                $this->flashMessage('Převzal jste tento dokument k vyřízení.');
            else
                $this->flashMessage('Označení dokumentu k vyřízení se nepodařilo.', 'warning');
        }

        $this->redirect('detail', $id);
    }

    public function actionVyrizeno($id)
    {
        $dokument_id = $id;

        $workflow = new DocumentWorkflow($id);
        if (!$workflow->canUserModify())
            $this->flashMessage('Nemáte oprávnění označit dokument za vyřízený.', 'warning');
        else {
            $ret = $workflow->close();
            if ($ret === true) {
                // automaticka udalost
                $this->flashMessage('Označil jste tento dokument za vyřízený.');
            } else if ($ret === "udalost") {
                // manualni udalost
                $this->flashMessage('Označil jste tento dokument za vyřízený.');
                $this->redirect('detail', ['id' => $dokument_id, 'udalost' => 1]);
            } else {
                foreach ($ret as $message)
                    $this->flashMessage($message, 'warning');

                $this->flashMessage('Označení dokumentu za vyřízený se nepodařilo. Zkuste to znovu.',
                        'warning');
            }
        }

        $this->redirect('detail', $id);
    }

    public function renderCjednaci($id, $evidence = 0)
    {
        $this->template->dokument_id = $id;
        $this->template->evidence = $evidence;
    }

    // tato metoda slouží pouze pro sběrný arch
    public function actionVlozitdosbernehoarchu($id, $vlozit_do)
    {
        try {
            if ($this->typ_evidence != 'sberny_arch')
                throw new \Exception("operace je platná pouze u typu evidence sběrný arch");

            $dokument_id = $id;
            $iniciacni_dokument_id = $vlozit_do;

            $Dokument = new Dokument();

            // getBasicInfo neháže výjimku, pokud dokument neexistuje
            // nasledujici prikaz pouze overi, ze dokument_id existuje
            new Document($dokument_id);
            // predpoklad - dok2 je iniciacni dokument spisu
            $dok2 = new Document($iniciacni_dokument_id);

            if ($dok2->poradi != 1)
                throw new \Exception("neplatný parametr");

            // spojit s dokumentem
            $poradi = $Dokument->getMaxPoradi($dok2->cislo_jednaci_id);

            $cislo_jednaci = $dok2->cislo_jednaci;
            $data = array();
            $data['cislo_jednaci_id'] = $dok2->cislo_jednaci_id;
            $data['cislo_jednaci'] = $cislo_jednaci;
            $data['poradi'] = $poradi;
            $data['podaci_denik'] = $dok2->podaci_denik;
            $data['podaci_denik_poradi'] = $dok2->podaci_denik_poradi;
            $data['podaci_denik_rok'] = $dok2->podaci_denik_rok;

            // predpoklad - spis musi existovat, jinak je neco hodne spatne
            $Spis = new SpisModel();
            $spis = $Spis->findByName($cislo_jednaci);
            if (!$spis)
                throw new \Exception("chyba integrity dat. Spis '$cislo_jednaci' neexistuje.");

            $Dokument->update($data, array(array('id = %i', $dokument_id)));

            // pripojime
            $spis = new Spis($spis->id);
            $doc = new Document($dokument_id);
            $doc->insertIntoSpis($spis);

            // zaznam do logu az nakonec, kdyz jsou vsechny operace uspesne
            $Log = new LogModel();
            $Log->logDocument($dokument_id, LogModel::DOK_UNDEFINED,
                    'Dokument připojen do evidence. Přiděleno číslo jednací: ' . $cislo_jednaci);

            echo '###zaevidovano###' . $this->link('detail', array('id' => $dokument_id));
        } catch (Exception $e) {
            echo __METHOD__ . "() - " . $e->getMessage();
        }

        $this->terminate();
    }

    public function actionPridelitcj()
    {
        $dokument_id = $this->getParameter('id', null);
        $cjednaci_id = $this->getParameter('cislo_jednaci_id', null);

        $Dokument = new Dokument();
        $dokument_info = $Dokument->getInfo($dokument_id);
        if (empty($dokument_info))
            throw new \Exception("Přidělení č.j. - nemohu načíst dokument id $dokument_id.");

        // Je treba zkontrolovat, jestli dokument uz cislo jednaci nema prideleno
        if (!empty($dokument_info['cislo_jednaci_id'])) {
            // throw new \Exception("Dokument má již č.j. přiděleno.");
            $this->flashMessage('Dokument má již číslo jednací přiděleno.', 'error');
            $this->redirect('detail', array('id' => $dokument_id));
        }

        $CJ = new CisloJednaci();

        if (!empty($cjednaci_id)) {
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

        $dokument = $Dokument->update($data, array(array('id=%i', $dokument_id))); //   array('dokument_id'=>0);// $Dokument->ulozit($data);
        if ($dokument) {

            $this->flashMessage('Číslo jednací přiděleno.');

            $Log = new LogModel();
            $Log->logDocument($dokument_id, LogModel::DOK_UNDEFINED,
                    'Přiděleno číslo jednací: ' . $cjednaci->cislo_jednaci);

            if ($this->typ_evidence == 'sberny_arch') {
                $Spis = new SpisModel();
                $spis = $Spis->findByName($cjednaci->cislo_jednaci);
                if (!$spis) {
                    // vytvorime spis
                    $spis_new = array(
                        'nazev' => $cjednaci->cislo_jednaci,
                        'popis' => '',
                        'typ' => 'S',
                    );
                    $spis_id = $Spis->vytvorit($spis_new);
                } else
                    $spis_id = $spis->id;

                $doc = new Document($dokument_id);
                $spis = new Spis($spis_id);
                $doc->insertIntoSpis($spis);
            }
        }

        $this->redirect('detail', array('id' => $dokument_id));
    }

    public function renderNovy()
    {
        if (!$this->user->isAllowed('Dokument', 'vytvorit')) {
            $this->flashMessage('Vytváření nových dokumentů je zakázáno', 'warning');
            $this->redirect('default');
        }
        
        $Dokumenty = new Dokument();
        $cisty = $this->getParameter('cisty', false);
        if ($cisty) {
            $Dokumenty->odstranit_rozepsane();
            $this->redirect('novy');
        }

        $args_rozd = array();
        $args_rozd['where'] = array(
            array('stav = %i', 0),
            array('user_created = %i', $this->user->id),
        );

        $args_rozd['order'] = array('date_created' => 'DESC');

        $this->template->Typ_evidence = $this->typ_evidence;

        $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);

        if ($rozdelany_dokument) {
            $dokument = reset($rozdelany_dokument);
            // Oprava Task #254
            $dokument = new Document($dokument->id);

            $this->flashMessage('Byl detekován a načten rozepsaný dokument.<p>Pokud chcete založit úplně nový dokument, klikněte na následující odkaz. <a href="' . $this->link('novy',
                            array('cisty' => 1)) . '">Vytvořit nový nerozepsaný dokument.</a></p>',
                    'info_ext');

            $DokumentPrilohy = new DokumentPrilohy();

            $this->template->Subjekty = $dokument->getSubjects();

            $prilohy = $DokumentPrilohy->prilohy($dokument->id);
            $this->template->Prilohy = $prilohy;

            if ($this->typ_evidence == 'priorace') {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument->id);
            }
        } else {
            if ($this->user->inheritsFromRole('podatelna')) {
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
            );
            $dokument_id = $Dokumenty->vytvorit($pred_priprava);
            $dokument = $Dokumenty->getInfo($dokument_id);

            $this->template->Spisy = null;
            $this->template->Subjekty = null;
            $this->template->Prilohy = null;
            $this->template->SouvisejiciDokumenty = null;
        }

        $this->template->Prideleno = $this->user->displayName;

        $CJ = new CisloJednaci();
        $this->template->cjednaci = $CJ->generuj();

        $this->template->typy_dokumentu = TypDokumentu::vsechnyJakoTabulku();

        if ($dokument) {
            $this->template->Dok = $dokument;
            $this->template->dokument_id = $dokument->id;
        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument není připraven k vytvoření', 'warning');
        }

        $this->template->form_name = 'novyForm';
    }

    public function renderOdpoved($id)
    {
        if (!$this->user->isAllowed('Dokument', 'vytvorit')) {
            $this->flashMessage('Vytváření nových dokumentů je zakázáno', 'warning');
            $this->redirect('default');
        }
        
        $document_id = $id;
        $doc = new Document($document_id);
        $Dokumenty = new Dokument();

        $args_rozd = array();
        $args_rozd['where'] = ['stav = 0',
            'dokument_typ_id = 2',
            ['cislo_jednaci = %s', $doc->cislo_jednaci],
            "user_created = {$this->user->id}"
        ];
        $args_rozd['order'] = array('date_created' => 'DESC');

        $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);
        if ($rozdelany_dokument) {
            // odpoved jiz existuje, tak ji nacteme
            $reply = reset($rozdelany_dokument);
            $reply = new Document($reply->id);

            $DokumentPrilohy = new DokumentPrilohy();

            $this->template->Subjekty = $reply->getSubjects();

            $prilohy = $DokumentPrilohy->prilohy($reply->id);
            $this->template->Prilohy = $prilohy;

            $this->template->Prideleno = $this->user->displayName;

            $CJ = new CisloJednaci();
            $this->template->Typ_evidence = $this->typ_evidence;
            if ($this->typ_evidence == 'priorace') {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($reply->id);
            } else if ($this->typ_evidence == 'sberny_arch') {
                // sberny arch
                //$dok_odpoved->poradi = $dok_odpoved->poradi;
            }

            $this->template->cjednaci = $CJ->nacti($doc->cislo_jednaci_id);

            $this->template->Dok = $reply;
        } else {
            // totozna odpoved neexistuje
            // nalezeni nejvyssiho cisla poradi v ramci spisu
            $poradi = $Dokumenty->getMaxPoradi($doc->cislo_jednaci_id);

            $pred_priprava = array(
                "nazev" => $doc->nazev,
                "popis" => $doc->popis,
                "stav" => 0,
                "dokument_typ_id" => 2,
                "zpusob_doruceni_id" => null,
                "cislo_jednaci_id" => $doc->cislo_jednaci_id,
                "cislo_jednaci" => $doc->cislo_jednaci,
                "podaci_denik" => $doc->podaci_denik,
                "podaci_denik_poradi" => $doc->podaci_denik_poradi,
                "podaci_denik_rok" => $doc->podaci_denik_rok,
                "poradi" => ($poradi),
                "cislo_jednaci_odesilatele" => $doc->cislo_jednaci_odesilatele,
                "datum_vzniku" => date('Y-m-d H:i:s'),
                "lhuta" => "30",
                "poznamka" => $doc->poznamka,
                "spisovy_znak_id" => $doc->spisovy_znak_id,
                "skartacni_znak" => $doc->skartacni_znak,
                "skartacni_lhuta" => $doc->skartacni_lhuta,
                "spousteci_udalost_id" => $doc->spousteci_udalost_id
            );
            $odpoved_id = $Dokumenty->vytvorit($pred_priprava);
            $reply = new Document($odpoved_id);

            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentPrilohy = new DokumentPrilohy();

            // kopirovani subjektu
            $subjekty_old = $doc->getSubjects();
            if (count($subjekty_old) > 0) {
                foreach ($subjekty_old as $subjekt) {
                    $rezim = $subjekt->rezim_subjektu;
                    if ($rezim == 'O')
                        $rezim = 'A';
                    else if ($rezim == 'A')
                        $rezim = 'O';
                    $DokumentSubjekt->pripojit($reply, new Subject($subjekt->id), $rezim);
                }
            }
            $this->template->Subjekty = $reply->getSubjects();

            // kopirovani prilohy
            $prilohy_old = $DokumentPrilohy->prilohy($document_id);

            if (count($prilohy_old) > 0) {
                foreach ($prilohy_old as $priloha) {
                    $DokumentPrilohy->pripojit($reply->id, $priloha->id);
                }
            }
            $prilohy_new = $DokumentPrilohy->prilohy($reply->id);
            $this->template->Prilohy = $prilohy_new;

            $this->template->Prideleno = $this->user->displayName;

            $CJ = new CisloJednaci();
            $this->template->Typ_evidence = $this->typ_evidence;
            $this->template->SouvisejiciDokumenty = null;
            if ($this->typ_evidence == 'priorace') {
                // priorace - Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $Souvisejici->spojit($reply->id, $document_id);
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($reply->id);
            } else if ($this->typ_evidence == 'sberny_arch') {
                // sberny arch
                //$dok_odpoved->poradi = $dok_odpoved->poradi;
            }

            $this->template->cjednaci = $CJ->nacti($doc->cislo_jednaci_id);
            $this->template->Dok = $reply;
        }

        $this->odpoved = true;
        $this->template->odpoved_na_dokument = true;
        $this->template->dokument_id = $this->template->Dok->id;

        $this->template->typy_dokumentu = TypDokumentu::vsechnyJakoTabulku();

        $this->template->form_name = 'odpovedForm';
    }

    public function renderDownload($id, $file)
    {
        $file_id = $file;

        $doc = new Document($id);
        $perm = $doc->getUserPermissions();
        $DokumentPrilohy = new DokumentPrilohy();
        $prilohy = $DokumentPrilohy->prilohy($id);
        if (!$perm['view'] || !array_key_exists($file_id, $prilohy)) {
            $this->flashMessage('Přístup zamítnut.', 'warning');
            $this->redirect('default');
        }

        $res = $this->storage->download($file_id);
        if ($res == 0)
            $this->terminate();

        // not found
        $this->flashMessage('Požadovaný soubor nenalezen v úložišti.', 'warning');
        $this->redirect('detail', $id);
    }

    public function renderHistorie($id)
    {
        $Log = new LogModel();
        $historie = $Log->getDocumentsHistory($id);
        $this->template->historie = $historie;
        $this->template->kompletni_historie = true;
        $this->setView('detail-historie');
    }

    public function actionOdeslat($id)
    {
        $dokument_id = $id;
        $Dokument = new Dokument();
        $dokument = $Dokument->getInfo($dokument_id, "subjekty,soubory,odeslani");

        if (!$dokument) {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('neexistuje');
            return;
        }

        // Neprováděj prozatím žádné kontroly. Nechceme, aby se uživateli zobrazil příkaz "odeslat dokument" a po kliknutí obdržel chybové hlášení.
        // Kontrola se provede jen v detailu dokumentu, kde se rozhodne, zda se uživateli příkaz zobrazí nebo ne.
        // Kód určující oprávnění v akci "detail" bude muset být zcela přepsán.
        $UzivatelOpravnen = true;

        if (!$UzivatelOpravnen) {
            $this->flashMessage('Nejste oprávněn odeslat tento dokument.', 'error');
            $this->redirect('detail', ['id' => $dokument_id]);
        }

        $this->template->Dok = $dokument;
        $this->template->VyzadatIsdsHeslo = Settings::get(Admin_EpodatelnaPresenter::ISDS_INDIVIDUAL_LOGIN,
                        false) && empty(UserSettings::get('isds_password'));

        $sznacka = "";
        if (isset($this->template->Dok->spisy) && is_array($this->template->Dok->spisy)) {
            $sznacka_A = array();
            foreach ($this->template->Dok->spisy as $spis) {
                $sznacka_A[] = $spis->nazev;
            }
            $sznacka = implode(", ", $sznacka_A);
        }
        $this->template->SpisovaZnacka = $sznacka;
    }

    public function renderOdeslat($id)
    {
        $dokument = $this->template->Dok;

        $max_vars = ini_get('max_input_vars');
        $safe_recipient_count = floor(($max_vars - 10) / 17);
        $recipient_count = 0;
        if ($dokument->subjekty)
            foreach ($dokument->subjekty as $subjekt)
                if ($subjekt->rezim_subjektu != 'O')
                    $recipient_count++;
        if ($recipient_count > $safe_recipient_count) {
            $this->flashMessage("Dokument má příliš mnoho adresátů a není možné jej odeslat. Maximální počet adresátů je $safe_recipient_count.",
                    'warning');
            $this->flashMessage("Limit je ovlivněn PHP nastavením \"max_input_vars\" na serveru.");
            $this->redirect('detail', array('id' => $id));
        }

        // Prilohy
        $prilohy_celkem = 0;
        if (count($dokument->prilohy) > 0) {
            foreach ($dokument->prilohy as $p) {
                $prilohy_celkem = $prilohy_celkem + $p->size;
            }
        }
        $this->template->PrilohyCelkovaVelikost = $prilohy_celkem;

        $this->template->OpravnenOdeslatDZ = $this->user->isAllowed('DatovaSchranka',
                'odesilani');

        $this->template->ZpusobyOdeslani = ZpusobOdeslani::getZpusoby();
    }

    protected function createComponentNovyForm()
    {
        $form = $this->createNovyOrOdpovedForm();

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->addRule(Nette\Forms\Form::FILLED, 'Lhůta k vyřízení musí být vyplněna!')
                ->addRule(Nette\Forms\Form::NUMERIC, 'Lhůta k vyřízení musí být číslo')
                ->setValue('30')
                ->setOption('description', 'dní');
        $form->addTextArea('predani_poznamka', 'Poznámka pro příjemce:', 80, 3);
        $form->addHidden('predano_user');
        $form->addHidden('predano_org');

        $form['zpusob_doruceni_id']->setDefaultValue(5); // v listinné podobě

        $form->addSubmit('novy_pridat', 'Vytvořit dokument a založit nový');
        $form['novy_pridat']->onClick[] = array($this, 'vytvoritClicked');

        return $form;
    }

    protected function createComponentOdpovedForm()
    {
        $form = $this->createNovyOrOdpovedForm();

        // Task #443 - Vytváření odpovědi umožňuje chybně typ dokumentu "příchozí"
        $items = $form['dokument_typ_id']->items;
        $all_types = TypDokumentu::vsechnyJakoTabulku();
        foreach (array_keys($items) as $id) {
            if ($all_types[$id]->smer == 0)
                unset($items[$id]); // odstraň příchozí typy dokumentu ze seznamu
        }
        $form['dokument_typ_id']->setItems($items);

        return $form;
    }

    protected function createNovyOrOdpovedForm()
    {
        $dok = null;
        if (isset($this->template->Dok)) {
            $dokument_id = isset($this->template->Dok->id) ? $this->template->Dok->id : 0;
            $dok = $this->template->Dok;
        } else {
            $dokument_id = 0;
        }

        $povolene_typy_dokumentu = TypDokumentu::dostupneUzivateli();

        $zpusob_doruceni = Dokument::zpusobDoruceni(2);

        $form = new Form();
        $form->addHidden('id')
                ->setValue($dokument_id);
        $form->addHidden('odpoved')
                ->setValue($this->odpoved === true ? 1 : 0);

        $form->addText('nazev', 'Věc:', 80, 250)
                ->setValue(@$dok->nazev);
        if (!$this->user->inheritsFromRole('podatelna')) {
            $form['nazev']->addRule(Nette\Forms\Form::FILLED,
                    'Název dokumentu (věc) musí být vyplněno!');
        }

        $form->addTextArea('popis', 'Popis:', 80, 3)
                ->setValue(@$dok->popis);

        $form->addSelect('dokument_typ_id', 'Typ dokumentu:', $povolene_typy_dokumentu);
        try {
            // TODO - problém, pokud je $dok entita a ne pole vrácené metodou getInfo(),
            // tak pole "typ_dokumentu" není definované a dojde k výjimce
            $form['dokument_typ_id']->setValue(@$dok->typ_dokumentu->id);
        } catch (\Exception $e) {
            $e->getMessage();
            // ignoruj chybu - uživatel má chybně nastavený číselník
        }

        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$dok->cislo_jednaci_odesilatele);

        $datum = date('d.m.Y');
        $cas = date('H:i:s');

        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:')
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);

        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:', $zpusob_doruceni);

        $form->addText('cislo_doporuceneho_dopisu', 'Číslo doporučeného dopisu:', 50, 50)
                ->setValue(@$dok->cislo_doporuceneho_dopisu);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet listů musí být číslo.');
        $form->addText('pocet_listu_priloh', 'Počet listů příloh:', 5, 10)
                ->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet musí být číslo.');
        $form->addText('nelistinne_prilohy', 'Počet a druh příloh v nelistinné podobě:', 20, 50);


        $form->addSubmit('novy', 'Vytvořit dokument');
        $form['novy']->onClick[] = array($this, 'vytvoritClicked');

        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoVytvoritClicked');

        return $form;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $data['id'];
        $data['stav'] = 1;

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] . " " . $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        $doc = new Document($dokument_id);
        if ($doc->stav != 0) {
            $this->flashMessage('Kontrola platnosti selhala:' . "Dokument ID $dokument_id je již vytvořen.",
                    'warning');
            $this->redirect('detail', $dokument_id);
        }

        try {
            dibi::begin();

            // Poznamka: c.j. se v pripade noveho dokumentu generuje az na pokyn uzivatele
            // a u odpovedi jsou sloupce c.j. vyplneny uz pri vytvareni odpovedi
            $dd = clone $data; // document data
            unset($dd['id'], $dd['odpoved'], $dd['predano_user'], $dd['predano_org'],
                    $dd['predani_poznamka']);
            $doc->modify($dd);
            $doc->save();

            $Log = new LogModel();
            $Log->logDocument($dokument_id, LogModel::DOK_NOVY);

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            $this->flashMessage('Dokument se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('Chyba: ' . $e->getMessage(), 'warning');
            $this->redirect('default');
        }

        if ($data['odpoved'] == 1) {
            $this->flashMessage('Odpověď byla vytvořena.');

            // ID dokumentu, na který vytváříme odpověď, je v URL
            try {
                $orig_id = $this->getParameter('id');
                $orig = new Document($orig_id);
                if ($spis = $orig->getSpis()) {
                    // zařaď odpověď do stejného spisu
                    $doc->insertIntoSpis($spis);
                }
            } catch (\Exception $e) {
                $this->flashMessage('Zařazení odpovědi do spisu se nepodařilo.', 'warning');
            }

            $this->forward('kvyrizeni', array('id' => $dokument_id));
        } else {
            $this->flashMessage('Dokument byl vytvořen.');

            if (!empty($data['predano_user']) || !empty($data['predano_org'])) {
                /* Predat dokument. Musime osetrit vstup z formulare! */
                if (empty($data['predano_user']))
                    $data['predano_user'] = null;
                if (empty($data['predano_org']))
                    $data['predano_org'] = null;
                try {
                    $doc->forward($data['predano_user'], $data['predano_org'],
                            $data['predani_poznamka']);
                    $this->flashMessage('Dokument předán zaměstnanci nebo organizační jednotce.');
                } catch (Exception $e) {
                    $this->flashMessage('Předání dokumentu se nepodařilo.', 'warning');
                    $this->flashMessage('Chyba: ' . $e->getMessage(), 'warning');
                }
            }

            $name = $button->getName();
            if ($name == "novy_pridat")
                $this->redirect('novy');
            else
                $this->redirect('detail', array('id' => $dokument_id));
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = isset($data['id']) ? $data['id'] : $this->getParameter('id');
        $this->redirect('detail', array('id' => $dokument_id));
    }

    public function stornoVytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['id'];
        if ($dokument_id) {
            $model = new Dokument();
            $where = [['id = %i', $dokument_id]];
            $dok = $model->select($where)->fetch();
            // ochrana proti útočníkovi
            if ($dok && $dok->stav === 0)
                $model->delete($where);
        }
        $this->redirect('default');
    }

    protected function createComponentMetadataForm()
    {
        $Dok = $this->template->Dok;

        $form = new Form();

        $form->addText('nazev', 'Věc:', 80, 250);
        if (!$this->user->inheritsFromRole('podatelna'))
            $form['nazev']->addRule(Nette\Forms\Form::FILLED,
                    'Název dokumentu (věc) musí být vyplněn.');

        $form->addTextArea('popis', 'Popis:', 80, 3);

        $povolene_typy_dokumentu = TypDokumentu::dostupneUzivateli();

        $lze_menit_urcita_pole = $Dok->stav == 1 || $this->user->isInRole('superadmin');
        if ($lze_menit_urcita_pole && in_array($Dok->typ_dokumentu->id,
                        array_keys($povolene_typy_dokumentu)) && count($povolene_typy_dokumentu) > 1) {
            $form->addSelect('dokument_typ_id', 'Typ Dokumentu:', $povolene_typy_dokumentu);
        }

        if (Settings::get('spisovka_allow_change_creation_date')) {
            $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:');
            $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15);
        }

        // doručení e-mailem a DS nastavuje systém, to uživatel nesmí měnit
        if ($lze_menit_urcita_pole && $Dok->typ_dokumentu->smer == 0 && !in_array($Dok->zpusob_doruceni_id,
                        [1, 2])) {
            $zpusob_doruceni = Dokument::zpusobDoruceni(2);
            $zpusob_doruceni[0] = '(není zadán)';
            ksort($zpusob_doruceni);
            $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:', $zpusob_doruceni);
        }

        if ($Dok->typ_dokumentu->smer == 0) {
            $form->addText('cislo_doporuceneho_dopisu', 'Číslo doporučeného dopisu:', 50, 50);
            $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50);
        }

        if (!empty($Dok->poznamka))
            $form->addTextArea('poznamka', 'Poznámka:', 80, 6);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet listů musí být číslo.');
        $form->addText('pocet_listu_priloh', 'Počet listů příloh:', 5, 10)
                ->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet musí být číslo.');
        $form->addText('nelistinne_prilohy', 'Počet a druh příloh v nelistinné podobě:', 20, 50);


        if (isset($form['dokument_typ_id']))
            $form['dokument_typ_id']->setDefaultValue($Dok->typ_dokumentu->id);
        $form['nazev']->setDefaultValue($Dok->nazev);
        $form['popis']->setDefaultValue($Dok->popis);
        if (isset($form['datum_vzniku'])) {
            $d = new \DateTime($Dok->datum_vzniku);
            $cas = $d->format('H:i:s');
            $form['datum_vzniku']->setDefaultValue($Dok->datum_vzniku);
            $form['datum_vzniku_cas']->setDefaultValue($cas);
        }
        if (isset($form['zpusob_doruceni_id']))
            $form['zpusob_doruceni_id']->setDefaultValue($Dok->zpusob_doruceni_id);
        if (isset($form['cislo_doporuceneho_dopisu'])) {
            $form['cislo_doporuceneho_dopisu']->setDefaultValue($Dok->cislo_doporuceneho_dopisu);
            $form['cislo_jednaci_odesilatele']->setDefaultValue($Dok->cislo_jednaci_odesilatele);
        }
        if (isset($form['poznamka']))
            $form['poznamka']->setDefaultValue($Dok->poznamka);
        $form['pocet_listu']->setDefaultValue($Dok->pocet_listu);
        $form['pocet_listu_priloh']->setDefaultValue($Dok->pocet_listu_priloh);
        $form['nelistinne_prilohy']->setDefaultValue($Dok->nelistinne_prilohy);

        $submit = $form->addSubmit('upravit', 'Uložit');
        $submit->onClick[] = array($this, 'upravitMetadataClicked');

        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitMetadataClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        // V aplikaci chybi \DateTimePicker
        if (isset($data['datum_vzniku'])) {
            $data['datum_vzniku'] = $data['datum_vzniku'] . " " . $data['datum_vzniku_cas'];
            unset($data['datum_vzniku_cas']);
        }

        try {
            $id = $this->getParameter('id');
            $doc = new Document($id);
            $doc->modify($data);
            $doc->save();

            $Log = new LogModel();
            $Log->logDocument($id, LogModel::DOK_ZMENEN, 'Upravena metadata dokumentu.');

            $this->flashMessage('Dokument "' . $data->nazev . '"  byl upraven.');
        } catch (Exception $e) {
            $this->flashMessage('Dokument "' . $data->nazev . '" se nepodařilo upravit.',
                    'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }

        $this->redirect('detail', ['id' => $id]);
    }

    protected function createComponentVyrizovaniForm()
    {
        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(1);

        $SpisovyZnak = new SpisovyZnak();
        $spousteci_udalost = $SpisovyZnak->spousteci_udalost(null, 1);

        $Dok = $this->template->Dok;

        $form = new Form();

        $form->addSelect('zpusob_vyrizeni_id', 'Způsob vyřízení:', $zpusob_vyrizeni)
                ->setValue(@$Dok->zpusob_vyrizeni_id);

        $unixtime = strtotime(@$Dok->datum_vyrizeni);
        if ($unixtime == 0) {
            $datum = date('d.m.Y');
            $cas = date('H:i:s');
        } else {
            $datum = date('d.m.Y', $unixtime);
            $cas = date('H:i:s', $unixtime);
        }

        $form->addDatePicker('datum_vyrizeni', 'Datum vyřízení:')
                ->setValue($datum);
        $form->addText('datum_vyrizeni_cas', 'Čas vyřízení:', 10, 15)
                ->setValue($cas);

        $form->addComponent(new Components\SpisovyZnakComponent(), 'spisovy_znak_id');
        $form->getComponent('spisovy_znak_id')
//                ->setRequired()
                ->setValue(@$Dok->spisovy_znak_id);

        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6)
                ->setValue(@$Dok->ulozeni_dokumentu);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 6)
                ->setValue(@$Dok->poznamka_vyrizeni);

        $form->addText('skartacni_znak', 'Skartační znak: ', 3, 3)
//                        ->setRequired('Vyberte platný spisový znak.')
                        ->setValue(@$Dok->skartacni_znak)
                ->controlPrototype->readonly = TRUE;
        $form->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
//                        ->setRequired('Vyberte platný spisový znak.')
                        ->setValue(@$Dok->skartacni_lhuta)
                ->controlPrototype->readonly = TRUE;

        $form->addSelect('spousteci_udalost_id', 'Spouštěcí událost: ', $spousteci_udalost);
        if (!empty($Dok->spousteci_udalost_id))
            $form['spousteci_udalost_id']->setDefaultValue($Dok->spousteci_udalost_id);
        else {
            $default_event = StartEvent::getDefault();
            if ($default_event)
                $form['spousteci_udalost_id']->setDefaultValue($default_event->id);
        }

        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_listu)->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet listů musí být číslo');
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_priloh)->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet příloh musí být číslo');
        $form->addText('vyrizeni_typ_prilohy', 'Typ přílohy:', 20, 50)
                ->setValue(@$Dok->vyrizeni_typ_prilohy);

        $form->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'upravitVyrizeniClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitVyrizeniClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        // uprava casu
        $data['datum_vyrizeni'] = $data['datum_vyrizeni'] . " " . $data['datum_vyrizeni_cas'];
        unset($data['datum_vyrizeni_cas']);

        try {
            $id = $this->getParameter('id');
            $doc = new Document($id);
            $doc->modify($data);
            $doc->save();

            $Log = new LogModel();
            $Log->logDocument($id, LogModel::DOK_ZMENEN, 'Upravena data vyřízení.');

            $this->flashMessage('Dokument "' . $doc->cislo_jednaci . '"  byl upraven.');
        } catch (DibiException $e) {
            $this->flashMessage('Dokument se nepodařilo upravit.', 'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
        $this->redirect('detail', array('id' => $id));
    }

    protected function createComponentUdalostForm()
    {
        $form = new Form();

        $options = array(
            '1' => 'Dnešní den',
            '2' => 'Zadám datum',
            '3' => 'Datum určím až v budoucnu',
        );
        $form->addRadioList('udalost_typ', 'Určete rozhodný okamžik:', $options)
                ->setHtmlId('radio-udalost-typ')
                ->setValue(1)
        ->controlPrototype->onclick("onChangeRadioButtonSpousteciUdalost();");
        $form['udalost_typ']->generateId = true;    // Nette 2.3

        $form->addDatePicker('datum_spousteci_udalosti', 'Datum spouštěcí události:')
                //->setDisabled() - nelze volat pri zpracovani odeslaneho formulare, vyresil jsem tedy v Javascriptu
                ->forbidPastDates()
                ->addConditionOn($form['udalost_typ'], Form::EQUAL, 2)
                ->addRule(Form::FILLED, 'Nebylo zadáno datum spuštění.');

        $form->addSubmit('ok', 'Potvrdit')
                ->onClick[] = array($this, 'udalostClicked');

        return $form;
    }

    public function udalostClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $this->getParameter('id');
        $datum = null;

        $doc = new DocumentWorkflow($dokument_id);
        if ($doc->canUserModify() && $doc->stav == DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA) {

            switch ($data['udalost_typ']) {
                case 1 :
                    $datum = date('Y-m-d');
                    $zprava = 'Událost byla spuštěna.';
                    break;

                case 2 :
                    $datum = $data['datum_spousteci_udalosti'];
                    $zprava = 'Datum spuštění bylo nastaveno.';
                    break;

                case 3 :
                // nedelej nic
            }

            if (!empty($datum)) {
                $doc->setStartDate($datum);
                $this->flashMessage($zprava, 'info');
            }
        } else
            $this->flashMessage('Nemáte oprávnění spustit událost.', 'warning');

        $this->redirect('detail', ['id' => $dokument_id]);
    }

    /**
     * Vytvoří část formuláře pro odeslání dokumentu. Jedinné, co nyní ošetřuje
     * Nette framework je část odeslání poštou.
     * @return Form
     */
    protected function createComponentOdeslatForm()
    {
        $Dok = $this->template->Dok;

        // odesilatele
        $ep = (new ConfigEpodatelna())->get();
        $odes = $ep['odeslani'];
        $adresa = empty($odes['jmeno']) ? $odes['email'] : "{$odes['jmeno']} <{$odes['email']}>";
        $odesilatele = ['system' => "$adresa [společná e-mailová adresa]"];

        $person = Person::fromUserId($this->user->id);
        if (!empty($person->email)) {
            $key = "user#" . Osoba::displayName($person, 'basic') . "#" . $person->email;
            $odesilatele[$key] = Osoba::displayName($person, 'basic') . " <" . $person->email . "> [zaměstnanec]";
        }

        $form = new Form();

        if (!empty($Dok->subjekty))
            foreach ($Dok->subjekty as $sid => $subjekt) {
                if ($subjekt->rezim_subjektu == 'O')
                    continue;

                $form->addDatePicker("datum_odeslani_postou_$sid", 'Datum odeslání:')
                        ->setRequired()
                        ->setDefaultValue("now")
                        ->forbidPastDates();

                // vytvoří novou instanci pro každý subjekt
                $form->addComponent(new Controls\VyberPostovniZasilkyControl(),
                        "druh_zasilky_$sid");
                $form["druh_zasilky_$sid"]->setRequired()
                        ->setDefaultValue([DruhZasilky::OBYCEJNE]);
                $form->addFloat("cena_zasilky_$sid", 'Cena:', 10)
                        ->setOption('description', 'Kč');
                $form->addFloat("hmotnost_zasilky_$sid", 'Hmotnost:', 10)
                        ->setOption('description', 'kg');
                $form->addText("poznamka_$sid", 'Poznámka:');

                // faxem
                $form->addDatePicker("datum_odeslani_faxu_$sid", 'Datum odeslání:')
                        ->setRequired()
                        ->setDefaultValue("now")
                        ->forbidPastDates();
                $form->addText("cislo_faxu_$sid", 'Číslo faxu:', 20);
                $form->addTextArea("zprava_faxu_$sid", 'Zpráva pro příjemce:', 80, 5);

                // e-mailem
                if (count($odesilatele)) {
                    $form->addSelect("email_from_$sid", 'Odesílatel:', $odesilatele)
                            ->setRequired();
                    $form->addText("email_predmet_$sid", 'Předmět zprávy:', 80)
                            ->setRequired()
                            ->setDefaultValue($Dok->nazev);
                    $form->addTextArea("email_text_$sid", 'Text zprávy:', 80, 10);
                }

                // isds
                if (!empty($subjekt->id_isds)) {
                    if ($this->template->VyzadatIsdsHeslo)
                        $form->addPassword("isds_heslo_$sid", 'Heslo do datové schránky:', 20);

                    $form->addText("isds_predmet_$sid", 'Předmět zprávy:', 80)
                            ->setRequired()
                            ->setDefaultValue($Dok->nazev);
                    $form->addText("isds_cjednaci_odes_$sid", 'Číslo jednací odesílatele:', 50)
                            ->setDefaultValue($Dok->cislo_jednaci);
                    $form->addText("isds_spis_odes_$sid", 'Spisová značka odesílatele:', 50)
                            ->setDefaultValue($this->template->SpisovaZnacka);
                    $form->addText("isds_cjednaci_adres_$sid", 'Číslo jednací adresáta:', 50)
                            ->setDefaultValue($Dok->cislo_jednaci_odesilatele);
                    $form->addText("isds_spis_adres_$sid", 'Spisová značka adresáta:', 50);

                    $form->addCheckbox("isds_dvr_$sid", 'Do vlastních rukou?');
                    $form->addCheckbox("isds_fikce_$sid", 'Doručit fikcí?')
                            ->setDefaultValue(true);
                }
            }

        $form->addSubmit('odeslat', 'Předat podatelně či Odeslat')
                ->onClick[] = array($this, 'odeslatClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function odeslatClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $this->getParameter('id');
        $Dokument = new Dokument();
        $File = new FileModel();

        // nejprve ověř, že dokument existuje
        $doc = $Dokument->getInfo($dokument_id);
        if (!$doc) {
            $this->flashMessage('Nemohu načíst dokument. Dokument nebude odeslán.', 'warning');
            $this->redirect('default');
        }

        $post_data = $this->getHttpRequest()->getPost();

        $prilohy = array();
        if (isset($post_data['prilohy']) && count($post_data['prilohy']) > 0) {
            foreach (array_keys($post_data['prilohy']) as $file_id) {
                $priloha = $File->getInfo($file_id);
                $priloha->tmp_file = $this->storage->getFilePath($priloha);
                $prilohy[$file_id] = $priloha;
            }
        }


        if (isset($post_data['subjekt']) && count($post_data['subjekt']) > 0) {
            foreach ($post_data['subjekt'] as $subjekt_id => $metoda_odeslani) {
                $adresat = new Subject($subjekt_id);

                $datum_odeslani = new \DateTime();
                $epodatelna_id = null;
                $zprava_odes = '';
                $cena = null;
                $hmotnost = null;
                $druh_zasilky = null;
                $cislo_faxu = '';
                $stav = 0;
                $poznamka = null;

                if ($metoda_odeslani == 0) {
                    // neodesilat - nebudeme delat nic
                    continue;
                } elseif ($metoda_odeslani == 1) {
                    // e-mailem
                    if (!isset($data['email_from_' . $subjekt_id]))
                    // neposilej mail, kdyz nemame adresu odesilatele
                    // (podformular odeslani mailem neexistuje)
                        continue;

                    if (!empty($adresat->email)) {

                        $email_data = array(
                            'dokument_id' => $dokument_id,
                            'email_from' => $data['email_from_' . $subjekt_id],
                            'email_predmet' => $data['email_predmet_' . $subjekt_id],
                            'email_text' => $data['email_text_' . $subjekt_id],
                        );

                        if ($zprava = $this->odeslatEmailem($adresat, $email_data, $prilohy)) {
                            $Log = new LogModel();
                            $Log->logDocument($dokument_id, LogModel::DOK_ODESLAN,
                                    'Dokument odeslán e-mailem na adresu "' . Subjekt::displayName($adresat,
                                            'email') . '".');
                            $this->flashMessage('Zpráva na e-mailovou adresu "' . Subjekt::displayName($adresat,
                                            'email') . '" byla úspěšně odeslána.');
                            $stav = 2;
                        } else {
                            $Log = new LogModel();
                            $Log->logDocument($dokument_id, LogModel::DOK_NEODESLAN,
                                    'Dokument se nepodařilo odeslat e-mailem na adresu "' . Subjekt::displayName($adresat,
                                            'email') . '".');
                            $this->flashMessage('Zprávu na e-mailovou adresu "' . Subjekt::displayName($adresat,
                                            'email') . '" se nepodařilo odeslat!', 'warning');
                            $stav = 0;
                            continue;
                        }

                        if (isset($zprava['epodatelna_id'])) {
                            $epodatelna_id = $zprava['epodatelna_id'];
                        }
                        if (isset($zprava['zprava'])) {
                            $zprava_odes = $zprava['zprava'];
                        }
                    } else {
                        $this->flashMessage('Subjekt "' . Subjekt::displayName($adresat,
                                        'jmeno')
                                . '" nemá e-mailovou adresu. Zprávu tomuto adresátovi nelze poslat přes e-mail!',
                                'warning');
                        continue;
                    }
                } elseif ($metoda_odeslani == 2) {
                    // isds
                    if (!$this->user->isAllowed('DatovaSchranka', 'odesilani')) {
                        $this->flashMessage('Nemáte oprávnění odesílat datové zprávy.',
                                'warning');
                        continue;
                    }

                    if (empty($adresat->id_isds)) {
                        $this->flashMessage('Subjekt "' . Subjekt::displayName($adresat,
                                        'jmeno') . '" nemá ID datové schránky. Zprávu tomuto adresátovi nelze poslat přes datovou schránku!',
                                'warning');
                        continue;
                    }

                    $isds_data = array(
                        'dokument_id' => $dokument_id,
                        'isds_predmet' => $data['isds_predmet_' . $subjekt_id],
                        'isds_cjednaci_odes' => $data['isds_cjednaci_odes_' . $subjekt_id],
                        'isds_spis_odes' => $data['isds_spis_odes_' . $subjekt_id],
                        'isds_cjednaci_adres' => $data['isds_cjednaci_adres_' . $subjekt_id],
                        'isds_spis_adres' => $data['isds_spis_adres_' . $subjekt_id],
                        'isds_dvr' => isset($data['isds_dvr_' . $subjekt_id]) ? true : false,
                        'isds_fikce' => isset($data['isds_fikce_' . $subjekt_id]) ? true : false,
                    );
                    if (isset($data['isds_heslo_' . $subjekt_id]))
                        $isds_data['isds_heslo'] = $data['isds_heslo_' . $subjekt_id];

                    if ($result = $this->odeslatISDS($adresat, $isds_data, $prilohy)) {
                        $Log = new LogModel();
                        $Log->logDocument($dokument_id, LogModel::DOK_ODESLAN,
                                'Dokument odeslán datovou zprávou na adresu "' . Subjekt::displayName($adresat,
                                        'isds') . '".');
                        $this->flashMessage('Datová zpráva pro "' . Subjekt::displayName($adresat,
                                        'isds') . '" byla úspěšně odeslána do systému ISDS.');
                        $stav = 2;
                        if (!is_array($result)) {
                            $this->flashMessage('Datovou zprávu pro "' . Subjekt::displayName($adresat,
                                            'isds') . '" se nepodařilo uložit do e-podatelny.',
                                    'warning');
                            continue;
                        }
                    } else {
                        $Log = new LogModel();
                        $Log->logDocument($dokument_id, LogModel::DOK_NEODESLAN,
                                'Dokument se nepodařilo odeslat datovou zprávou na adresu "' . Subjekt::displayName($adresat,
                                        'isds') . '".');
                        $this->flashMessage('Datovou zprávu pro "' . Subjekt::displayName($adresat,
                                        'isds') . '" se nepodařilo odeslat do systému ISDS!',
                                'warning');
                        $stav = 0;
                        continue;
                    }

                    if (isset($result['epodatelna_id'])) {
                        $epodatelna_id = $result['epodatelna_id'];
                    }
                    if (isset($result['zprava'])) {
                        $zprava_odes = $result['zprava'];
                    }
                } else if ($metoda_odeslani == 3) {
                    // postou
                    $c = "datum_odeslani_postou_" . $subjekt_id;
                    $datum_odeslani = new \DateTime($data->$c);
                    $c = "druh_zasilky_" . $subjekt_id;
                    $druh_zasilky = implode(',', $data->$c);
                    $c = "cena_zasilky_" . $subjekt_id;
                    $cena = $data->$c;
                    if ($cena === '')
                        $cena = null;
                    $c = "hmotnost_zasilky_" . $subjekt_id;
                    $hmotnost = $data->$c;
                    $c = "poznamka_" . $subjekt_id;
                    $poznamka = $data->$c;
                    $stav = 1;

                    $this->flashMessage('Dokument předán na podatelnu k odeslání poštou na adresu "' . Subjekt::displayName($adresat,
                                    'plna_adresa') . '".');

                    $Log = new LogModel();
                    $Log->logDocument($dokument_id, LogModel::DOK_PREDODESLAN,
                            'Dokument předán na podatelnu k odeslání poštou na adresu "' . Subjekt::displayName($adresat,
                                    'plna_adresa') . '".');
                } else if ($metoda_odeslani == 4) {
                    // faxem
                    $c = "datum_odeslani_faxu_" . $subjekt_id;
                    $datum_odeslani = new \DateTime($data->$c);

                    $cislo_faxu = $data['cislo_faxu_' . $subjekt_id];
                    $zprava_odes = $data['zprava_faxu_' . $subjekt_id];
                    $stav = 1;

                    $this->flashMessage('Dokument předán na podatelnu k odeslání faxem na číslo "' . $cislo_faxu . '".');

                    $Log = new LogModel();
                    $Log->logDocument($dokument_id, LogModel::DOK_PREDODESLAN,
                            'Dokument předán na podatelnu k odeslání faxem na číslo "' . $cislo_faxu . '".');
                } else {
                    // jinak - externe (osobne, ...)

                    if (isset($post_data['datum_odeslani'][$subjekt_id])) {
                        $datum_odeslani = new \DateTime($post_data['datum_odeslani'][$subjekt_id]);
                    }

                    $Log = new LogModel();
                    $Log->logDocument($dokument_id, LogModel::DOK_ODESLAN,
                            'Dokument odeslán způsobem "' . ZpusobOdeslani::getName($metoda_odeslani) . '" adresátovi "' . Subjekt::displayName($adresat,
                                    'jmeno') . '".');
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
                    'stav' => $stav,
                    'poznamka' => $poznamka
                );
                $DokumentOdeslani->ulozit($row);
            }
        } else {
            // zadni adresati
        }

        $this->redirect('detail', array('id' => $dokument_id));
    }

    protected function odeslatEmailem($adresat, $data, $prilohy)
    {
        $mail = new Mail;
        $mail->setFromConfig();

        try {
            if (strpos($data['email_from'], "user#") !== false) {
                $a = explode("#", $data['email_from']);
                $mail->setFrom($a[2], $a[1]);
            }

            // skrytá, nyní uživateli nepoužívaná funkce, kde mail lze
            // danému subjektu posílat na více adres
            if (strpos($adresat->email, ';') !== false) {
                $a = explode(';', $adresat->email);
                foreach ($a as $el)
                    $mail->addTo($el);
            } elseif (strpos($adresat->email, ',') !== false) {
                $a = explode(',', $adresat->email);
                foreach ($a as $el)
                    $mail->addTo($el);
            } else {
                $mail->addTo($adresat->email, Subjekt::displayName($adresat));
            }

            $mail->setSubject($data['email_predmet']);
            $mail->setBody($data['email_text']);
            $mail->appendSignature($this->user);

            if (count($prilohy) > 0) {
                foreach ($prilohy as $p) {
                    $mail->addAttachment($p->tmp_file);
                }
            }

            $mail->send();
        } catch (Exception $e) {
            $this->flashMessage('Chyba při odesílání emailu! ' . $e->getMessage(), 'error_ext');
            return false;
        }


        // zapis do epodatelny
        $Epodatelna = new Epodatelna();
        $zprava = array();
        $zprava['odchozi'] = 1;
        $zprava['typ'] = 'E';
        $zprava['poradi'] = $Epodatelna->getMax(1);
        $zprava['rok'] = date('Y');
        $zprava['email_id'] = '<nezalezi@na.tom>';
        $zprava['predmet'] = $mail->getSubject();
        if (empty($zprava['predmet']))
            $zprava['predmet'] = "(bez předmětu)";
        $zprava['popis'] = $data['email_text'];
        // V databázi je prohozen adresát a odesilatel!!
        $zprava['adresat'] = $adresat->email;
        $zprava['subjekt_id'] = $adresat->id;
        $zprava['odesilatel'] = '';
        $zprava['prijato_dne'] = new \DateTime();
        $zprava['doruceno_dne'] = new \DateTime();
        $zprava['user_id'] = $this->user->id;

        $zprava_prilohy = array();
        foreach ($prilohy as $pr) {
            $zprava_prilohy[] = array(
                'name' => $pr->real_name,
                'size' => $pr->size,
                'mimetype' => $pr->mime_type
            );
        }

        $zprava['prilohy'] = serialize($zprava_prilohy);

        $zprava['dokument_id'] = $data['dokument_id'];
        // 1 = odesláno, neodeslané e-maily a datové zprávy se v e-podatelně vůbec neobjeví
        $zprava['stav'] = 1;
        $zprava['stav_info'] = '';
        $zprava['file_id'] = null;

        $epod_id = $Epodatelna->insert($zprava);

        return array(
            'epodatelna_id' => $epod_id,
            'zprava' => $data['email_text']
        );
    }

    protected function odeslatISDS($adresat, $data, $prilohy)
    {
        $id_mess = null;
        $mess = null;
        $epod_id = null;
        $zprava = null;
        $popis = null;
        $password = isset($data['isds_heslo']) ? $data['isds_heslo'] : null;

        try {
            $isds = new ISDS_Spisovka($password);

            $dmEnvelope = array(
                "dbIDRecipient" => $adresat->id_isds,
                "cislo_jednaci" => $data['isds_cjednaci_odes'],
                "spisovy_znak" => $data['isds_spis_odes'],
                "vase_cj" => $data['isds_cjednaci_adres'],
                "vase_sznak" => $data['isds_spis_adres'],
                "k_rukam" => $data['isds_dvr'],
                "anotace" => $data['isds_predmet'],
                "do_vlastnich" => ($data['isds_dvr'] == true) ? 1 : 0,
                "doruceni_fikci" => ($data['isds_fikce'] == true) ? 0 : 1
            );

            $id_mess = $isds->odeslatZpravu($dmEnvelope, $prilohy);
            if (!$id_mess) {
                $this->flashMessage('Chyba ISDS: ' . $isds->GetStatusMessage(), 'warning_ext');
                return false;
            }

            sleep(3);
            $odchozi_zpravy = $isds->seznamOdeslanychZprav(time() - 3600, time() + 3600);
            if (count($odchozi_zpravy) > 0) {
                foreach ($odchozi_zpravy as $oz) {
                    if ($oz->dmID == $id_mess) {
                        $mess = $oz;
                        break;
                    }
                }
            }
            if (is_null($mess)) {
                return false;
            }

            $popis = '';
            $popis .= "ID datové zprávy    : " . $mess->dmID . "\n"; // = 342682
            $popis .= "Věc, předmět zprávy : " . $mess->dmAnnotation . "\n"; //  = Vaše datová zpráva byla přijata
            $popis .= "\n";
            $popis .= "Číslo jednací odesílatele   : " . $mess->dmSenderRefNumber . "\n"; //  = AB-44656
            $popis .= "Spisová značka odesílatele : " . $mess->dmSenderIdent . "\n"; //  = ZN-161
            $popis .= "Číslo jednací příjemce     : " . $mess->dmRecipientRefNumber . "\n"; //  = KAV-34/06-ŘKAV/2010
            $popis .= "Spisová značka příjemce    : " . $mess->dmRecipientIdent . "\n"; //  = 0.06.00
            $popis .= "\n";
            $popis .= "Do vlastních rukou? : " . (!empty($mess->dmPersonalDelivery) ? "ano" : "ne") . "\n"; //  =
            $popis .= "Doručeno fikcí?     : " . (!empty($mess->dmAllowSubstDelivery) ? "ano" : "ne") . "\n"; //  =
            $popis .= "Zpráva určena pro   : " . $mess->dmToHands . "\n";
            $popis .= "\n";
            $popis .= "Odesílatel:\n";
            $popis .= "            " . $mess->dbIDSender . "\n"; //  = hjyaavk
            $popis .= "            " . $mess->dmSender . "\n"; //  = Město Milotice
            $popis .= "            " . $mess->dmSenderAddress . "\n"; //  = Kovářská 14/1, 37612 Milotice, CZ
            $popis .= "            " . $mess->dmSenderType . " - " . ISDS_Spisovka::typDS($mess->dmSenderType) . "\n"; //  = 10
            $popis .= "\n";
            $popis .= "Příjemce:\n";
            $popis .= "            " . $mess->dbIDRecipient . "\n"; //  = pksakua
            $popis .= "            " . $mess->dmRecipient . "\n"; //  = Společnost pro výzkum a podporu OpenSource
            $popis .= "            " . $mess->dmRecipientAddress . "\n"; //  = 40501 Děčín, CZ
            $popis .= "\n";
            $popis .= "Status: " . $mess->dmMessageStatus . " - " . ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) . "\n";
            $dt_dodani = strtotime($mess->dmDeliveryTime);
            $dt_doruceni = strtotime($mess->dmAcceptanceTime);
            $popis .= "Datum a čas dodání   : " . date("j.n.Y G:i:s", $dt_dodani) . "\n";
            if ($dt_doruceni == 0) {
                $popis .= "Datum a čas doručení : (příjemce zprávu zatím nepřijal)\n";
            } else {
                $popis .= "Datum a čas doručení : " . date("j.n.Y G:i:s", $dt_doruceni) . "\n";
            }
            $popis .= "Přibližná velikost všech příloh : " . $mess->dmAttachmentSize . "kB\n";

            $UploadFile = $this->storage;

            $Epodatelna = new Epodatelna();

            $zprava = array();
            $zprava['odchozi'] = 1;
            $zprava['typ'] = 'I';
            $zprava['poradi'] = $Epodatelna->getMax(1);
            $zprava['rok'] = date('Y');
            $zprava['isds_id'] = $mess->dmID;
            $zprava['predmet'] = empty($mess->dmAnnotation) ? "(Datová zpráva bez předmětu)" : $mess->dmAnnotation;
            $zprava['popis'] = $popis;
            $zprava['adresat'] = $mess->dmRecipient . ', ' . $mess->dmRecipientAddress;
            $zprava['subjekt_id'] = $adresat->id;
            $zprava['odesilatel'] = '';
            $zprava['prijato_dne'] = new \DateTime();
            $zprava['doruceno_dne'] = new \DateTime($mess->dmAcceptanceTime);
            $zprava['user_id'] = $this->user->id;

            $aprilohy = array();
            if (count($prilohy) > 0) {
                foreach ($prilohy as $index => $file) {
                    $aprilohy[] = array(
                        'name' => $file->real_name,
                        'size' => $file->size,
                        'mimetype' => $file->mime_type,
                        'id' => $index
                    );
                }
            }
            $zprava['prilohy'] = serialize($aprilohy);

            $zprava['dokument_id'] = $data['dokument_id'];
            $zprava['stav'] = 0;
            $zprava['stav_info'] = '';

            if ($epod_id = $Epodatelna->insert($zprava)) {

                /* Ulozeni podepsane ISDS zpravy */
                $data = array(
                    'filename' => 'ep-isds-' . $epod_id . '.zfo',
                    'dir' => 'EP-O-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                    'typ' => '5',
                    'popis' => 'Podepsaný originál ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                );

                $signedmess = $isds->SignedSentMessageDownload($id_mess);

                $UploadFile->uploadEpodatelna($signedmess, $data);

                /* Ulozeni reprezentace zpravy */
                $data = array(
                    'filename' => 'ep-isds-' . $epod_id . '.bsr',
                    'dir' => 'EP-O-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                    'typ' => '5',
                    'popis' => ' Byte-stream reprezentace ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                );

                if ($file = $UploadFile->uploadEpodatelna(serialize($mess), $data)) {
                    // ok
                    $zprava['stav_info'] = 'Zpráva byla uložena';
                    //$zprava['file_id'] = $file->id ."-". $file_o->id;
                    $zprava['file_id'] = $file->id;
                    $Epodatelna->update(
                            ['stav' => 1,
                        'stav_info' => $zprava['stav_info'],
                        'file_id' => $file->id
                            ], "id = $epod_id"
                    );
                } else {
                    // $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                }
            } else {
                // $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
            }

            return array(
                'epodatelna_id' => $epod_id,
                'zprava' => $popis
            );
        } catch (DibiException $e) {
            $this->flashMessage('Chyba v DB: ' . $e->getMessage(), 'warning_ext');
            if (!empty($id_mess))
                return ['epodatelna_id' => $epod_id,
                    'zprava' => $popis
                ];
        } catch (Exception $e) {
            // chyba v pripojeni k datove schrance
            $this->flashMessage('Chyba ISDS: ' . $e->getMessage(), 'warning_ext');
        }

        return false;
    }

    protected function createComponentSearchForm()
    {
        $hledat = !is_null($this->hledat) ? $this->hledat : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);

        $s3_hledat = UserSettings::get('spisovka_dokumenty_hledat');
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

        UserSettings::set('spisovka_dokumenty_hledat', serialize($data['dotaz']));
        $this->redirect('default');
    }

    protected function createComponentFiltrForm()
    {
        // Typ pristupu na organizacni jednotku
        $filtr = !is_null($this->filtr) ? $this->filtr : 'vse';
        $select = ['Předávání' => [
                'kprevzeti' => 'K převzetí',
                'predane' => 'Předané'],
            'nove' => 'Nové',
            'kvyrizeni' => 'Vyřizuje se',
            'vyrizene' => 'Vyřízené',
            'zapujcene' => 'Zapůjčené',
            'vse' => 'Všechny',
            'Výpravna' => ['doporucene' => 'Doporučené',
                'predane_k_odeslani' => 'K odeslání',
                'odeslane' => 'Odeslané',],
        ];

        $filtr_bezvyrizenych = !is_null($this->filtr_bezvyrizenych) ? $this->filtr_bezvyrizenych
                    : false;
        $filtr_moje = !is_null($this->filtr_moje) ? $this->filtr_moje : false;

        $form = new Nette\Application\UI\Form();
        $form->addHidden('hidden')
                ->setValue(1);

        $control = $form->addSelect('filtr', 'Filtr:', $select);
        try {
            $form['filtr']->setValue($filtr);
        } catch (Exception $ex) {
            // zmena filtru v aplikaci muze zpusobit neplatnou predvolbu uzivatele
            $ex->getMessage();
        }

        $control->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        if ($this->zakaz_filtr)
            $control->setDisabled();

        $form->addCheckbox('bez_vyrizenych', 'Nezobrazovat vyřízené dokumenty')
                ->setValue($filtr_bezvyrizenych)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");

        // Zde by se melo kontrolovat opravneni a podle nej pripadne Input vlozit jako Hidden pole
        // Pokud uzivatel neni v zadne org. jednotce,  na hodnote filtru "jen_moje" nezalezi
        $orgjednotka_id = OrgJednotka::dejOrgUzivatele();
        $user = $this->user;

        if (($orgjednotka_id === null || !$user->isAllowed('Dokument', 'cist_moje_oj')) && !$user->isAllowed('Dokument',
                        'cist_vse'))
            $control = $form->addHidden('jen_moje');
        else
            $control = $form->addCheckbox('jen_moje', 'Zobrazit jen dokumenty na mé jméno');
        $control->setValue($filtr_moje)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");

        $form->onSuccess[] = array($this, 'filtrClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function filtrClicked(Nette\Application\UI\Form $form, $data)
    {
        $data2 = array('filtr' => $data['filtr'],
            'bez_vyrizenych' => $data['bez_vyrizenych'],
            'jen_moje' => $data['jen_moje']);

        UserSettings::set('spisovka_dokumenty_filtr', serialize($data2));

        $this->redirect('default');
    }

    protected function createComponentSeraditForm()
    {
        $select = array(
            'stav' => 'stavu dokumentu (vzestupně)',
            'stav_desc' => 'stavu dokumentu (sestupně)',
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
        );

        $form = new Nette\Application\UI\Form();
        $form->addSelect('seradit', 'Seřadit podle:', $select)
                ->getControlPrototype()->onchange("return document.forms['frm-seraditForm'].submit();");
        if (isset($this->seradit))
            $form['seradit']->setValue($this->seradit);

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
        UserSettings::set('spisovka_dokumenty_seradit', $form_data['seradit']);
        $this->redirect('default');
    }

    public function actionZnovuOtevrit($id)
    {
        try {
            $doc = new DocumentWorkflow($id);
            $doc->reopen();
            $this->flashMessage('Dokument byl otevřen.');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }
        $this->redirect('detail', $id);
    }

}
