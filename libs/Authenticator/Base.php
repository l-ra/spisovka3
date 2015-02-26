<?php

class Authenticator_Base extends Control
{

    protected function handleLogin($data)
    {
        try {
            $user = Environment::getUser();
            $user->setNamespace(KLIENT);
            $user->authenticate($data['username'], $data['password']);

            $redirect_home = (bool)Settings::get('login_redirect_homepage', false);
            if (!$redirect_home && isset($data['backlink']) && !empty($data['backlink']))
                $this->presenter->redirectUri($data['backlink']);
            else
                $this->presenter->redirect('this');
				// $this->presenter->redirect(':Spisovka:Default:default');
        }
		catch (AuthenticationException $e) {
            $this->presenter->flashMessage($e->getMessage(), 'warning');
			sleep(2); // sniz riziko brute force utoku
        }
    }

    protected function formAddRoleSelect(AppForm $form)
    {
        $Role = new RoleModel();
        $form->addSelect('role', 'Role:', $Role->seznam());    
    }

    protected function formAddOrgSelect(AppForm $form)
    {
        $m = new Orgjednotka;
        $seznam = $m->linearniSeznam();
        $select = array(0 => 'žádná');
        foreach ($seznam as $org)
            $select[$org->id] = $org->ciselna_rada . ' - ' . $org->zkraceny_nazev;

        $form->addSelect('orgjednotka_id', 'Organizační jednotka:', $select);
    }
    
    // Přidá uživatelský účet existující osobě
    protected function handleNewUser($data)
    {
        if (!isset($data['osoba_id'])) {
            $this->presenter->redirect('this');
        }
        
        $this->vytvoritUcet($data['osoba_id'], $data);

        $this->presenter->redirect('this', array('id' => $data['osoba_id']));
    }

    // nedelej nic
    protected function handleSync($data)
    {
        $this->presenter->redirect('this');
    }
    
    // Pouzito pro SSO a LDAP
    protected function handleSync2($data)
    {
        unset($data['synchonizovat']);
        
        if ( count($data)>0 ) {
            $Osoba = new Osoba();
            $User = new UserModel();
            $user_add = 0;
            foreach ( $data as $user ) {
                if ( isset($user['add']) && $user['add'] == true ) {

                    $osoba = array(
                        'jmeno' => $user['jmeno'],
                        'prijmeni' => $user['prijmeni'],
                        'email' => $user['email']
                    );

                    $user_data = array(
                        'username' => $user['username'],
                        'heslo' => $user['email'],
                        'local' => 1,
                        'role' => $user['role']
                    );
                    
                    $success = $this->vytvoritUcet($osoba, $user_data);

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
    
    // pouze SSO a LDAP
    protected function handleSyncManual()
    {
        $data = Environment::getHttpRequest()->getPost();
        
        if ( isset($data['usersynch_pripojit']) && count($data['usersynch_pripojit'])>0 ) {
            $Osoba = new Osoba();
            $User = new UserModel();
            $user_add = 0;
            foreach ( $data['usersynch_pripojit'] as $index => $status )
                if ( $status == "on" ) {
                
                    $username = $data['usersynch_username'][$index];
                    
                    $osoba = array(
                        'jmeno' => $data['usersynch_jmeno'][$index],
                        'prijmeni' => $data['usersynch_prijmeni'][$index],
                        'email' => $data['usersynch_email'][$index],
                    );
                    if (isset($data['usersynch_titul_pred'][$index]))
                        $osoba['titul_pred'] = $data['usersynch_titul_pred'][$index];
                    if (isset($data['usersynch_telefon'][$index]))
                        $osoba['telefon'] = $data['usersynch_telefon'][$index];
                    if (isset($data['usersynch_funkce'][$index]))
                        $osoba['pozice'] = $data['usersynch_funkce'][$index];                        
                    
                    $user_data = array(
                        'username' => $username,
                        'heslo' => $data['usersynch_email'][$index],
                        'local' => 1,
                        'role' => $data['usersynch_role'][$index]
                    );
                    
                    $success = $this->vytvoritUcet($osoba, $user_data);

                    $user_add++;
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
    
    // Vytvoří uživatelský účet a případně i účet osoby
    // $osoba_data - je buď id osoby nebo pole
    // vrátí boolean - úspěch operace
    public function vytvoritUcet($osoba_data, $user_data, $silent = false)
    {
        $osoba_vytvorena = false;
        
        try {
            if (is_array($osoba_data)) {
                $osoba_model = new Osoba;
                $osoba_id = $osoba_model->ulozit($osoba_data);
                $osoba_vytvorena = true;
            }
            else
                $osoba_id = $osoba_data;
                
            UserModel::pridatUcet($osoba_id, $user_data);            

            if (!$silent)
                $this->presenter->flashMessage('Účet uživatele "'. $user_data['username'] .'" byl úspěšně vytvořen.');
            
            return true;
        }
        catch (Exception $e) {
            if (!$silent)
                if ( $e->getCode() == 1062 ) {
                    $this->presenter->flashMessage("Uživatelský účet s názvem \"{$user_data['username']}\" již existuje. Zvolte jiný název.", 'warning');
                } else {
                    $this->presenter->flashMessage('Účet uživatele se nepodařilo vytvořit.', 'warning');
                    $this->presenter->flashMessage('Chyba: '. $e->getMessage(), 'warning');
                }
            
            if ($osoba_vytvorena) {
                // pokud byl uživatel vytvořen "napůl", odstraň záznam zaměstnance
                $osoba_model->delete("[id] = $osoba_id");
            }

            return false;
        }
    }

}

