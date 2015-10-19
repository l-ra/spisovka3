<?php

class Authenticator_UI extends Nette\Application\UI\Control
{

    protected $authenticator;
    protected $httpRequest;
    protected $userImport;
    protected $action;
    protected $form_params = [];  // promenna 'params' je jiz pouzita k jinemu ucelu

    // nutne kvuli vytvoreni sluzby pomoci Nette DI

    public function __construct(Authenticator_Basic $authenticator, Nette\Http\IRequest $httpRequest, IUserImport $userImport
    = null)
    {
        parent::__construct();
        $this->authenticator = $authenticator;
        $this->httpRequest = $httpRequest;
        $this->userImport = $userImport;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function setParams($params)
    {
        $this->form_params = $params;
        return $this;
    }

    public function render()
    {
        $t = null;
        $form = null;
        switch ($this->action) {
            case 'login':
                $t = '/auth_login.phtml';
                break;
            case 'change_auth':
                $form = $this->createComponentChangeAuthTypeForm('changeAuthTypeForm');
                break;
            case 'change_password':
                $form = $this->createComponentChangePasswordForm('changePasswordForm');
                break;
            case 'new_user':
                $form = $this->createComponentNewUserForm('newUserForm');
                break;
            case 'sync':
                $form = $this->createComponentSyncForm('syncForm');
                break;
            default:
                break;
        }
        if ($t) {
            $this->template->setFile(dirname(__FILE__) . $t);
            $this->template->render();
        } elseif ($form)
            $form->render();
    }

    protected function createComponentLoginForm($name)
    {
        $form = new Nette\Application\UI\Form($this, $name);
        $form->addText('username', 'Uživatelské jméno:')
                ->addRule(Nette\Forms\Form::FILLED, 'Zadejte uživatelské jméno, nebo e-mail.');

        $form->addPassword('password', 'Heslo:')
                ->addRule(Nette\Forms\Form::FILLED, 'Zadejte přihlašovací heslo.');

        $form->addHidden('backlink');
        if (!$form->isSubmitted()) {
            $url = $this->httpRequest->url->getAbsoluteUrl();
            $form['backlink']->setValue($url);
        }

        $form->addSubmit('login', 'Přihlásit');
        $form->onSuccess[] = array($this, 'handleLogin');
        $form->addProtection('Prosím přihlašte se znovu.');

        return $form;
    }

    protected function createComponentNewUserForm($name)
    {
        $form = new Nette\Application\UI\Form($this, $name);

        $form->addHidden('osoba_id');
        if (isset($this->form_params['osoba_id']))
            $form['osoba_id']->setValue($this->form_params['osoba_id']);

        $form->addText('username', 'Uživatelské jméno:', 30, 150);
        $form['username']->addRule(Nette\Forms\Form::FILLED,
                'Uživatelské jméno musí být vyplněno!');

        try {
            $user_list = $this->getPossibleUsers();
            if (!empty($user_list))
                $user_list = ['' => 'můžete vybrat ze seznamu'] + $user_list;
        } catch (Exception $e) {
            $user_list = ['' => $e->getMessage()];
        }

        if (!empty($user_list)) {
            $form->addSelect('username_list', "Uživatelé z externího zdroje:", $user_list)
            ->controlPrototype->onchange('$("[name=username]").val($(this).val())');
        }

        $this->formAddAuthSelect($form);

        $form->addPassword('heslo', 'Heslo:', 30, 30);
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addConditionOn($form["heslo"], Nette\Forms\Form::FILLED)
                ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat!", $form["heslo"]);

        $this->formAddRoleSelect($form);
        $this->formAddOrgSelect($form);

        if (!$this->authenticator->supportsRemoteAuth()) {
            $form['heslo']->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné.');
            $form['heslo_potvrzeni']->addRule(Nette\Forms\Form::FILLED,
                    'Potvrzení hesla musí být vyplněné.');
        } else {
            $form['heslo']->addConditionOn($form["external_auth"], Nette\Forms\Form::NOT_EQUAL,
                            1)
                    ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné.');
            if (!$form['heslo']->getValue()) {
                $form['heslo']->setDisabled();
                $form['heslo_potvrzeni']->setDisabled();
            }
            $form['external_auth']->controlPrototype->onchange(
                    'var dis = $(this).val() == 1; $("[name=heslo]").prop("disabled", dis);'
                    . '$("[name=heslo_potvrzeni]").prop("disabled", dis);');
        }
        $form->addSubmit('new_user', 'Vytvořit účet')
                ->onClick[] = array($this, 'handleNewUser');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'handleCancel');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    protected function createComponentSyncForm($name)
    {
        if (!$this->userImport) {
            echo '<div class="prazdno">';
            echo 'Import uživatelů není nakonfigurován.';
            echo "</div>";
            return null;
        }

        $form = new Nette\Application\UI\Form($this, $name);

        try {
            $seznam = $this->userImport->getRemoteUsers();
        } catch (Exception $e) {
            echo '<div class="prazdno">';
            echo $e->getMessage();
            echo '<p>';
            echo 'Zkontrolujte prosím, že konfigurace je správná.';
            echo "</div>";
            return null;
        }

        if (is_array($seznam) && !empty($seznam)) {
            if (!$form->isSubmitted()) {
                echo "<div>Vyberte zaměstnance, které chcete přidat do aplikace a zvolte"
                . " jejich roli.<br /><br /></div><br />";
            }

            $User = new UserModel();
            $user_seznam = $User->select()->fetchAssoc('username');

            foreach ($seznam as $id => $user) {

                if (!isset($user['jmeno']))
                    $user['jmeno'] = '';
                if (!isset($user['email']))
                    $user['email'] = '';

                $form->addGroup('');
                $cont = $form->addContainer("user_$id");

                if (!isset($user_seznam[$user['username']])) {
                    $cont->addCheckbox('add', 'Přidat');
                    $cont->addText('username', "Uživatelské jméno:")
                            ->addRule(Nette\Forms\Form::FILLED,
                                    'Uživatelské jméno musí být vyplněné.')
                            ->setValue($user['username']);
                    $cont->addText('prijmeni', 'Příjmení:')
                            ->addRule(Nette\Forms\Form::FILLED, 'Příjmení musí být vyplněné.')
                            ->setValue($user['prijmeni']);
                    $cont->addText('jmeno', 'Jméno:')
                            ->setValue($user['jmeno']);
                    $cont->addText('email', 'Email:')
                            ->setValue($user['email'])
                            ->addCondition(Nette\Forms\Form::FILLED)
                                ->addRule(Nette\Forms\Form::EMAIL);

                    $this->formAddRoleSelect($cont);
                    $this->formAddOrgSelect($cont);

                    $cont['email']->getControlPrototype()->style(['width' => '170px']);
                    $cont['role']->getControlPrototype()->style(['width' => '110px']);
                    $cont['orgjednotka_id']->getControlPrototype()->style(['width' => '130px']);
                } else {
                    $cont->addCheckbox('add', 'Již existuje')
                            ->setDisabled();
                    $cont->addText('username', "Uživatelské jméno:")
                            ->setValue($user['username']);
                    $cont->addText('prijmeni', 'Příjmení:')
                            ->setValue($user['prijmeni']);
                    $cont->addText('jmeno', 'Jméno:')
                            ->setValue($user['jmeno']);
                }

                $cont['username']->getControlPrototype()->style(['width' => '100px']);
                $cont['prijmeni']->getControlPrototype()->style(['width' => '100px']);
                $cont['jmeno']->getControlPrototype()->style(['width' => '70px']);
            }


            $form->addGroup('');
            $form->addSubmit('pridat', 'Přidat');
            $form->onSuccess[] = array($this, 'handleSync');

            $renderer = $form->getRenderer();

            $renderer->wrappers['form']['container'] = "table";
            $renderer->wrappers['group']['container'] = "tr";
            $renderer->wrappers['group']['label'] = null;
            $renderer->wrappers['controls']['container'] = null;
            $renderer->wrappers['pair']['container'] = 'td';
            $renderer->wrappers['label']['container'] = null;
            $renderer->wrappers['control']['container'] = null;
        } else {
            echo '<div class="prazdno">';
            echo 'Nenalezl jsem žádné uživatele.';
            echo '<p>';
            echo 'Zkontrolujte prosím, že konfigurace je správná.';
            echo "</div>";
        }

        return $form;
    }

    protected function createComponentChangePasswordForm($name)
    {
        $form = new Nette\Application\UI\Form($this, $name);

        $form->addHidden('osoba_id');
        $form->addHidden('user_id');

        $params = $this->form_params;
        if (isset($params['osoba_id']))
            $form['osoba_id']->setValue($params['osoba_id']);
        if (isset($params['user_id']))
            $form['user_id']->setValue($params['user_id']);

        $form->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné.')
                ->addConditionOn($form["heslo"], Nette\Forms\Form::FILLED)
                ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat!", $form["heslo"]);

        $form->addSubmit('change_password', 'Změnit heslo')
                ->onClick[] = array($this, 'handleChangePassword');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'handleCancel');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    protected function createComponentChangeAuthTypeForm($name)
    {
        $form = new Nette\Application\UI\Form($this, $name);

        $form->addHidden('osoba_id');
        $form->addHidden('user_id');

        $params = $this->form_params;
        if (isset($params['osoba_id']))
            $form['osoba_id']->setValue($params['osoba_id']);

        $auth_type = null;
        if (isset($params['user_id'])) {
            $form['user_id']->setValue($params['user_id']);
            $user_info = UserModel::getUser($params['user_id']);
            $auth_type = $user_info->external_auth;
        }

        $this->formAddAuthSelect($form, $auth_type);

        $form->addSubmit('change_auth', 'Změnit ověření')
                ->onClick[] = array($this, 'handleChangeAuthType');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'handleCancel');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function handleCancel(Nette\Forms\Controls\SubmitButton $button)
    {
        $this->presenter->redirect('this');
    }

    public function handleLogin(Nette\Application\UI\Form $form, $data)
    {
        try {
            $this->presenter->user->login($data['username'], $data['password']);

            $this->afterLogin();
            $redirect_home = (bool) Settings::get('login_redirect_homepage', false);
            $url = isset($data['backlink']) ? $data['backlink'] : '';
            if (!$redirect_home && !empty($url)) {
                $this->presenter->redirectUrl($url);
            }
            else
                $this->presenter->redirect(':Spisovka:Default:default');
        } catch (Nette\Security\AuthenticationException $e) {
            $this->presenter->flashMessage($e->getMessage(), 'warning');
            sleep(2); // sniz riziko brute force utoku
        }
    }

    protected function afterLogin()
    {
        /* Pokus o pridani upozorneni se nezdaril, protoze je problem zobrazit jakoukoli
         * flash zpravu (kvuli pouziti redirectUrl() po prihlaseni)
        // Zkontroluj, zda ma uzivatel predane dokumenty
        $Dokument = new Dokument;
        $args_f = $Dokument->fixedFiltr('kprevzeti', false, false);
        $args = $Dokument->spisovka($args_f);
        $result = $Dokument->seznam($args);

        if (count($result))
            $this->presenter->flashMessage('Máte dokument(y) k převzetí. Počet dokumentů: ' . count($result), 'info');
         
        */
    }
    
    protected function formAddAuthSelect(Nette\Forms\Container $form, $value = null)
    {
        if ($this->authenticator->supportsRemoteAuth()) {
            $form->addSelect('external_auth', "Způsob ověření hesla:",
                    array(1 => 'externí ověření',
                0 => 'lokální ověření spisovkou'
            ));
            if ($value !== null)
                $form['external_auth']->setDefaultValue($value);
        }
    }

    protected function formAddRoleSelect(Nette\Forms\Container $form)
    {
        static $default_role;
        static $role_list;

        $Role = new RoleModel();
        if (!$default_role)
            $default_role = $Role->getDefaultRole();
        if (!$role_list)
            $role_list = $Role->seznam();

        $form->addSelect('role', 'Role:', $role_list)
                ->setDefaultValue($default_role);
    }

    protected function formAddOrgSelect(Nette\Forms\Container $form)
    {
        static $select;

        if (!$select) {
            $m = new Orgjednotka;
            $seznam = $m->linearniSeznam();
            $select = array(0 => 'žádná');
            foreach ($seznam as $org)
                $select[$org->id] = $org->ciselna_rada . ' - ' . $org->zkraceny_nazev;
        }

        $form->addSelect('orgjednotka_id', 'Organizační jednotka:', $select);
    }

    // Přidá uživatelský účet existující osobě
    public function handleNewUser(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        if (!isset($data['osoba_id'])) {
            $this->presenter->redirect('this');
        }

        $this->vytvoritUcet($data['osoba_id'], $data);

        $this->presenter->redirect('this');
    }

    public function handleChangePassword(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $User = new UserModel();
        if ($User->changePassword($data['user_id'], $data['heslo']))
            $this->presenter->flashMessage('Heslo úspěšně změněno.');
        else
            $this->presenter->flashMessage('Heslo není možné změnit.', 'warning');
        
        $this->presenter->redirect('this');
    }

    public function handleChangeAuthType(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $model = new UserModel();
        $model->changeAuthType($data['user_id'], $data['external_auth']);
        $this->presenter->flashMessage('Nastavení změněno.');
        $this->presenter->redirect('this');
    }

    public function handleSync(Nette\Application\UI\Form $form, $data)
    {
        $users_added = 0;
        $users_failed = 0;

        foreach ($data as $user)
            if (isset($user['add']) && $user['add'] === true) {

                $osoba = ['jmeno' => $user['jmeno'],
                    'prijmeni' => $user['prijmeni'],
                    'email' => $user['email']
                ];

                if (isset($user['titul_pred']))
                    $osoba['titul_pred'] = $user['titul_pred'];
                if (isset($user['telefon']))
                    $osoba['telefon'] = $user['telefon'];
                if (isset($user['pozice']))
                    $osoba['pozice'] = $user['pozice'];

                $user_data = ['username' => $user['username'],
                    'heslo' => null,
                    'external_auth' => 1,
                    'role' => $user['role'],
                    'orgjednotka_id' => $user['orgjednotka_id']
                ];

                $success = $this->vytvoritUcet($osoba, $user_data);
                if ($success)
                    $users_added++;
                else
                    $users_failed++;
            }

        if ($users_added)
            $this->presenter->flashMessage("Bylo přidáno $users_added zaměstnanců.");
        else
            $this->presenter->flashMessage('Nebyli přidáni žádní zaměstnanci.');
        if ($users_failed)
            $this->presenter->flashMessage("$users_failed zaměstnanců se nepodařilo přidat.",
                    'warning');

        $this->presenter->redirect('this');
    }

    // Vytvoří uživatelský účet a případně i entitu osoby
    // $osoba_data - je buď id osoby nebo pole
    // $silent - je true pouze během instalace aplikace
    // vrátí boolean - úspěch operace
    public function vytvoritUcet($osoba_data, $user_data, $silent = false)
    {
        $osoba_vytvorena = false;

        try {
            if (is_array($osoba_data)) {
                $osoba_model = new Osoba;
                $osoba_id = $osoba_model->ulozit($osoba_data);
                $osoba_vytvorena = true;
            } else
                $osoba_id = $osoba_data;

            UserModel::pridatUcet($osoba_id, $user_data);

            if (!$silent)
                $this->presenter->flashMessage('Účet uživatele "' . $user_data['username'] . '" byl úspěšně vytvořen.');

            return true;
        } catch (Exception $e) {
            if (!$silent)
                if ($e->getCode() == 1062) {
                    $this->presenter->flashMessage("Uživatelský účet s názvem \"{$user_data['username']}\" již existuje. Zvolte jiný název.",
                            'warning');
                } else {
                    $this->presenter->flashMessage('Účet uživatele se nepodařilo vytvořit.',
                            'warning');
                    $this->presenter->flashMessage('Chyba: ' . $e->getMessage(), 'warning');
                }

            if ($osoba_vytvorena) {
                // pokud byl uživatel vytvořen "napůl", odstraň záznam zaměstnance
                $osoba_model->delete("[id] = $osoba_id");
            }

            return false;
        }
    }

    protected function getPossibleUsers()
    {
        $remote_users = $this->userImport ? $this->userImport->getRemoteUsers() : null;
        $users = array();
        if (is_array($remote_users)) {
            $User = new UserModel();
            $existing_users = $User->select()->fetchAssoc('username');

            foreach ($remote_users as $user) {
                $username = $user['username'];
                if (!isset($existing_users[$username]))
                    $users[$username] = "$username - {$user['prijmeni']} {$user['jmeno']}";
            }
        }

        return $users;
    }

}
