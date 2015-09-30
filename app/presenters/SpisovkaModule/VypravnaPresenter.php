<?php

class Spisovka_VypravnaPresenter extends BasePresenter
{

    private $typ_evidence = null;
    private $pdf_output = false;
    private $seradit = null;
    // retezec, ktery uzivatel zadal do vyhledavaciho pole
    private $jednoduche_hledani = null;

    public function startup()
    {
        $client_config = Nette\Environment::getVariable('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;        
        $this->template->Oddelovac_poradi = $client_config->cislo_jednaci->oddelovac;

        parent::startup();
    }

    public function renderDefault()
    {
        $post = $this->getRequest()->getPost();
        if (isset($post['hromadna_submit'])) {
            $this->actionAkce($post);
        }

        $this->template->Typ_evidence = $this->typ_evidence;

        $Dokument = new DokumentOdeslani();
        $seznam = array();

        $seradit = UserSettings::get('vypravna_seradit', 'datum');
        // Uloz hodnotu pro pouziti ve formulari razeni
        $this->seradit = $seradit;

        $hledat = UserSettings::get('vypravna_hledat');
        $this->jednoduche_hledani = $hledat;
        $this->template->zobraz_zrusit_hledani = !empty($hledat);

        $filtr = UserSettings::get('vypravna_filtr');
        $this->template->zobraz_zrusit_filtr = !empty($filtr);

        // Volba vystupu - web/tisk/pdf
        if ($this->getParameter('print') || $this->getParameter('print_balik')) {
            @ini_set("memory_limit", PDF_MEMORY_LIMIT);
            if ($vyber = $this->getParameter('vyber')) {
                $seznam = $Dokument->kOdeslani($seradit, explode('-', $vyber));
            } else {
                $filtr_tisk = $this->getParameter('print_balik') ? "balik" : "doporucene";
                $seznam = $Dokument->kOdeslani($seradit, $hledat, $filtr_tisk);
            }
            $this->pdf_output = true;
            $this->template->count_page = ceil(count($seznam) / 10);
            $this->template->cislo_zakaznicke_karty = Settings::get('Ceska_posta_cislo_zakaznicke_karty',
                            '');
            $this->template->zpusob_uhrady = Settings::get('Ceska_posta_zpusob_uhrady', '');

            $ciselnik = Admin_NastaveniPresenter::$ciselnik_zpusoby_uhrad;
            array_shift($ciselnik);
            $this->template->zpusoby_uhrad = $ciselnik;

            $this->setLayout(false);
            $this->setView('podaciarch');
        } else {
            $seznam = $Dokument->kOdeslani($seradit, $hledat, $filtr);
        }

        /* if ( count($seznam)>0 ) {
          foreach ($seznam as $subjekt_index => $subjekt) {
          $seznam[ $subjekt_index ]->druh_zasilky = @unserialize($seznam[ $subjekt_index ]->druh_zasilky);
          }
          } */

        $this->template->seznam = $seznam;
    }

    protected function shutdown($response)
    {

        if ($this->pdf_output) {

            ob_start();
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
            $content = ob_get_clean();
            if ($content) {

                @ini_set("memory_limit", PDF_MEMORY_LIMIT);
                $content = str_replace("<td", "<td valign='top'", $content);

                // Poznamka: zde dany font se nepouzije, pouzije se font z CSS
                $mpdf = new mPDF('iso-8859-2', 'A4', 9, 'Helvetica', 7, 9, 8, 6, 0, 0);

                $app_info = Nette\Environment::getVariable('app_info');
                $app_info = explode("#", $app_info);
                $app_name = (isset($app_info[2])) ? $app_info[2] : 'OSS Spisová služba v3';
                $mpdf->SetCreator($app_name);
                $mpdf->SetAuthor($this->user->getIdentity()->display_name);
                $mpdf->SetTitle('Podací arch');
                $mpdf->WriteHTML($content);
                $mpdf->Output('podaci_arch.pdf', 'I');
            }
        }
    }

    public function actionAkce($data)
    {
        if (isset($data['hromadna_akce'])) {
            if (!isset($data['dokument_vyber'])) {
                $this->flashMessage('Nevybrali jste žádný dokument.', 'warning');
                return;
            }

            $DokumentOdeslani = new DokumentOdeslani();
            switch ($data['hromadna_akce']) {
                /* odeslat */
                case 'odeslat':
                    $count_ok = $count_failed = 0;
                    foreach ($data['dokument_vyber'] as $dokument_odeslani_id)
                        if ($DokumentOdeslani->odeslano($dokument_odeslani_id))
                            $count_ok++;
                        else
                            $count_failed++;

                    if ($count_ok > 0)
                        $this->flashMessage('Úspěšně jste odeslal ' . $count_ok . ' dokumentů.');
                    if ($count_failed > 0)
                        $this->flashMessage('' . $count_failed . ' dokumentů se nepodařilo odeslat!',
                                'warning');
                    if ($count_ok > 0 && $count_failed > 0)
                        $this->redirect('this');
                    break;

                case 'vratit':
                    $count_ok = $count_failed = 0;
                    foreach ($data['dokument_vyber'] as $dokument_odeslani_id)
                        if ($DokumentOdeslani->vraceno($dokument_odeslani_id))
                            $count_ok++;
                        else
                            $count_failed++;

                    if ($count_ok > 0)
                        $this->flashMessage('Úspěšně jste vrátil ' . $count_ok . ' dokumentů.');
                    if ($count_failed > 0)
                        $this->flashMessage('' . $count_failed . ' dokumentů se nepodařilo vrátit!',
                                'warning');
                    if ($count_ok > 0 && $count_failed > 0)
                        $this->redirect('this');
                    break;

                case 'podaci_arch':
                    $vyber = $data['dokument_vyber'];
                    $this->redirect('this', ['print' => 1, 'vyber' => implode('-', $vyber)]);
                    break;

                default:
                    break;
            }
        }
    }

    public function actionZobrazfax()
    {

        $DokumentOdeslani = new DokumentOdeslani();
        $id = $this->getParameter('id');

        $dokument = $DokumentOdeslani->get($id);
        if (!$dokument)
            throw new Exception("Záznam o odeslání ID $id neexistuje.");

        $this->template->dokument = $dokument;
        $this->template->isPrint = $this->getParameter('print');

        $this->setLayout(false);
    }

    public function actionDetail()
    {

        $DokumentOdeslani = new DokumentOdeslani();
        $id = $this->getParameter('id');

        $post_data = $this->getHttpRequest()->getPost();
        if (isset($post_data['datum_odeslani'])) {
            // Ulozit data

            $row = array();
            if (isset($post_data['datum_odeslani'])) {
                $row['datum_odeslani'] = new DateTime($post_data['datum_odeslani']);
            }

            $druh_zasilky_form = @$post_data['druh_zasilky'];
            if (count($druh_zasilky_form) > 0) {
                $row['druh_zasilky'] = serialize(array_keys($druh_zasilky_form));
            } else {
                $row['druh_zasilky'] = null;
            }

            if (isset($post_data['cena_zasilky'])) {
                $row['cena'] = floatval($post_data['cena_zasilky']);
            }
            if (isset($post_data['hmotnost_zasilky'])) {
                $row['hmotnost'] = floatval($post_data['hmotnost_zasilky']);
            }
            if (isset($post_data['cislo_faxu'])) {
                $row['cislo_faxu'] = $post_data['cislo_faxu'];
            }
            if (isset($post_data['zprava'])) {
                $row['zprava'] = $post_data['zprava'];
            }
            if (isset($post_data['poznamka'])) {
                $row['poznamka'] = $post_data['poznamka'];
            }

            try {
                $DokumentOdeslani->update($row, array(array("id=%i", $id)));
                echo "###provedeno###";
                exit;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        $odes = $DokumentOdeslani->get($id);
        if (!$odes)
            throw new Exception("Záznam o odeslání ID $id neexistuje.");

        $this->template->dokument = $odes;

        $this->addComponent(new VyberPostovniZasilky($odes->druh_zasilky), 'druhZasilky');

        $this->setLayout(false);
    }

    protected function createComponentSeraditForm()
    {

        $select = array(
            'datum' => 'data odeslání (vzestupně)',
            'datum_desc' => 'data odeslání (sestupně)',
            'cj' => 'čísla jednacího (vzestupně)',
            'cj_desc' => 'čísla jednacího (sestupně)'
        );

        $form = new Nette\Application\UI\Form();
        $form->addSelect('seradit', 'Seřadit podle:', $select)
                ->setValue($this->seradit)
                ->getControlPrototype()->onchange("return document.forms['frm-seraditForm'].submit();");

        $submit = $form->addSubmit('go_seradit', 'Seřadit');
        $submit->getControlPrototype()->style(array('display' => 'none'));

        $form->onSuccess[] = array($this, 'seraditSucceeded');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function seraditSucceeded(Nette\Application\UI\Form $form, $form_data)
    {
        UserSettings::set('vypravna_seradit', $form_data['seradit']);
        $this->redirect(':Spisovka:Vypravna:default');
    }

    protected function createComponentSearchForm()
    {
        $hledat = !is_null($this->jednoduche_hledani) ? $this->jednoduche_hledani : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);

        $controlPrototype = $form['dotaz']->getControlPrototype();
        $controlPrototype->title = "Hledat lze dle adresáta, předávajícího a čísla jednacího";

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
        UserSettings::set('vypravna_hledat', $data['dotaz']);
        $this->redirect(':Spisovka:Vypravna:' . $this->view);
    }

    public function actionReset()
    {
        $what = $this->getParameter('reset');
        if ($what == 'hledat')
            UserSettings::remove('vypravna_hledat');
        elseif ($what == 'filtr')
            UserSettings::remove('vypravna_filtr');
        $this->redirect(':Spisovka:Vypravna:default');
    }

    public function actionFiltrovat()
    {
        $post_data = $this->getHttpRequest()->getPost();

        // hidden element zajisti, ze detekujeme odeslani formulare, kde neni zadny checkbox zaskrtnuty
        if (!empty($post_data)) {
            if (isset($post_data['druh_zasilky'])) {
                // nastav filtrovani               
                UserSettings::set('vypravna_filtr', array_keys($post_data['druh_zasilky']));
            } else {
                // zrus filtrovani
                UserSettings::remove('vypravna_filtr');
            }
            // v obou pripadech prejdi na vychozi stranku vypravny
            $this->redirect(':Spisovka:Vypravna:default');
        }
    }

    public function renderFiltrovat()
    {
        $filtr = UserSettings::get('vypravna_filtr');
        $this->addComponent(new VyberPostovniZasilky($filtr), 'druhZasilky');
        $this->setLayout(false);
    }

}
