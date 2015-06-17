<?php

class Admin_SubjektyPresenter extends SubjektyPresenter
{

    private $hledat;

    // Nejpreve prepsane metody z parent tridy

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
        $data = $button->getForm()->getValues();

        $Subjekt = new Subjekt();

        try {
            $subjekt_id = $Subjekt->ulozit($data);
            $this->flashMessage('Subjekt  "' . Subjekt::displayName($data, 'jmeno') . '"  byl vytvořen.');
            $this->redirect(':Admin:Subjekty:detail', array('id' => $subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "' . Subjekt::displayName($data, 'jmeno') . '" se nepodařilo vytvořit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');
            $this->redirect(':Admin:Subjekty:novy');
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
        new AbcFilter($this, 'abc');
        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek) ? $user_config->nastaveni->pocet_polozek
                    : 20;

        // hledani
        $this->hledat = "";
        $this->template->no_items = 0;
        $args = [];
        if (isset($hledat) && empty($abc)) {
            $args['where'][] = [ "LOWER(CONCAT_WS('', nazev_subjektu,prijmeni,jmeno,ic,adresa_mesto,adresa_ulice,email,telefon,id_isds)) LIKE LOWER(%s)",
                "%$hledat%"];

            $this->hledat = $hledat;
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

    public function renderDetail()
    {
        $this->template->title = " - Detail subjektu";

        $this->template->FormUpravit = $this->getParameter('upravit', null);

        $subjekt_id = $this->getParameter('id', null);

        $Subjekt = new Subjekt();
        $subjekt = $Subjekt->getInfo($subjekt_id);
        $this->template->Subjekt = $subjekt;

        $this->template->subjektForm = $this['upravitForm'];
    }

    public function renderImport()
    {
        
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

        $Subjekt = new Subjekt();

        try {
            $Subjekt->ulozit($data, $subjekt_id);
            $this->flashMessage('Subjekt  "' . Subjekt::displayName($data, 'jmeno') . '"  byl upraven.');

            $this->redirect(':Admin:Subjekty:detail', array('id' => $subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "' . Subjekt::displayName($data, 'jmeno') . '" se nepodařilo upravit.',
                    'warning');
            $this->flashMessage($e->getMessage(), 'warning');

            $this->redirect(':Admin:Subjekty:seznam');
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
        $this->redirect(':Admin:Subjekty:seznam');
    }

    protected function createComponentStavForm()
    {
        $stav_select = Subjekt::stav();

        $form1 = new Nette\Application\UI\Form();
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

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function zmenitStavClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];
        $Subjekt = new Subjekt();

        try {
            $Subjekt->zmenitStav($data);
            $this->flashMessage('Stav subjektu byl změněn.');
            $this->redirect(':Admin:Subjekty:detail', array('id' => $subjekt_id));
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Stav subjektu se nepodařilo změnit.', 'warning');
        }
    }

    protected function createComponentSearchForm()
    {

        $hledat = !is_null($this->hledat) ? $this->hledat : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze názvu subjektu, jména, IČ, emailu, telefonu, ISDS, města, PSČ";

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

        $this->redirect('seznam', array('hledat' => $data['dotaz']));
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

        return $form;
    }

    public function filterChanged(Nette\Application\UI\Form $form)
    {
        $filter = $form->getValues()->filter;
        UserSettings::set('admin_subjekty_filtr', $filter);
        $this->redirect('seznam');
    }

}
