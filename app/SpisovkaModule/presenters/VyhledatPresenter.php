<?php

class Spisovka_VyhledatPresenter extends BasePresenter
{

    public function renderDefault()
    {
        $this->template->searchForm = $this['searchForm'];

        if ( $this->getParam('is_ajax') ) {
            $this->layout = false;
        }

    }

    public function handleAutoComplete($text, $typ)
    {
        $this->payload->autoComplete = array();

	$text = trim($text);
	if ($text !== '') {

            $OrgJednotka = new Orgjednotka();
            $args = array(
                array(
                    'zkraceny_nazev LIKE %s OR','%'.$text.'%',
                    'plny_nazev LIKE %s OR','%'.$text.'%',
                    'ciselna_rada LIKE %s','%'.$text.'%'
                )
            );
            $seznam = $OrgJednotka->seznam($args);
            if ( count($seznam)>0 ) {
                foreach( $seznam as $org ) {
                    if ( $typ == 2 ) {
                        $this->payload->autoComplete[] =
                            "<input type='checkbox' name='predano_org[]' value='". $org->id ."' />organizační jednotce ". $org->zkraceny_nazev ." (". $org->ciselna_rada .")";
                    } else {
                        $this->payload->autoComplete[] =
                            "<input type='checkbox' name='prideleno_org[]' value='". $org->id ."' />organizační jednotce ". $org->zkraceny_nazev ." (". $org->ciselna_rada .")";
                    }
                }
            }

            $Zamestnanci = new Osoba2User();
            $seznam = $Zamestnanci->hledat($text);
            if ( count($seznam)>0 ) {
                foreach( $seznam as $user ) {
                    if ( $typ == 2 ) {
                        $this->payload->autoComplete[] =
                            "<input type='checkbox' name='predano[]' value='". $user->id ."' /> ". Osoba::displayName($user) ." (". $user->name .")";
                    } else {
                        $this->payload->autoComplete[] =
                            "<input type='checkbox' name='prideleno[]' value='". $user->id ."' /> ". Osoba::displayName($user) ." (". $user->name .")";

                    }
                    
                }
            }
	}

        $this->terminate();
    }

    protected function createComponentSearchForm()
    {

        $typ_dokumentu = array();
        $typ_dokumentu = Dokument::typDokumentu(null,3);

        $typ_doruceni = array(
            '0'=>'všechny',
            '1'=>'pouze doručené přes elektronickou podatelnu',
            '2'=>'pouze doručené přes email',
            '3'=>'pouze doručené přes datovou schránkou',
            '4'=>'doručené mimo epodatelnu',
        );

        $typ_select = array();
        $typ_select = Subjekt::typ_subjektu(null,3);

        $stat_select = array();
        $stat_select = Subjekt::stat(null,3);

        $zpusob_doruceni = array();
        $zpusob_doruceni = Dokument::zpusobDoruceni(null, 3);

        $zpusob_odeslani = array();
        $zpusob_odeslani = Dokument::zpusobOdeslani(null, 3);

        $zpusob_vyrizeni = array();
        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(null, 3);

        $spudalost_seznam = array();
        $spudalost_seznam = SpisovyZnak::spousteci_udalost(null, 3);

        $skartacni_znak = array('0'=>'jakýkoli znak','A'=>'A','V'=>'V','S'=>'S');

        $stav_dokumentu = array(
            ''=>'jakýkoli stav',
            '1'=>'nový / rozpracovaný',
            '2'=>'přidělen / předán',
            '3'=>'vyřizuje se',
            '4'=>'vyřízen',
            '5'=>'vyřazen'
            );

        $pridelen = array('0'=>'kdokoli','2'=>'přidělen','1'=>'předán');

        $form = new AppForm();

        $form->addText('nazev', 'Věc:', 80, 100);
        $form->addTextArea('popis', 'Stručný popis:', 80, 3);
        $form->addText('cislo_jednaci', 'Číslo jednací:', 50, 50);
        $form->addText('spisova_znacka', 'Spisová značka:', 50, 50);
        $form->addSelect('dokument_typ_id', 'Typ Dokumentu:', $typ_dokumentu);
        $form->addSelect('typ_doruceni', 'Způsob doručení:', $typ_doruceni);
        $form->addSelect('zpusob_doruceni_id', 'Způsob doručení:', $zpusob_doruceni);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50);
        $form->addDatePicker('datum_vzniku_od', 'Datum doručení/vzniku (od):', 10);
        $form->addText('datum_vzniku_cas_od', 'Čas doručení (od):', 10, 15);
        $form->addDatePicker('datum_vzniku_do', 'Datum doručení/vzniku do:', 10);
        $form->addText('datum_vzniku_cas_do', 'Čas doručení do:', 10, 15);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10);
        $form->addSelect('stav_dokumentu', 'Stav dokumentu:', $stav_dokumentu);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->setValue('30');
        $form->addTextArea('poznamka', 'Poznámka:', 80, 4);

        $form->addSelect('zpusob_vyrizeni', 'Způsob vyřízení:', $zpusob_vyrizeni);
        $form->addDatePicker('datum_vyrizeni_od', 'Datum vyřízení od:', 10);
        $form->addText('datum_vyrizeni_cas_od', 'Čas vyřízení od:', 10, 15);
        $form->addDatePicker('datum_vyrizeni_do', 'Datum vyřízení do:', 10);
        $form->addText('datum_vyrizeni_cas_do', 'Čas vyřízení do:', 10, 15);

        $form->addSelect('zpusob_odeslani', 'Způsob odeslání:', $zpusob_odeslani);
        $form->addDatePicker('datum_odeslani_od', 'Datum odeslání (od):', 10);
        $form->addText('datum_odeslani_cas_od', 'Čas odeslání (od):', 10, 15);
        $form->addDatePicker('datum_odeslani_do', 'Datum odeslání do:', 10);
        $form->addText('datum_odeslani_cas_do', 'Čas odeslání do:', 10, 15);

        $form->addText('spisovy_znak_id', 'spisový znak:');
        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 4);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 4);
        $form->addSelect('skartacni_znak','Skartační znak: ', $skartacni_znak);
        $form->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5);
        $form->addSelect('spousteci_udalost','Spouštěcí událost: ', $spudalost_seznam);
        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10);
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10);

        $form->addText('prideleno', 'Přiděleno:', 50, 255);
        $form->addText('predano', 'Předáno:', 50, 255);

        $form->addCheckbox('prideleno_osobne', 'Přiděleno na mé jméno');
        $form->addCheckbox('prideleno_na_organizacni_jednotku', 'Přiděleno na mou organizační jednotku');
        $form->addCheckbox('predano_osobne', 'Předáno na mé jméno');
        $form->addCheckbox('predano_na_organizacni_jednotku', 'Předáno na mou organizační jednotku');

        
        $form->addSelect('subjekt_type', 'Typ subjektu:', $typ_select);
        $form->addText('subjekt_nazev', 'Název subjektu, jméno, IČ:', 50, 255);
        $form->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form->addText('adresa_mesto', 'Obec:', 50, 48);
        $form->addText('adresa_psc', 'PSČ:', 10, 10);

        $form->addText('subjekt_email', 'Email:', 50, 250);
        $form->addText('subjekt_telefon', 'Telefon:', 50, 150);
        $form->addText('subjekt_isds', 'ID datové schránky:', 10, 50);

        $form->addSubmit('vyhledat', 'Vyhledat')
                 ->onClick[] = array($this, 'vyhledatClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function vyhledatClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Dokument = new Dokument();

        if ( isset($_POST['prideleno']) ) {
            $data['prideleno'] = $_POST['prideleno'];
        }
        if ( isset($_POST['predano']) ) {
            $data['predano'] = $_POST['predano'];
        }
        if ( isset($_POST['prideleno_org']) ) {
            $data['prideleno_org'] = $_POST['prideleno_org'];
        }
        if ( isset($_POST['predano_org']) ) {
            $data['predano_org'] = $_POST['predano_org'];
        }

        $args = $Dokument->filtr(null,$data);

        $this->forward(':Spisovka:Dokumenty:default',array('hledat'=>$args));


    }



}

