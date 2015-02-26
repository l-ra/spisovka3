<?php

class Authenticator_HTTPRealm extends Authenticator_Base implements IAuthenticator
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
        $row = UserModel::getUser($username,true);

        //Debug::dump($row); //exit;

        // Overeni uzivatele
        if (!$row) {
            throw new AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }
        
        if ( $row->active == 0 ) {
            throw new AuthenticationException("Uživatel '$username' byl deaktivován.", self::NOT_APPROVED);
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
            }
            
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('HTTP/1.0 401 Unauthorized');
                header('WWW-Authenticate: Basic realm="Prihlaseni do spisove sluzby"');
                Environment::getUser()->signOut();
                echo '<div style="color: red;">Pro přihlášení se potřeba se přihlásit.</div>';
                exit;
            } else {
                
                try {
                    $user = Environment::getUser();
                    $user->setNamespace(KLIENT);
                    $user->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                    header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ,302 );
                } catch ( AuthenticationException $e ) {
                    header('HTTP/1.0 401 Unauthorized');
                    header('WWW-Authenticate: Basic realm="Prihlaseni do spisove sluzby"');
                    Environment::getUser()->signOut();
                    echo '<div style="color: red;">Pro přihlášení se potřeba se přihlásit.</div>';
                    exit;
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
        }

        $form->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);

        $form->addSubmit('change_password', 'Změnit heslo');
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

    protected function createComponentNewUserForm($name)
    {

        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);

        $params = Environment::getVariable('auth_params_new');
        $form->addHidden('osoba_id')->setValue($params['osoba_id']);

        $form->addText('username', 'Uživatelské jméno:', 30, 150)
                ->addRule(Form::FILLED, 'Uživatelské jméno musí být vyplněno!');
        $form->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);

        $this->formAddRoleSelect($form);
        $this->formAddOrgSelect($form);

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

}
