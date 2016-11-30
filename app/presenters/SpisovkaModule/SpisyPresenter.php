<?php

class SpisyPresenter extends BasePresenter
{

    /**
     *
     * @return \Spisovka\Form
     */
    protected function createForm()
    {
        $spousteci = SpisovyZnak::spousteci_udalost(null, 1);
        $skar_znak = array('' => 'nezadán', 'A' => 'A', 'S' => 'S', 'V' => 'V');

        $m = new SpisModel();
        $params = ['where' => ["tb.typ = 'F'"]];
        $folders = $m->selectBox(0, $params);
        $folders = [0 => '(hlavní větev)'] + $folders;

        $form = new Spisovka\Form();

        $form->addText('nazev', 'Název spisu:', 50, 80)
                ->addRule(Nette\Forms\Form::FILLED, 'Název spisu musí být vyplněn!');
        $form->addText('popis', 'Popis:', 50, 200);
        $form->addSelect('parent_id', 'Složka:', $folders);

        $form->addComponent(new SpisovyZnakComponent(), 'spisovy_znak_id');
        $form->getComponent('spisovy_znak_id');

        $form->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
                ->addCondition(Spisovka\Form::FILLED)
                ->addRule(Spisovka\Form::INTEGER);
        $form->addSelect('spousteci_udalost_id', 'Spouštěcí událost:', $spousteci);

        return $form;
    }

    protected function createComponentNovyForm()
    {
        $form = $this->createForm();

        $form->addSubmit('vytvorit', 'Vytvořit');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        if ($this->isAjax()) {
            $form->getElementPrototype()->onsubmit("return spisVytvoritSubmit(this);");
            $form['storno']->controlPrototype->onclick("return closeDialog();");
            $form->onSuccess[] = array($this, 'vytvoritAjaxClicked');
        } else
            $form->onSuccess[] = array($this, 'vytvoritClicked');

        return $form;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $this->getParameter('id');

        try {
            $Spisy = new SpisModel();
            $Spisy->upravit($data, $spis_id);
            $this->flashMessage('Spis  "' . $data['nazev'] . '"  byl upraven.');
        } catch (Exception $e) {
            $this->flashMessage('Spis "' . $data['nazev'] . '" se nepodařilo upravit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');
        }

        $this->redirect("detail", array('id' => $spis_id));
    }

    public function vytvoritClicked(Nette\Application\UI\Form $form, $data)
    {
        $Spisy = new SpisModel();

        try {
            $Spisy->vytvorit($data);
            if (isset($data->typ) && $data->typ == 'F')
                $this->flashMessage('Složka "' . $data['nazev'] . '"  byla vytvořena.');
            else
                $this->flashMessage('Spis "' . $data['nazev'] . '"  byl vytvořen.');
        } catch (Exception $e) {
            $this->flashMessage('Spis "' . $data['nazev'] . '" se nepodařilo vytvořit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');
            return;
        }

        $this->redirect("default");
    }

    public function stornoClicked()
    {
        $id = $this->getParameter('id');
        if ($id !== null) {
            $this->redirect('detail', ['id' => $id]);
        } else {
            $this->redirect('default');
        }
    }

    protected function createComponentUpravitForm()
    {
        $form = $this->createForm();

        $spis = isset($this->template->Spis) ? $this->template->Spis : null;
        if ($spis)
            foreach ($spis as $key => $value) {
                if (isset($form[$key]))
                    $form[$key]->setDefaultValue($value);
            }

        $form->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    /**
     * @param boolean $upravit - formulář upravit složku nebo nová složka
     * @return \Spisovka\Form
     */
    protected function createFolderForm($upravit)
    {
        $form = new Spisovka\Form();

        $m = new SpisModel();
        $params = ['where' => ["tb.typ = 'F'"]];
        if ($upravit) {
            $folder_id = $this->getParameter('id');
            if (!is_numeric($folder_id))
                return null; // ochrana před útokem
            $params['exclude_id'] = $folder_id;
        }
        $folders = $m->selectBox(0, $params);
        $folders = [0 => '(hlavní větev)'] + $folders;

        $form->addHidden('typ', 'F');
        $form->addText('nazev', 'Název složky:', 50, 80)
                ->addRule(Nette\Forms\Form::FILLED);
        $form->addText('popis', 'Popis:', 50, 200);
        $form->addSelect('parent_id', 'Složka:', $folders);

        return $form;
    }

    protected function createComponentNovaSlozkaForm()
    {
        $form = $this->createFolderForm(false);

        $form->addSubmit('vytvorit', 'Vytvořit');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        $form->onSuccess[] = array($this, 'vytvoritClicked');

        return $form;
    }

    protected function createComponentUpravitSlozkuForm()
    {
        $form = $this->createFolderForm(true);

        $spis = isset($this->template->Spis) ? $this->template->Spis : null;
        if ($spis)
            foreach ($spis as $key => $value) {
                if (isset($form[$key]))
                    $form[$key]->setDefaultValue($value);
            }

        $form->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

}

class Spisovka_SpisyPresenter extends SpisyPresenter
{

    private $hledat;

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->template->Typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
        parent::startup();
    }

    public function renderVyber()
    {
        $this->template->dokument_id = $this->getParameter('id');
    }

    /**
     * Zobraz seznam spisu ve stavu "otevren", bez hledani, bez strankovani.
     * Vlozeni dokumentu do spisu.
     */
    public function renderStrom()
    {
        $this->template->dokument_id = $this->getParameter('dokument_id');

        $Spisy = new SpisModel();
        $args = ['stav = ' . SpisModel::OTEVREN];
        $args = $Spisy->spisovka($args);
        $result = $Spisy->seznamRychly($args);
        $result->setRowClass(null);
        $this->template->spisy = $result->fetchAll();
        $this->template->on_select = 'spisVlozitDokument';
    }

    /**
     * Jako renderStrom(), ale neomezuj stav
     */
    public function renderStrom2()
    {
        $Spisy = new SpisModel();
        $args = $Spisy->spisovka();
        $result = $Spisy->seznamRychly($args);
        $result->setRowClass(null);
        $this->template->spisy = $result->fetchAll();
    }

    public function renderSeznamAjax($q, $filter)
    {
        $Spisy = new SpisModel();
        $result = $Spisy->search($q, $filter);

        $this->sendJson($result);
    }

    // TODO: Zcela chybi kontrola opravneni
    public function actionVlozitDokument()
    {
        $spis_id = $this->getParameter('id', null);
        $dokument_id = $this->getParameter('dok_id', null);

        $spis = new Spis($spis_id);
        $doc = new Document($dokument_id);
        $doc->insertIntoSpis($spis);

        $this->sendJson($spis->getData());
    }

    // TODO: Zcela chybi kontrola opravneni
    public function actionVyjmoutDokument($dok_id)
    {
        $document_id = $dok_id;
        $doc = new Document($document_id);
        $doc->takeOutFromSpis();
        $this->sendJson(['ok' => true]);
    }

    public function renderDefault($hledat = null)
    {
        $Spisy = new SpisModel();

        $args = [];
        if (!empty($hledat)) {
            $args = [["tb.nazev LIKE %s", '%' . $hledat . '%']];
            $this->hledat = $hledat;
        }

        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = $Spisy->spisovka($args);
        $predat_do_spisovny = $this->getParameter('do_spisovny', false);
        $order_by = null;
        if ($predat_do_spisovny) {
            $args[] = 'tb.stav = ' . SpisModel::UZAVREN;
            $order_by = 'nazev';
        }
        $result = $Spisy->seznam(['where' => $args], $order_by);
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
            $spis_ids = array();
            foreach ($seznam as &$spis) {
                $spis_ids[] = $spis->id;
                // v seznamu uzavřených spisů je zobraz zarovnaně, ignoruj umístění ve stromu
                if ($predat_do_spisovny)
                    $spis->uroven = 0;
            }
            $this->template->pocty_dokumentu = $Spisy->poctyDokumentu($spis_ids);
        } else {
            $this->template->pocty_dokumentu = array();
        }

        $this->template->seznam = $seznam;

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->select()->fetchAssoc('id');
        $this->template->SpisoveZnaky = $spisove_znaky;
    }

    public function renderDetail($id, $upravit)
    {
        $this->template->Spis = $spis = new Spis($id);

        $this->template->SpisZnak_nazev = "";
        if (!empty($spis->spisovy_znak_id)) {
            $SpisovyZnak = new SpisovyZnak();
            $sz = $SpisovyZnak->select(["[id] = $spis->spisovy_znak_id"])->fetch();
            $this->template->SpisZnak_nazev = $sz->nazev;
        }

        $opravneni = $spis->getUserPermissions();
        $this->template->opravneni = $opravneni;
        $this->template->Editovat = $opravneni['lze_menit'] && $upravit == 'info';

        if (!$opravneni['lze_cist']) {
            $this->setView('noaccess');
            return;
        }

        $this->template->seznam = DokumentSpis::dokumentyVeSpisu($id);
    }

    public function actionPrevzit($id)
    {
        $spis = new Spis($id);
        $documents = $spis->getDocuments();
        if ($documents) {
            // obsahuje dokumenty - predame i dokumenty
            $doc = current($documents);
            if ($doc->canUserTakeOver()) {
                if ($doc->takeOver())
                    $this->flashMessage('Úspěšně jste převzal tento spis.');
                else
                    $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.',
                            'warning');
            } else
                $this->flashMessage('Nemáte oprávnění k převzetí spisu.', 'warning');
        } else {
            $spis->takeOver();
            $this->flashMessage('Úspěšně jste převzal tento spis.');
            /* $this->flashMessage('Převzetí spisu do vlastnictví se nepodařilo. Zkuste to znovu.',
                        'warning'); */
        }

        $this->redirect('detail', $id);
    }

    /** Tato operace je povolena pouze, kdyz spis nema zadneho vlastnika */
    public function actionPrivlastnit($id)
    {
        $orgunit = $this->user->getOrgUnit();
        $spis = new Spis($id);
        if ($spis->orgjednotka_id !== null || $spis->orgjednotka_id_predano !== null)
            $this->flashMessage('Operace zamítnuta.', 'error');
        else if (!$orgunit)
            $this->flashMessage('Nemůžete převzít spis, protože nejste zařazen do organizační jednotky.',
                    'warning');
        else { 
            $spis->orgjednotka_id = $orgunit->id;
            $spis->save();
            $this->flashMessage('Úspěšně jste si převzal tento spis. Pokud spis obsahoval dokumenty, jejich vlastnictví změněno nebylo.');
        }

        $this->redirect('detail', $id);
    }

    public function actionZrusitPredani($id)
    {
        $spis = new Spis($id);
        $documents = $spis->getDocuments();
        if (!$documents)
            throw new Exception('Spisy, které neobsahují žádný dokument, není možné předávat.');

        $doc = current($documents);
        if ($doc->cancelForwarding()) {
            $this->flashMessage('Zrušil jste předání spisu.');
        } else {
            $this->flashMessage('Nejste oprávněn zrušit předání.', 'warning');
        }

        $this->redirect('detail', $id);
    }

    public function actionOdmitnoutPrevzeti($id)
    {
        $spis = new Spis($id);
        $documents = $spis->getDocuments();
        if (!$documents)
            throw new Exception('Spisy, které neobsahují žádný dokument, není možné předávat.');

        $doc = current($documents);
        if ($doc->reject()) {
            $this->flashMessage('Odmítl jste převzetí spisu.');
            $this->redirect('default');
        } else {
            $this->flashMessage('Nejste oprávněn spis převzít nebo převzetí odmítnout.',
                    'warning');
            $this->redirect('detail', $id);
        }
    }

    public function createComponentBulkAction()
    {
        $BA = new Spisovka\Components\BulkAction();

        $actions = ['predat_spisovna' => 'předat do spisovny'];
        $BA->setActions($actions);
        $BA->setCallback([$this, 'bulkAction']);
        $BA->text_object = 'spis';

        return $BA;
    }

    public function bulkAction($action, $spisy)
    {
        $Spis = new SpisModel();
        switch ($action) {
            /* Predani vybranych spisu do spisovny  */
            case 'predat_spisovna':
                $count_ok = $count_failed = 0;
                foreach ($spisy as $spis_id) {
                    $result = $Spis->transferToSpisovna($spis_id);
                    if ($result === true) {
                        $count_ok++;
                    } else {
                        $count_failed++;
                        foreach ($result as $msg)
                            $this->flashMessage($msg, 'warning');
                    }
                }

                if ($count_ok > 0)
                    $this->flashMessage(sprintf("Úspěšně jste předal $count_ok %s do spisovny.",
                                    SpisModel::cislovat($count_ok)));
                if ($count_failed > 0)
                    $this->flashMessage(sprintf($count_failed . ' %s se nepodařilo předat do spisovny.',
                                    SpisModel::cislovat($count_failed)), 'warning');
                break;
        }
    }

    public function actionPredatDoSpisovny($id)
    {
        try {
            $spis = new Spis($id);
            $spis->transferToSpisovna($id);
            $this->flashMessage('Spis byl předán do spisovny.');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
            $this->flashMessage('Spis se nepodařilo předat do spisovny.', 'warning');
            $this->redirect('detail', $id);
        }

        $this->redirect('default');
    }

    public function actionOtevrit($id)
    {
        try {
            $spis = new Spis($id);
            $spis->reopen();
            $this->flashMessage('Spis byl otevřen.');
        } catch (\Exception $e) {
            $this->flashMessage('Spis se nepodařilo otevřít: ' . $e->getMessage(), 'error');
        }

        $this->redirect('detail', $id);
    }

    public function actionUzavrit($id)
    {
        try {
            $spis = new Spis($id);
            $spis->close();
            $this->flashMessage('Spis byl uzavřen.');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->redirect('detail', $id);
    }

    public function vytvoritAjaxClicked(Nette\Application\UI\Form $form, $data)
    {
        $Spisy = new SpisModel();

        try {
            $spis_id = $Spisy->vytvorit($data);
        } catch (Exception $e) {
            $form->addError("Spis se nepodařilo vytvořit.");
            $form->addError($e->getMessage());
            return;
        }

        $this->sendJson(['status' => 'OK', 'id' => $spis_id, 'name' => $data['nazev']]);
    }

    public function renderPrideleni()
    {
        // tento nefunkční hack by se měl z programu odstranit
        $this->flashMessage('Funkce byla z programu odstraněna.', 'error');
        $this->redirect('default');

        /*
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
          $this->redirect('default');
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
          $spisove_znaky = $SpisovyZnak->selectBox(11);
          $this->template->SpisoveZnaky = $spisove_znaky;
         */
    }

}
