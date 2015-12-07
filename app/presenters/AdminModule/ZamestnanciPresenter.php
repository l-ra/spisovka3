<?php

use Spisovka\Form;

class Admin_ZamestnanciPresenter extends BasePresenter
{

    public $hledat;

    public function renderSeznam($hledat = null, $abc = null)
    {

        // paginator
        new AbcFilter($this, 'abc');
        $client_config = Nette\Environment::getVariable('client_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        // hledani
        $this->hledat = "";
        $this->template->no_items = 0;
        $args = [];
        if (isset($hledat)) {
            $args = array(array(
                    "CONCAT(jmeno,' ',prijmeni) LIKE %s OR", '%' . $hledat . '%',
                    "CONCAT(prijmeni,' ',jmeno) LIKE %s OR", '%' . $hledat . '%',
                    'email LIKE %s OR', '%' . $hledat . '%',
                    'telefon LIKE %s', '%' . $hledat . '%'
                )
            );

            $this->hledat = $hledat;
            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        // zobrazit podle pismena
        if (!empty($abc)) {
            $args[] = array("prijmeni LIKE %s", $abc . '%');
        }

        // [P.L.] stav neni pouzit v aplikaci
        $args[] = 'stav=0';

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

    public function actionDetail($id)
    {
        $this->template->title = " - Detail zaměstnance";

        $Osoba = new Osoba();
        $User = new UserModel();

        $osoba_id = $id;
        $this->template->Osoba = $Osoba->getInfo($osoba_id);

        // Parametr urcuje, co budeme editovat (jaky zobrazime formular)
        $this->template->FormUpravit = $this->getParameter('upravit', null);
        $this->template->UpravitUserId = $this->getParameter('user', null);

        // Zmena roli
        $this->template->RoleUpravit = $this->getParameter('role', null);

        $accounts = $Osoba->getUser($osoba_id, 1);
        $this->template->Accounts = $accounts;

        // Zmena hesla
        $this->template->ZmenaHesla = null;
        $user_id = $this->getParameter('user', null);
        if ($this->template->FormUpravit == 'heslo') {
            // Stupidni kontrola parametru. Kdo je opravnen spravou uzivatelu v administraci,
            // muze menit heslo komukoliv.
            if (array_key_exists($user_id, $accounts)) {
                $this->template->ZmenaHesla = (int) $user_id;
            }
        }

        $this->template->remote_auth_supported = $this->context->getService('authenticator')->supportsRemoteAuth();

        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('change_password');
        $Auth->setParams(['osoba_id' => $osoba_id, 'user_id' => $user_id]);
        $this->addComponent($Auth, 'changePasswordForm');


        // Vytvoreni uctu
        $vytvorit_ucet = $this->getParameter('new_user', null);
        if (!is_null($vytvorit_ucet)) {
            $this->template->vytvoritUcet = 1;
        }
        $Auth2 = $this->context->createService('authenticatorUI');
        $Auth2->setAction('new_user');
        $Auth2->setParams(['osoba_id' => $osoba_id]);
        $this->addComponent($Auth2, 'newUserForm');

        // Zmena prihlaseni
        $this->template->ZmenaPrihlaseni = $this->getParameter('upravit', '') == 'typ_auth'
                    ? (int) $user_id : false;
        $Auth3 = $this->context->createService('authenticatorUI');
        $Auth3->setAction('change_auth');
        $Auth3->setParams(['osoba_id' => $osoba_id, 'user_id' => $user_id]);
        $this->addComponent($Auth3, 'changeAuthTypeForm');

        // Odebrani uctu
        $odebrat_ucet = $this->getParameter('odebrat', false);
        if ($odebrat_ucet) {
            try {
                $User->odebratUcet($osoba_id, $odebrat_ucet);
                $this->flashMessage('Účet uživatele byl odebrán.');
            } catch (Exception $e) {
                $this->flashMessage($e->getMessage(), 'warning');
            }
            $this->redirect('this', array('id' => $osoba_id));
        }

        if (count($accounts)) {
            $role = array();
            foreach ($accounts as &$uziv) {
                if ($uziv->orgjednotka_id !== null)
                    $uziv->org_nazev = Orgjednotka::getName($uziv->orgjednotka_id);
                else
                    $uziv->org_nazev = "žádná";

                $user_roles = UserModel::getRoles($uziv->id);
                $role[$uziv->id] = $user_roles ?: [];
            }

            $this->template->Role = $role;
        } else {
            $this->template->Role = [];
        }
    }

    public function renderDetail()
    {
    }

    public function actionSync()
    {
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('sync');
        $this->addComponent($Auth, 'syncForm');
    }

    /**
     * 
     * @return Form
     */
    public static function createOsobaForm()
    {
        $form1 = new Form();
        $form1->addText('jmeno', 'Jméno:', 50, 150);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->addRule(Form::FILLED, 'Příjmení musí být vyplněno!');
        $form1->addText('titul_pred', 'Titul před:', 50, 150);
        $form1->addText('titul_za', 'Titul za:', 50, 150);
        $form1->addText('email', 'Email:', 50, 150)
                ->addCondition(Form::FILLED)
                    ->addRule(Form::EMAIL);
        $form1->addText('telefon', 'Telefon:', 50, 150);
        $form1->addText('pozice', 'Funkce:', 50, 150);
        
        return $form1;
    }
    
    protected function createComponentNovyForm()
    {
        $form1 = self::createOsobaForm();
        
        $form1->addSubmit('novy', 'Vytvořit')
                ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form1;
    }

    protected function createComponentUpravitForm()
    {
        $form = self::createOsobaForm();
        $form->addHidden('id');
        
        $osoba = $this->template->Osoba;
        if ($osoba) {
            $form['id']->setValue($osoba->id);
            $form['jmeno']->setValue($osoba->jmeno);
            $form['prijmeni']->setValue($osoba->prijmeni);
            $form['titul_pred']->setValue($osoba->titul_pred);
            $form['titul_za']->setValue($osoba->titul_za);
            $form['email']->setValue($osoba->email);
            $form['telefon']->setValue($osoba->telefon);
            $form['pozice']->setValue($osoba->pozice);
        }        
        
        $form->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();
        $osoba_id = $data['id'];
        unset($data['id']);

        try {
            $osoba_id = $Osoba->ulozit($data, $osoba_id);
            $this->flashMessage('Zaměstnanec  "' . Osoba::displayName($data) . '"  byl upraven.');
            $this->redirect('this', array('id' => $osoba_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zaměstnanec  "' . Osoba::displayName($data) . '"  se nepodařilo upravit.',
                    'warning');
            Nette\Diagnostics\Debugger::dump($e);
        }
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();
        $osoba_id = !empty($data['osoba_id']) ? $data['osoba_id'] : $data['id'];
        $this->redirect('this', array('id' => $osoba_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Admin:Zamestnanci:seznam');
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();

        try {
            $osoba_id = $Osoba->ulozit($data);
            $this->flashMessage('Zaměstnanec  "' . Osoba::displayName($data) . '"  byl vytvořen.');
            $this->redirect(':Admin:Zamestnanci:detail', array('id' => $osoba_id));
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Zaměstnance "' . Osoba::displayName($data) . '" se nepodařilo vytvořit.',
                    'warning');
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

        $user_id = $this->getHttpRequest()->getPost('user_id', null);
        if ($user_id === null) {
            $user_id = $this->getParameter('role', null);
        }

        $user_role = UserModel::getRoles($user_id);

        $Role = new RoleModel();
        $role_select = $Role->seznam();

        $form1 = new Spisovka\Form();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->id);
        $form1->addHidden('user_id')
                ->setValue($user_id);

        if (isset($user_role)) {
            foreach ($user_role as $ur) {

                if (isset($form1['role' . $ur->id]))
                    continue;

                $form1->addGroup('role_id_' . $ur->id);
                $subForm = $form1->addContainer('role' . $ur->id);
                $subForm->addCheckbox("user_role")
                        ->setValue(1);

                // zamez tomu, aby uživatel mohl stejnou roli mít přiřazenu několikrát
                unset($role_select[$ur->id]);
            }
        }

        $form1->addCheckbox("add_role", 'Přidat roli:')
                ->setValue(0);
        $form1->addSelect('role', 'Role:', $role_select);

        $form1->addSubmit('zmenitRole', 'Upravit role')
                ->onClick[] = array($this, 'upravitRoleClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitRoleClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $osoba_id = $data['osoba_id'];
        $user_id = $data['user_id'];
        $add_role = $data['add_role'];
        $add_role_id = $data['role'];

        unset($data['osoba_id'], $data['user_id'], $data['add_role'], $data['role']);

        $UserRole = new User2Role();

        $user_role = UserModel::getRoles($user_id);

        //Nette\Diagnostics\Debugger::dump($data);
        //Nette\Diagnostics\Debugger::dump($user_role);
        // Predkontrola - vyrazeni nemenici role
        foreach ($data as $id => $stav) {

            $role_id = (int) substr($id, 4); // role4
            // porovnat s puvodnim daty = role, ktere se nemenily, vyradime
            foreach ($user_role as $urole_id => $urole) {
                if (($urole->id == $role_id) && ($stav['user_role'] == TRUE)) {
                    unset($data[$id]);
                    unset($user_role[$urole_id]);
                    continue;
                }
            }
        }

        //echo "===================";
        //Nette\Diagnostics\Debugger::dump($data);
        //Nette\Diagnostics\Debugger::dump($user_role);
        // odebrani nevybranych roli
        if (count($data) > 0) {
            foreach ($data as $id => $stav) {
                $role_id = (int) substr($id, 4);
                if (!empty($role_id) && !empty($user_id)) {
                    $UserRole->delete(array(
                        array('role_id=%i', $role_id),
                        array('user_id=%i', $user_id)
                            )
                    );
                }
            }
        }

        // pridani nove role
        if ($add_role == TRUE) {
            $rowur = array('role_id' => $add_role_id,
                'user_id' => $user_id,
                'date_added' => new DateTime()
            );
            $UserRole->insert($rowur);
        }

        $this->flashMessage('Role uživatele byly upraveny.');
        $this->redirect('this', array('id' => $osoba_id));
    }

    protected function createComponentSearchForm()
    {
        $hledat = !is_null($this->hledat) ? $this->hledat : '';

        $form = new Nette\Application\UI\Form();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle jména, emailu, telefonu";

        $form->addSubmit('hledat', 'Hledat')
                ->onClick[] = array($this, 'hledatSimpleClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function hledatSimpleClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->redirect('seznam', array('hledat' => $data['dotaz']));
    }

    protected function createComponentOJForm()
    {
        $form1 = new Spisovka\Form();

        $m = new Orgjednotka;
        $seznam = $m->linearniSeznam();
        $select = array(0 => 'žádná');
        foreach ($seznam as $org)
            $select[$org->id] = $org->ciselna_rada . ' - ' . $org->zkraceny_nazev;

        $osoba = $this->template->Osoba;
        $user_id = $this->getParameter('user', null);

        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->id);
        $form1->addHidden('id')
                ->setValue($user_id);

        $c = $form1->addSelect('orgjednotka_id', 'Organizační jednotka:', $select);
        if (isset($this->template->Accounts)) {
            $user = $this->template->Accounts[$user_id];
            $c->setValue($user->orgjednotka_id);
        }

        $form1->addSubmit('upravit', 'Změnit')
                ->onClick[] = array($this, 'zmenitOJClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function zmenitOJClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $orgjednotka_id = $data['orgjednotka_id'];
        if ($orgjednotka_id === 0)
            $orgjednotka_id = null;

        $model = new UserModel();
        $model->update(array('orgjednotka_id' => $orgjednotka_id),
                array(array('id = %i', $data['id'])));
        $this->flashMessage('Organizační jednotka byla změněna.');

        $this->redirect('this', array('id' => $data['osoba_id']));
    }

}
