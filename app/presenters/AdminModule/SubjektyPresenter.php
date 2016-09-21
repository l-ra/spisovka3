<?php

class Admin_SubjektyPresenter extends SubjektyPresenter
{

    // Nejprve prepsane metody z parent tridy

    protected function createComponentNovyForm()
    {
        $form1 = parent::createComponentNovyForm();

        // Tento formular se neodesila pres Ajax
        $form1->getElementPrototype()->onsubmit('');

        $form1['novy']->onClick[] = array($this, 'vytvoritClicked');
        $form1['storno']->onClick[] = array($this, 'stornoSeznamClicked');

        return $form1;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        try {
            $subject = Subject::create($data);
            $this->flashMessage('Subjekt  "' . Subjekt::displayName($data, 'jmeno') . '"  byl vytvořen.');
            $this->redirect('detail', $subject->id);
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "' . Subjekt::displayName($data, 'jmeno') . '" se nepodařilo vytvořit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');
            $this->redirect('novy');
        }
    }

    protected function createComponentUpravitForm()
    {
        $form1 = parent::createComponentUpravitForm();

        $form1['upravit']->onClick[] = array($this, 'upravitClicked');
        $form1['storno']->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    // ------------------------------------------------------------------------



    public function renderSeznam($hledat = null, $abc = null)
    {
        // paginator
        new AbcFilter($this, 'abc', $this->getHttpRequest());
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        // hledani
        $this->template->no_items = 0;
        $args = [];
        if (isset($hledat) && empty($abc)) {
            $args['where'][] = [ "LOWER(CONCAT_WS('', nazev_subjektu,prijmeni,jmeno,ic,adresa_mesto,adresa_ulice,email,telefon,id_isds)) LIKE LOWER(%s)",
                "%$hledat%"];

            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        // zobrazit podle pismena
        if (!empty($abc))
            $args['where'][] = ["nazev_subjektu LIKE %s OR prijmeni LIKE %s", "$abc%", "$abc%"];

        $filter = UserSettings::get('admin_subjekty_filtr', 'V');
        if ($filter != 'V')
            $args['where'][] = "stav = " . ($filter == 'A' ? 1 : 2);

        // nacteni
        $Subjekt = new Subjekt();
        $result = $Subjekt->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;
    }

    public function renderDetail($id)
    {
        $this->template->title = " - Detail subjektu";
        $this->template->FormUpravit = $this->getParameter('upravit', null);
        $this->template->Subjekt = new Subject($id);
        $this->template->subjektForm = $this['upravitForm'];
    }

    public function renderExport()
    {

        if ($this->getHttpRequest()->isPost()) {
            // Exportovani
            $post_data = $this->getHttpRequest()->getPost();
            //Nette\Diagnostics\Debugger::dump($post_data);

            $Subjekt = new Subjekt();
            $args = null;
            if ($post_data['export_co'] == 2) {
                // pouze aktivni
                $args['where'] = array(array('stav=1'));
            }
            $seznam = $Subjekt->seznam($args)->fetchAll();
            if ($seznam) {

                if ($post_data['export_do'] == "csv") {
                    // export do CSV
                    $ignore_cols = array("date_created", "user_created", "date_modified", "user_modified");
                    $export_data = CsvExport::csv(
                                    $seznam, $ignore_cols, $post_data['csv_code'],
                                    $post_data['csv_radek'], $post_data['csv_sloupce'],
                                    $post_data['csv_hodnoty']);

                    //echo "<pre>"; echo $export_data; echo "</pre>"; exit;

                    $httpResponse = $this->getHttpResponse();
                    $httpResponse->setContentType('application/octetstream');
                    $httpResponse->setHeader('Content-Description', 'File Transfer');
                    $httpResponse->setHeader('Content-Disposition',
                            'attachment; filename="export_subjektu.csv"');
                    $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
                    $httpResponse->setHeader('Expires', '0');
                    $httpResponse->setHeader('Cache-Control',
                            'must-revalidate, post-check=0, pre-check=0');
                    $httpResponse->setHeader('Pragma', 'public');
                    $httpResponse->setHeader('Content-Length', strlen($export_data));
                    echo $export_data;
                    exit;
                }
            } else {
                $this->flashMessage('Nebyly nalezany žádné data k exportu!', 'warning');
            }
        }
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];

        if (empty($data['stat_narozeni']))
            $data['stat_narozeni'] = null;
        if (empty($data['adresa_stat']))
            $data['adresa_stat'] = null;

        try {
            $subject = new Subject($subjekt_id);
            $subject->modify($data);
            $subject->save();
            $this->flashMessage('Subjekt  "' . Subjekt::displayName($data, 'jmeno') . '"  byl upraven.');

            $this->redirect('detail', $subjekt_id);
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "' . Subjekt::displayName($data, 'jmeno') . '" se nepodařilo upravit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');

            $this->redirect('seznam');
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];
        $this->redirect('this', array('id' => $subjekt_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect('seznam');
    }

    protected function createComponentStavForm()
    {
        $stav_select = Subjekt::stav();

        $form1 = new Spisovka\Form();
        $form1->addHidden('id');
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select);
        $form1->addSubmit('zmenit_stav', 'Změnit stav')
                ->onClick[] = array($this, 'zmenitStavClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        if (isset($this->template->Subjekt)) {
            $subjekt = $this->template->Subjekt;
            $form1['id']->setValue($subjekt->id);
            $form1['stav']->setValue($subjekt->stav);
        }

        return $form1;
    }

    public function zmenitStavClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $id = $data['id'];

        try {
            $subject = new Subject($id);
            $subject->stav = $data->stav;
            $subject->save();
            $this->flashMessage('Stav subjektu byl změněn.');
            $this->redirect('detail', $id);
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Stav subjektu se nepodařilo změnit.', 'warning');
        }
    }

    protected function createComponentFilterForm()
    {
        $form = new Nette\Application\UI\Form();
        $items = ['V' => 'všechny', 'A' => 'aktivní', 'N' => 'neaktivní'];
        $form->addSelect('filter', 'Filtr:', $items)
                ->setDefaultValue(UserSettings::get('admin_subjekty_filtr'))
                ->getControlPrototype()->style('width: 150px;')
                ->onchange("return document.forms['frm-filterForm'].submit();");

        $form->onSuccess[] = array($this, 'filterChanged');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;        

        return $form;
    }

    public function filterChanged(Nette\Application\UI\Form $form)
    {
        $filter = $form->getValues()->filter;
        UserSettings::set('admin_subjekty_filtr', $filter);
        $this->redirect('this');
    }

}
