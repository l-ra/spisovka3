<?php

class Admin_OrgjednotkyPresenter extends BasePresenter
{

    private $hledat;

    public function renderSeznam($hledat = null)
    {
        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        // hledani
        $this->hledat = "";
        $this->template->no_items = 0;
        $args = null;
        if ( isset($hledat) ) {
            $args = array(array(
                    'plny_nazev LIKE %s OR','%'.$hledat.'%',
                    'zkraceny_nazev LIKE %s OR','%'.$hledat.'%',
                    'ciselna_rada LIKE %s','%'.$hledat.'%'
                )
            );

            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        $OrgJednotka = new Orgjednotka();
       
        $result = $OrgJednotka->seznam($args,1);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nová organizační jednotka";

    }


    public function actionDetail()
    {
        $this->template->title = " - Detail organizační jednotky";

        $orgjednotka_id = $this->getParam('id',null);
        $OrgJednotka = new Orgjednotka();

        $org = $OrgJednotka->getInfo($orgjednotka_id);
        $this->template->OrgJednotka = $org;

        $role = $OrgJednotka->seznamRoli($orgjednotka_id);
        $this->template->SeznamRole = $role['role'];
        $this->template->SeznamOrgRole = $role['role_org'];

        // Zmena udaju organizacni jednotky
        $this->template->FormUpravit = $this->getParam('upravit',null);


    }

    public function renderDetail()
    {
        $this->template->roleForm = $this['roleForm'];
    }


/**
 *
 * Formular a zpracovani pro zmenu udaju org. jednotky
 *
 */

    protected function createComponentUpravitForm()
    {

        $org = $this->template->OrgJednotka;
        $OrgJednotka = new Orgjednotka();
        $org_seznam = $OrgJednotka->select(1, @$org->id);

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue(@$org->id);
        $form1->addText('zkraceny_nazev', 'Zkrácený název:', 50, 100)
                ->setValue(@$org->zkraceny_nazev)
                ->addRule(Form::FILLED, 'Zkrácený název org. jednotky musí být vyplněno.');
        $form1->addText('plny_nazev', 'Plný název jednotky:', 50, 200)
                ->setValue(@$org->plny_nazev);
        $form1->addText('ciselna_rada', 'Zkratka / číselná řada:', 15, 30)
                ->setValue(@$org->ciselna_rada)
                ->addRule(Form::FILLED, 'Číselná řada org. jednotky musí být vyplněno.');
        $form1->addTextArea('note', 'Informace:', 50, 5)
                ->setValue(@$org->note);
        $form1->addSelect('stav', 'Stav:', array(0=>'neaktivní',1=>'aktivní'))
                ->setValue(@$org->stav);
        $form1->addSelect('parent_id', 'Nadřazená složka:', $org_seznam)
                ->setValue(@$org->parent_id);
        $form1->addHidden('parent_id_old')
                ->setValue(@$org->parent_id);

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

        $OrgJednotka = new Orgjednotka();
        $orgjednotka_id = $data['id'];
        unset($data['id']);

        try {
            $res = $OrgJednotka->ulozit($data, $orgjednotka_id);
            
            if ( is_object($res) ) {
                $this->flashMessage('Organizační jednotku  "'. $data['zkraceny_nazev'] .'" se nepodařilo upravit.','warning');
                $this->flashMessage($res->getMessage(),'warning');
            } else {
                $this->flashMessage('Organizační jednotka  "'. $data['zkraceny_nazev'] .'"  byla upravena.');
            }            
        } catch (DibiException $e) {
            $this->flashMessage('Organizační jednotku  "'. $data['zkraceny_nazev'] .'" se nepodařilo upravit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }
        $this->redirect('this',array('id'=>$orgjednotka_id));
    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $orgjednotka_id = $data['id'];
        $this->redirect('this',array('id'=>$orgjednotka_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Orgjednotky:seznam');
    }

    protected function createComponentNovyForm()
    {

        $OrgJednotka = new Orgjednotka();
        $org_seznam = $OrgJednotka->select(1);

        $form1 = new AppForm();
        $form1->addText('zkraceny_nazev', 'Zkrácený název:', 50, 100)
                ->addRule(Form::FILLED, 'Zkrácený název org. jednotky musí být vyplněno.');
        $form1->addText('plny_nazev', 'Plný název jednotky:', 50, 200);
        $form1->addText('ciselna_rada', 'Zkratka / číselná řada:', 15, 30)
                ->addRule(Form::FILLED, 'Číselná řada org. jednotky musí být vyplněno.');
        $form1->addTextArea('note', 'Informace:', 50, 5);
        $form1->addSelect('parent_id', 'Nadřazená složka:', $org_seznam);
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

        $OrgJednotka = new Orgjednotka();

        try {
            $orgjednotka_id = $OrgJednotka->ulozit($data);
            if ( is_object($orgjednotka_id) ) {
                $this->flashMessage('Organizační jednotku "'. $data['zkraceny_nazev'] .'" se nepodařilo vytvořit.','warning');
                $this->flashMessage($orgjednotka_id->getMessage(),'warning');
            } else {
                $this->flashMessage('Organizační jednotka  "'. $data['zkraceny_nazev'] .'" byla vytvořena.');
                $this->redirect(':Admin:Orgjednotky:detail',array('id'=>$orgjednotka_id));
            }               
        } catch (DibiException $e) {
            $this->flashMessage('Organizační jednotku "'. $data['zkraceny_nazev'] .'" se nepodařilo vytvořit.','warning');
            $this->flashMessage($e->getMessage(),'warning');
        }
    }

/*
 * Nastaveni organizacni struktury
 */
    protected function createComponentRoleForm()
    {

        $orgjednotka_id = $this->getParam('id',null);
        $OrgJednotka = new Orgjednotka();
        
        $role = $OrgJednotka->seznamRoli($orgjednotka_id);

        $form1 = new AppForm();
        $form1->addHidden('id')
                ->setValue($orgjednotka_id);

        foreach ($role['role'] as $r) {

                $role_available = 0;
                foreach ($role['role_org'] as $role_org) {
                    if ( $role_org->parent_id == $r->id ) {
                        $role_available = 1;
                        break;
                    }
                }

                $form1->addGroup('role_id_' . $r->id);
                $subForm = $form1->addContainer('role'.$r->id);
                $subForm->addCheckbox("org_role", 'povolit')
                        ->setValue( $role_available );

        }

        $form1->addSubmit('upravit', 'Upravit organizační strukturu')
                 ->onClick[] = array($this, 'upravitRoleClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitRoleClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $orgjednotka_id = $data['id'];
        $OrgJednotka = new Orgjednotka();
        unset($data['id']);

        $role = $OrgJednotka->seznamRoli($orgjednotka_id);

        //Debug::dump($data);
        //Debug::dump($role['role_org']);
        //exit;

        // Predkontrola - vyrazeni nemenici opravneni a nedefinovanych opravneni
        foreach ($data as $id => $stav) {

            $role_id = (int) substr($id, 4); // role4

            // porovnat s puvodnim daty = role, ktere se nemenily, vyradime
            foreach ($role['role_org'] as $role_org_id => $role_org) {
                if ( ($role_org->parent_id == $role_id) && ($stav['org_role']==TRUE) ) {
                    unset($data[$id]);
                    unset($role['role_org'][ $role_org_id ]);
                    continue;
                }
            }

            // Vyradime FALSE data - nebyly vybrany
            if ( $stav['org_role']==FALSE ) {
                    unset($data[$id]);
                    continue;
            }

        }

        //echo "=======================";
        //Debug::dump($data);
        //Debug::dump($role['role_org']);
        //exit;


        // Odebrani zbyvajicich roli = oznaceny k odebrani
        if ( count($role['role_org']) > 0 ) {
            foreach ($role['role_org'] as $roleorg_id => $ro) {

                // Odebrani organizacni role ze systemu
                $OrgJednotka->odebratOrganizacniStrukturu($orgjednotka_id, $roleorg_id);
                
            }
        }

        // Pridani novych organizacnich roli
        if ( count($data) > 0 ) {
            foreach ($data as $id => $stav) {
                $role_id = (int) substr($id, 4);

                // Pridani organizacnich roli do systemu
                $OrgJednotka->pridatOrganizacniStrukturu($orgjednotka_id, $role_id);

            }
        }

        $this->flashMessage('Organizační struktura byla upravena.');
        $this->redirect('this',array('id'=>$orgjednotka_id));
    }

    public function actionTruncate()
    {

        set_time_limit(600);

        $Org = new Orgjednotka();
        $Org->deleteAllOrg();

        echo "smnazano";
        exit;
    }

    public function actionObnovit()
    {

        set_time_limit(600);

        $Org = new Orgjednotka();
        $seznam = $Org->nacti();

        echo "<pre>";

        foreach ( $seznam as $org ) {

            //Debug::dump($org);
            if ( empty($org->sekvence) ) {
                echo $org->zkraceny_nazev ."\n";
                $obnovit = array(
                    'sekvence' => $org->id,
                    'sekvence_string' => $org->ciselna_rada .".". $org->id,
                    'uroven' => 0
                );
                $Org->update($obnovit, array( array('id=%i',$org->id) ));
                unset($obnovit);
            }


        }

        exit;
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze názvu organizační jednotky (plný, zkrácený, zkratka)";

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
