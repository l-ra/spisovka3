<?php

class SubjektyPresenter extends BasePresenter
{

    public function renderVyber($abc = null)
    {
        new AbcFilter($this, 'abc', $this->getHttpRequest());
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = array('where' => array("stav = 1"));
        if (!empty($abc))
            $args['where'][] = array("nazev_subjektu LIKE %s OR prijmeni LIKE %s", $abc . '%', $abc . '%');

        $Subjekt = new Subjekt();
        $result = $Subjekt->seznam($args);
        $paginator->itemCount = count($result);
        $this->template->seznam = $result->fetchAll($paginator->offset,
                $paginator->itemsPerPage);
    }

    public function renderAres($ic)
    {
        $ares = new Ares();
        $data = $ares->get($ic);
        if (is_string($data))
            $data = ['error' => $data];

        $this->sendJson($data);
    }

    public function renderIsds($box)
    {
        if (is_null($box))
            exit;

        try {
            $isds = new ISDS_Spisovka();
            $filtr['dbID'] = $box;
            $prijemci = $isds->FindDataBoxEx($filtr);
            if (isset($prijemci->dbOwnerInfo)) {
                $info = $prijemci->dbOwnerInfo[0];
                echo json_encode($info);
            } else {
                echo json_encode(array("error" => $isds->GetStatusMessage()));
            }
        } catch (Exception $e) {
            echo json_encode(array("error" => $e->getMessage()));
        }

        $this->terminate();
    }

    public function renderNovy()
    {
        $this->template->subjektForm = $this['novyForm'];
    }

    protected function vytvorFormular()
    {
        $typ_select = Subjekt::typ_subjektu();
        $stat_select = array("" => "Neuveden") + Subjekt::stat();

        $form = new Spisovka\Form();
        $form->getElementPrototype()->id('subjekt-vytvorit');

        $form->addSelect('type', 'Typ subjektu:', $typ_select);
        $form->addText('nazev_subjektu', 'Název subjektu:', 50, 255);
        $description = \Nette\Utils\Html::el('a')
                ->href('#')
                ->onclick("return aresSubjekt(this);")
                ->setText('Vyhledat pomocí systému ARES');
        $form->addText('ic', 'IČO:', 12, 8)
                ->setOption('description', $description)
                ->addCondition(Spisovka\Form::FILLED)
                ->addRule(Spisovka\Form::INTEGER);
        $form->addText('dic', 'DIČ:', 12, 12);

        $form->addText('jmeno', 'Jméno:', 50, 24);
        $form->addText('prostredni_jmeno', 'Prostřední jméno:', 50, 35);
        $form->addText('prijmeni', 'Příjmení:', 50, 35);
        $form->addText('rodne_jmeno', 'Rodné jméno:', 50, 35);
        $form->addText('titul_pred', 'Titul před:', 20, 35);
        $form->addText('titul_za', 'Titul za:', 20, 10);

        $form['nazev_subjektu']->addConditionOn($form['prijmeni'], ~Spisovka\Form::FILLED)
                ->setRequired('Je nutné vyplnit název subjektu nebo příjmení');
        
        $form->addDatePicker('datum_narozeni', 'Datum narození:');
        $form->addText('misto_narozeni', 'Místo narození:', 50, 48);
        $form->addText('okres_narozeni', 'Okres narození:', 50, 48);
        $form->addText('narodnost', 'Národnost / Stát registrace:', 50, 48);
        $form->addSelect('stat_narozeni', 'Stát narození:', $stat_select)
                ->setValue('CZE');

        $form->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form->addText('adresa_cp', 'číslo popisné:', 10, 10);
        $form->addText('adresa_co', 'Číslo orientační:', 10, 10);
        $form->addText('adresa_mesto', 'Obec:', 50, 48);
        $form->addText('adresa_psc', 'PSČ:', 10, 10);
        $form->addSelect('adresa_stat', 'Stát:', $stat_select)
                ->setValue('CZE');

        $form->addText('email', 'E-mail:', 50, 250);
        $form->addText('telefon', 'Telefon:', 50, 150);

        $description = \Nette\Utils\Html::el('a')
                ->href('#')
                ->onclick("return isdsSubjekt(this);")
                ->setText('Vyhledat pomocí systému ISDS');
        $form->addText('id_isds', 'ID datové schránky:', 10, 50)
                ->setOption('description', $description);

        $form->addTextArea('poznamka', 'Poznámka:', 50, 6);

        return $form;
    }

    protected function createComponentNovyForm()
    {
        $form1 = $this->vytvorFormular();

        $form1->getElementPrototype()->onsubmit('return false;');
        $form1->addSubmit('novy', 'Vytvořit');
        $form1->addSubmit('storno', 'Zrušit')
                ->setValidationScope(FALSE);

        return $form1;
    }

    protected function createComponentUpravitForm()
    {
        $form1 = $this->vytvorFormular();

        $subjekt = @$this->template->Subjekt;
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);

        $form1->addSubmit('upravit', 'Upravit');
        $form1->addSubmit('storno', 'Zrušit')
                ->setValidationScope(FALSE);

        if ($subjekt !== null)
            foreach ($subjekt as $key => $value)
                if (isset($form1[$key]))
                    $form1[$key]->setDefaultValue($value);

        return $form1;
    }

}

class Spisovka_SubjektyPresenter extends SubjektyPresenter
{

    public function renderNovy()
    {
        // Pouzij novy, zkraceny formular
        $this->view = 'form2';
    }

    /** Volano pouze pres Ajax
     * 
     * @param int $id  ID dokumentu
     */
    public function renderNacti($id)
    {
        $doc = new Document($id);
        $this->template->subjekty = $doc->getSubjects();
        $this->template->dokument_id = $id;
    }

    // Volano pouze pres Ajax
    public function actionVybrano($id, $dok_id, $typ = null)
    {
        try {
            $subjekt_id = $id;
            $dokument_id = $dok_id;

            $subject = new Subject($subjekt_id);
            // Propojit s dokumentem
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentSubjekt->pripojit(new Document($dokument_id), $subject, $typ);

            echo '###vybrano###' . $dokument_id;
        } catch (Exception $e) {
            echo 'Chyba ' . $e->getCode() . ' - ' . $e->getMessage();
        }

        $this->terminate();
    }

    public function renderOdebrat()
    {
        $subjekt_id = $this->getParameter('id', null);
        $dokument_id = $this->getParameter('dok_id', null);

        $DokumentSubjekt = new DokumentSubjekt();
        $param = array(array('subjekt_id=%i', $subjekt_id), array('dokument_id=%i', $dokument_id));

        if ($seznam = $DokumentSubjekt->odebrat($param)) {

            $Log = new LogModel();
            $subjekt_info = new Subject($subjekt_id);
            $Log->logDokument($dokument_id, LogModel::SUBJEKT_ODEBRAN,
                    'Odebrán subjekt "' . Subjekt::displayName($subjekt_info, 'jmeno') . '"');

            $this->flashMessage('Subjekt byl úspěšně odebrán.');
        } else {
            $this->flashMessage('Subjekt se nepodařilo odebrat. Zkuste to znovu.', 'warning');
        }
        $this->redirect('Dokumenty:detail', array('id' => $dokument_id));
    }

    public function actionSeznamAjax()
    {

        $Subjekt = new Subjekt();

        $seznam = array();

        $term = $this->getParameter('term');

        if (!empty($term)) {
            $args = array('where' => array(array("LOWER(CONCAT_WS('', nazev_subjektu,prijmeni,jmeno,ic,adresa_mesto,adresa_ulice,email,telefon,id_isds)) LIKE LOWER(%s)", '%' . $term . '%'),
                    'stav = 1'
            ));
            $seznam_subjektu = $Subjekt->seznam($args);
        } else {
            $seznam_subjektu = $Subjekt->seznam();
        }

        if (count($seznam_subjektu) > 0) {
            foreach ($seznam_subjektu as $subjekt) {
                $seznam[] = array(
                    "id" => $subjekt->id,
                    "value" => Subjekt::displayName($subjekt, 'full'),
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }

    public function actionSeznamtypusubjektu()
    {
        $typy_subjektu = Subjekt::typ_subjektu();
        echo json_encode($typy_subjektu);
        exit;
    }

    public function actionSeznamStatuAjax()
    {
        echo json_encode(Subjekt::stat());
        exit;
    }

    public function renderUpravit($id)
    {
        $this->template->Subjekt = new Subject($id);
        $this->template->subjektForm = $this['upravitForm'];
    }

    /**
     *
     * Formular a zpracovani pro udaju osoby
     *
     */
    protected function createComponentUpravitForm()
    {
        $form1 = parent::createComponentUpravitForm();

        $form1->getElementPrototype()->onsubmit('return false;');
        $form1->onSuccess[] = array($this, 'upravitFormSucceeded');
        $form1['upravit']->controlPrototype->onclick("return subjektUpravitSubmit();");
        $form1['storno']->controlPrototype->onclick("return closeDialog();");

        return $form1;
    }

    public function upravitFormSucceeded(Nette\Application\UI\Form $form, $data)
    {
        $subjekt_id = $data['id'];

        if (empty($data['stat_narozeni']))
            $data['stat_narozeni'] = null;
        if (empty($data['adresa_stat']))
            $data['adresa_stat'] = null;

        try {
            $subject = new Subject($subjekt_id);
            $subject->modify($data);
            $subject->save();
            echo "###zmeneno###";
        } catch (Exception $e) {
            echo "Chyba! Subjekt se nepodařilo upravit.<br/>" . $e->getMessage();
        }

        $this->terminate();
    }

    protected function createComponentNovyForm()
    {
        $form = parent::createComponentNovyForm();

        // formulář je odesílán přes Ajax, nelze tedy navázat událost na submit tlačítko
        $form->onSuccess[] = array($this, 'novyFormSucceeded');

        $callback = $this->getParameter('f', 'novySubjektOk');
        $form['novy']->controlPrototype->onclick("return handleNovySubjekt($callback);");
        $form['storno']->controlPrototype->onclick("$('#dialog').dialog('close'); return false;");

        $form->addHidden('dokument_id');
        $dok_id = $this->getParameter('dok_id');
        if ($dok_id)
            $form['dokument_id']->setValue($dok_id);

        $form->addHidden('extra_data', $this->getParameter('extra_data'));

        $form['stat_narozeni']->setDisabled(true);

        $form['jmeno']->setAttribute('size', 20);
        $form['prijmeni']->setAttribute('size', 30);
        $form['titul_pred']->setAttribute('size', 5);
        $form['titul_za']->setAttribute('size', 5);
        $form['adresa_ulice']->setAttribute('size', 40);

        // nastaveni pripadnych vychozich hodnot
        $form['type']->setValue($this->getParameter('type'));
        $form['nazev_subjektu']->setValue($this->getParameter('nazev_subjektu'));
        $form['jmeno']->setValue($this->getParameter('jmeno'));
        $form['prijmeni']->setValue($this->getParameter('prijmeni'));

        $form['adresa_ulice']->setValue($this->getParameter('adresa_ulice'));
        $form['adresa_cp']->setValue($this->getParameter('adresa_cp'));
        $form['adresa_co']->setValue($this->getParameter('adresa_co'));
        $form['adresa_psc']->setValue($this->getParameter('adresa_psc'));
        $form['adresa_mesto']->setValue($this->getParameter('adresa_mesto'));
        $form['email']->setValue($this->getParameter('email'));
        $form['id_isds']->setValue($this->getParameter('id_isds'));
        $form['poznamka']->setAttribute('rows', 1)
                ->setAttribute('style', 'width: 400px')
        ->controlPrototype->onfocus("$(this).attr('rows', 5)");

        return $form;
    }

    public function novyFormSucceeded(Nette\Application\UI\Form $form, $data)
    {
        $dokument_id = isset($data['dokument_id']) ? $data['dokument_id'] : null;
        $extra_data = isset($data['extra_data']) ? $data['extra_data'] : null;
        $payload = ['status' => 'OK', 'extra_data' => $extra_data];

        try {
            unset($data->dokument_id);
            unset($data->extra_data);
            $subject = Subject::create((array) $data);
            try {
                if ($dokument_id) {
                    // byli jsme zavolani z dokumentu modulu spisovka
                    $DokumentSubjekt = new DokumentSubjekt();
                    $DokumentSubjekt->pripojit(new Document($dokument_id), $subject, 'AO');
                }
                $payload['id'] = $subject->id;
                $payload['name'] = Subjekt::displayName($data, 'full');
            } catch (Exception $e) {
                $payload['status'] = "Subjekt byl vytvořen ale nepodařilo se jej připojit k dokumentu.";
            }
        } catch (Exception $e) {
            $payload['status'] = "Chyba! Subjekt se nepodařilo vytvořit.\n" . $e->getMessage();
        }

        $this->sendJson($payload);
    }

    // Volano pouze pres Ajax
    // Nevraci zpet informaci (krome HTTP stavoveho kodu), predpoklada se, ze operace se vzdy provede uspesne
    public function actionZmenRezim()
    {
        $subjekt_id = $this->getParameter('id', null);
        $dokument_id = $this->getParameter('dok_id', null);
        $typ = $this->getParameter('typ', null);

        // Zmen typ propojeni
        $DokumentSubjekt = new DokumentSubjekt();
        $DokumentSubjekt->zmenit($dokument_id, $subjekt_id, $typ);

        echo 'OK';
        $this->terminate();
    }

}
