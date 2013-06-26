<?php

class Admin_ZamestnanciPresenter extends BasePresenter
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
            $args = array(array(
                    "CONCAT(jmeno,' ',prijmeni) LIKE %s OR",'%'.$hledat.'%',
                    "CONCAT(prijmeni,' ',jmeno) LIKE %s OR",'%'.$hledat.'%',
                    'email LIKE %s OR','%'.$hledat.'%',
                    'telefon LIKE %s','%'.$hledat.'%'
                )
            );

            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        // zobrazit podle pismena
        $abc = $abcPaginator->getParam('abc');
        if ( !empty($abc) ) {
            if ( is_array($args) ) {
                $args[] = array("prijmeni LIKE %s",$abc.'%');
            } else {
                $args = array(array("prijmeni LIKE %s",$abc.'%'));
            }
        }

        // pouze aktivni
        if ( is_array($args) ) {
            $args[] = array('stav=0');
        } else {
            $args = array('stav=0');
        }


        // nacteni
        $Osoba = new Osoba();
        $result = $Osoba->seznam($args);
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

        $authenticator = (array) Environment::getConfig('service');
        $authenticator = $authenticator['Nette-Security-IAuthenticator'];

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
        $Auth1 = new $authenticator();
        $Auth1->setAction('change_password');
        Environment::setVariable('auth_params_change', array('osoba_id'=>$osoba_id,'user_id'=>$zmena_hesla, 'admin'=>1));
        $this->addComponent($Auth1, 'changePasswordForm');


        // Vytvoreni uctu
        $vytvorit_ucet = $this->getParam('new_user',null);
        if ( !is_null($vytvorit_ucet) ) {
            $this->template->vytvoritUcet = 1;
        }
        $Auth2 = new $authenticator();
        $Auth2->setAction('new_user');
        Environment::setVariable('auth_params_new', array('osoba_id'=>$osoba_id));
        $this->addComponent($Auth2, 'newUserForm');

        // Odebrani uctu
        $odebrat_ucet = $this->getParam('odebrat',null);
        if ( !is_null($odebrat_ucet) ) {
            if ( $User->odebratUcet($osoba_id, $odebrat_ucet) ) {
                $this->flashMessage('Účet uživatele byl odebrán.');
            }
        }

        $uzivatel = $Osoba->getUser($osoba_id,1);
        $this->template->Uzivatel = $uzivatel;

        if ( count($uzivatel)>0 ) {
            $role = array();
            foreach ($uzivatel as $uziv) {
                $role[ $uziv->id ] = UserModel::getRoles($uziv->id);
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

    public function actionSync()
    {

        $authenticator = (array) Environment::getConfig('service');
        $authenticator = $authenticator['Nette-Security-IAuthenticator'];
        $Auth = new $authenticator();
        $Auth->setAction('sync');
        $this->addComponent($Auth, 'syncForm');

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
        $form1->addHidden('id')
                ->setValue(@$osoba->id);
        $form1->addText('jmeno', 'Jméno:', 50, 150)
                ->setValue(@$osoba->jmeno);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->setValue(@$osoba->prijmeni)
                ->addRule(Form::FILLED, 'Příjmení musí být vyplněno!');
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
        $osoba_id = $data['id'];
        unset($data['id']);

        try {
            $osoba_id = $Osoba->ulozit($data, $osoba_id);
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl upraven.');
            $this->redirect(':Admin:Zamestnanci:detail',array('id'=>$osoba_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  se nepodařilo upravit.','warning');
            Debug::dump($e);
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();
        $osoba_id = !empty($data['id'])?$data['id']:$data['osoba_id'];
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
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->addRule(Form::FILLED, 'Příjmení musí být vyplněno!');
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

        try {
            $osoba_id = $Osoba->ulozit($data);
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl vytvořen.');
            $this->redirect(':Admin:Zamestnanci:detail',array('id'=>$osoba_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zaměstnance "'. Osoba::displayName($data) .'" se nepodařilo vytvořit.','warning');
            //$this->flashMessage('Chyba: '. $e->getMessage(),'warning');
        }
        
    }

/**
 *
 * Formular a zpracovani pro zmenu hesla
 *
 */

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
        $user_role = UserModel::getRoles($user_id);

        $Role = new RoleModel();
        $role_seznam = $Role->seznam();
        $role_select = array();
        foreach ($role_seznam as $key => $value) {
            if ( $value->fixed == 1 ) continue;
            $role_select[ $value->id ] = $value->name;
        }

        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->id);
        $form1->addHidden('user_id')
                ->setValue($user_id);

        if ( isset($user_role) ) {
            foreach ($user_role as $ur) {

                if ( isset($form1['role'.$ur->id]) ) continue;
                
                $form1->addGroup('role_id_' . $ur->id);
                $subForm = $form1->addContainer('role'.$ur->id);
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

        $user_role = UserModel::getRoles($user_id);

        //Debug::dump($data);
        //Debug::dump($user_role);

        // Predkontrola - vyrazeni nemenici role
        foreach ($data as $id => $stav) {

            $role_id = (int) substr($id, 4); // role4
            // porovnat s puvodnim daty = role, ktere se nemenily, vyradime
            foreach ($user_role as $urole_id => $urole) {
                if ( ($urole->id == $role_id) && ($stav['user_role']==TRUE) ) {
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
                if ( !empty($role_id) && !empty($user_id) ) {
                    $UserRole->delete( array(
                                        array('role_id=%i',$role_id),
                                        array('user_id=%i',$user_id)
                                    )
                                 );
                }
            }
        }

        // pridani nove role
        if ( $add_role == TRUE ) {
            $rowur = array( 'role_id'=>$add_role_id,
                            'user_id'=>$user_id,
                            'date_added'=>new DateTime()
                    );
            $UserRole->insert($rowur);
        }

        $this->flashMessage('Role uživatele byly upraveny.');
        $this->redirect('this',array('id'=>$osoba_id));

    }

    public function changePasswordFormHandler(SubmitButton $button)
    {
	$form = $button->getParent();
	$changePasswordForm = $this->getComponent('changePasswordForm');
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle jména, emailu, telefonu";

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
