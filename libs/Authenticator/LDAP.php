<?php

class Authenticator_LDAP extends Authenticator_Base implements IAuthenticator
{

    private $server = "localhost";
    private $port = 389;
    private $baseDN;
    private $rdn_prefix;
    private $rdn_postfix;
    private $rdn_user;
    private $rdn_pass;

    private $ldap_conn;

    protected $receivedSignal;
    protected $action;
    protected $wasRendered = FALSE;

    /*
     * Autentizacni proces
     *
     */

    public function authenticate(array $credentials)
    {

        // vstupy
        $username = $credentials[self::USERNAME];
        $password = $credentials[self::PASSWORD];
        $password_local = sha1( $credentials[self::USERNAME] . $credentials[self::PASSWORD] );

        // Vyhledani uzivatele
        $user = new UserModel();
        $log = new LogModel();
        $row = UserModel::getUser($username,true);

        if ( isset($row->local) && $row->local == 0 ) {
            
            if ( $row->active == 0 ) {
                throw new AuthenticationException("Uživatel '$username' byl deaktivován.", self::NOT_APPROVED);
            }            
            
            if ($row->password !== $password_local) {
                $log->logAccess($row->id, 0);
                throw new AuthenticationException("Neplatné přihlašovací údaje.", self::INVALID_CREDENTIAL);
            } else {
                $user->zalogovan($row->id);
                $log->logAccess($row->id, 1);
            }
        } else {
            // LDAP autentizace
            $ldap_params = Environment::getConfig('authenticator');
            if ( !isset($ldap_params->ldap) ) {
                throw new AuthenticationException("Nedostupné nastavení nutné pro přihlášení.", self::FAILURE);
            }

            try {
                if ( $this->ldap_login($username, $password, $ldap_params->ldap) ) {
                    if ( $row ) {
                        
                        if ( $row->active == 0 ) {
                            throw new AuthenticationException("Uživatel '$username' byl deaktivován.", self::NOT_APPROVED);
                        }                          
                        
                        $user->zalogovan($row->id);
                        $log->logAccess($row->id, 1);
                    } else {
                        throw new AuthenticationException("Uživatel '$username' není evidován v tomto systému. Kontaktujte svého správce.", self::IDENTITY_NOT_FOUND);
                        return false;
                    }
                } else {
                    /*if ( isset($row->local) && $row->local == 1 ) {
                        if ( $row->password !== $password_local ) {
                            $user->zalogovan($row->id);
                            $log->logAccess($row->id, 1);
                        } else {
                            $log->logAccess($row->id, 0);
                            throw new AuthenticationException("Neplatné přihlašovací údaje. (local)", self::INVALID_CREDENTIAL);
                        }
                    } else {*/
                        if ( $row ) {
                            $log->logAccess($row->id, 0);
                        }
                        throw new AuthenticationException("Neplatné přihlašovací údaje.", self::INVALID_CREDENTIAL);
                    //}
                }
            } catch (AuthenticationException $e) {
                if ( $e->getCode() == self::FAILURE ) {
                    if ( isset($row->local) && $row->local == 2 ) {
                        if ( $row->password === $password_local ) {
                            $user->zalogovan($row->id);
                            $log->logAccess($row->id, 1);
                        } else {
                            $log->logAccess($row->id, 0);
                            throw new AuthenticationException("Neplatné přihlašovací údaje. (local)", self::INVALID_CREDENTIAL);
                        }
                    } else {
                        throw new AuthenticationException($e->getMessage(), $e->getCode());
                    }
                } else {
                    throw new AuthenticationException($e->getMessage(), $e->getCode());
                }
            }

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
     * Metody autentizatoru
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
                    throw new AuthenticationException("Chyba LDAP: ". ldap_error($lconn), self::INVALID_CREDENTIAL);
                    return false;
                }
            } else {
                throw new AuthenticationException("Chyba LDAP: ". ldap_error($lconn), self::FAILURE);
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

    protected function ldap_login( $username, $password, $params )
    {

        if (function_exists('ldap_connect') ) {
            if ( $lconn = @ldap_connect($params->server, $params->port) ) {

                ldap_set_option($lconn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($lconn, LDAP_OPT_REFERRALS, 0);

                if ( empty($username) ) {
                    $bind_rdn = null;
                    $pass = null;
                } else {
                    $bind_rdn = $params->rdn_prefix ."". $username ."". $params->rdn_postfix;
                    $pass = $password;
                }
                
                if ( $lbind = @ldap_bind($lconn, $bind_rdn, $pass) ) {
                    $this->ldap_conn = $lconn;
                    return $lconn;
                } else {
                    $error_no = ldap_errno($lconn);
                    if ( $error_no == 49 ) {
                        return false;
                    } else if ( $error_no == -1 ) {
                        throw new AuthenticationException("Nelze se připojit k LDAP serveru.", self::FAILURE);
                        return false;
                    } else {
                        // -1 - no connect to server
                        throw new AuthenticationException("Chyba LDAP: ". ldap_errno($lconn) ." - ". ldap_error($lconn), self::INVALID_CREDENTIAL);
                        return false;
                    }
                }
            } else {
                throw new AuthenticationException("Chyba LDAP: ". ldap_error($lconn), self::FAILURE);
                return false;
            }

        } else {
            throw new AuthenticationException("Nedostupná LDAP komponenta.", self::FAILURE);
            return false;
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

        /*$user = array();
            $user[0]["dn"] = "admin";
            $user[0]["plne_jmeno"] = "Prijmeni Jmeno ";
            $user[0]["uid"] = "admin";
            $user[0]["jmeno"] = "Jméno";
            $user[0]["prijmeni"] = "Příjmení";
            $user[0]["email"] = "";          
        for ( $i=1; $i < 10; $i++ ) {
            $user[$i]["dn"] = "user$i";
            $user[$i]["plne_jmeno"] = "Prijmeni Jmeno $i";
            $user[$i]["uid"] = "user$i";
            $user[$i]["jmeno"] = "Jméno $i";
            $user[$i]["prijmeni"] = "Příjmení $i";
            $user[$i]["email"] = "";            
        }
        
        return $user;*/
        
        // LDAP autentizace
        $ldap_params = Environment::getConfig('authenticator');
        if ( !isset($ldap_params->ldap) ) {
            throw new AuthenticationException("Nedostupné nastavení nutné pro přihlášení.", self::FAILURE);
        }

        try {

            if ( $this->ldap_connect($ldap_params->ldap) ) {

                $seznam = $this->ldap_getAllUser();
                return $seznam;
            } 



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
            $this->template->setFile(dirname(__FILE__) . '/auth_login.phtml');
            $this->template->render();
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

        $user_info = UserModel::getUser($user_id);
        $local = @$user_info->local;

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes LDAP)',
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

        $params = Environment::getVariable('auth_params_new');
        $form->addHidden('osoba_id')->setValue($params['osoba_id']);

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes LDAP)',
                          0=>'pouze lokální přihlášení',
                          2=>'kombinované přihlášení (pokud selže externí přihlášení, tak se použije lokální přihlášení)'
                    )
               )->setValue(1);

        $seznam = $this->getAllUser();
        $ldap_seznam = array();
        $ldap_seznam[0] = "vyberte ze seznamu...";
        if ( is_array($seznam) ) {
            $User = new UserModel();
            $user_seznam = $User->fetchAll()->fetchAssoc('username');

            foreach ($seznam as $user) {
                if ( !isset($user_seznam[$user['uid']]) ) {
                    $ldap_seznam[ $user['uid'] ] = $user['uid'] ." - ". $user['prijmeni'] ." ". $user['jmeno'];
                }
            }
        }

        $form->addSelect('username_ldap',"Uživatelské jméno z LDAP:",$ldap_seznam);

        $form->addText('username', 'Uživatelské jméno:', 30, 150);
                //->addRule(Form::FILLED, 'Uživatelské jméno musí být vyplněno!');
        $form->addPassword('heslo', 'Heslo:', 30, 30);
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
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

    public function  createComponentSyncAppForm($name)
    {
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
            
            /*$renderer->wrappers['form']['container'] = "table";
            $renderer->wrappers['group']['container'] = "tr";
            $renderer->wrappers['group']['label'] = null;
            $renderer->wrappers['controls']['container'] = null;
            $renderer->wrappers['pair']['container'] = 'td';
            $renderer->wrappers['label']['container'] = 't3';
            $renderer->wrappers['control']['container'] = 't4';*/
            
            
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
                        if ( $User->zmenitHeslo($user->id, $data['heslo'], $data['local']) ) {
                            if ( !empty($data['heslo']) ) {
                                $zmeneno = 1;
                            } else {
                                $zmeneno = 2;
                            }
                            
                        }
                        break;
                    }
                }
            }

            if ( $zmeneno == 2 ) {
                $this->presenter->flashMessage('Hodnoty byly změněny. Heslo uživatele "'. $user->username .'" však zůstává stejné.');
            } else if ( $zmeneno == 1 ) {
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
        if ( $data['username_ldap'] != "0" )
            $data['username'] = $data['heslo'] = $data['username_ldap'];
            
        parent::handleNewUser($data);
    }
    
    public function handleSync($data)
    {
        $this->handleSync2($data);
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
