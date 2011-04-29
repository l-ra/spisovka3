<?php

class Spisovka_VyhledatPresenter extends BasePresenter
{

    public function renderDefault()
    {
        $this->template->searchForm = $this['searchForm'];
    }

    public function handleAutoComplete($text, $typ)
    {
        $this->payload->autoComplete = array();

	$text = trim($text);
	if ($text !== '') {

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

    public function handleAutoCompleteOrg($text, $typ)
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
                            "<input type='checkbox' name='predano_org[]' value='". $org->id ."' /> ". $org->zkraceny_nazev ." (". $org->ciselna_rada .")";
                    } else {
                        $this->payload->autoComplete[] =
                            "<input type='checkbox' name='prideleno_org[]' value='". $org->id ."' /> ". $org->zkraceny_nazev ." (". $org->ciselna_rada .")";

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

        $typ_select = array();
        $typ_select = Subjekt::typ_subjektu(null,3);

        $stat_select = array();
        $stat_select = Subjekt::stat(null,3);

        $zpusob_vyrizeni = array();
        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(null, 3);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = $SpisovyZnak->seznam(null,3);

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
        $form->addSelect('typ_dokumentu_id', 'Typ Dokumentu:', $typ_dokumentu);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50);
        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15);
        $form->addText('pocet_listu', 'Počet listů:', 5, 10);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10);
        $form->addSelect('stav_dokumentu', 'Stav dokumentu:', $stav_dokumentu);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->setValue('30');
        $form->addTextArea('poznamka', 'Poznámka:', 80, 6);

        $form->addSelect('zpusob_vyrizeni', 'Způsob vyřízení:', $zpusob_vyrizeni);
        $form->addDatePicker('datum_vyrizeni', 'Datum vyřízení:', 10);
        $form->addText('datum_vyrizeni_cas', 'Čas vyřízení:', 10, 15);
        $form->addDatePicker('datum_odeslani', 'Datum odeslání:', 10);
        $form->addText('datum_odeslani_cas', 'Čas odeslání:', 10, 15);
        $form->addSelect('spisovy_znak_id', 'spisový znak:', $spisznak_seznam);
        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 6);

        $form->addSelect('skartacni_znak','Skartační znak: ', $skartacni_znak);
        $form->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5);
        $form->addSelect('spousteci_udalost','Spouštěcí událost: ', $spudalost_seznam);
        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10);
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10);

        $form->addText('prideleno', 'Přiděleno:', 50, 255);
        $form->addText('predano', 'Předáno:', 50, 255);

        $form->addText('prideleno_org', 'Přiděleno:', 50, 255);
        $form->addText('predano_org', 'Předáno:', 50, 255);

        $form->addSelect('subjekt_type', 'Typ subjektu:', $typ_select);
        $form->addText('subjekt_nazev', 'Název subjektu:', 50, 255);
        $form->addText('subjekt_ic', 'IČ:', 12, 8);
        $form->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form->addText('adresa_cp', 'číslo popisné:', 10, 10);
        $form->addText('adresa_co', 'Číslo orientační:', 10, 10);
        $form->addText('adresa_mesto', 'Obec:', 50, 48);
        $form->addText('adresa_psc', 'PSČ:', 10, 10);
        $form->addSelect('adresa_stat', 'Stát:', $stat_select);

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

