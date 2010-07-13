<?php

class Orgjednotka extends BaseModel
{

    protected $name = 'orgjednotka';
    protected $primary = 'orgjednotka_id';
    
    
    public function getInfo($orgjednotka_id)
    {

        $result = $this->fetchRow(array('orgjednotka_id=%i',$orgjednotka_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null)
    {
        $args = func_get_args();

        $select = $this->fetchAll(array('zkraceny_nazev'));

        return $select;

        //$rows = $select->fetchAll();
        //return ($rows) ? $rows : NULL;

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
            $role[ $r->role_id ] = $r;
        }
        $role_org = array();
        foreach( $role_org_data as $ro ) {
            $role_org[ $ro->role_id ] = $ro;
        }

        return array('role'=>$role, 'role_org'=>$role_org);

    }

    public function pridatOrganizacniStrukturu($orgjednotka_id, $parent_role_id) {

        // vytvoreni role ze zakladu
        $RoleModel = new RoleModel();

        $role_parent = $RoleModel->getInfo($parent_role_id);
        $orgjednotka = $this->getInfo($orgjednotka_id);
        
        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        $row = array();
        $row['parent_id'] = $parent_role_id;
        $row['code'] = $role_parent->code ."_". $orgjednotka_id;
        $row['name'] = $role_parent->name ." ". $orgjednotka->ciselna_rada;
        $row['active'] = 1;
        $row['date_created'] = new DateTime();
        $row['orgjednotka_id'] = $orgjednotka_id;
        $row['fixed'] = 0;
        $row['order'] = $role_parent->order;
        $role_id = $RoleModel->insert($row);

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
            } else {
                $rule_id = $pravidlo[0]->rule_id;
            }


            // Aplikace pravidla na roli
            $row2 = array();
            $row2['role_id'] = $role_id;
            $row2['rule_id'] = $rule_id;
            $row2['allowed'] = 'Y';
            $AclModel->insert($row2);
                
            //if ($transaction)
            //dibi::commit();
            
        } else {
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
        $RoleModel->delete(array('role_id=%i',$role_id));
        
        //if ($transaction)
        //dibi::commit();

        return true;
        
    }


}
