<?php

namespace Spisovka;

use Nette;

class Spisovna_SpisyPresenter extends BasePresenter
{

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
        $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
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
            $this->template->pocty_dokumentu = $Spisy->poctyDokumentu($spis_ids);
        } else {
            $this->template->pocty_dokumentu = array();
        }

        $this->template->seznam = $seznam;

        $SpisovyZnak = new SpisovyZnak();
        $spisove_znaky = $SpisovyZnak->select()->fetchAssoc('id');
        $this->template->SpisoveZnaky = $spisove_znaky;
    }

    public function createComponentBulkAction()
    {
        $BA = new Components\BulkAction();

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
        switch ($action) {
            /* Predani vybranych spisu do spisovny  */
            case 'prevzit_spisovna':
                $count_ok = $count_failed = 0;
                foreach ($spisy as $spis_id) {
                    try {
                        $spis = new Spis($spis_id);
                        $spis->receiveIntoSpisovna();
                        $count_ok++;
                    } catch (\Exception $e) {
                        $this->flashMessage($e->getMessage(), 'warning');
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
                    try {
                        $spis = new Spis($spis_id);
                        $spis->returnFromSpisovna();
                    } catch (\Exception $e) {
                        $all_ok = false;                        
                    }
                }
                if (count($spisy) == 1)
                    $msg = $all_ok ? 'Spis byl vrácen.' : 'Spis se nepodařilo vrátit.';
                else
                    $msg = $all_ok ? 'Spisy byly vráceny.' : 'Některé spisy se nepodařilo vrátit.';
                $this->flashMessage($msg, $all_ok ? 'info' : 'warning');
                break;
        }
    }

    public function renderDetail($id)
    {
        $this->template->Spis = $spis = new Spis($id);

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

        $this->template->seznam = $spis->getDocumentsPlus();

        $this->template->lzeEditovat = $this->user->isAllowed('Spisovna',
                'zmenit_skartacni_rezim');
    }

    protected function createComponentUpravitForm()
    {
        $skar_znak = array('A' => 'A', 'S' => 'S', 'V' => 'V');

        $form1 = new Form();
        $form1->addHidden('id');
        $form1->addComponent(new Components\SpisovyZnakComponent(), 'spisovy_znak_id');
        $form1->addSelect('skartacni_znak', 'Skartační znak:', $skar_znak);
        $form1->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
                ->addRule(Form::INTEGER, 'Skartační lhůta musí být celé číslo.');

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

        $data['date_modified'] = new \DateTime();
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

    public function renderStrom()
    {
        $Spisy = new SpisModel();
        $filter = $Spisy->spisovna();
        $result = $Spisy->seznamRychly($filter);
        $result->setRowClass(null);
        $this->template->spisy = $result->fetchAll();
    }

}
