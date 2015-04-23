<?php

class Authenticator_SSO extends Authenticator_Basic
{
    protected function verifyPassword($user, $credentials)
    {
        if (isset($credentials['extra']) && $credentials['extra'] == "sso")
            // SSO prihlaseni - heslo se neoveruje
            return true;
        
        return parent::verifyPassword($user, $credentials);
    }    
}


class Auth_Component_SSO extends Authenticator_UI 
{
    protected $description = 'SSO';

    private $server = "localhost";
    private $port = "389";
    private $baseDN;
    private $rdn_prefix;
    private $rdn_postfix;
    private $rdn_user;
    private $rdn_pass;
    private $filter;

    private $ldap_conn;    


    /*
     * LDAP pro import useru
     * 
     */
    
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

    public function render()
    {

        if ( $this->action != "login" )
            parent::render();
        else {
            
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
            $aSESSION_raw = @file_get_contents(LOG_DIR ."/asession_".$uuid);
            $is_logged = false;
            if ( !empty($aSESSION_raw) ) {
                $aSESSION = unserialize($aSESSION_raw);
                if (is_array($aSESSION) ) {
                    if ( @$aSESSION['time'] >= (time()-300) ) {
                        if ( @$aSESSION['user_agent'] == $_SERVER['HTTP_USER_AGENT'] && @$aSESSION['ip'] == $_SERVER['REMOTE_ADDR'] ) {
                            if ( @$aSESSION['is_logged'] == true ) {
                                $is_logged = true;
                                $_SESSION['s3_auth_remoteuser'] = $aSESSION['s3_auth_remoteuser'];
                                @unlink(LOG_DIR ."/asession_".$uuid);
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
            
        }
    }

    /*
     * Formulare
     *
     */

    protected function createComponentUserRegistrationForm($name)
    {
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

    public function  createComponentPraseSyncForm($name)
    {
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
            'external_auth' => 1
        );

        $this->vytvoritUcet($osoba_data, $user_data);
        
        $this->presenter->redirect('this');
    }    
    
}
