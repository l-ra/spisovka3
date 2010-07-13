<?php

class Admin_ZamestnanciPresenter extends BasePresenter
{

    public function renderSeznam()
    {
        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $Osoba = new Osoba();
        $result = $Osoba->seznam();
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);


        $this->template->seznam = $seznam;

    }

    public function actionNovy()
    {
        $this->template->title = " - Nový zaměstnanec";

    }


    public function actionDetail()
    {
        $this->template->title = " - Detail zaměstnance";
        $Osoba = new Osoba();
        $User = new UserModel();

        $osoba_id = $this->getParam('id',null);
        $this->template->Osoba = $Osoba->getInfo($osoba_id);

        // Zmena osobnich udaju
        $this->template->FormUpravit = $this->getParam('upravit',null);

        // Zmena roli
        $this->template->RoleUpravit = $this->getParam('role',null);


        $uzivatel = $Osoba->getUser($osoba_id);

        $this->template->Uzivatel = $uzivatel;

        // Zmena hesla
        $zmena_hesla = $this->getParam('user',null);
        $this->template->ZmenaHesla = null;
        if ( !is_null($zmena_hesla) ) {
            if ( key_exists($zmena_hesla, $uzivatel) ) {
                $this->template->ZmenaHesla = $zmena_hesla;
            }
        }

        // Vytvoreni uctu
        $vytvorit_ucet = $this->getParam('new_user',null);
        if ( !is_null($vytvorit_ucet) ) {
            $this->template->vytvoritUcet = 1;
        }

        // Odebrani uctu
        $odebrat_ucet = $this->getParam('odebrat',null);
        if ( !is_null($odebrat_ucet) ) {
            if ( $User->odebratUcet($osoba_id, $odebrat_ucet) ) {
                $this->flashMessage('Účet uživatele byl odebrán.');
            }
        }

        
        if ( count($uzivatel)>0 ) {
            $role = array();
            foreach ($uzivatel as $uziv) {
                $role[ $uziv->user_id ] = $User->getRoles($uziv->user_id);
            }

            $this->template->Role = $role;
        } else {
            $this->template->Role = null;
        }

    }

    public function renderDetail()
    {
        $this->template->roleForm = $this['roleForm'];
    }


/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    protected function createComponentUpravitForm()
    {

        $osoba = $this->template->Osoba;

        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->osoba_id);
        $form1->addText('jmeno', 'Jméno:', 50, 150)
                ->setValue(@$osoba->jmeno);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->setValue(@$osoba->prijmeni);
        $form1->addText('titul_pred', 'Titul před:', 50, 150)
                ->setValue(@$osoba->titul_pred);
        $form1->addText('titul_za', 'Titul za:', 50, 150)
                ->setValue(@$osoba->titul_za);
        $form1->addText('email', 'Email:', 50, 150)
                ->setValue(@$osoba->email);
        $form1->addText('telefon', 'Telefon:', 50, 150)
                ->setValue(@$osoba->telefon);
        $form1->addText('pozice', 'Funkce:', 50, 150)
                ->setValue(@$osoba->pozice);
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
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();
        $osoba_id = $data['osoba_id'];
        $data['date_modified'] = new DateTime();
        unset($data['osoba_id']);

        $Osoba->update($data,array('osoba_id = %i',$osoba_id));

        $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl upraven.');
        $this->redirect('this',array('id'=>$osoba_id));
    }

    public function stornoClicked(SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();
        //$osoba = $this->template->Osoba;
        //$osoba_id = $osoba->osoba_id;
        $osoba_id = $data['osoba_id'];
        $this->redirect('this',array('id'=>$osoba_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Admin:Zamestnanci:seznam');
    }

    protected function createComponentNovyForm()
    {

        $form1 = new AppForm();
        $form1->addText('jmeno', 'Jméno:', 50, 150);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150);
        $form1->addText('titul_pred', 'Titul před:', 50, 150);
        $form1->addText('titul_za', 'Titul za:', 50, 150);
        $form1->addText('email', 'Email:', 50, 150);
        $form1->addText('telefon', 'Telefon:', 50, 150);
        $form1->addText('pozice', 'Funkce:', 50, 150);
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
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();
        $data['stav'] = 1;
        $data['date_created'] = new DateTime();

        try {
            $osoba_id = $Osoba->insert($data);
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl vytvořen.');
            $this->redirect(':Admin:Zamestnanci:detail',array('id'=>$osoba_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zaměstnance "'. Osoba::displayName($data) .'" se nepodařilo vytvořit.','warning');
        }
        
    }


/**
 *
 * Formular a zpracovani pro zmenu hesla
 *
 */

    protected function createComponentUserForm()
    {

        $osoba = $this->template->Osoba;
        $user_id = $this->template->ZmenaHesla;


        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->osoba_id);
        $form1->addHidden('user_id')
                ->setValue($user_id);

        $form1->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form1->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form1["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form1["heslo"]);

        $form1->addSubmit('upravit', 'Změnit heslo')
                 ->onClick[] = array($this, 'zmenitHesloClicked');
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


    public function zmenitHesloClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $User = new UserModel();
        $Osoba = new Osoba();

        $osoba_id = $data['osoba_id'];
        $uzivatel = $Osoba->getUser($osoba_id);
        $zmeneno = 0;
        
        foreach ($uzivatel as $u) {
            if ( $u->user_id == $data['user_id'] ) {
                if ( $User->zmenitHeslo($u->user_id, $data['heslo']) ) {
                    $zmeneno = 1;
                }
                break;
            }
        }

        if ( $zmeneno == 1 ) {
            $this->flashMessage('Heslo uživatele "'. $u->username .'"  bylo úspěšně změněno.');
        } else {
            $this->flashMessage('Nedošlo k žádné změně.');
        }
        $this->redirect('this',array('id'=>$osoba_id));
    }

/**
 *
 * Formular pro vytvoreni uctu
 *
 */

    protected function createComponentNewUserForm()
    {

        $osoba = $this->template->Osoba;
        $Role = new RoleModel();
        $role_seznam = $Role->seznam();
        $role_select = array();
        foreach ($role_seznam as $key => $value) {
            $role_select[ $value->role_id ] = $value->name;
        }

        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->osoba_id);

        $form1->addText('username', 'Uživatelské jméno:', 30, 150);
        $form1->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form1->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form1["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form1["heslo"]);
        $form1->addSelect('role', 'Role:', $role_select);


        $form1->addSubmit('vytvoritUcet', 'Vytvořit účet')
                 ->onClick[] = array($this, 'vytvoritUcetClicked');
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

    public function vytvoritUcetClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $osoba_id = $data['osoba_id'];

        $User = new UserModel();
        try {
            $User->pridatUcet($osoba_id, $data);
            $this->flashMessage('Účet uživatele "'. $data['username'] .'" byl úspěšně vytvořen.');
            $this->redirect('this',array('id'=>$osoba_id));
        } catch (DibiException $e) {
            if ( $e->getCode() == 1062 ) {
                $this->flashMessage('Uživatel "'. $data['username'] .'" již existuje. Zvolte jiný.','warning');

                if ( !isset($this->template->Osoba) ) {
                    $Osoba = new Osoba();
                    $this->template->Osoba = $Osoba->getInfo($osoba_id);
                }

            } else {
                $this->flashMessage('Účet uživatele se nepodařilo vytvořit.','warning');
            }
            $this->template->vytvoritUcet = 1;
        }
    }


/*
 * Zmena roli
 *
 */

    protected function createComponentRoleForm()
    {

        $osoba = $this->template->Osoba;
        
        $user = $this->template->Uzivatel;

        if ( isset($_POST['user_id']) ) {
            $user_id = $_POST['user_id'];
        } else {
            $user_id = $this->getParam('role', null);
        }

        $User = new UserModel();
        $user_role = $User->getRoles($user_id);

        $Role = new RoleModel();
        $role_seznam = $Role->seznam();
        $role_select = array();
        foreach ($role_seznam as $key => $value) {
            $role_select[ $value->role_id ] = $value->name;
        }

        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->osoba_id);
        $form1->addHidden('user_id')
                ->setValue($user_id);

        if ( isset($user_role) ) {
            foreach ($user_role as $ur) {
                $form1->addGroup('role_id_' . $ur->role_id);
                $subForm = $form1->addContainer('role'.$ur->role_id);
                $subForm->addCheckbox("user_role", 'povolit')
                        ->setValue(1);
            }
        }

        $form1->addCheckbox("add_role", 'Přadat roli')
                        ->setValue(0);
        $form1->addSelect('role', 'Role:', $role_select);

        $form1->addSubmit('zmenitRole', 'Upravit role')
                 ->onClick[] = array($this, 'upravitRoleClicked');
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

    public function upravitRoleClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $osoba_id = $data['osoba_id'];
        $user_id = $data['user_id'];
        $add_role = $data['add_role'];
        $add_role_id = $data['role'];

        unset($data['osoba_id'],$data['user_id'],$data['add_role'],$data['role']);

        $User = new UserModel();
        $UserRole = new User2Role();

        $user_role = $User->getRoles($user_id);

        //Debug::dump($data);
        //Debug::dump($user_role);

        // Predkontrola - vyrazeni nemenici role
        foreach ($data as $id => $stav) {

            $role_id = (int) substr($id, 4); // role4
            // porovnat s puvodnim daty = role, ktere se nemenily, vyradime
            foreach ($user_role as $urole_id => $urole) {
                if ( ($urole->role_id == $role_id) && ($stav['user_role']==TRUE) ) {
                    unset($data[$id]);
                    unset($user_role[ $urole_id ]);
                    continue;
                }
            }
        }

        //echo "===================";
        //Debug::dump($data);
        //Debug::dump($user_role);


        // odebrani nevybranych roli
        if ( count($data) > 0 ) {
            foreach ($data as $id => $stav) {
                $role_id = (int) substr($id, 4);
                $UserRole->delete( array(
                                        array('role_id=%i',$role_id),
                                        array('user_id=%i',$user_id)
                                    )
                                 );
            }
        }

        // pridani nove role
        if ( $add_role == TRUE ) {
            $rowur = array( 'role_id'=>$add_role_id,
                            'user_id'=>$user_id,
                            'date_added'=>new DateTime()
                    );
            $UserRole->insert_basic($rowur);
        }

        $this->flashMessage('Role uživatele byly upraveny.');
        $this->redirect('this',array('id'=>$osoba_id));

    }


}
