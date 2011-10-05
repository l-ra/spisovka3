<?php

class Orgjednotka extends TreeModel
{

    protected $name = 'orgjednotka';
    protected $nazev = 'zkraceny_nazev';
    protected $nazev_sekvence = 'ciselna_rada';
    protected $primary = 'id';
    
    
    public function getInfo($orgjednotka_id)
    {

        $result = $this->fetchRow(array('id=%i',$orgjednotka_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null, $no_result = 0)
    {

        $params = null;
        if ( !is_null($args) ) {
            $params['where'] = $args;
        }
        if ( $no_result == 1 ) {
            $params['paginator'] = 1;
        }

        $params['order'] = array('zkraceny_nazev');
        return $this->nacti(null, true, true, $params);

    }

    public function seznamRoli($orgjednotka_id) {

        $RoleModel = new RoleModel();
        // dostupne zakladni role
        $role_data = $RoleModel->seznam(0,array('where'=>array('r.fixed=1')));
        // registrovane role teto organizacni jednotky
        $role_org_data = $RoleModel->seznam(0,array(
                                            'where'=>array(
                                                array('r.orgjednotka_id=%i',$orgjednotka_id)
                                                )
                                            )
                                         );

        $role = array();
        foreach( $role_data as $r ) {
            $role[ $r->id ] = $r;
        }
        $role_org = array();
        foreach( $role_org_data as $ro ) {
            $role_org[ $ro->id ] = $ro;
        }

        return array('role'=>$role, 'role_org'=>$role_org);

    }

    public function ulozit($data, $orgjednotka_id = null)
    {

        if ( !empty($orgjednotka_id) ) {
            // aktualizovat
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = (int) Environment::getUser()->getIdentity()->id;
            
            if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
            if ( empty($data['parent_id']) ) $data['parent_id'] = null;
            if ( !isset($data['parent_id_old']) ) $data['parent_id_old'] = null;
            if ( empty($data['parent_id_old']) ) $data['parent_id_old'] = null;
            if ( !empty($data['stav']) ) $data['stav'] = (int) $data['stav'];            
            
            $orgjednotka_id = $this->upravitH($data, $orgjednotka_id);
            //$this->update($data, array(array('id = %i',$orgjednotka_id)));
        } else {
            // insert
            $data['date_created'] = new DateTime();
            $data['user_created'] = (int) Environment::getUser()->getIdentity()->id;
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = (int) Environment::getUser()->getIdentity()->id;
            $data['stav'] = (int) 1;
            
            if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
            if ( empty($data['parent_id']) ) $data['parent_id'] = null;
            
            //$orgjednotka_id = $this->insert($data);
            $orgjednotka_id = $this->vlozitH($data);
        }

        if ( $orgjednotka_id ) {
            return $orgjednotka_id;
        } else {
            return false;
        }
    }

    public function pridatOrganizacniStrukturu($orgjednotka_id, $parent_role_id) {

        // vytvoreni role ze zakladu
        $RoleModel = new RoleModel();

        $role_fixed = $RoleModel->getInfo($parent_role_id);
        $orgjednotka = $this->getInfo($orgjednotka_id);

        //dibi::begin('org_struct');

        $row = array();
        $row['parent_id'] = $parent_role_id;
        $row['code'] = $role_fixed->code ."_". $orgjednotka_id;
        $row['name'] = $role_fixed->name ." ". $orgjednotka->ciselna_rada;
        $row['active'] = 1;
        $row['date_created'] = new DateTime();
        $row['orgjednotka_id'] = $orgjednotka_id;
        $row['fixed'] = 0;
        $row['order'] = $role_fixed->order;

        //Debug::dump($row);
        //exit;

        $role_id = $RoleModel->vlozit($row);

        //echo dibi::$sql;

        //Debug::dump($role_id);

        if ( $role_id ) {
            // pridani pravidla pro tuto konkretni org. jednotku
            $AclModel = new AclModel();
            $pravidlo = $AclModel->hledatPravidlo(array('privilege'=>"orgjednotka_". $orgjednotka_id));

            if ( !(count($pravidlo) > 0) ) {
                // Pravidlo
                $row1 = array();
                $row1['name'] = "Oprávnění pro org. jednotku ". $orgjednotka->zkraceny_nazev;
                $row1['note'] = "Oprávnění platné pouze pro organizační jednotku ". $orgjednotka->zkraceny_nazev;
                $row1['privilege'] = "orgjednotka_". $orgjednotka_id;
                $rule_id = $AclModel->insertRule($row1);
                //echo dibi::$sql;
            } else {
                $rule_id = $pravidlo[0]->id;
            }


            // Aplikace pravidla na roli
            $row2 = array();
            $row2['role_id'] = (int) $role_id;
            $row2['rule_id'] = (int) $rule_id;
            $row2['allowed'] = 'Y';

            //Debug::dump($row2);

            $AclModel->insert($row2);


            //echo dibi::$sql;

            //dibi::commit('org_struct');
            
        } else {
            //dibi::rollback('org_struct');
            return false;
        }

    }

    public function odebratOrganizacniStrukturu($orgjednotka_id, $role_id) {

        $AclModel = new AclModel();
        $UserModel = new User2Role();
        $RoleModel = new RoleModel();

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();
        
        // odebrani roli uzivatelum
        $UserModel->delete(array('role_id=%i',$role_id));
        // obebrani pravidel z role
        $AclModel->delete(array('role_id=%i',$role_id));
        // obebrani pravidel
        //$AclModel->deleteRule(array('privilege=%s',"orgjednotka_". $orgjednotka_id));
        // odebrani role
        $RoleModel->delete(array('id=%i',$role_id));
        
        //if ($transaction)
        //dibi::commit();

        return true;
        
    }

    public static function isInOrg($orgjednotka_id, $role = null, $user_id = null) {
        $is = false;

        if ( empty($orgjednotka_id) ) return false;
        
        if ( !is_null($user_id) ) {
            $UserModel = new UserModel();
            $user = $UserModel->getUser($user_id, true);
        } else {
            $user = Environment::getUser()->getIdentity();
        }
        
        if ( count( $user->user_roles )>0 ) {
            foreach ( $user->user_roles as $r ) {
                if ( $r->orgjednotka_id == $orgjednotka_id ) {
                    if ( is_null($role) ) {
                        $is = true;
                    } else {
                        if (strpos($r->code, $role) !== false ) {
                            $is = true;
                        }
                    }
                }
            }
        }
        return $is;
    }

    public static function childOrg($orgjednotka_id) {

        if ( empty($orgjednotka_id) ) return null;

        $org = array();
        $org[] = $orgjednotka_id;

        $OrgJednotka = new Orgjednotka();
        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
        if ( $org_info ) {
            $fetch = $OrgJednotka->fetchAll(array('sekvence'),
                                array( array('sekvence LIKE %s', $org_info->sekvence .'.%')  )
                            );
            $result = $fetch->fetchAll();
            if ( count($result)>0 ) {
                foreach ( $result as $res ) {
                    $org[] = $res->id;
                }
                
            }
        }

        return $org;
    }

    public function  deleteAllOrg() {

        $Workflow = new Workflow();
        $Workflow->update(array('orgjednotka_id'=>null),array('orgjednotka_id IS NOT NULL'));

        $CJ = new CisloJednaci();
        $CJ->update(array('orgjednotka_id'=>null),array('orgjednotka_id IS NOT NULL'));


        $UserModel = new User2Role();
        $UserModel->delete(array('role_id > 6'));

        $AclModel = new AclModel();
        $AclModel->delete(array('role_id > 6'));
        $AclModel->deleteRule(array("privilege LIKE 'orgjednotka_%'"));

        $RoleModel = new RoleModel();
        $RoleModel->delete(array('fixed=0'));
        
        parent::deleteAll();
    }
}
