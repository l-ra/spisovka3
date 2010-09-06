<?php

class Spisovka_SubjektyPresenter extends BasePresenter
{

    public function renderVyber()
    {

        $this->template->spis_id = $this->getParam('id',null);
        $this->template->dokument_id = $this->getParam('dok_id',null);

        $Subjekt = new Subjekt();
        $args = array( 'where'=>array("stav=1") );
        $seznam = $Subjekt->seznam($args);
        $this->template->seznam = $seznam;
    }

    public function renderNacti()
    {
        $dokument_id = $this->getParam('id',null); // tady jako dokument_id

        $DokumentSubjekt = new DokumentSubjekt();
        $seznam = $DokumentSubjekt->subjekty($dokument_id);
        $this->template->seznamSubjektu = $seznam;
        $this->template->dokument_id = $dokument_id;

    }

    public function renderVybrano()
    {

        $subjekt_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);
        $typ = $this->getParam('typ',null);
        $Subjekt = new Subjekt();

        $subjekt = $Subjekt->getInfo($subjekt_id);
        if ( $subjekt ) {

            // Propojit s dokumentem
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, $typ);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::SUBJEKT_PRIDAN,'Přidán subjekt "'. Subjekt::displayName($subjekt,'jmeno') .'"');

            echo '###vybrano###'. $dokument_id;//. $spis->nazev;
            $this->terminate();

        } else {
            // chyba
            $this->template->dokument_id = $this->getParam('id',null);

            $Spisy = new Spis();
            $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
            $seznam = $Spisy->seznam($args);
            $this->template->seznam = $seznam;

            $this->template->chyba = 1;

            $this->template->render('vyber');

        }
    }

    public function renderOdebrat()
    {
        $subjekt_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);

        $DokumentSubjekt = new DokumentSubjekt();
        $param = array( array('subjekt_id=%i',$subjekt_id),array('dokument_id=%i',$dokument_id) );

        if ( $seznam = $DokumentSubjekt->odebrat($param) ) {

            $Log = new LogModel();
            $Subjekt = new Subjekt();
            $subjekt_info = $Subjekt->getInfo($subjekt_id);
            $Log->logDokument($dokument_id, LogModel::SUBJEKT_ODEBRAN,'Odebrán subjekt "'. Subjekt::displayName($subjekt_info,'jmeno') .'"');

            $this->flashMessage('Subjekt byl úspěšně odebrán.');
        } else {
            $this->flashMessage('Subjekt se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

    public function actionUpravit()
    {
        $subjekt_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);

        $this->template->FormUpravit = $this->getParam('upravit',null);
        if ( strpos($subjekt_id, '-')!==false ) {
            list($subjekt_id, $subjekt_version) = explode('-',$subjekt_id);
        } else {
            $subjekt_version = null;
        }
        $Subjekt = new Subjekt();

        $subjekt = $Subjekt->getInfo($subjekt_id, $subjekt_version);
        $this->template->Subjekt = $subjekt;
        $this->template->dokument_id = $dokument_id;


    }

    public function actionNovy()
    {
    }


    public function actionDetail()
    {
        $this->template->FormUpravit = $this->getParam('upravit',null);
        $subjekt_id = $this->getParam('id',null);
        if ( strpos($subjekt_id, '-')!==false ) {
            list($subjekt_id, $subjekt_version) = explode('-',$subjekt_id);
        } else {
            $subjekt_version = null;
        }
        $Subjekt = new Subjekt();

        $subjekt = $Subjekt->getInfo($subjekt_id, $subjekt_version);
        $this->template->Subjekt = $subjekt;

    }

    public function renderDetail()
    {
        $this->template->upravitForm = $this['upravitForm'];
    }

    public function renderUpravit()
    {
        $this->template->upravitForm = $this['upravitForm'];
    }

    public function renderNovy()
    {
        $this->template->novyForm = $this['novyForm'];
    }
/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    public function renderAres()
    {
        $ic = $this->getParam('id',null);
        $ares = new Ares($ic);
        $data = $ares->get();
        echo json_encode($data);
        exit;
    }

    protected function createComponentUpravitForm()
    {

        $subjekt = $this->template->Subjekt;
        $dokument_id = $this->template->dokument_id;
        $typ_select = Subjekt::typ_subjektu();
        $stat_select = Subjekt::stat();

        $DokumentSubjekt = new DokumentSubjekt();
        $seznam = $DokumentSubjekt->subjekty($dokument_id);
        if ( isset($seznam[@$subjekt->id]) ) {
            $smer_default = $seznam[@$subjekt->id]->rezim_subjektu;
        } else {
            $smer_default = null;
        }
        unset($seznam);
        
        $smer_select = array('AO'=>'adresát i odesílatel','A'=>'adresát','O'=>'odesílatel');

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('subjekt-vytvorit');
        
        $form1->addHidden('subjekt_id')
                ->setValue(@$subjekt->id);
        $form1->addHidden('subjekt_version')
                ->setValue(@$subjekt->version);
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
        $data['user_added'] = Environment::getUser()->getIdentity()->id;

        try {
            $subjekt_id = $Subjekt->insert_version($data, $subjekt_id);

            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, $smer);

            $subjekt_info = $Subjekt->getInfo($subjekt_id);
            //$Log = new LogModel();
            //$Log->logDokument($dokument_id, LogModel::SUBJEKT_ZMENEN,'Změněn subjekt "'. Subjekt::displayName($subjekt_info) .'"');

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

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::SUBJEKT_VYTVOREN,'Vytvořen nový subjekt "'. Subjekt::displayName($subjekt,'jmeno') .'"');


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
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);
        $form1->addHidden('version')
                ->setValue(@$subjekt->version);
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

        $subjekt_id = $data['id'];
        $subjekt_version = $data['version'];

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
