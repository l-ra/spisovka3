<?php

class Admin_SubjektyPresenter extends BasePresenter
{

    private $hledat;

    public function renderSeznam($hledat = null)
    {

        // paginator
        $abcPaginator = new AbcPaginator($this, 'abc');
        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        // hledani
        $this->hledat = "";
        $this->template->no_items = 0;
        $args = null;
        if ( isset($hledat) ) {
            $args = array( 'where'=>array(array(
                    'nazev_subjektu LIKE %s OR','%'.$hledat.'%',
                    'ic LIKE %s OR','%'.$hledat.'%',
                    'email LIKE %s OR','%'.$hledat.'%',
                    'telefon LIKE %s OR','%'.$hledat.'%',
                    'id_isds LIKE %s OR','%'.$hledat.'%',
                    'adresa_mesto LIKE %s OR','%'.$hledat.'%',
                    'adresa_psc LIKE %s OR','%'.$hledat.'%',
                    "CONCAT(jmeno,' ',prijmeni) LIKE %s OR",'%'.$hledat.'%',
                    "CONCAT(prijmeni,' ',jmeno) LIKE %s",'%'.$hledat.'%'
                ))
            );

            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }
        
        // zobrazit podle pismena
        $abc = $abcPaginator->getParam('abc');
        if ( !empty($abc) ) {
            if ( isset($args['where']) ) {
                $args['where'][] = array("nazev_subjektu LIKE %s OR prijmeni LIKE %s",$abc.'%',$abc.'%');
            } else {
                $args = array('where'=>array(array("nazev_subjektu LIKE %s OR prijmeni LIKE %s",$abc.'%',$abc.'%')));
            }
        }

        // pouze aktivni
        if ( isset($args['where']) ) {
            $args['where'][] = array('stav=1');
        } else {
            $args = array('where'=>array('stav=1'));
        }

        // nacteni
        $Subjekt = new Subjekt();
        $result = $Subjekt->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nový subjekt";

    }


    public function actionDetail()
    {
        $this->template->title = " - Detail subjektu";

        $this->template->FormUpravit = $this->getParam('upravit',null);

        $subjekt_id = $this->getParam('id',null);

        $Subjekt = new Subjekt();
        $subjekt = $Subjekt->getInfo($subjekt_id);
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
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);

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
                 ->onClick[] = array($this, 'stornoClicked');

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

        $subjekt_id = $data['id'];
        unset($data['id']);

        $Subjekt = new Subjekt();

        try {
            $subjekt_id = $Subjekt->ulozit($data, $subjekt_id);
            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl upraven.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo upravit.','warning');
            Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $subjekt_id = $data['id'];
        $this->redirect('this',array('id'=>$subjekt_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Subjekty:seznam');
    }

    protected function createComponentNovyForm()
    {

        $typ_select = Subjekt::typ_subjektu();
        $stat_select = Subjekt::stat();

        $form1 = new AppForm();
        
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

        try {
            $subjekt_id = $Subjekt->ulozit($data);
            $this->flashMessage('Subjekt  "'. Subjekt::displayName($data,'jmeno') .'"  byl vytvořen.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Subjekt "'. Subjekt::displayName($data,'jmeno') .'" se nepodařilo vytvořit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }
    }

    protected function createComponentStavForm()
    {

        $subjekt = $this->template->Subjekt;
        $stav_select = Subjekt::stav();

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);
        $form1->addSelect('stav', 'Změnit stav na:', $stav_select)
                ->setValue(@$subjekt->stav);
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
        $Subjekt = new Subjekt();

        try {
            $Subjekt->zmenitStav($data);
            $this->flashMessage('Stav subjektu byl změněn.');
            $this->redirect(':Admin:Subjekty:detail',array('id'=>$subjekt_id));
        } catch (DibiException $e) {
            $this->flashMessage('Stav subjektu se nepodařilo změnit.','warning');
        }
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
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

    public function hledatSimpleClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->forward('this', array('hledat'=>$data['dotaz']));

    }


}
