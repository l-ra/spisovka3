<?php

class Authenticator_Base extends Control
{

    public function formAddRoleSelect(AppForm $form)
    {
        $Role = new RoleModel();
        $role_seznam = $Role->seznam();
        $role_select = array();
        foreach ($role_seznam as $key => $value) {
            $role_select[ $value->id ] = $value->name;
        }        

        $form->addSelect('role', 'Role:', $role_select);    
    }

    public function formAddOrgSelect(AppForm $form)
    {
        $m = new Orgjednotka;
        $seznam = $m->linearniSeznam();
        $select = array(0 => 'žádná');
        foreach ($seznam as $org)
            $select[$org->id] = $org->ciselna_rada . ' - ' . $org->zkraceny_nazev;

        $form->addSelect('orgjednotka_id', 'Organizační jednotka:', $select);
    }
    
    public function handleNewUser($data)
    {
        if ( isset($data['osoba_id']) ) {

            $User = new UserModel();

            $user_data = array(
                'username' => $data['username'],
                'heslo' => $data['heslo'],
                'orgjednotka_id' => $data['orgjednotka_id']
            );
            if (isset($data['local']))
                $user_data['local'] = $data['local'];

            try {
                $user_id = $User->insert($user_data);
                $User->pridatUcet($user_id, $data['osoba_id'], $data['role']);

                $this->presenter->flashMessage('Účet uživatele "'. $data['username'] .'" byl úspěšně vytvořen.');
                $this->presenter->redirect('this',array('id'=>$data['osoba_id']));
            }
            catch (DibiException $e) {
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
    
}

