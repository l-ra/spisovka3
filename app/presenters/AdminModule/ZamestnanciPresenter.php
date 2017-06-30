<?php

namespace Spisovka;

use Nette;

class Admin_ZamestnanciPresenter extends BasePresenter
{

    public function renderSeznam($hledat = null, $abc = null)
    {
        // paginator
        new Components\AbcFilter($this, 'abc', $this->getHttpRequest());
        $client_config = GlobalVariables::get('client_config');
        $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        // hledani
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

            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        // zobrazit podle pismena
        if (!empty($abc)) {
            $args[] = array("prijmeni LIKE %s", $abc . '%');
        }

        // nacteni
        $Osoba = new Osoba();
        $result = $Osoba->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;
    }

    protected function createComponentNewUserForm()
    {
        $person = new Person($this->getParameter('id'));
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('new_user');
        $Auth->setParams(['osoba_id' => $person->id]);
        return $Auth;
    }

    protected function createComponentChangePasswordForm()
    {
        $a = new UserAccount($this->getParameter('id'));
        $person = $a->getPerson();
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('change_password');
        $Auth->setParams(['osoba_id' => $person->id, 'user_id' => $a->id]);
        return $Auth;
    }
    
    protected function createComponentChangeAuthTypeForm()
    {
        $a = new UserAccount($this->getParameter('id'));
        $person = $a->getPerson();
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('change_auth');
        $Auth->setParams(['osoba_id' => $person->id, 'user_id' => $a->id]);
        return $Auth;
    }
    
    public function actionSmazatUcet($id)
    {
        $account = new UserAccount($id);
        try {
            $account->deactivate();            
            $this->flashMessage('Účet uživatele byl smazán.');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }
        $this->redirect('detail', $account->getPerson()->id);
    }

    public function renderUcet($id)
    {
        $this->template->edit = $this->getParameter('edit');
        $this->template->u = $account = new UserAccount($id);
        $this->template->person = $account->getPerson();
        $this->template->remote_auth_supported = $this->context->getService('authenticator')->supportsRemoteAuth();

        $roles = $account->getRoles();
        $this->template->roles = $roles ? : [];
    }

    public function renderDetail($id)
    {
        $this->template->person = $person = new Person($id);
        $this->template->accounts = $person->getAccounts();
    }

    protected function createComponentSyncForm()
    {
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('sync');
        return $Auth;
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
        $form1->addText('email', 'E-mail:', 50, 150)
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

        $osoba = isset($this->template->person) ? $this->template->person : null;
        if ($osoba) {
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
        $data = $button->getForm()->getValues(true);
        $osoba_id = $this->getParameter('id');

        try {
            $osoba = new Person($osoba_id);
            $osoba->modify($data);
            $osoba->save();
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
        $this->redirect('this');
    }

    public function stornoSeznamClicked()
    {
        $this->redirect('seznam');
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues(true);

        try {
            $person = Person::create($data);
            $this->flashMessage('Zaměstnanec  "' . $person->displayName() . '"  byl vytvořen.');
            $this->redirect(':Admin:Zamestnanci:detail', ['id' => $person->id]);
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
        $user_id = $this->getParameter('id');
        $roles = (new UserAccount($user_id))->getRoles();

        $Role = new RoleModel();
        $role_select = $Role->seznam();

        $form1 = new Form();

        if ($roles) {
            foreach ($roles as $ur) {
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

        $user_id = $this->getParameter('id');
        $add_role = $data['add_role'];
        $add_role_id = $data['role'];

        unset($data['osoba_id'], $data['user_id'], $data['add_role'], $data['role']);

        $UserRole = new User2Role();

        $user_role = (new UserAccount($user_id))->getRoles();

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
                'date_added' => new \DateTime()
            );
            $UserRole->insert($rowur);
        }

        // Odhlaš uživatele, aby se změny rolí okamžitě projevily
        $account = new UserAccount($user_id);
        $account->force_logout = true;
        $account->save();

        $this->flashMessage('Role uživatele byly upraveny.');
        $this->redirect('this');
    }

    protected function createComponentOJForm()
    {
        $form1 = new Form();

        $m = new OrgJednotka;
        $seznam = $m->linearniSeznam();
        $select = array(0 => 'žádná');
        foreach ($seznam as $org)
            $select[$org->id] = $org->ciselna_rada . ' - ' . $org->zkraceny_nazev;

        $c = $form1->addSelect('orgjednotka_id', 'Organizační jednotka:', $select);
        $a = new UserAccount($this->getParameter('id'));
        $ou = $a->getOrgUnit();
        // uživatel může být přiřazen do neaktivní o.j., která nemá položku v select boxu 
        $c->setDefaultValue($ou && in_array($ou->id, array_keys($select)) ? $ou->id : 0);

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

        $a = new UserAccount($this->getParameter('id'));
        $a->orgjednotka_id = $orgjednotka_id;
        $a->save();
        $this->flashMessage('Organizační jednotka byla změněna.');

        $this->redirect('this');
    }

    protected function createComponentChangeUsernameForm()
    {
        $form = new Form();

        $c = $form->addText('username', 'Uživatelské jméno:', 30)
                ->setRequired();
        if (isset($this->template->u))
            $c->setDefaultValue($this->template->u->username);
        
        $form->addSubmit('upravit', 'Změnit')
                ->onClick[] = array($this, 'zmenitUsernameClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');
        
        return $form;
    }
    
    public function zmenitUsernameClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        
        $a = new UserAccount($this->getParameter('id'));
        $a->username = $data->username;
        $a->save();
        
        $this->flashMessage('Uživatelské jméno bylo změněno.');
        $this->redirect('this');
    }
}
