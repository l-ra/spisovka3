<?php

class Authenticator_SSO extends Authenticator_Base implements Nette\Security\IAuthenticator
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
    private $filter;

    private $ldap_conn;    

    public function authenticate(array $credentials)
    {

        // vstupy
        $username = $credentials[self::USERNAME];
        $password = sha1( $credentials[self::USERNAME] . $credentials[self::PASSWORD] );
        // u SSO se heslo neoveruje. Ten se overil pri overeni, zde jen zkontrolujeme usera

        // Vyhledani uzivatele
        $user = new UserModel();
        $log = new LogModel();
        $row = UserModel::getUser($username,true);

        //Nette\Diagnostics\Debugger::dump($row); //exit;

        // Overeni uzivatele
        if (!$row) {
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }
        
        if ( $row->active == 0 ) {
            throw new Nette\Security\AuthenticationException("Uživatel '$username' byl deaktivován.", self::NOT_APPROVED);
        }          

        if ( isset($credentials['extra']) && $credentials['extra'] == "sso" ) {
            // SSO prihlaseni - heslo se neoveruje
            $user->zalogovan($row->id);
            $log->logAccess($row->id, 1);
        }
        else {
            // Alternativni prihlaseni klasickym zpusobem - overeni hesla
            if ($row->password !== $password) {
                $log->logAccess($row->id, 0);
                throw new Nette\Security\AuthenticationException("Neplatné heslo.", self::INVALID_CREDENTIAL);
            } else {
                $user->zalogovan($row->id);
                $log->logAccess($row->id, 1);
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
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nemá přiřazenou žádnou roli. Není možné ho připustit k aplikaci. Kontaktujte svého správce.", self::NOT_APPROVED);
        }
        
        $row->klient = KLIENT;

        // tady nacitam taky roli
        return new Nette\Security\Identity($row->display_name, $identity_role, $row);
    }

    /*
     * LDAP pro import useru
     * 
     */
    
    protected function ldap_connect( $params )
    {

        if (function_exists('ldap_connect') ) {
            if ( $lconn = ldap_connect($params->server, $params->port) ) {

                ldap_set_option($lconn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($lconn, LDAP_OPT_REFERRALS, 0);

                //$bind_rdn = $params->rdn_prefix . $params->user . $params->rdn_postfix;

                if ( $lbind = ldap_bind($lconn, $params->user, $params->pass) ) {

                    $this->server = $params->server;
                    $this->port = $params->port;
                    $this->baseDN = $params->baseDN;
                    $this->rdn_prefix = $params->rdn_prefix;
                    $this->rdn_postfix = $params->rdn_postfix;
                    $this->rdn_user = $params->user;
                    $this->rdn_pass = $params->pass;
                    $this->filter = $params->filter;

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
            throw new Nette\Security\AuthenticationException("Nedostupná LDAP komponenta.", self::FAILURE);
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

    protected function ldap_getAllUser($lconn = null, $seznam = null)
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
        // $filtr = "(".$this->rdn_prefix."*)";
        $filtr = $this->filter;
        if (empty($filtr))
            $filtr = '(objectClass=user)';
        $rec = ldap_search($lconn, $this->baseDN, $filtr);
        $info = ldap_get_entries($lconn, $rec);
        
        /* Parsovani dat */
        $parse = $this->ldap_parseEntries($info, $seznam);
        return $parse;

    }

    public function getAllUser()
    {

        // LDAP autentizace
        $ldap_params = Nette\Environment::getConfig('authenticator');
        if ( !isset($ldap_params->ldap) ) {
            throw new Nette\Security\AuthenticationException("Nedostupné nastavení nutné pro přihlášení.", self::FAILURE);
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
            
            $seznam = array(); $spojeno = 0;
            foreach ( $access as $params ) {
                if ( $this->ldap_connect($params) ) {
                    $seznam = $this->ldap_getAllUser(null, $seznam);
                    $this->ldap_close();
                    $spojeno++;
                    //break;
                }
            }
            if ( $spojeno == 0 ) {
                return "Nepodařilo se spojit s žádným LDAP serverem!";
            } else {
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

    protected function ldap_parseEntries($info, $user = array())
    {

        //$user = array();

        for($i = 0; $i < $info["count"]; $i++) {
            
            $uid = isset($info[$i]["samaccountname"][0])?$info[$i]["samaccountname"][0]:$i;
            $user[$uid]["server"] = $this->server;
            $user[$uid]["dn"] = isset($info[$i]["distinguishedname"][0])?$info[$i]["distinguishedname"][0]:"";
            $user[$uid]["plne_jmeno"] = isset($info[$i]["displayname"][0])?$info[$i]["displayname"][0]:"";
            $user[$uid]["uid"] = isset($info[$i]["samaccountname"][0])?$info[$i]["samaccountname"][0]:"";
            $user[$uid]["jmeno"] = isset($info[$i]["givenname"][0])?$info[$i]["givenname"][0]:"";
            $user[$uid]["prijmeni"] = isset($info[$i]["sn"][0])?$info[$i]["sn"][0]:$user[$uid]["plne_jmeno"];
            
            $user[$uid]["titul_pred"] = isset($info[$i]["personaltitle"][0])?$info[$i]["personaltitle"][0]:"";
            
            //   > title = všeobecná sestra
            //   > department = neurologie dětská ambulance
            //   > company = Nemocnice Teplice, o.z.
            $funkce_a = array();
            if ( !empty($info[$i]['title'][0]) ) $funkce_a[] = $info[$i]['title'][0];
            if ( !empty($info[$i]['department'][0]) ) $funkce_a[] = $info[$i]['department'][0];
            if ( !empty($info[$i]['company'][0]) ) $funkce_a[] = $info[$i]['company'][0];
            
            $funkce = implode(", ",$funkce_a);
            $user[$uid]["funkce"] = $funkce;
            
            if ( !empty($info[$i]['mail'][0]) ) {
                $email = $info[$i]['mail'][0];
            //} else if ( !empty($info[$i]['userprincipalname'][0]) ) {
            //    $email = $info[$i]['userprincipalname'][0];
            } else {
                $email = "";
            }
            $user[$uid]["email"] = $email;
            
            $tel_a = array();
            if ( !empty($info[$i]['telephonenumber'][0]) ) $tel_a[] = $info[$i]['telephonenumber'][0];
            if ( !empty($info[$i]['mobile'][0]) ) $tel_a[] = $info[$i]['mobile'][0];
            
            $telefon = implode(", ",$tel_a);
            $user[$uid]["telefon"] = $telefon;           

            //foreach ($info[$i] as $key => $value) {
            //    if ( is_numeric($key) ) continue;
            //    $user[$uid]['ldap'][$key] = $value[0];
            //}
        }
        
        if ( count($user) > 0 ) {
            ksort($user);
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
            
            if ( Nette\Environment::getHttpRequest()->getCookie('s3_logout') ) {
                unset($_SESSION['s3_auth_remoteuser']);
                Nette\Environment::getHttpResponse()->setCookie('s3_logout', null, time());
                header("Location: ". Nette\Environment::getVariable('baseUri') ."auth/logout.php" ,302 );
                exit;
            }            
            
            //$headers = apache_request_headers();
            //echo "<pre>"; print_r($headers); echo "</pre>"; exit;
            //echo "<pre>"; print_r($_SERVER); echo "</pre>";
            
            // SESSION fix
            $uuid = Nette\Environment::getHttpRequest()->getQuery('_asession');
            //echo "<pre>dd: "; print_r($uuid); echo "</pre>"; exit;
            $aSESSION_raw = @file_get_contents(APP_DIR ."/../log/asession_".$uuid);
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
            
            
            if ( @$_SESSION['s3_auth_remoteuser'] ) {
                
                try {
                    $user = Nette\Environment::getUser();
                    $user->setNamespace(KLIENT);
                    $user->authenticate($_SESSION['s3_auth_remoteuser'], "", "sso");
                    header("Location: ". Nette\Environment::getVariable('klientUri',Nette\Environment::getVariable('baseUri')) ,302 );
                } catch ( Nette\Security\AuthenticationException $e ) {
                    $this->action = "user_registration";
                    $this->template->setFile(dirname(__FILE__) . '/auth_user_registration.phtml');
                    $this->template->render();
                    //header("Location: ". Environment::getVariable('klientUri',Environment::getVariable('baseUri')) ."auth" ,302 );                
                }                
            } else if ( Nette\Environment::getHttpRequest()->getQuery('alternativelogin') ) {
                $base_url = Nette\Environment::getVariable('klientUri',Nette\Environment::getVariable('baseUri'));
                $this->template->alter_login = "SSO přihlášení selhalo nebo nebylo provedeno!<br />Zkuste znovu použít následující odkaz <a href='". $base_url ."'>Zkusit znovu přihlášení přes SSO</a>.<br /> Pokud se situace opakuje, kontaktujte svého správce.<br />Následující přihlašovací formulář slouží pouze pro alternativní přihlášení.";
                $this->template->setFile(dirname(__FILE__) . '/auth_login.phtml');
                $this->template->render();                 
                
            } else {
            
                // - vyrazeni Negotiate - provadi dvojite prihlaseni, ktere muze byt matouci
                //$headers = apache_request_headers();
                //if(empty($headers['Authorization'])) {
                //    header("HTTP/1.1 401 Authorization Required");
                //    header("WWW-Authenticate: Negotiate");
                    // SSO není podporováno, zobrazím standardní login či informace
                    //echo "SSO autentizace neprobehla.";
                //    $this->template->alter_login = "SSO přihlášení selhalo nebo nebylo provedeno!<br />Zkuste to znovu. Pokud se situace opakuje, kontaktujte svého správce.<br />Následující přihlašovací formulář slouží pouze pro alternativní přihlášení.";
                //    $this->template->setFile(dirname(__FILE__) . '/auth_login.phtml');
                //    $this->template->render();                    
                    //exit;
                //} else { 
                    // SSO OK, přesměruju na SSO login
                    header("Location: ". Nette\Environment::getVariable('baseUri') ."auth",302 );                
                    exit;
                //}  
                
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

        $form = new Nette\Application\UI\Form($this, $name);
        $form->addText('username', 'Uživatelské jméno:')
            ->addRule(Nette\Forms\Form::FILLED, 'Zadejte uživatelské jméno, nebo e-mail.');

        $form->addPassword('password', 'Heslo:')
            ->addRule(Nette\Forms\Form::FILLED, 'Zadejte přihlašovací heslo.');

        $form->addSubmit('login', 'Přihlásit');
        $form->onSubmit[] = array($this, 'formSubmitHandler');
        //$form->addProtection('Prosím přihlašte se znovu.');

        return $form;

    }

    protected function createComponentChangePasswordForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
        }

        $form = new Nette\Application\UI\Form($this, $name);

        $params = Nette\Environment::getVariable('auth_params_change');
        if ( isset($params['admin']) ) {
            $form->addHidden('osoba_id')->setValue($params['osoba_id']);
            $form->addHidden('user_id')->setValue($params['user_id']);
            $user_id = $params['user_id'];
        } else {
            $user_id = Nette\Environment::getUser()->getIdentity()->id;
        }

        $user_info = UserModel::getUser($user_id);
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
                ->addConditionOn($form["heslo"], Nette\Forms\Form::FILLED)
                    ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);

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

        $form = new Nette\Application\UI\Form($this, $name);

        $params = Nette\Environment::getVariable('auth_params_new');
        $form->addHidden('osoba_id')->setValue($params['osoba_id']);

        $form->addSelect('local', "Způsob přihlášení:",
                    array(1=>'pouze externí přihlášení (přes SSO)',
                          0=>'pouze lokální přihlášení',
                          2=>'kombinované přihlášení (pokud selže externí přihlášení, tak se použije lokální přihlášení)'
                    )
               )->setValue(1);

        $form->addText('username', 'Uživatelské jméno:', 30, 150);
                //->addRule(Form::FILLED, 'Uživatelské jméno musí být vyplněno!');
        $form->addPassword('heslo', 'Heslo:', 30, 30);
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                //->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["heslo"], Nette\Forms\Form::FILLED)
                    ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat !", $form["heslo"]);

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

    protected function createComponentUserRegistrationForm($name)
    {

        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
        }

        $form = new Nette\Application\UI\Form($this, $name);

        $form->addText('jmeno', 'Jméno:', 50, 150);
        $form->addText('prijmeni', 'Příjmení:', 50, 150)
                ->addRule(Nette\Forms\Form::FILLED, 'Příjmení musí být vyplněno!');
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

        $form = new Nette\Application\UI\Form($this, $name);
        if ( is_array($seznam) ) {

            $Role = new RoleModel();
            $role_seznam = $Role->selectBox();

            $User = new UserModel();
            $user_seznam = $User->select()->fetchAssoc('username');

            foreach ($seznam as $id => $user) {

                $form->addGroup($user['plne_jmeno'] ." - ". $user['uid']);
                $subForm = $form->addContainer('user_'. $id);
                //$subForm = $form->addContainer('user_'. Nette\Utils\Strings::webalize($user['uid']));

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
        
        $form = new Nette\Application\UI\Form($this, $name);
        if ( is_array($seznam) ) {

            $Role = new RoleModel();
            $role_seznam = $Role->selectBox();

            $User = new UserModel();
            $user_seznam = $User->select()->fetchAssoc('username');

            echo "<div>\n";
            echo "Zde naleznete seznam všech uživatelů uložených přes LDAP.\n<br /><br />\n";
            echo "Přidání zaměstnance se provádí tak, že u každého uživatele zaškrtnete položku připojit a ve stejném řádku vyberete požadovanou roli.\n<br /><br />\n";
            echo "Po dokončení nastavení a úprav stisknete na tlačítko synchronizovat.\n<br /><br />\n";

            echo "</div>\n<br />\n";
            echo "<div>Nalezeno ". count($seznam) ." záznamů.</div>\n";
            echo "<form action='' method='post'>\n";
            echo "<table id='synch_table'>\n";
            echo "  <tr>\n";
            echo "    <th>Připojit</th>\n";
            echo "    <th>Uživatelské jméno</th>\n";
            echo "    <th>Role</th>\n";
            echo "    <th>Jméno a příjmení<br />Funkce</th>\n";
            echo "    <th>Email<br />Telefon</th>\n";
            echo "  </tr>\n";
            foreach ($seznam as $id => $user) {
                if ( !isset($user_seznam[ $user['uid'] ])  ) {
                    // novy - nepripojen
                    echo "  <tr>\n";
                    echo "    <td>\n";
                    echo "       <input type='checkbox' name='usersynch_pripojit[".$id."]' />\n";
                    echo "    </td>\n";
                    echo "    <td>\n";
                    echo "       <strong>". $user['uid'] ."</strong>\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_username[".$id."]' value='". $user['uid'] ."' />\n";
                    echo "    </td>\n";
                    echo "    <td>\n";
                    echo "       ". $this->mySelect("usersynch_role[".$id."]", $role_seznam, 2) ."\n";
                    echo "    </td>\n";
                    echo "    <td>\n";
                    echo "       <strong>". $user['jmeno'] ." ". $user['prijmeni'] ."</strong><br/>\n";
                    echo "       ". $user['funkce'] ."<br/>\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_prijmeni[".$id."]' value='". $user['prijmeni'] ."' />\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_jmeno[".$id."]' value='". $user['jmeno'] ."' />\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_funkce[".$id."]' value='". $user['funkce'] ."' />\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_titul_pred[".$id."]' value='". $user['titul_pred'] ."' />\n";
                    echo "    </td>\n";
                    echo "    <td>\n";
                    echo "       ". $user['email'] ."<br/>\n";
                    echo "       ". $user['telefon'] ."<br/>\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_email[".$id."]' value='". $user['email'] ."' />\n";
                    echo "       <input class='synch_input' type='hidden' name='usersynch_telefon[".$id."]' value='". $user['telefon'] ."' />\n";
                    echo "    </td>\n";
                    echo "  </tr>\n";                    
                } else {
                    // pripojen
                    echo "  <tr>\n";
                    echo "    <td>&nbsp;</td>\n";
                    echo "    <td><strong>". $user['uid'] ."</strong></td>\n";
                    echo "    <td colspan='3'>Uživatel je připojen do spisové služby.</td>\n";
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
    
    
    public function formSubmitHandler(Nette\Application\UI\Form $form)
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
                throw new Nette\InvalidStateException("Unknown submit button.");
            }
        }
        if (!$this->presenter->isAjax())
            $this->presenter->redirect('this');
    }

    public function handleChangePassword($data)
    {
        $zmeneno = 0;
        $User = new UserModel();

        $params = Nette\Environment::getVariable('auth_params_change');

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

        $this->vytvoritUcet($osoba_data, $user_data);
        
        $this->presenter->redirect('this');
    }    

    public function handleSync($data)
    {
        $this->handleSync2($data);
    }

    protected function mySelect($name,$data,$default=null)
    {
        
        $el = Nette\Utils\Html::el('select')->name($name);
        foreach ( $data as $index => $value ) {
            $el->create('option')
                ->value($index)
                ->selected($index == $default)
                ->setText($value);
        }
        return $el;        
        
    }    
    
}
