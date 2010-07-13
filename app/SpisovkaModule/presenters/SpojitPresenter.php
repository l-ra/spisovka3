<?php

class Spisovka_SpojitPresenter extends BasePresenter
{

    public function renderVyber()
    {
        $this->template->dokument_id = $this->getParam('id',null);
    }

    public function renderNacti()
    {
        $dokument_id = $this->getParam('id',null);
        $query = $this->getParam('q',null);

        $Dokument = new Dokument();
        $args = $Dokument->hledat($query,'dokument');
        $args['order'] = array('podaci_denik_rok','podaci_denik_poradi');

        $seznam = $Dokument->seznam($args);

        if ( count($seznam)>0 ) {
            $tmp = array();
            foreach ( $seznam as $dokument_id ) {
                $dok = $Dokument->getBasicInfo($dokument_id);
                $tmp[ $dok->dokument_id ]['dokument_id'] = $dok->dokument_id;
                $tmp[ $dok->dokument_id ]['cislo_jednaci'] = $dok->cislo_jednaci;
                $tmp[ $dok->dokument_id ]['jid'] = $dok->jid;
                $tmp[ $dok->dokument_id ]['nazev'] = $dok->nazev;
            }
            echo json_encode($tmp);

        } else {
            echo "";
        }

        $this->terminate();
    }

    public function renderVybrano()
    {

        $dokument_id = $this->getParam('id',null);
        $dokument_spojit = $this->getParam('spojit_s',null);

        $Dokument = new Dokument();

        $dok_in = $Dokument->getBasicInfo($dokument_id);
        $dok_out = $Dokument->getBasicInfo($dokument_spojit);
        if ( $dok_in && $dok_out ) {

            // spojit s dokumentem
            $SouvisejiciDokument = new SouvisejiciDokument();
            $SouvisejiciDokument->spojit($dokument_id, $dokument_spojit);

            echo '###vybrano###'. $dok_out->cislo_jednaci .' ('. $dok_out->jid .')';//. $spis->nazev;
            $this->terminate();

        } else {
            // chyba
            $this->template->chyba = 1;
            $this->template->render('vyber');
        }
    }

    public function renderOdebrat()
    {
        $dokument_id = $this->getParam('id',null);
        $spojit_s = $this->getParam('spojeny',null);

        $Souvisejici = new SouvisejiciDokument();
        $param = array( array('dokument_id=%i',$dokument_id),array('spojit_s=%i',$spojit_s) );

        if ( $Souvisejici->odebrat($param) ) {
            $this->flashMessage('Spojený dokument byl odebrán z dokumentu.');
        } else {
            $this->flashMessage('Spojený dokument se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    protected function createComponentUpravitForm()
    {

        $subjekt = $this->template->Subjekt;
        $dokument_id = $this->template->dokument_id;
        $typ_select = Subjekt::typ_subjektu();
        $stat_select = Subjekt::stat();

        $DokumentSubjekt = new DokumentSubjekt();
        $seznam = $DokumentSubjekt->subjekty($dokument_id);
        if ( isset($seznam[@$subjekt->subjekt_id]) ) {
            $smer_default = $seznam[@$subjekt->subjekt_id]->rezim_subjektu;
        } else {
            $smer_default = null;
        }
        unset($seznam);
        
        $smer_select = array('AO'=>'adresát i odesílatel','A'=>'adresát','O'=>'odesílatel');

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('subjekt-vytvorit');
        
        $form1->addHidden('subjekt_id')
                ->setValue(@$subjekt->subjekt_id);
        $form1->addHidden('subjekt_version')
                ->setValue(@$subjekt->subjekt_version);
        $form1->addHidden('dokument_id')
                ->setValue(@$dokument_id);
        
        $form1->addSelect('smer', 'Připojit jako:', $smer_select)
                ->setValue($smer_default);

        $form1->addSelect('type', 'Typ subjektu:', $typ_select)
                ->setValue(@$subjekt->type);
        $form1->addText('nazev_subjektu', 'Název subjektu:', 50, 255)
                ->setValue(@$subjekt->nazev_subjektu);
        $form1->addText('ic', 'IČ:', 12, 8)
                ->setValue(@$subjekt->ic);
        $form1->addText('dic', 'DIČ:', 12, 12)
                ->setValue(@$subjekt->dic);

        $form1->addText('jmeno', 'Jméno:', 50, 24)
                ->setValue(@$subjekt->jmeno);
        $form1->addText('prostredni_jmeno', 'Prostřední jméno:', 50, 35)
                ->setValue(@$subjekt->prostredni_jmeno);
        $form1->addText('prijmeni', 'Příjmení:', 50, 35)
                ->setValue(@$subjekt->prijmeni);
        $form1->addText('rodne_jmeno', 'Rodné jméno:', 50, 35)
                ->setValue(@$subjekt->rodne_jmeno);
        $form1->addText('titul_pred', 'Titul před:', 20, 35)
                ->setValue(@$subjekt->titul_pred);
        $form1->addText('titul_za', 'Titul za:', 20, 10)
                ->setValue(@$subjekt->titul_za);

        $form1->addDatePicker('datum_narozeni', 'Datum narození:', 10)
                ->setValue(@$subjekt->datum_narozeni);
        $form1->addText('misto_narozeni', 'Místo narození:', 50, 48)
                ->setValue(@$subjekt->misto_narozeni);
        $form1->addText('okres_narozeni', 'Okres narození:', 50, 48)
                ->setValue(@$subjekt->okres_narozeni);
        $form1->addText('narodnost', 'Národnost / Stát registrace:', 50, 48)
                ->setValue(@$subjekt->narodnost);
        $form1->addSelect('stat_narozeni', 'Stát narození:', $stat_select)
                ->setValue(@$subjekt->stat_narozeni);

        $form1->addText('adresa_ulice', 'Ulice / část obce:', 50, 48)
                ->setValue(@$subjekt->adresa_ulice);
        $form1->addText('adresa_cp', 'číslo popisné:', 10, 10)
                ->setValue(@$subjekt->adresa_cp);
        $form1->addText('adresa_co', 'Číslo orientační:', 10, 10)
                ->setValue(@$subjekt->adresa_co);
        $form1->addText('adresa_mesto', 'Obec:', 50, 48)
                ->setValue(@$subjekt->adresa_mesto);
        $form1->addText('adresa_psc', 'PSČ:', 10, 10)
                ->setValue(@$subjekt->adresa_psc);
        $form1->addSelect('adresa_stat', 'Stát:', $stat_select)
                ->setValue(@$subjekt->adresa_stat);

        $form1->addText('email', 'Email:', 50, 250)
                ->setValue(@$subjekt->email);
        $form1->addText('telefon', 'Telefon:', 50, 150)
                ->setValue(@$subjekt->telefon);
        $form1->addText('id_isds', 'ID datové schránky:', 10, 50)
                ->setValue(@$subjekt->id_isds);

        $form1->addTextArea('poznamka', 'Poznámka', 50, 6)
                ->setValue(@$subjekt->poznamka);

        $form1->addSubmit('upravit', 'Upravit')
                 ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->controlPrototype->onclick("subjektUpravitStorno();");
                 //->onClick[] = array($this, 'stornoClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function upravitClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $subjekt_id = $data['subjekt_id'];
        $subjekt_version = $data['subjekt_version'];
        $dokument_id = $data['dokument_id'];
        $smer = $data['smer'];
        unset($data['subjekt_id'],$data['subjekt_version'],$data['dokument_id'], $data['smer']);

        $Subjekt = new Subjekt();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_added'] = Environment::getUser()->getIdentity()->user_id;

        try {
            $subjekt_id = $Subjekt->insert_version($data, $subjekt_id);

            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, $smer);

            if (!$this->isAjax()) {
                //$this->redirect('this');
                echo "###zmeneno###".$dokument_id; exit;
                $this->terminate();
            } else {
                $this->invalidateControl('doksubjekt');
            }

            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl upraven.');
            //$this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo upravit.','warning');
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['subjekt_id'];
        $this->redirect('this',array('id'=>$subjekt_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Spisovka:Subjekty:vyber');
    }

    protected function createComponentNovyForm()
    {

        $typ_select = Subjekt::typ_subjektu();
        $stat_select = Subjekt::stat();
        $dokument_id = $this->getParam('dok_id',null);

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('subjekt-vytvorit');

        $form1->addHidden('dokument_id')
                ->setValue($dokument_id);

        $form1->addSelect('type', 'Typ subjektu:', $typ_select);
        $form1->addText('nazev_subjektu', 'Název subjektu:', 50, 255);
        $form1->addText('ic', 'IČ:', 12, 8);
        $form1->addText('dic', 'DIČ:', 12, 12);

        $form1->addText('jmeno', 'Jméno:', 50, 24);
        $form1->addText('prostredni_jmeno', 'Prostřední jméno:', 50, 35);
        $form1->addText('prijmeni', 'Příjmení:', 50, 35);
        $form1->addText('rodne_jmeno', 'Rodné jméno:', 50, 35);
        $form1->addText('titul_pred', 'Titul před:', 20, 35);
        $form1->addText('titul_za', 'Titul za:', 20, 10);

        $form1->addDatePicker('datum_narozeni', 'Datum narození:', 10);
        $form1->addText('misto_narozeni', 'Místo narození:', 50, 48);
        $form1->addText('okres_narozeni', 'Okres narození:', 50, 48);
        $form1->addText('narodnost', 'Národnost / Stát registrace:', 50, 48);
        $form1->addSelect('stat_narozeni', 'Stát narození:', $stat_select);

        $form1->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form1->addText('adresa_cp', 'číslo popisné:', 10, 10);
        $form1->addText('adresa_co', 'Číslo orientační:', 10, 10);
        $form1->addText('adresa_mesto', 'Obec:', 50, 48);
        $form1->addText('adresa_psc', 'PSČ:', 10, 10);
        $form1->addSelect('adresa_stat', 'Stát:', $stat_select);

        $form1->addText('email', 'Email:', 50, 250);
        $form1->addText('telefon', 'Telefon:', 50, 150);
        $form1->addText('id_isds', 'ID datové schránky:', 10, 50);

        $form1->addTextArea('poznamka', 'Poznámka', 50, 6);

        $form1->addSubmit('novy', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->controlPrototype->onclick("return subjektNovyStorno($dokument_id);");
                 //->onClick[] = array($this, 'stornoSeznamClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $data['dokument_id'];
        unset($data['dokument_id']);


        $Subjekt = new Subjekt();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_added'] = Environment::getUser()->getIdentity()->user_id;

        //Debug::dump($data);
        //exit;

        try {
            $subjekt_id = $Subjekt->insert_version($data);

            if (!$this->isAjax()) {
                //$this->redirect('this');
                $this->forward('vyber',array('dok_id'=>$dokument_id));
                //$this->template->render('vyber');
            } else {
                $this->invalidateControl('doksubjekt');
            }

            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl vytvořen.');
            //$this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo vytvořit.','warning');
        }
    }

    protected function createComponentStavForm()
    {

        $subjekt = $this->template->Subjekt;
        $stav_select = Subjekt::stav();

        $form1 = new AppForm();
        $form1->addHidden('subjekt_id')
                ->setValue(@$subjekt->subjekt_id);
        $form1->addHidden('subjekt_version')
                ->setValue(@$subjekt->subjekt_version);
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select);
        $form1->addSubmit('zmenit_stav', 'Změnit stav')
                 ->onClick[] = array($this, 'zmenitStavClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoSeznamClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function zmenitStavClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $subjekt_id = $data['subjekt_id'];
        $subjekt_version = $data['subjekt_version'];

        $Subjekt = new Subjekt();

        //Debug::dump($data); exit;

        try {
            $Subjekt->zmenitStav($data);
            $this->flashMessage('Stav subjektu byl změněn.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id .'-'. $subjekt_version));
        } catch (DibiException $e) {
            $this->flashMessage('Stav subjektu se nepodařilo změnit.','warning');
        }
    }



}
