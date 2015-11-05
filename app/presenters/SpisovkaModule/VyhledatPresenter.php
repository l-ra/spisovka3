<?php

class Spisovka_VyhledatPresenter extends BasePresenter
{

    public function getSettingName()
    {
        return 'spisovka_dokumenty_hledat';
    }

    public function getRedirectPath($backlink = null)
    {
        return ':Spisovka:Dokumenty:default';
    }

    public function renderDefault()
    {
        $this->template->form = $this['searchForm'];

        $user = $this->user;
        $this->template->muzeHledatDlePrideleni = $user->isAllowed(NULL, 'is_vedouci') || $user->isAllowed('Dokument',
                        'cist_moje_oj') || $user->isAllowed('Dokument', 'cist_vse');
        $this->template->vidiVsechnyDokumenty = $user->isAllowed('Dokument', 'cist_vse');
    }

    public function handleAutoComplete($text, $typ, $user = null, $org = null)
    {
        self::autoCompleteHandler($this, $text, $typ, $user, $org);
    }

    public static function autoCompleteHandler($presenter, $text, $typ, $user = null, $org = null)
    {
        $presenter->payload->autoComplete = array();

        $user_a = array();
        $org_a = array();
        $user = trim($user);
        if (!empty($user)) {
            $user_a = explode(",", $user);
        }
        $org = trim($org);
        if (!empty($org)) {
            $org_a = explode(",", $org);
        }

        $text = trim($text);
        if ($text !== '') {

            $OrgJednotka = new Orgjednotka();
            $args = array(
                array(
                    'zkraceny_nazev LIKE %s OR', '%' . $text . '%',
                    'plny_nazev LIKE %s OR', '%' . $text . '%',
                    'ciselna_rada LIKE %s', '%' . $text . '%'
                )
            );
            $seznam = $OrgJednotka->seznam($args);
            if (count($seznam) > 0) {
                foreach ($seznam as $org) {
                    $checked_org = ( in_array($org->id, $org_a) ) ? ' checked="checked"' : '';
                    if ($typ == 2) {
                        $presenter->payload->autoComplete[] = "<input type='checkbox' name='predano_org[]' value='" . $org->id . "' $checked_org />organizační jednotce " . $org->zkraceny_nazev . " (" . $org->ciselna_rada . ")";
                    } else {
                        $presenter->payload->autoComplete[] = "<input type='checkbox' name='prideleno_org[]' value='" . $org->id . "' $checked_org />organizační jednotce " . $org->zkraceny_nazev . " (" . $org->ciselna_rada . ")";
                    }
                }
            }

            $Zamestnanci = new Osoba2User();
            $seznam = $Zamestnanci->hledat($text);
            if (count($seznam) > 0) {
                foreach ($seznam as $user) {
                    $display_name = Osoba::displayName($user);
                    $checked_user = ( in_array($user->user_id, $user_a) ) ? ' checked="checked"'
                                : '';

                    if ($typ == 2)
                        $s = "<input type='checkbox' name='predano[]'";
                    else
                        $s = "<input type='checkbox' name='prideleno[]'";
                    $s .= " value='" . $user->user_id . "' $checked_user /> $display_name";
                    if ($user->pocet_uctu > 1)
                        $s .= " ( {$user->username} )";
                    $presenter->payload->autoComplete[] = $s;
                }
            }
        }

        $presenter->terminate();
    }

    public function actionReset()
    {
        UserSettings::set($this->getSettingName(), serialize(null));
        $this->redirect($this->getRedirectPath());
    }

    protected function createComponentSearchForm()
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

        $skartacni_znak = array('0' => 'jakýkoli znak', 'A' => 'A', 'V' => 'V', 'S' => 'S');

        /** Hledání podle stavu bylo z nějakého důvodu z programu odstraněno
            $stav_dokumentu = array(
                '' => 'jakýkoli stav',
                '1' => 'nový / rozpracovaný',
                '2' => 'přidělen / předán',
                '3' => 'vyřizuje se',
                '4' => 'vyřízen',
                '5' => 'vyřazen'
            );
        */

        $hledat = UserSettings::get($this->getSettingName());

        if (!empty($hledat)) {
            $hledat = unserialize($hledat);
            if (!is_array($hledat)) {
                $hledat = null;
            }
        } else {
            $hledat = null;
        }
        $this->template->params = $hledat;
        // Nette\Diagnostics\Debugger::dump($hledat);
        unset($hledat['prideleno'], $hledat['predano'], $hledat['prideleno_org'],
                $hledat['predano_org']);

        $form = new Spisovka\Form();

        $form->addHidden('backlink')
                ->setValue($this->getParameter('zpet', ''));
        
        $form->addText('nazev', 'Věc:', 80, 100)
                ->setValue(@$hledat['nazev']);
        $form->addTextArea('popis', 'Popis:', 80, 3)
                ->setValue(@$hledat['popis']);
        $form->addText('cislo_jednaci', 'Číslo jednací:', 50, 50)
                ->setValue(@$hledat['cislo_jednaci']);
        $form->addText('spisova_znacka', 'Název spisu:', 50, 50)
                ->setValue(@$hledat['spisova_znacka']);
        $form->addSelect('dokument_typ_id', 'Typ dokumentu:', $typ_dokumentu)
                ->setValue(@$hledat['dokument_typ_id']);
        $form->addSelect('typ_doruceni', 'Způsob doručení před e-podatelnu:', $typ_doruceni)
                ->setValue(@$hledat['typ_doruceni']);
        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:', $zpusob_doruceni)
                ->setValue(@$hledat['zpusob_doruceni_id']);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$hledat['cislo_jednaci_odesilatele']);
        $form->addText('cislo_doporuceneho_dopisu', 'Číslo doporučeného dopisu:', 50, 50)
                ->setValue(@$hledat['cislo_doporuceneho_dopisu']);
        $form->addCheckbox('cislo_doporuceneho_dopisu_pouze', 'Pouze doporučené dopisy')
                ->setValue((@$hledat['cislo_doporuceneho_dopisu_pouze']) ? 1 : 0);
        $form->addDatePicker('datum_vzniku_od', 'Datum doručení/vzniku (od):', 10)
                ->setValue(@$hledat['datum_vzniku_od']);
        $form->addText('datum_vzniku_cas_od', 'Čas doručení (od):', 10, 15)
                ->setValue(@$hledat['datum_vzniku_cas_od']);
        $form->addDatePicker('datum_vzniku_do', 'Datum doručení/vzniku do:', 10)
                ->setValue(@$hledat['datum_vzniku_do']);
        $form->addText('datum_vzniku_cas_do', 'Čas doručení do:', 10, 15)
                ->setValue(@$hledat['datum_vzniku_cas_do']);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$hledat['pocet_listu']);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$hledat['pocet_priloh']);
        //$form->addSelect('stav_dokumentu', 'Stav dokumentu:', $stav_dokumentu)
        //        ->setValue(@$hledat['stav_dokumentu']);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->setValue(@$hledat['lhuta']);
        $form->addTextArea('poznamka', 'Poznámka:', 80, 4)
                ->setValue(@$hledat['poznamka']);

        $form->addSelect('zpusob_vyrizeni', 'Způsob vyřízení:', $zpusob_vyrizeni)
                ->setValue(@$hledat['zpusob_vyrizeni']);
        $form->addDatePicker('datum_vyrizeni_od', 'Datum vyřízení od:', 10)
                ->setValue(@$hledat['datum_vyrizeni_od']);
        $form->addText('datum_vyrizeni_cas_od', 'Čas vyřízení od:', 10, 15)
                ->setValue(@$hledat['datum_vyrizeni_cas_od']);
        $form->addDatePicker('datum_vyrizeni_do', 'Datum vyřízení do:', 10)
                ->setValue(@$hledat['datum_vyrizeni_do']);
        $form->addText('datum_vyrizeni_cas_do', 'Čas vyřízení do:', 10, 15)
                ->setValue(@$hledat['datum_vyrizeni_cas_do']);

        $form->addSelect('zpusob_odeslani', 'Způsob odeslání:', $zpusob_odeslani)
                ->setValue(@$hledat['zpusob_odeslani']);
        $form->addDatePicker('datum_odeslani_od', 'Datum odeslání (od):', 10)
                ->setValue(@$hledat['datum_odeslani_od']);
        $form->addText('datum_odeslani_cas_od', 'Čas odeslání (od):', 10, 15)
                ->setValue(@$hledat['datum_odeslani_cas_od']);
        $form->addDatePicker('datum_odeslani_do', 'Datum odeslání do:', 10)
                ->setValue(@$hledat['datum_odeslani_do']);
        $form->addText('datum_odeslani_cas_do', 'Čas odeslání do:', 10, 15)
                ->setValue(@$hledat['datum_odeslani_cas_do']);

        $vyber = isset($hledat['druh_zasilky']) ? unserialize($hledat['druh_zasilky']) : null;
        $form->addComponent(new VyberPostovniZasilky($vyber), 'druh_zasilky');
        unset($hledat['druh_zasilky']);

        $form->addComponent(new SpisovyZnakComponent(), 'spisovy_znak_id');
        $form->getComponent('spisovy_znak_id')->setValue(@$hledat['spisovy_znak_id'])
        ->controlPrototype->onchange('');

        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 4)
                ->setValue(@$hledat['ulozeni_dokumentu']);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 4)
                ->setValue(@$hledat['poznamka_vyrizeni']);
        $form->addSelect('skartacni_znak', 'Skartační znak: ', $skartacni_znak)
                ->setValue(@$hledat['skartacni_znak']);
        $form->addText('skartacni_lhuta', 'Skartační lhůta: ', 5, 5)
                ->setValue(@$hledat['skartacni_lhuta']);
        $form->addSelect('spousteci_udalost', 'Spouštěcí událost: ', $spudalost_seznam)
                ->setValue(@$hledat['spousteci_udalost']);
        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$hledat['vyrizeni_pocet_listu']);
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$hledat['vyrizeni_pocet_priloh']);

        $form->addText('prideleno_text', 'Přidělen:', 50, 255)
                        ->setValue(@$hledat['prideleno_text'])
                        ->getControlPrototype()->autocomplete = 'off';

        $form->addText('predano_text', 'Předán:', 50, 255)
                        ->setValue(@$hledat['predano_text'])
                        ->getControlPrototype()->autocomplete = 'off';

        $form->addCheckbox('prideleno_osobne', 'Přidělen na mé jméno')
                ->setValue((@$hledat['prideleno_osobne']) ? 1 : 0);
        $form->addCheckbox('prideleno_na_organizacni_jednotku',
                        'Přidělen na mou organizační jednotku')
                ->setValue((@$hledat['prideleno_na_organizacni_jednotku']) ? 1 : 0);
        $form->addCheckbox('predano_osobne', 'Předán na mé jméno')
                ->setValue((@$hledat['predano_osobne']) ? 1 : 0);
        $form->addCheckbox('predano_na_organizacni_jednotku',
                        'Předán na mou organizační jednotku')
                ->setValue((@$hledat['predano_na_organizacni_jednotku']) ? 1 : 0);


        $form->addSelect('subjekt_type', 'Typ subjektu:', $typ_select)
                ->setValue(@$hledat['subjekt_type']);
        $form->addText('subjekt_nazev', 'Název subjektu, jméno, IČ:', 50, 255)
                ->setValue(@$hledat['subjekt_nazev']);
        $form->addText('adresa_ulice', 'Ulice / část obce:', 50, 48)
                ->setValue(@$hledat['adresa_ulice']);
        $form->addText('adresa_mesto', 'Obec:', 50, 48)
                ->setValue(@$hledat['adresa_mesto']);
        $form->addText('adresa_psc', 'PSČ:', 10, 10)
                ->setValue(@$hledat['adresa_psc']);

        $form->addText('subjekt_email', 'Email:', 50, 250)
                ->setValue(@$hledat['subjekt_email']);
        $form->addText('subjekt_telefon', 'Telefon:', 50, 150)
                ->setValue(@$hledat['subjekt_telefon']);
        $form->addText('subjekt_isds', 'ID datové schránky:', 10, 50)
                ->setValue(@$hledat['subjekt_isds']);

        $form->addSubmit('vyhledat', 'Vyhledat')
                ->onClick[] = array($this, 'vyhledatClicked');


        if (count($hledat)) {
            foreach (array_keys($hledat) as $hledat_index) {
                $controlPrototype = $form[$hledat_index]->getControlPrototype();
                $controlPrototype->style(array('background-color' => '#ccffcc', 'border' => '1px #c0c0c0 solid'));
            }
        }

        return $form;
    }

    public function vyhledatClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true /* jako pole, ne ArrayHash */);

        $backlink = $data['backlink'];
        unset($data['backlink']);

        $post = $this->getHttpRequest()->getPost();
        
        if (isset($post['prideleno'])) {
            $data['prideleno'] = $post['prideleno'];
        }
        if (isset($post['predano'])) {
            $data['predano'] = $post['predano'];
        }
        if (isset($post['prideleno_org'])) {
            $data['prideleno_org'] = $post['prideleno_org'];
        }
        if (isset($post['predano_org'])) {
            $data['predano_org'] = $post['predano_org'];
        }
        if (isset($post['druh_zasilky'])) {
            if (count($post['druh_zasilky']) > 0) {
                $data['druh_zasilky'] = serialize(array_keys($post['druh_zasilky']));
            }
        } else {
            $data['druh_zasilky'] = null;
        }

        // eliminujeme prazdne hodnoty
        foreach ($data as $d_index => $d_value) {
            if (is_array($d_value)) {
                continue;
            } else if (@strlen($d_value) == 0) {
                unset($data[$d_index]);
            } else if ($d_value == "0") {
                unset($data[$d_index]);
            } else if ($d_value === false) {
                unset($data[$d_index]);
            }
        }

        UserSettings::set($this->getSettingName(), serialize($data));
        $this->redirect($this->getRedirectPath($backlink));
    }

}
