<?php

class Epodatelna_SubjektyPresenter extends BasePresenter
{

    private $nsubjekt;
    private $nzprava;

    public function renderVyber()
    {

        $this->template->spis_id = $this->getParam('id',null);
        $this->template->epodatelna_id = $this->getParam('epod_id',null);
        
        $Subjekt = new Subjekt();
        $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
        $seznam = $Subjekt->seznam($args);
        $this->template->seznam = $seznam;
    }

    public function renderNacti()
    {
        $subjekt_id = $this->getParam('id',null);
        $epodatelna_id = $this->getParam('epod_id',null);

        $Subjekt = new Subjekt();
        $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
        $subjekt = $Subjekt->getInfo($subjekt_id);
        $this->template->subjekt = $subjekt;
        $this->template->epodatelna_id = $epodatelna_id;

    }

    public function renderVybrano()
    {

        $subjekt_id = $this->getParam('id',null);
        $epod_id = $this->getParam('epod_id',null);
        $typ = $this->getParam('typ',null);
        $Subjekt = new Subjekt();

        $subjekt = $Subjekt->getInfo($subjekt_id);
        if ( $subjekt ) {
            echo '###vybrano###'. $subjekt_id ."#". $epod_id;
            $this->terminate();

        } else {
            // chyba
            $this->template->epodatelna_id = $this->getParam('epod_id',null);

            $Spisy = new Spis();
            $args = null;// array( 'where'=>array("nazev_subjektu like %s",'%blue%') );
            $seznam = $Spisy->seznam($args);
            $this->template->seznam = $seznam;

            $this->template->chyba = 1;

            $this->template->render('vyber');

        }
    }

    public function actionNovy()
    {
        /* Nacteni zpravy */
        $Epodatelna = new Epodatelna();
        $epodatelna_id = $this->getParam('epod_id',null);

        if ( $epodatelna_id ) {
            $zprava = $Epodatelna->getInfo($epodatelna_id);
            if ( $zprava ) {

                $this->nzprava = $zprava;
                $subjekt = new stdClass();

                $original = null;
                if ( !empty($zprava->email_signature) ) {
                    // Nacteni originalu emailu
                    if ( !empty( $zprava->source_id ) ) {
                        $DefaultPresenter = new Epodatelna_DefaultPresenter();
                        $original = $DefaultPresenter->nactiEmail($zprava->source_id);
                    }

                    $subjekt->nazev = $zprava->odesilatel;
                    $subjekt->prijmeni = $original['zprava']->from->personal;
                    $subjekt->email = $original['zprava']->from->email;

                    if ( $original['signature']['signed'] == 1 ) {
                        $subjekt->nazev = $original['signature']['cert_info']['organizace'];
                        $subjekt->prijmeni = $original['signature']['cert_info']['jmeno'];
                        $subjekt->email = $original['signature']['cert_info']['email'];
                        $subjekt->adresa_ulice = $original['signature']['cert_info']['adresa'];
                    }
                    $this->nsubjekt = $subjekt;

                } else if ( !empty($zprava->isds_signature) ) {
                    // Nacteni originalu DS
                } else {
                    // zrejme odchozi zprava ven
                }
            }
        }
    }

    public function actionUpravit()
    {
        $subjekt_id = $this->getParam('id',null);
        //$dokument_id = $this->getParam('dok_id',null);
        $epodatelna_id = $this->getParam('epod_id',null);

        $this->template->FormUpravit = $this->getParam('upravit',null);
        if ( strpos($subjekt_id, '-')!==false ) {
            list($subjekt_id, $subjekt_version) = explode('-',$subjekt_id);
        } else {
            $subjekt_version = null;
        }
        $Subjekt = new Subjekt();

        $subjekt = $Subjekt->getInfo($subjekt_id, $subjekt_version);
        $this->template->Subjekt = $subjekt;
        //$this->template->dokument_id = $dokument_id;
        $this->template->epodatelna_id = $epodatelna_id;


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

    public function renderNovy()
    {
        $this->template->novyForm = $this['novyForm'];
    }

    public function renderUpravit()
    {
        $this->template->upravitForm = $this['upravitForm'];
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
        $typ_select = Subjekt::typ_subjektu();
        $stat_select = Subjekt::stat();

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('epodsubjekt-vytvorit');

        $form1->addHidden('subjekt_id')
                ->setValue(@$subjekt->subjekt_id);
        $form1->addHidden('subjekt_version')
                ->setValue(@$subjekt->subjekt_version);
        $form1->addHidden('epodatelna_id')
                ->setValue(@$this->template->epodatelna_id);

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
        //$dokument_id = $data['dokument_id'];
        $epodatelna_id = $data['epodatelna_id'];
        $smer = 0;
        unset($data['subjekt_id'],$data['subjekt_version'],$data['epodatelna_id']);

        $Subjekt = new Subjekt();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_added'] = Environment::getUser()->getIdentity()->user_id;

        try {
            $subjekt_id = $Subjekt->insert_version($data, $subjekt_id);

            $subjekt_info = $Subjekt->getInfo($subjekt_id);
            //$Log = new LogModel();
            //$Log->logDokument($dokument_id, LogModel::SUBJEKT_ZMENEN,'Změněn subjekt "'. Subjekt::displayName($subjekt_info) .'"');

            if (!$this->isAjax()) {
                //$this->redirect('this');
                echo "###zmeneno###".$subjekt_id ."#".$epodatelna_id; exit;
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
        $subjekt = isset($this->nsubjekt)?$this->nsubjekt:null;

        $form1 = new AppForm();
        $form1->getElementPrototype()->id('subjekt-vytvorit');
        
        $form1->addSelect('type', 'Typ subjektu:', $typ_select);
        $form1->addText('nazev_subjektu', 'Název subjektu:', 50, 255)
                ->setValue(@$subjekt->nazev);
        $form1->addText('ic', 'IČ:', 12, 8)
                ->setValue(@$subjekt->ic);
        $form1->addText('dic', 'DIČ:', 12, 12);

        $form1->addText('jmeno', 'Jméno:', 50, 24)
                ->setValue(@$subjekt->jmeno);
        $form1->addText('prostredni_jmeno', 'Prostřední jméno:', 50, 35);
        $form1->addText('prijmeni', 'Příjmení:', 50, 35)
                ->setValue(@$subjekt->prijmeni);
        $form1->addText('rodne_jmeno', 'Rodné jméno:', 50, 35);
        $form1->addText('titul_pred', 'Titul před:', 20, 35);
        $form1->addText('titul_za', 'Titul za:', 20, 10);

        $form1->addDatePicker('datum_narozeni', 'Datum narození:', 10);
        $form1->addText('misto_narozeni', 'Místo narození:', 50, 48);
        $form1->addText('okres_narozeni', 'Okres narození:', 50, 48);
        $form1->addText('narodnost', 'Národnost / Stát registrace:', 50, 48);
        $form1->addSelect('stat_narozeni', 'Stát narození:', $stat_select);

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
        $form1->addSelect('adresa_stat', 'Stát:', $stat_select);

        $form1->addText('email', 'Email:', 50, 250)
                ->setValue(@$subjekt->email);
        $form1->addText('telefon', 'Telefon:', 50, 150)
                ->setValue(@$subjekt->telefon);
        $form1->addText('id_isds', 'ID datové schránky:', 10, 50)
                ->setValue(@$subjekt->id_isds);

        $form1->addTextArea('poznamka', 'Poznámka', 50, 6);

        $form1->addSubmit('novy', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
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

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

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
                $this->forward('vyber');
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
