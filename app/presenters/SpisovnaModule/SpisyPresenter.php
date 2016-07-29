<?php

class Spisovna_SpisyPresenter extends BasePresenter
{

    private $hledat;

    protected function isUserAllowed()
    {
        return $this->user->isAllowed('Spisovna', 'cist_dokumenty');
    }

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->template->Typ_evidence = $client_config->cislo_jednaci->typ_evidence;
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;
        parent::startup();
    }

    public function renderPrijem($hledat)
    {
        if (!$this->user->isAllowed('Spisovna', 'prijem_dokumentu'))
            $this->forward(':NoAccess:default');

        $this->_renderSeznam($hledat, true);
    }

    public function renderDefault($hledat)
    {
        $this->_renderSeznam($hledat, false);
    }

    public function _renderSeznam($hledat, $prijem)
    {
        $Spisy = new SpisModel();

        $filter = [];
        if (!empty($hledat))
            $filter = [["tb.nazev LIKE %s", '%' . $hledat . '%']];
        $this->template->pouzito_hledani = !empty($hledat);

        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        if ($prijem)
            $filter = $Spisy->spisovna_prijem($filter);
        else
            $filter = $Spisy->spisovna($filter);

        $result = $Spisy->seznam(['where' => $filter]);
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
            foreach ($seznam as $spis) {
                $spis_ids[] = $spis->id;
            }
            $this->template->seznam_dokumentu = $Spisy->seznamDokumentu($spis_ids);
        } else {
            $this->template->seznam_dokumentu = array();
        }

        $this->template->seznam = $seznam;

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->select()->fetchAssoc('id');
        $this->template->SpisoveZnaky = $spisove_znaky;
    }

    public function createComponentBulkAction()
    {
        $BA = new Spisovka\Components\BulkAction();

        $actions = [];
        switch ($this->view) {
            case 'prijem':
                $actions = ['prevzit_spisovna' => 'převzetí spisů do spisovny',
                    'vratit' => 'vrátit (nepřevzít) spisy'
                ];
                break;
            default:
                break;
        }

        $BA->setActions($actions);
        $BA->setDefaultAction('prevzit_spisovna');
        $BA->setCallback([$this, 'bulkAction']);
        $BA->text_object = 'spis';
        return $BA;
    }

    public function bulkAction($action, $spisy)
    {
        $Spis = new SpisModel();
        switch ($action) {
            /* Predani vybranych spisu do spisovny  */
            case 'prevzit_spisovna':
                $count_ok = $count_failed = 0;
                foreach ($spisy as $spis_id) {
                    $stav = $Spis->pripojitDoSpisovny($spis_id);
                    if ($stav === true) {
                        $count_ok++;
                    } else {
                        if (is_string($stav)) {
                            $this->flashMessage($stav, 'warning');
                        }
                        $count_failed++;
                    }
                }
                if ($count_ok > 0)
                    $this->flashMessage(sprintf("Úspěšně jste přijal $count_ok %s do spisovny.",
                                    SpisModel::cislovat($count_ok)));
                if ($count_failed > 0)
                    $this->flashMessage(sprintf("$count_failed %s se nepodařilo přijmout do spisovny.",
                                    SpisModel::cislovat($count_failed)), 'warning');
                break;

            case 'vratit':
                $all_ok = true;
                foreach ($spisy as $spis_id) {
                    $spis = new Spis($spis_id);
                    $ok = $spis->returnFromSpisovna();
                    if (!$ok)
                        $all_ok = false;
                }
                if (count($spisy) == 1)
                    $msg = $ok ? 'Spis byl vrácen.' : 'Spis se nepodařilo vrátit.';
                else
                    $msg = $all_ok ? 'Spisy byly vráceny.' : 'Některé spisy se nepodařilo vrátit.';
                $this->flashMessage($msg, $all_ok ? 'info' : 'warning');
                break;
        }
    }

    public function renderDetail($id)
    {
        // Info o spisu
        $Spisy = new SpisModel();
        $spis_id = $id;
        $this->template->Spis = $spis = $Spisy->getInfo($spis_id, true);

        if (!$spis) {
            $this->setView('noexist');
            return;
        }

        $this->template->SpisZnak_nazev = "";
        if (!empty($spis->spisovy_znak_id)) {
            $SpisovyZnak = new SpisovyZnak();
            $sz = $SpisovyZnak->select(["[id] = $spis->spisovy_znak_id"])->fetch();
            $this->template->SpisZnak_nazev = $sz->nazev;
        }

        $DokumentSpis = new DokumentSpis();
        $result = $DokumentSpis->dokumenty($spis_id);
        $this->template->seznam = $result;

        $this->template->lzeEditovat = $this->user->isAllowed('Spisovna', 'zmenit_skartacni_rezim');
    }

    protected function createComponentUpravitForm()
    {
        $skar_znak = array('A' => 'A', 'S' => 'S', 'V' => 'V');

        $form1 = new Spisovka\Form();
        $form1->addHidden('id');
        $form1->addComponent(new SpisovyZnakComponent(), 'spisovy_znak_id');
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
                ->addRule(Spisovka\Form::INTEGER, 'Skartační lhůta musí být celé číslo.');

        if (isset($this->template->Spis)) {
            $spis = $this->template->Spis;
            $form1['id']->setValue($spis->id);
            $form1['spisovy_znak_id']->setValue($spis->spisovy_znak_id);
            $form1['skartacni_znak']->setValue($spis->skartacni_znak);
            $form1['skartacni_lhuta']->setValue($spis->skartacni_lhuta);
        }

        $form1->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Spisy = new SpisModel();

        $spis_id = $data['id'];
        unset($data['id']);

        $data['date_modified'] = new DateTime();
        $data['user_modified'] = $this->user->id;

        //Nette\Diagnostics\Debugger::dump($data); exit;

        try {
            $Spisy->update($data, array(array('id=%i', $spis_id)));
            $this->flashMessage('Spis byl upraven.');
            $this->redirect(':Spisovna:Spisy:detail', array('id' => $spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Spis se nepodařilo upravit.', 'warning');
            $this->flashMessage('CHYBA: "' . $e->getMessage(), 'error_ext');
            $this->redirect(':Spisovna:Spisy:detail', array('id' => $spis_id));
            //Nette\Diagnostics\Debugger::dump($e);
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $spis_id = $data['id'];
        $this->redirect(':Spisovna:Spisy:detail', array('id' => $spis_id));
    }

    protected function createComponentSearchForm()
    {
        $hledat = !is_null($this->hledat) ? $this->hledat : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle názvu spisu";

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

    public function renderStrom()
    {
        $Spisy = new SpisModel();
        $filter = $Spisy->spisovna();
        $result = $Spisy->seznamRychly($filter);
        $result->setRowClass(null);
        $this->template->spisy = $result->fetchAll();
    }

}
