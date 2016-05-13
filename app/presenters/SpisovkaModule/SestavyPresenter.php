<?php

class Spisovka_SestavyPresenter extends BasePresenter
{

    static protected $sloupce_nazvy = array(
        'smer' => 'Směr',
        'cislo_jednaci' => 'Číslo jednací',
        'spis' => 'Název spisu',
        'datum_vzniku' => 'Datum doruč./vzniku',
        'subjekty' => 'Odesílatel / adresát',
        'cislo_jednaci_odesilatele' => 'Č.j. odesílatele',
        'pocet_listu' => 'Počet listů',
        'pocet_priloh' => 'Počet příloh',
        'pocet_nelistu' => 'Počet nelistů',
        'nazev' => 'Věc',
        'vyridil' => 'Přidělen / Vyřídil',
        'zpusob_vyrizeni' => 'Způsob vyřízení',
        'datum_odeslani' => 'Datum odeslání',
        'spisovy_znak' => 'Spis. znak',
        'skartacni_znak' => 'Skart. znak',
        'skartacni_lhuta' => 'Skart. lhůta',
        'zaznam_vyrazeni' => 'Záznam vyřazení',
        'popis' => 'Popis',
        'poznamka_predani' => 'Poznámka k předání',
        'prazdny_sloupec' => 'Prázdný sloupec',
    );
    
    protected function pdfExport($content)
    {
        @ini_set("memory_limit", PDF_MEMORY_LIMIT);
        
        $app_info = new VersionInformation();
        $app_name = $app_info->name;

        $pdf = new mPDF('', 'A4-L', 9, 'Helvetica');
        $pdf->SetCreator($app_name);
        $person_name = $this->user->displayName;
        $pdf->SetAuthor($person_name);
        $pdf->SetTitle('Sestava');

        $pdf->defaultheaderfontsize = 10; /* in pts */
        $pdf->defaultheaderfontstyle = B; /* blank, B, I, or BI */
        $pdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */

        $pdf->defaultfooterfontsize = 9; /* in pts */
        $pdf->defaultfooterfontstyle = B; /* blank, B, I, or BI */
        $pdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */

        $pdf->SetHeader($this->template->Sestava->nazev . '||' . $this->template->Urad->nazev . ', ' . $this->template->rok);
        // $pdf->SetFooter("{DATE j.n.Y}/" . $person_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

        $pdf->WriteHTML($content);
        $pdf->Output('sestava.pdf', 'I');
    }

    protected function isUserAllowed()
    {
        return Sestava::isUserAllowed();
    }

    public function renderDefault()
    {
        $client_config = GlobalVariables::get('client_config');

        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;
        $paginator->itemCount = Sestava::getCount();

        $seznam = Sestava::getAll(array('offset' => $paginator->offset,
                    'limit' => $paginator->itemsPerPage,
                    'order' => array('typ' => 'DESC', 'nazev')));
        $this->template->sestavy = $seznam;
    }

    public function handleAutoComplete($text, $typ, $user = null, $org = null)
    {
        Spisovka_VyhledatPresenter::autoCompleteHandler($this, $text, $typ, $user, $org);
    }

    public function renderPdf($id)
    {
        $this->renderDetail($id, true);
    }

    public function renderTisk($id)
    {
        $this->setView('detail');
        $this->renderDetail($id);
    }

    public function renderDetail($id, $pdf = false)
    {
        $Dokument = new Dokument();

        $sestava = new Sestava($this->getParameter('id'));
        $this->template->Sestava = $sestava;

        $this->template->pdf = $pdf;
        $this->template->sloupce_nazvy = self::$sloupce_nazvy;

        $zobr = isset($sestava->zobrazeni_dat) ? unserialize($sestava->zobrazeni_dat) : false;
        if ($zobr === false)
            $zobr = array();

        // nastav vychozi hodnoty
        if (!isset($zobr['zobrazeni_cas']))
            $zobr['zobrazeni_cas'] = false;
        if (!isset($zobr['zobrazeni_adresa']))
            $zobr['zobrazeni_adresa'] = false;

        $this->template->sloupce = explode(',', $sestava->sloupce);
        $this->template->zobrazeni = $zobr;

        try {
            if (empty($sestava->parametry)) {
                $parametry = null;
                $args = array();
            } else {
                $parametry = unserialize($sestava->parametry);
                $args = $Dokument->paramsFiltr($parametry);
            }
        } catch (Exception $e) {
            $this->flashMessage("Sestavu nelze zobrazit: " . $e->getMessage(), 'warning');
            $this->redirect('default');
        }

        if (isset($sestava->seradit))
            $criteria = explode(',', $sestava->seradit);
        else
            $criteria = $this->vychoziRazeni();

        $order_by = [];
        foreach ($criteria as $criterion) {

            $desc = strpos($criterion, "desc") !== false;
            $criterion = str_replace("_desc", "", $criterion);

            switch ($criterion) {
                case 'cj':
                    $col = 'd.cislo_jednaci';
                    break;
                case 'jid':
                    $col = 'd.id';
                    break;
                case 'dvzniku':
                    $col = 'd.datum_vzniku';
                    break;
                case 'dvyrizeni':
                    $col = 'd.datum_vyrizeni';
                    break;
                case 'denik':
                    $col = 'd.podaci_denik';
                    break;
                case 'rok':
                    $col = 'd.podaci_denik_rok';
                    break;
                case 'poradovecislo':
                    $col = 'd.podaci_denik_poradi';
                    break;
                case 'vec':
                    $col = 'd.nazev';
                    break;
                case 'stav':
                    $col = 'wf.stav_dokumentu';
                    break;
                default:
                    $col = null;
                    break;
            }
            if ($col)
                $order_by[$col] = $desc ? 'desc' : 'asc';
        }

        // Pozor, Dokument::seznam() neumi neradit vysledek dotazu.
        // Je-li "order" parametr prazdny, pouzije vychozi razeni.
        // Nasledujici podminka pouze osetri SQL chybu syntaxe.
        if (!empty($order_by))
            $args['order'] = $order_by;

        // vstup
        $pc_od = $this->getParameter('pc_od');
        $pc_do = $this->getParameter('pc_do');
        $d_od = $this->getParameter('d_od');
        $d_do = $this->getParameter('d_do');

        if ($d_od) {
            try {
                $d_od = date("Y-m-d", strtotime($d_od));
                //$d_od = new DateTime($this->getParameter('d_od',null));
            } catch (Exception $e) {
                $d_od = null;
            }
        }
        if ($d_do) {
            try {
                $d_do = date("Y-m-d", strtotime($d_do) + 86400);
                //$d_do = new DateTime($this->getParameter('d_do',null));
            } catch (Exception $e) {
                $d_do = null;
            }
        }

        $today = $this->getParameter('d_today', null);
        // dnesek
        if (!empty($today)) {
            $d_od = date("Y-m-d");
            $d_do = date("Y-m-d", time() + 86400);
        }

        $rok = $this->getParameter('rok', null);
        $this->template->rok = !empty($rok) ? $rok : date('Y');

        // podaci denik
        if ($sestava->id == 1) { // pouze na podaci denik, u jinych sestav zatim ne
            // P.L. V podacim deniku nemohou byt dokumenty, ktere nemaji c.j.
            $args['where'][] = 'd.cislo_jednaci IS NOT NULL';

            $client_config = GlobalVariables::get('client_config');

            if ($client_config->cislo_jednaci->typ_deniku == "org") {
                $orgjednotka_id = OrgJednotka::dejOrgUzivatele();
                $org = $orgjednotka_id === null ? null : new OrgUnit($orgjednotka_id);

                // jen zaznamy z vlastniho podaciho deniku organizacni jednotky
                $args['where'][] = array('d.podaci_denik=%s', $client_config->cislo_jednaci->podaci_denik . (!empty($org)
                                ? "_" . $org->ciselna_rada : ""));
            }
        } // if sestava->id == 1
        // rok
        if (!empty($rok)) {
            $args['where'][] = array('d.podaci_denik_rok = %i', $rok);
        }

        // rozsah poradoveho cisla
        if (!empty($pc_od) && !empty($pc_do)) {
            $args['where'][] = array(
                'd.podaci_denik_poradi >= %i AND ', $pc_od,
                'd.podaci_denik_poradi <= %i', $pc_do
            );
        } else if (!empty($pc_od) && empty($pc_do)) {
            $args['where'][] = array('d.podaci_denik_poradi >= %i', $pc_od);
        } else if (empty($pc_od) && !empty($pc_do)) {
            $args['where'][] = array('d.podaci_denik_poradi <= %i', $pc_do);
        }

        // rozsah datumu
        if (!empty($d_od) && !empty($d_do)) {
            $args['where'][] = array(
                'd.datum_vzniku >= %s AND ', $d_od,
                'd.datum_vzniku <= %s', $d_do
            );
        } else if (!empty($d_od) && empty($d_do)) {
            $args['where'][] = array('d.datum_vzniku >= %s', $d_od);
        } else if (empty($d_od) && !empty($d_do)) {
            $args['where'][] = array('d.datum_vzniku <= %s', $d_do);
        }

        // vystup
        $args = $Dokument->sestavaOmezeniOrg($args);

        $result = $Dokument->seznam($args);
        $seznam = $result->fetchAll();

        if (count($seznam) > 0) {
            $mnoho = count($seznam) > ($pdf ? 100 : 500);
            $this->template->pocet_dokumentu = count($seznam);

            if ($mnoho && !$this->getParameter('pokracovat', false)) {
                $this->template->prilis_mnoho = 1;
                $seznam = array();
                if ($pdf)
                    $this->setView('detail');
            } else {
                $dokument_ids = array();
                foreach ($seznam as $row)
                    $dokument_ids[] = $row->id;

                // $start_memory = memory_get_usage();
                $this->template->subjekty = DokumentSubjekt::subjekty2($dokument_ids);
                $this->template->d2s = DokumentSubjekt::dejAsociaci($dokument_ids);
                // Nette\Diagnostics\Debugger::dump("Pamet zabrana nahranim subjektu: " . (memory_get_usage() - $start_memory));

                $pocty_souboru = DokumentPrilohy::pocet_priloh($dokument_ids);

                $datumy_odeslani = DokumentOdeslani::datumy_odeslani($dokument_ids);

                foreach ($seznam as $index => $row) {
                    $dok = $Dokument->getInfo($row->id, '');
                    $id = $dok->id;
                    $dok->pocet_souboru = isset($pocty_souboru[$id]) ? $pocty_souboru[$id] : 0;
                    $dok->datum_odeslani = isset($datumy_odeslani[$id]) ? $datumy_odeslani[$id]
                                : '';
                    $seznam[$index] = $dok;
                }
            }
        }

        $this->template->seznam = $seznam;
        $this->setLayout('print');
    }

    public function renderNova()
    {
        $this->template->form = $this['newForm'];
        $this->template->nadpis = 'Nová sestava';

        $user = $this->user;
        $this->template->vidiVsechnyDokumenty = $user->isAllowed('Dokument', 'cist_vse');
        $this->setView('form');
    }

    public function renderUpravit($id)
    {
        $sestava = new Sestava($id);
        $this->template->sestava = $sestava;

        if (!$sestava->isModifiable()) {
            $this->flashMessage('Sestavu "' . $sestava->nazev . '" není možné měnit.',
                    'warning');
            $this->redirect(':Spisovka:Sestavy:default');
        }

        $this->template->form = $this['upravitForm'];
        $this->template->nadpis = 'Upravit sestavu';

        $user = $this->user;
        $this->template->vidiVsechnyDokumenty = $user->isAllowed('Dokument', 'cist_vse');
        $this->setView('form');
    }

    protected function createForm()
    {
        $typ_dokumentu = [0 => 'jakýkoli typ dokumentu'] + TypDokumentu::vsechny();
        
        $typ_doruceni = array(
            '0' => 'všechny',
            '1' => 'pouze doručené přes elektronickou podatelnu',
            /** Tato informace je nyní v metadatech dokumentu ve zpusobu doruceni
             * '2' => 'pouze doručené přes email',
             * '3' => 'pouze doručené přes datovou schránkou',
             */
            '4' => 'doručené mimo e-podatelnu',
        );

        $typ_select = array();
        $typ_select = Subjekt::typ_subjektu(null, 3);

        $stat_select = array();
        $stat_select = Subjekt::stat(null, 3);

        $zpusob_doruceni = array();
        $zpusob_doruceni = Dokument::zpusobDoruceni(3);

        $zpusob_odeslani = array();
        $zpusob_odeslani = Dokument::zpusobOdeslani(3);

        $zpusob_vyrizeni = array();
        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(3);

        $spudalost_seznam = array();
        $spudalost_seznam = SpisovyZnak::spousteci_udalost(null, 3);

        $skartacni_znak = ['0' => 'jakýkoli znak', 'A' => 'A', 'V' => 'V', 'S' => 'S'];

        $stav_dokumentu = array(
            '' => 'jakýkoli stav',
            '1' => 'nový / rozpracovaný',
            '2' => 'přidělen / předán',
            '3' => 'vyřizuje se',
            '4' => 'vyřízen',
            '5' => 'vyřazen'
        );

        $order_by = array(
            '' => 'není určeno',
            'stav' => 'stavu dokumentu (vzestupně)',
            'stav_desc' => 'stavu dokumentu (sestupně)',
            'cj' => 'čísla jednacího (vzestupně)',
            'cj_desc' => 'čísla jednacího (sestupně)',
            'jid' => 'JID (vzestupně)',
            'jid_desc' => 'JID (sestupně)',
            'dvzniku' => 'data přijetí/vzniku (vzestupně)',
            'dvzniku_desc' => 'data přijetí/vzniku (sestupně)',
            'dvyrizeni' => 'data vyřízení (vzestupně)',
            'dvyrizeni_desc' => 'data vyřízení (sestupně)',
            // -----------------------------
            'denik' => 'podací deník (vzestupně)',
            'denik_desc' => 'podací deník (sestupně)',
            'rok' => 'rok přijetí/vzniku (vzestupně)',
            'rok_desc' => 'rok přijetí/vzniku (sestupně)',
            'poradovecislo' => 'pořadové číslo (vzestupně)',
            'poradovecislo_desc' => 'pořadové číslo (sestupně)',
            // -----------------------------
            'vec' => 'věci (vzestupně)',
            'vec_desc' => 'věci (sestupně)',
        );

        $form = new Spisovka\Form();
        $form->elementPrototype->onsubmit('sestavaFormSubmit(this)');

        $form->addText('sestava_nazev', 'Název sestavy:', 80, 100)
                ->setRequired();
        $form->addTextArea('sestava_popis', 'Popis sestavy:', 80, 3);
        $form->addSelect('sestava_typ', 'Lze měnit? :',
                array('1' => 'upravitelná sestava', '2' => 'pevná sestava'));
        $form->addCheckbox('sestava_filtr', 'Filtrovat? (zobrazit výběr rozsahu podle pořadového čísla nebo data)');

        $form->addCheckbox('zobrazeni_cas', 'U datumů zobrazit i čas:');
        $form->addCheckbox('zobrazeni_adresa', 'Zobrazit adresy u subjektů:');

        $form->addMultiSelect('vybrane_sloupce', 'Vybrané sloupce:', null, 10);
        $form->addMultiSelect('dostupne_sloupce', 'Dostupné sloupce:', self::$sloupce_nazvy, 10);
        $form->onValidate[] = array($this, 'validateVybraneSloupce');
        
        $form->addSelect('razeni1', '1. kritérium:', $order_by);
        $form->addSelect('razeni2', '2. kritérium:', $order_by);
        $form->addSelect('razeni3', '3. kritérium:', $order_by);

        $form->addText('nazev', 'Věc:', 80, 100);
        $form->addTextArea('popis', 'Stručný popis:', 80, 3);
        $form->addText('cislo_jednaci', 'Číslo jednací:', 50, 50);
        $form->addText('spisova_znacka', 'Název spisu:', 50, 50);
        $form->addSelect('dokument_typ_id', 'Typ dokumentu:', $typ_dokumentu);
        $form->addSelect('typ_doruceni', 'Způsob doručení:', $typ_doruceni);
        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:', $zpusob_doruceni);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50);
        $form->addText('cislo_doporuceneho_dopisu', 'Číslo doporučeného dopisu:', 50, 50);
        $form->addCheckbox('cislo_doporuceneho_dopisu_pouze', 'Pouze doporučené dopisy');
        $form->addDatePicker('datum_vzniku_od', 'Datum doručení/vzniku (od):');
        $form->addText('datum_vzniku_cas_od', 'Čas doručení (od):', 10, 15);
        $form->addDatePicker('datum_vzniku_do', 'Datum doručení/vzniku do:');
        $form->addText('datum_vzniku_cas_do', 'Čas doručení do:', 10, 15);
//nepouzito v sablone
//        $form->addText('pocet_listu', 'Počet listů:', 5, 10);
//        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10);
        $form->addSelect('stav_dokumentu', 'Stav dokumentu:', $stav_dokumentu);

        $form->addTextArea('poznamka', 'Poznámka:', 80, 4);

        $form->addSelect('zpusob_vyrizeni', 'Způsob vyřízení:', $zpusob_vyrizeni);
        $form->addDatePicker('datum_vyrizeni_od', 'Datum vyřízení od:');
        $form->addText('datum_vyrizeni_cas_od', 'Čas vyřízení od:', 10, 15);
        $form->addDatePicker('datum_vyrizeni_do', 'Datum vyřízení do:');
        $form->addText('datum_vyrizeni_cas_do', 'Čas vyřízení do:', 10, 15);

        $form->addSelect('zpusob_odeslani', 'Způsob odeslání:', $zpusob_odeslani);
        $form->addDatePicker('datum_odeslani_od', 'Datum odeslání (od):');
        $form->addText('datum_odeslani_cas_od', 'Čas odeslání (od):', 10, 15);
        $form->addDatePicker('datum_odeslani_do', 'Datum odeslání do:');
        $form->addText('datum_odeslani_cas_do', 'Čas odeslání do:', 10, 15);

        $form->addComponent(new Spisovka\Controls\VyberPostovniZasilkyControl(), 'druh_zasilky');

        $form->addCheckbox('spisovy_znak_prazdny', 'Spisový znak nevyplněn');
        $form->addComponent(new SpisovyZnakComponent(), 'spisovy_znak_id');
        $form['spisovy_znak_id']->controlPrototype->onchange('');

        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 4);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 4);
        $form->addSelect('skartacni_znak', 'Skartační znak: ', $skartacni_znak);
        $form->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5);
        $form->addSelect('spousteci_udalost', 'Spouštěcí událost: ', $spudalost_seznam);

        $form->addText('prideleno_text', 'Přidělen:', 50, 255)
                        ->getControlPrototype()->autocomplete = 'off';
        $form->addText('predano_text', 'Předán:', 50, 255)
                        ->getControlPrototype()->autocomplete = 'off';

        $form->addCheckbox('prideleno_osobne', 'Přidělen na mé jméno');
        $form->addCheckbox('prideleno_na_organizacni_jednotku',
                'Přidělen na mou organizační jednotku');
        $form->addCheckbox('predano_osobne', 'Předán na mé jméno');
        $form->addCheckbox('predano_na_organizacni_jednotku',
                'Předán na mou organizační jednotku');


        $form->addSelect('subjekt_type', 'Typ subjektu:', $typ_select);
        $form->addText('subjekt_nazev', 'Název subjektu, jméno, IČ:', 50, 255);
        $form->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form->addText('adresa_mesto', 'Obec:', 50, 48);
        $form->addText('adresa_psc', 'PSČ:', 10, 10);

        $form->addText('subjekt_email', 'Email:', 50, 250);
        $form->addText('subjekt_telefon', 'Telefon:', 50, 150);
        $form->addText('subjekt_isds', 'ID datové schránky:', 10, 50);

        return $form;
    }

    public function validateVybraneSloupce(Spisovka\Form $form)
    {
        // Nette "feature". onValidate se vola, prestoze pouziji setValidationScope()
        $submit = $form->isSubmitted();
        if ($submit->name == 'storno')
            return;
        
        $columns = $form['vybrane_sloupce']->getRawValue();
        if (empty($columns))
            $form['vybrane_sloupce']->addError('Vyberte sloupce sestavy.');
    }
    
    protected function createComponentNewForm()
    {
        $form = $this->createForm();

        $form->addSubmit('odeslat', 'Vytvořit')
                ->onClick[] = array($this, 'vytvoritClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $form = $button->getForm();
        $data = $form->getValues();

        $sestava_data = $this->handleSubmit($form, $data);

        try {
            Sestava::create($sestava_data);

            $this->flashMessage('Sestava "' . $sestava_data['nazev'] . '" byla vytvořena.');
            $this->redirect(':Spisovka:Sestavy:default');
        } catch (DibiException $e) {
            $this->flashMessage('Sestavu "' . $sestava_data['nazev'] . '" se nepodařilo vytvořit.',
                    'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
    }

    protected function createComponentUpravitForm()
    {
        $sestava = @$this->template->sestava;

        $params = array();
        if (isset($sestava->parametry))
            $params = unserialize($sestava->parametry);
        $this->template->params = $params;
        if (isset($sestava->zobrazeni_dat) && !empty($sestava->zobrazeni_dat))
            $params = array_merge($params, unserialize($sestava->zobrazeni_dat));

        unset($params['prideleno'], $params['predano'], $params['prideleno_org'],
                $params['predano_org']);

        $form = $this->createForm();

        $form->addHidden('id')
                ->setValue(@$sestava->id);

        if (isset($sestava->nazev)) {
            $form['sestava_nazev']->setValue($sestava->nazev);
            $form['sestava_popis']->setValue($sestava->popis);
            $form['sestava_typ']->setValue($sestava->typ);
            $form['sestava_filtr']->setValue($sestava->filtr);
        }

        if (isset($params['druh_zasilky']))
            $form['druh_zasilky']->setDefaultValue($params['druh_zasilky']);
        unset($params['druh_zasilky']);

        if (!empty($params))
            foreach ($params as $key => $value)
                try {
                    $input = $form[$key];
                    if ($input instanceof Nette\Forms\Controls\Checkbox)
                        ;
                    // nedelej nic, framework provadi kontrolu parametru lepe
                    // $value = $value ? true : false;
                    $input->setValue($value);
                } catch (Exception $e) {
                    // ignoruj
                    $e->getMessage();
                }

        if (empty($sestava->seradit))
            $order_by = $this->vychoziRazeni();
        else
            $order_by = explode(',', $sestava->seradit);

        $form['razeni1']->setDefaultValue($order_by[0]);
        if (!empty($order_by[1]))
            $form['razeni2']->setDefaultValue($order_by[1]);
        if (!empty($order_by[2]))
            $form['razeni3']->setDefaultValue($order_by[2]);

        if (!empty($sestava->sloupce)) {
            $column_keys = explode(',', $sestava->sloupce);
            $items = [];
            foreach ($column_keys as $key)
                $items[$key] = self::$sloupce_nazvy[$key];
            $form['vybrane_sloupce']->setItems($items);
        }

        $form->addSubmit('odeslat', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    /**
     * 
     * @param array $data
     * @return array
     */
    protected function handleSubmit(Spisovka\Form $form, $data)
    {
        $sestava = array();
        $sestava['nazev'] = $data['sestava_nazev'];
        $sestava['popis'] = $data['sestava_popis'];
        $sestava['typ'] = $data['sestava_typ'];
        $sestava['filtr'] = ($data['sestava_filtr']) ? 1 : 0;

        $a = [];
        if (!empty($data['razeni1']))
            $a[] = $data['razeni1'];
        if (!empty($data['razeni2']))
            $a[] = $data['razeni2'];
        if (!empty($data['razeni3']))
            $a[] = $data['razeni3'];
        $sestava['seradit'] = implode(',', $a);

        $unset_keys = ['id', 'sestava_nazev', 'sestava_popis', 'sestava_typ', 'sestava_filtr',
            'razeni1', 'razeni2', 'razeni3'];
        foreach ($unset_keys as $key) {
            unset($data[$key]);
        }

        // pro sestaveni sloupce
        $sloupce = $form['vybrane_sloupce']->getRawValue();
        $sestava['sloupce'] = implode(',', $sloupce);
        unset($data['vybrane_sloupce']);
        unset($data['dostupne_sloupce']);

        $postdata = $this->getHttpRequest()->getPost();

        // pro sestaveni parametru
        if (isset($postdata['prideleno'])) {
            $data['prideleno'] = $postdata['prideleno'];
        }
        if (isset($postdata['predano'])) {
            $data['predano'] = $postdata['predano'];
        }
        if (isset($postdata['prideleno_org'])) {
            $data['prideleno_org'] = $postdata['prideleno_org'];
        }
        if (isset($postdata['predano_org'])) {
            $data['predano_org'] = $postdata['predano_org'];
        }

        $zobrazeni_dat = array();
        $nazvy_poli = array('zobrazeni_cas', 'zobrazeni_adresa');
        foreach ($nazvy_poli as $key) {
            $zobrazeni_dat[$key] = $data[$key];
            unset($data[$key]);
        }
        $sestava['zobrazeni_dat'] = serialize($zobrazeni_dat);

        $params = '';
        $params = serialize((array) $data); // $data jsou nove ArrayHash
        $sestava['parametry'] = $params;

        return $sestava;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $form = $button->getForm();
        $data = $form->getValues();
        $id = $data['id'];
        $sestava_data = $this->handleSubmit($form, $data);

        try {
            $sestava = new Sestava($id);
            $sestava->modify($sestava_data);
            $sestava->save();

            $this->flashMessage("Sestava '$sestava->nazev' byla upravena.");
        } catch (Exception $e) {
            $this->flashMessage("Sestavu '$sestava_data[nazev]' se nepodařilo upravit.",
                    'warning');
            $this->flashMessage('Popis chyby: ' . $e->getMessage(), 'warning');
        }

        $this->redirect(':Spisovka:Sestavy:default');
    }

    public function stornoClicked()
    {
        $this->redirect(':Spisovka:Sestavy:default');
    }

    public function actionSmazat()
    {
        $s = new Sestava($this->getParameter('id'));

        try {
            $s->delete();
            $this->flashMessage('Sestava byla smazána.');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }

        $this->redirect(':Spisovka:Sestavy:default');
    }

    public function renderFiltr()
    {
        $this->template->id = $this->getParameter('id');
    }

    protected function vychoziRazeni()
    {
        // vychozi razeni zaznamu - vzdy pouzito ve spisovce starsi nez 3.5.0
        // tam byla ale chyba, ze se neradilo dle roku
        // vychozi razeni se pouzije, dokud uzivatel neupravi sestavu
        // nebo pro pevnou sestavu Podaci denik
        return ['rok', 'poradovecislo', 'vec'];
    }

}
