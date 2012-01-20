<?php

class Authenticator_HTTPhtaccess extends Control implements IAuthenticator
{

    protected $receivedSignal;
    protected $action;
    protected $wasRendered = FALSE;
    
    private $_realm;
    private $_users;

    public function authenticate(array $credentials)
    {

        // vstupy
        $username = $credentials[self::USERNAME];
        $password = sha1( $credentials[self::USERNAME] . $credentials[self::PASSWORD] );

        // Vyhledani uzivatele
        $user = new UserModel();
        $log = new LogModel();
        $row = $user->getUser($username,true);

        //Debug::dump($row); //exit;

        // Overeni uzivatele
        if (!$row) {
            throw new AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }

        // Overeni hesla
        if ($row->password !== $password) {
            $log->logAccess($row->id, 0);
            throw new AuthenticationException("Neplatné heslo.", self::INVALID_CREDENTIAL);
        } else {
            $user->zalogovan($row->id);
            $log->logAccess($row->id, 1);
        }

        // Odstraneni hesla ve vypisu
        unset($row->password);

        // Sestaveni roli
        $identity_role = array();
        if ( count($row->user_roles) > 0 ) {
            foreach ($row->user_roles as $role) {
                $identity_role[] = $role->code;
            }
        } else {
            throw new AuthenticationException("Uživatel '$username' nemá přiřazenou žádnou roli. Není možné ho připustit k aplikaci. Kontaktujte svého správce.", self::NOT_APPROVED);
        }
        
        $row->klient = KLIENT;

        // tady nacitam taky roli
        return new Identity($row->display_name, $identity_role, $row);
    }

    
    /*
     * Componenta
     */

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function render()
    {

        if ( $this->action == "login" ) {
            
            if ( Environment::getHttpRequest()->getCookie('s3_logout') ) {
                unset($_SERVER['PHP_AUTH_USER']);
                Environment::getHttpResponse()->setCookie('s3_logout', null, time());
                header("Location: ". Environment::getVariable('baseUri') ."auth/logout.php" ,302 );
                exit;
            }            

            if ( Environment::getHttpRequest()->getQuery('alternativelogin') ) {
                $base_url = Environment::getVariable('klientUri',Environment::getVariable('baseUri'));
                $this->template->alter_login = "SSO přihlášení selhalo nebo nebylo provedeno!<br />Zkuste znovu použít následující odkaz <a href='". $base_url ."'>Zkusit znovu přihlášení přes SSO</a>.<br /> Pokud se situace opakuje, kontaktujte svého správce.<br />Následující přihlašovací formulář slouží pouze pro alternativní přihlášení.";
                $this->template->setFile(dirname(__FILE__) . '/auth_login.phtml');
                $this->template->render();                 
            } else if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header("Location: ". Environment::getVariable('baseUri') ."auth/index.php",302 );                
            } else {
                try {
                    $user = Environment::getUser();
                    $user->setNamespace(KLIENT);
                    $user->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                    header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ,302 );
                } catch ( AuthenticationException $e ) {
                    $this->action = "user_registration";
                    $this->template->setFile(dirname(__FILE__) . '/auth_user_registration.phtml');
                    $this->template->render();
                    //header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ."auth" ,302 );                
                }
                
            }            
        } else if ( $this->action == "change_password" ) {
            $this->template->setFile(dirname(__FILE__) . '/auth_change_password.phtml');
            $this->template->render();
        } else if ( $this->action == "new_user" ) {
            $this->template->setFile(dirname(__FILE__) . '/auth_new_user.phtml');
            $this->template->render();
        } else if ( $this->action == "sync" ) {
            $this->template->setFile(dirname(__FILE__) . '/auth_sync.phtml');
            $this->template->render();
        } else {

        }


    }

    /*
     * Formulare
     *
     */

    public function isSignalReceiver($signal = TRUE)
    {
        if ($signal == 'submit') {
            return $this->receivedSignal === 'submit';
	} else {
            return $this->getPresenter()->isSignalReceiver($this, $signal);
	}
    }

    protected function createComponentLoginForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);
        $form->addText('username', 'Uživatelské jméno:')
            ->addRule(Form::FILLED, 'Zadejte uživatelské jméno, nebo e-mail.');

        $form->addPassword('password', 'Heslo:')
            ->addRule(Form::FILLED, 'Zadejte přihlašovací heslo.');

        $form->addSubmit('login', 'Přihlásit');
        $form->onSubmit[] = array($this, 'formSubmitHandler');
        $form->addProtection('Prosím přihlašte se znovu.');

        return $form;

    }

    protected function createComponentChangePasswordForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);

        $params = Environment::getVariable('auth_params_change');
        if ( isset($params['admin']) ) {
            $form->addHidden('osoba_id')->setValue($params['osoba_id']);
            $form->addHidden('user_id')->setValue($params['user_id']);
            $user_id = $params['user_id'];
        } else {
            $user_id = Environment::getUser()->getIdentity()->id;
        }

        $User = new UserModel();
        $user_info = $User->getUser($user_id);
        $local = @$user_info->local;

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes HTTP server)',
                          0=>'pouze lokální přihlášení',
                          2=>'kombinované přihlášení (pokud selže externí přihlášení, tak se použije lokální přihlášení)'
                    )
               )->setValue($local);

        $form->addPassword('heslo', 'Heslo:', 30, 30);
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);

        $form->addSubmit('change_password', 'Změnit heslo');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE);
        $form->onSubmit[] = array($this, 'formSubmitHandler');

        $text = '<dl class="detail_item"><dt>&nbsp;</dt><dd>';
        $text .= '<u>Upozornění!</u><br>Změna hesla se vztahuje pouze na lokální přihlášení. Pokud je zvoleno externí přihlášení, pak tato změna hesla nebude mít vliv na změnu hesla v externím zdroji. ';
        $text .= "</dd></dl>";
        $this->template->text = $text;

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    protected function createComponentNewUserForm($name)
    {

        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);

        $Role = new RoleModel();
        $role_seznam = $Role->seznam();
        $role_select = array();
        foreach ($role_seznam as $key => $value) {
            if ( $value->fixed == 1 ) continue;
            $role_select[ $value->id ] = $value->name;
        }  

        $params = Environment::getVariable('auth_params_new');
        $form->addHidden('osoba_id')->setValue($params['osoba_id']);

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes HTTP server)',
                          0=>'pouze lokální přihlášení',
                          2=>'kombinované přihlášení (pokud selže externí přihlášení, tak se použije lokální přihlášení)'
                    )
               );

        $form->addText('username', 'Uživatelské jméno:', 30, 150);
                //->addRule(Form::FILLED, 'Uživatelské jméno musí být vyplněno!');
        $form->addPassword('heslo', 'Heslo:', 30, 30);
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);
        $form->addSelect('role', 'Role:', $role_select);


        $form->addSubmit('new_user', 'Vytvořit účet');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE);
        $form->onSubmit[] = array($this, 'formSubmitHandler');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }


    protected function createComponentUserRegistrationForm($name)
    {

        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);

        $form->addText('jmeno', 'Jméno:', 50, 150);
        $form->addText('prijmeni', 'Příjmení:', 50, 150)
                ->addRule(Form::FILLED, 'Příjmení musí být vyplněno!');
        $form->addText('titul_pred', 'Titul před:', 50, 150);
        $form->addText('titul_za', 'Titul za:', 50, 150);
        $form->addText('email', 'Email:', 50, 150);
        $form->addText('telefon', 'Telefon:', 50, 150);
        $form->addText('pozice', 'Funkce:', 50, 150);

        $form->addSubmit('user_registration', 'Vytvořit účet');
        //$form->addSubmit('storno', 'Zrušit')
        //         ->setValidationScope(FALSE);
        $form->onSubmit[] = array($this, 'formSubmitHandler');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }
    

    protected function  createComponentSyncForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);

        echo '<div class="prazdno">';
        echo 'Tento autentizátor nepodporuje synchronizaci!';
        echo "</div>";

        return $form;

    }


    public function formSubmitHandler(AppForm $form)
    {
        $this->receivedSignal = 'submit';

	// was form submitted?
	if ($form->isSubmitted()) {

            $values = $form->getValues();
            $data = $form->getHttpData();

            if ( isset($data['login']) ) {
                $this->handleLogin($data);
            } else if ( isset($data['new_user']) ) {
                $this->handleNewUser($data);
            } else if ( isset($data['change_password']) ) {
                $this->handleChangePassword($data);
            } else if ( isset($data['synchonizovat']) ) {
                $this->handleSync($data);
            } else if ( isset($data['user_registration']) ) {
                $this->handleUserRegistration($data);                
            } else if ( isset($data['storno']) ) {
                if ( isset($data['osoba_id']) ) {
                    $this->presenter->redirect('this', array('id'=>$data['osoba_id']));
                } else {
                    $this->presenter->redirect('this');
                }
            } else {
                throw new InvalidStateException("Unknown submit button.");
            }
	}
	if (!$this->presenter->isAjax()) $this->presenter->redirect('this');
    }

    public function handleLogin($data)
    {
        try {
            $user = Environment::getUser();
            $user->setNamespace(KLIENT);
            $user->authenticate($data['username'], $data['password']);
            $this->presenter->redirect(':Spisovka:Default:default');
        } catch (AuthenticationException $e) {
            $this->presenter->flashMessage($e->getMessage(), 'warning');
        }
    }

    public function handleChangePassword($data)
    {
        $zmeneno = 0;
        $User = new UserModel();

        $params = Environment::getVariable('auth_params_change');

        if ( isset($data['osoba_id']) ) {
            $params['osoba_id'] = $data['osoba_id'];
            $params['user_id'] = $data['user_id'];
        }

        if ( isset($params['osoba_id']) ) {
            $Osoba = new Osoba();
            $uzivatel = $Osoba->getUser($params['osoba_id']);
            if ( count($uzivatel)>0 ) {
                foreach ($uzivatel as $user) {
                    if ( $user->id == $params['user_id'] ) {
                        if ( $User->zmenitHeslo($user->id, $data['heslo'], 0) ) {
                            $zmeneno = 1;
                        }
                        break;
                    }
                }
            }

            if ( $zmeneno == 1 ) {
                $this->presenter->flashMessage('Heslo uživatele "'. $user->username .'"  bylo úspěšně změněno.');
            } else {
                $this->presenter->flashMessage('Nedošlo k žádné změně.');
            }
            $this->presenter->redirect('this', array('id'=>$params['osoba_id']));
        }
        $this->presenter->redirect('this');
        
    }

    public function handleNewUser($data)
    {
        if ( isset($data['osoba_id']) ) {

           // Debug::dump($data); exit;

            $User = new UserModel();

            $user_data = array(
                'username'=>$data['username'],
                'heslo'=>$data['heslo'],
                'local'=> 1,
            );

            try {

                $user_id = $User->insert($user_data);
                $User->pridatUcet($user_id, $data['osoba_id'], $data['role']);

                $this->presenter->flashMessage('Účet uživatele "'. $data['username'] .'" byl úspěšně vytvořen.');
                $this->presenter->redirect('this',array('id'=>$data['osoba_id']));
            } catch (DibiException $e) {
                if ( $e->getCode() == 1062 ) {
                    $this->presenter->flashMessage('Uživatel "'. $data['username'] .'" již existuje. Zvolte jiný.','warning');
                } else {
                    $this->presenter->flashMessage('Účet uživatele se nepodařilo vytvořit.','warning');
                }
                $this->presenter->redirect('this',array('id'=>$data['osoba_id'],'new_user'=>1));
            }
        } else {
            //$this->presenter->redirect('this');
        }
        $this->presenter->redirect('this');
    }

    public function handleUserRegistration($data)
    {
        
        $Osoba = new Osoba();
        $osoba_data = array(
            'jmeno' => $data['jmeno'],
            'prijmeni' => $data['prijmeni'],
            'titul_pred' => $data['titul_pred'],
            'titul_za' => $data['titul_za'],
            'email' => $data['email'],
            'telefon' => $data['telefon'],
            'pozice' => $data['pozice'],
        );
        
        $User = new UserModel();
        $user_data = array(
            'username'=> $_SERVER['PHP_AUTH_USER'],
            'heslo'=> $_SERVER['PHP_AUTH_PW'],
            'local' => 1
        );

        try {
            
            dibi::begin();

            $user_id = $User->insert($user_data);
            $osoba_id = $Osoba->ulozit($osoba_data);
            $User->pridatUcet($user_id, $osoba_id, 2);
            
            dibi::commit();

            $this->presenter->flashMessage('Účet uživatele "'. $user_data['username'] .'" byl úspěšně vytvořen.');
        } catch (DibiException $e) {
            if ( $e->getCode() == 1062 ) {
                $this->presenter->flashMessage('Uživatel "'. $user_data['username'] .'" již existuje. Kontaktujte svého správce pro vyřešení tohoto problému.','warning');
            } else {
                $this->presenter->flashMessage('Účet uživatele se nepodařilo vytvořit.','warning');
                $this->presenter->flashMessage('Chyba: '. $e->getMessage(),'warning');
            }
        }
        
        $this->presenter->redirect('this');
    }    

    public function handleSync($data)
    {
        unset($data['synchonizovat']);

        $this->presenter->redirect('this');
    }

}
