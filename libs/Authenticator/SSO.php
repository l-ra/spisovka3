<?php

class Authenticator_SSO extends Control implements IAuthenticator
{

    protected $receivedSignal;
    protected $action;
    protected $wasRendered = FALSE;
    
    private $_realm;
    private $_users;
    
    private $server = "localhost";
    private $port = "389";
    private $baseDN;
    private $rdn_prefix;
    private $rdn_postfix;
    private $rdn_user;
    private $rdn_pass;

    private $ldap_conn;    

    public function authenticate(array $credentials)
    {

        // vstupy
        $username = $credentials[self::USERNAME];
        //$password = sha1( $credentials[self::USERNAME] . $credentials[self::PASSWORD] );
        // u SSO se heslo neoveruje. Ten se overil pri overeni, zde jen zkontrolujeme usera

        // Vyhledani uzivatele
        $user = new UserModel();
        $log = new LogModel();
        $row = $user->getUser($username,true);

        //Debug::dump($row); //exit;

        // Overeni uzivatele
        if (!$row) {
            throw new AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }

        $user->zalogovan($row->id);
        $log->logAccess($row->id, 1);

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
     * LDAP pro import useru
     * 
     */
    
    protected function ldap_connect( $params )
    {

        if (function_exists('ldap_connect') ) {
            if ( $lconn = @ldap_connect($params->server, $params->port) ) {

                ldap_set_option($lconn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($lconn, LDAP_OPT_REFERRALS, 0);

                //$bind_rdn = $params->rdn_prefix . $params->user . $params->rdn_postfix;

                if ( $lbind = @ldap_bind($lconn, $params->user, $params->pass) ) {

                    $this->server = $params->server;
                    $this->port = $params->port;
                    $this->baseDN = $params->baseDN;
                    $this->rdn_prefix = $params->rdn_prefix;
                    $this->rdn_postfix = $params->rdn_postfix;
                    $this->rdn_user = $params->user;
                    $this->rdn_pass = $params->pass;

                    $this->ldap_conn = $lconn;
                    return $lconn;
                } else {
                    //throw new AuthenticationException("Chyba LDAP: ". ldap_error($lconn), self::INVALID_CREDENTIAL);
                    return false;
                }
            } else {
                //throw new AuthenticationException("Chyba LDAP: ". ldap_error($lconn), self::FAILURE);
                return false;
            }

        } else {
            throw new AuthenticationException("Nedostupná LDAP komponenta.", self::FAILURE);
            return false;
        }

    }

    protected function ldap_close($lconn = null)
    {
        if ( $lconn != null ) {
            ldap_close($lconn);
            return true;
        } else if ( $this->ldap_conn ) {
            ldap_close($this->ldap_conn);
            $this->ldap_conn = null;
            return true;
        }
    }

    protected function ldap_getUser($uid, $lconn = null)
    {

        /* Kontrola ukazatele pripojeni */
        if ( $lconn == null ) {
            if ( $this->ldap_conn ) {
                $lconn = $this->ldap_conn;
            } else {
                return false;
            }
        }

        /* Nastaveni a nacteni dat */
        $filtr = $this->rdn_prefix. $uid ."*";
        $rec = ldap_search($lconn, $this->baseDN, $filtr);
        $info = ldap_get_entries($lconn, $rec);

        //print_r($info);

        /* Parsovani dat */
        $user = $this->ldap_parseEntries($info);
        return $user;

    }

    protected function ldap_getAllUser($lconn = null)
    {

        /* Kontrola ukazatele pripojeni */
        if ($lconn == null) {
            if ($this->ldap_conn) {
                $lconn = $this->ldap_conn;
             } else {
                return false;
            }
        }

        /* Nastaveni a nacteni dat */
        $filtr = "(".$this->rdn_prefix."*)";
        $rec = @ldap_search($lconn, $this->baseDN, $filtr);
        $info = @ldap_get_entries($lconn, $rec);

        /* Parsovani dat */
        $parse = $this->ldap_parseEntries($info);
        return $parse;

    }

    public function getAllUser()
    {

        // LDAP autentizace
        $ldap_params = Environment::getConfig('authenticator');
        if ( !isset($ldap_params->ldap) ) {
            throw new AuthenticationException("Nedostupné nastavení nutné pro přihlášení.", self::FAILURE);
        }

        try {
            
            $access = array();
            if ( isset($ldap_params->ldap->_0) ) {
                foreach ( $ldap_params->ldap as $li => $ldap ) {
                    $access[$li] = $ldap;
                }
            } else {
                $access[0] = $ldap_params->ldap;
            }
            
            $seznam = array();
            foreach ( $access as $params ) {
                if ( $this->ldap_connect($params) ) {
                    $seznam = $this->ldap_getAllUser();
                    //$seznam = array_merge($seznam, $seznam_in);
                    $this->ldap_close();
                    break;
                }
            }
            return $seznam;

        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    protected function ldap_getAllInfo( $lconn = null )
    {

        /* Kontrola ukazatele pripojeni */
        if ($lconn == null) {
            if ($this->ldap_conn) {
                $lconn = $this->ldap_conn;
            } else {
                return false;
            }
        }

        /* Nastaveni a nacteni dat */
        $filtr = "(cn=*)";
        $rec = @ldap_search($lconn, $this->baseDN, $filtr);
        $info = @ldap_get_entries($lconn, $rec);

        /* Parsovani dat */
        $parse = $this->ldap_parseEntries($info);
        return $parse;

    }

    protected function ldap_parseEntries($info)
    {

        $user = array();

        for($i = 0; $i < $info["count"]; $i++) {
            
            $user[$i]["server"] = $this->server;
            $user[$i]["dn"] = isset($info[$i]["dn"][0])?$info[$i]["dn"][0]:"";
            $user[$i]["plne_jmeno"] = isset($info[$i]["cn"][0])?$info[$i]["cn"][0]:"";
            $user[$i]["uid"] = isset($info[$i]["uid"][0])?$info[$i]["uid"][0]:"";
            $user[$i]["jmeno"] = isset($info[$i]["givenname"][0])?$info[$i]["givenname"][0]:"";
            $user[$i]["prijmeni"] = isset($info[$i]["sn"][0])?$info[$i]["sn"][0]:$user[$i]["plne_jmeno"];
            $user[$i]["email"] = isset($info[$i]["mail"][0])?$info[$i]["mail"][0]:"";

            foreach ($info[$i] as $key => $value) {
                if ( is_numeric($key) ) continue;
                $user[$i]['ldap'][$key] = $value[0];
            }
        }

        if ( count($user) > 0 ) {
            return $user;
        } else {
            return null;
        }

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
            
            @session_start();
            
            if ( Environment::getHttpRequest()->getCookie('s3_logout') ) {
                unset($_SESSION['s3_auth_remoteuser']);
                Environment::getHttpResponse()->setCookie('s3_logout', null, time());
                header("Location: ". Environment::getVariable('baseUri') ."auth/logout.php" ,302 );
                exit;
            }            
            
            //$headers = apache_request_headers();
            //echo "<pre>"; print_r($headers); echo "</pre>"; exit;
            //echo "<pre>"; print_r($_SERVER); echo "</pre>";
            
            // SESSION fix
            $uuid = Environment::getHttpRequest()->getQuery('_asession');
            //echo "<pre>dd: "; print_r($uuid); echo "</pre>"; exit;
            $aSESSION_raw = file_get_contents(APP_DIR ."/../log/asession_".$uuid);
            $is_logged = false;
            if ( !empty($aSESSION_raw) ) {
                $aSESSION = unserialize($aSESSION_raw);
                if (is_array($aSESSION) ) {
                    if ( @$aSESSION['time'] >= (time()-300) ) {
                        if ( @$aSESSION['user_agent'] == $_SERVER['HTTP_USER_AGENT'] && @$aSESSION['ip'] == $_SERVER['REMOTE_ADDR'] ) {
                            if ( @$aSESSION['is_logged'] == true ) {
                                $is_logged = true;
                                $_SESSION['s3_auth_remoteuser'] = $aSESSION['s3_auth_remoteuser'];
                                @unlink(APP_DIR ."/../log/asession_".$uuid);
                            }
                        }
                    }
                }
            }            
            
            
            if ( $_SESSION['s3_auth_remoteuser'] ) {
                
                try {
                    $user = Environment::getUser();
                    $user->setNamespace(KLIENT);
                    $user->authenticate($_SESSION['s3_auth_remoteuser'], "");
                    header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ,302 );
                } catch ( AuthenticationException $e ) {
                    $this->action = "user_registration";
                    $this->template->setFile(dirname(__FILE__) . '/auth_user_registration.phtml');
                    $this->template->render();
                    //header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ."auth" ,302 );                
                }                
                
            } else {
            
                $headers = apache_request_headers();
                if(empty($headers['Authorization'])) {
                    header("HTTP/1.1 401 Authorization Required");
                    header("WWW-Authenticate: Negotiate");
                    // SSO není podporováno, zobrazím standardní login či informace
                    echo "SSO autentizace neprobehla.";
                    exit;
                } else { 
                    // SSO OK, přesměruju na SSO login
                    header("Location: ". Environment::getVariable('baseUri') ."auth",302 );                
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
            $user_id = $params['user_id'];
        } else {
            $user_id = Environment::getUser()->getIdentity()->id;
        }

        $User = new UserModel();
        $user_info = $User->getUser($user_id);
        $local = @$user_info->local;

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes SSO)',
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
                    array(1=>'pouze externí přihlášení (přes SSO)',
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

    protected function  createComponentSyncAppForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}        
        
        $seznam = $this->getAllUser();

        $form = new AppForm($this, $name);
        if ( is_array($seznam) ) {

            $Role = new RoleModel();
            $role_seznam = $Role->select();

            $User = new UserModel();
            $user_seznam = $User->fetchAll()->fetchAssoc('username');

            foreach ($seznam as $id => $user) {

                $form->addGroup($user['plne_jmeno'] ." - ". $user['uid']);
                $subForm = $form->addContainer('user_'. $id);
                //$subForm = $form->addContainer('user_'. String::webalize($user['uid']));

                if ( !isset($user_seznam[ $user['uid'] ])  ) {
                    $subForm->addCheckbox('add', 'Připojit');
                    $subForm->addText('username', "Uživatelské jméno")
                            ->setValue($user['uid']);
                    $subForm->addText('prijmeni', 'Příjmení')
                            ->setValue($user['prijmeni']);
                    $subForm->addText('jmeno', 'Jméno')
                            ->setValue($user['jmeno']);
                    $subForm->addText('email', 'Email')
                            ->setValue($user['email']);
                    $subForm->addSelect('role','Role',$role_seznam);
                } else {
                    $subForm->addCheckbox('add', 'Připojen')
                            ->setValue(1)
                            ->setDisabled(true);
                }

            }
            $form->addGroup('Synchornizovat');
            $form->addSubmit('synchonizovat', 'Synchornizovat');
            $form->onSubmit[] = array($this, 'formSubmitHandler');

            $renderer = $form->getRenderer();
            $renderer->wrappers['controls']['container'] = null;
            $renderer->wrappers['pair']['container'] = 'dl';
            $renderer->wrappers['label']['container'] = 'dt';
            $renderer->wrappers['control']['container'] = 'dd';
            
        } else if ( is_null($seznam) ) {
            echo '<div class="prazdno">';
            echo 'Seznam uživatelů není k dispozici.';
            echo '<p>';
            echo 'Zkontrolujte správnost LDAP nastavení.';
            echo "</div>";
        } else {
            echo '<div class="prazdno">';
            echo $seznam;
            echo '<p>';
            echo 'Zkontrolujte správnost LDAP nastavení.';
            echo "</div>";
        }
        return $form;


    }

    public function  createComponentSyncForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}        

        $this->handleSyncManual();
        
        $seznam = $this->getAllUser();
        
        $form = new AppForm($this, $name);
        if ( is_array($seznam) ) {

            $Role = new RoleModel();
            $role_seznam = $Role->select();

            $User = new UserModel();
            $user_seznam = $User->fetchAll()->fetchAssoc('username');

            echo "<div>\n";
            echo "Zde naleznete seznam všech uživatelů uložených přes LDAP.\n<br /><br />\n";
            echo "Přidání zaměstnance se provádí tak, že u každého uživatele zaškrtnete položku připojit a ve stejném řádku vyberete požadovanou roli a případně poupravit nebo doplnit další hodnoty jako příjmení, jméno a email.\n<br /><br />\n";
            echo "Po dokončení nastavení a úprav stisknete na tlačítko synchronizovat.\n<br /><br />\n";

            echo "</div>\n<br />\n";
            echo "<form action='' method='post'>\n";
            echo "<table id='synch_table'>\n";
            echo "  <tr>\n";
            echo "    <th>Připojit</th>\n";
            echo "    <th>Uživatelské jméno</th>\n";
            echo "    <th>Role</th>\n";
            echo "    <th>Příjmení</th>\n";
            echo "    <th>Jméno</th>\n";
            echo "    <th>Email</th>\n";
            echo "  </tr>\n";
            foreach ($seznam as $id => $user) {
                if ( !isset($user_seznam[ $user['uid'] ])  ) {
                    // novy - nepripojen
                    echo "  <tr>\n";
                    echo "    <td><input type='checkbox' name='usersynch_pripojit[".$id."]' /></td>\n";
                    echo "    <td><input class='synch_input' type='text' name='usersynch_username[".$id."]' value='". $user['uid'] ."' readonly='readonly' /></td>\n";
                    echo "    <td>". $this->mySelect("usersynch_role[".$id."]", $role_seznam, 2) ."</td>\n";
                    echo "    <td><input class='synch_input' type='text' name='usersynch_prijmeni[".$id."]' value='". $user['prijmeni'] ."' /></td>\n";
                    echo "    <td><input class='synch_input' type='text' name='usersynch_jmeno[".$id."]' value='". $user['jmeno'] ."' /></td>\n";
                    echo "    <td><input class='synch_input' type='text' name='usersynch_email[".$id."]' value='". $user['email'] ."' /></td>\n";
                    echo "  </tr>\n";                    
                } else {
                    // pripojen
                    echo "  <tr>\n";
                    echo "    <td>&nbsp;</td>\n";
                    echo "    <td>". $user['uid'] ."</td>\n";
                    echo "    <td colspan='4'>Uživatel je připojen do spisové služby.</td>\n";
                    echo "  </tr>\n";                    
                }
            }
            echo "</table>\n";
            echo "<div id='hromadna_akce'>\n";
            echo "<input type='submit' name='usersych_gosynch' value='Synchronizovat' />";
            echo "</div>\n";
            echo "</form>\n";
            
        } else if ( is_null($seznam) ) {
            echo '<div class="prazdno">';
            echo 'Seznam uživatelů není k dispozici.';
            echo '<p>';
            echo 'Zkontrolujte správnost LDAP nastavení.';
            echo "</div>";
        } else {
            echo '<div class="prazdno">';
            echo $seznam;
            echo '<p>';
            echo 'Zkontrolujte správnost LDAP nastavení.';
            echo "</div>";
        }
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
        
        @session_start();
        if ( !isset($_SESSION['s3_auth_remoteuser']) ) {
            $this->presenter->flashMessage('Nepodařilo se získat uživatelské jméno přihlašeného uživatele!','warning');
            $this->presenter->redirect('this');
            return false;
        }
        
        $User = new UserModel();
        $user_data = array(
            'username'=> $_SESSION['s3_auth_remoteuser'],
            'heslo'=> uniqid(),
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
        
        if ( count($data)>0 ) {
            $Osoba = new Osoba();
            $User = new UserModel();
            $user_add = 0;
            foreach ( $data as $user ) {
                if ( isset($user['add']) && $user['add'] == true ) {

                    dibi::begin();

                    $user_data = array(
                        'username' => $user['username'],
                        'heslo' => $user['email'],
                        'local' => 1
                    );
                    $user_id = $User->insert($user_data);

                    $osoba = array(
                        'jmeno' => $user['jmeno'],
                        'prijmeni' => $user['prijmeni'],
                        'email' => $user['email']
                    );
                    $osoba_id = $Osoba->ulozit($osoba);
                    $User->pridatUcet($user_id, $osoba_id, $user['role']);

                    dibi::commit();

                    $this->presenter->flashMessage('Uživatel "'. $user['username'] .'" byl přidán do systému.');
                    $user_add++;

                }
            }
            if ( $user_add == 0 ) {
                $this->presenter->flashMessage('Nebyli přidáni žádní zaměstnanci.');
            }

        } else {
            $this->presenter->flashMessage('Nebyli přidáni žádní zaměstnanci.');
        }

        $this->presenter->redirect('this');

    }

    public function handleSyncManual()
    {
        $data = Environment::getHttpRequest()->getPost();
        
        if ( isset($data['usersynch_pripojit']) && count($data['usersynch_pripojit'])>0 ) {
            $Osoba = new Osoba();
            $User = new UserModel();
            $user_add = 0;
            foreach ( $data['usersynch_pripojit'] as $index => $status ) {
                if ( $status == "on" ) {

                    $user = array(
                        'username' => $data['usersynch_username'][$index],
                        'prijmeni' => $data['usersynch_prijmeni'][$index],
                        'jmeno' => $data['usersynch_jmeno'][$index],
                        'email' => $data['usersynch_email'][$index],
                        'role' => $data['usersynch_role'][$index],
                    );
                    
                    dibi::begin();

                    $user_data = array(
                        'username' => $user['username'],
                        'heslo' => $user['email'],
                        'local' => 1
                    );
                    $user_id = $User->insert($user_data);

                    $osoba = array(
                        'jmeno' => $user['jmeno'],
                        'prijmeni' => $user['prijmeni'],
                        'email' => $user['email']
                    );
                    $osoba_id = $Osoba->ulozit($osoba);
                    $User->pridatUcet($user_id, $osoba_id, $user['role']);

                    dibi::commit();

                    $this->presenter->flashMessage('Uživatel "'. $user['username'] .'" byl přidán do systému.');
                    $user_add++;

                }
            }
            if ( $user_add == 0 ) {
                $this->presenter->flashMessage('Nebyli přidáni žádní zaměstnanci.');
            }

        } else {
            $this->presenter->flashMessage('Nebyli přidáni žádní zaměstnanci.');
        }

        if ( Environment::getHttpRequest()->getMethod() == "POST" ) {
            //$this->presenter->redirect('this');
            header("Location: ". Environment::getHttpRequest()->getUri()->getAbsoluteUri() ,"303");
        }
    }    

    protected function mySelect($name,$data,$default=null)
    {
        
        $el = Html::el('select')->name($name);
        foreach ( $data as $index => $value ) {
            $el->create('option')
                ->value($index)
                ->selected($index == $default)
                ->setText($value);
        }
        return $el;        
        
    }    
    
}
