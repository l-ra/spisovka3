<?php

class UserModel extends BaseModel
{

    protected $name = 'user';
    protected $primary = 'id';

    /**
     * Zjisti informace o uzivateli na základe ID nebo username
     *
     * @param int|string $mixed 
     * @return DibiRow
     */

    public function getUser($mixed,$identity = FALSE)
    {

        /*
         * Ziskání uzivatele
         */
        if ( is_numeric($mixed) ) {
            // $mixed is numeric -> ID
            $row = $this->fetchRow(array('id=%i',$mixed))->fetch();
        } else {
            // $mixed is string -> username
            $row = $this->fetchRow(array('username=%s',$mixed))->fetch();
        }

        if ( is_null($row) ) return null;
        if ( !$row ) return null;

        /*
         * Nacteni identity uzivatele
         */
        if ( $identity == TRUE ) {
            $row->identity = $this->getIdentity($row->id);
            $row->display_name = Osoba::displayName($row->identity);
        } else {
            $row->display_name = $row->username;
        }

        /*
         * Nacteni roli uzivatele
         */
        $user = new UserModel();
        $row->user_roles = self::getRoles($row->id);

        return ($row) ? $row : NULL;
    }


    /**
     * Zjisti osobni informace o uzivateli na základe ID
     *
     * @param int|string $mixed
     * @return DibiRow
     */

    public function getIdentity($user_id)
    {

        $row = dibi::fetch('SELECT o.*
                            FROM [:PREFIX:'. self::OSOBA2USER_TABLE . '] ou
                            LEFT JOIN [:PREFIX:'. self::OSOBA_TABLE .'] o ON (o.id = ou.osoba_id)
                            WHERE ou.user_id=%i AND o.stav<10',$user_id);

        return ($row) ? $row : NULL;

    }

    public static function getRoles($user_id)
    {

        $rows = dibi::fetchAll('SELECT r.*
                                 FROM [:PREFIX:'. self::USER2ROLE_TABLE . '] ur
                                 LEFT JOIN [:PREFIX:'. self::ROLE_TABLE .'] r ON (r.id = ur.role_id)
                                 WHERE ur.user_id=%i',$user_id);

        return ($rows) ? $rows : NULL;

    }

    public function insert(array $data)
    {
        
        $rown = array('username'=>$data['username'],
                      'password'=>sha1($data['username'] . $data['heslo']),
                      'date_created'=> new DateTime(),
                      'local' => (isset($data['local'])?$data['local']:0),
                      'active'=>1
                );

        return parent::insert($rown);

    }

    public function pridatUcet($user_id, $osoba_id, $role_id) {

        if ( !empty($user_id) && !empty($osoba_id) && !empty($role_id) ) {
            
            $Osoba2User = new Osoba2User();
            $rowou = array( 'osoba_id'=>$osoba_id, 
                            'user_id'=>$user_id,
                            'date_added'=>new DateTime()
                    );
            $Osoba2User->insert_basic($rowou);

            $User2Role = new User2Role();
            $rowur = array( 'role_id'=>$role_id,
                            'user_id'=>$user_id,
                            'date_added'=>new DateTime()
                    );
            $User2Role->insert_basic($rowur);


            return true;
        } else {
            return null;
        }

    }

    public function odebratUcet($osoba_id, $user_id) {

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        $Osoba2User = new Osoba2User();
        $Osoba2User->update(array('active'=>0),
                            array(
                               array('user_id=%i',$user_id),
                               array('osoba_id=%i',$osoba_id)
                            ));

        /*$Osoba2User->delete(array(
                               array('user_id=%i',$user_id),
                               array('osoba_id=%i',$osoba_id)
                            ));
        $User2Role = new User2Role();
        $User2Role->delete(array('user_id=%i',$user_id));
        */

        $ret = $this->update(array(
                        'active'=>0,
                        'username%sql'=>"CONCAT(username,'_',".time().")"
                      ),
                      array('id=%i',$user_id)
                );

        //$ret = $this->delete(array('user_id=%i',$user_id));

        //if ($transaction)
        //dibi::commit();

        return $ret;
    }

    public function zmenitHeslo($user_id, $password, $local = null) {

        $user = $this->fetchRow(array('id=%i',$user_id))->fetch();

        // zabran, aby uzivatel mohl u dema menit heslo k urcitym uctum
        if (Demo::isDemo() && !Demo::canChangePassword($user))
            return false;
            
        $row = array();
        $row['last_modified'] = new DateTime();

        // pokud je heslo prazdne = zadna zmena, jinak zmena
        if ( !empty($password) ) {
            $row['password'] = sha1($user->username . $password);
        }

        if ( !is_null($local) ) {
            $row['local'] = $local;
        }

        //Debug::dump($row); exit;

        return $this->update($row,array('id=%i',$user_id));

    }

    public function zalogovan($user_id) {

        $row = array('last_login' => new DateTime(),
                     'last_ip' => Environment::getHttpRequest()->getRemoteAddress()
                );
        return $this->update($row,array('id=%i',$user_id));

    }

    public function  deleteAll() {

        $Workflow = new Workflow();
        $Workflow->update(
                array('user_id'=>null,'prideleno_id'=>null),
                array('1')
        );

        $CJ = new CisloJednaci();
        $CJ->update(array('user_id'=>null),array('user_id IS NOT NULL'));

        $Dokument = new Dokument();
        $Dokument->update(
                array('user_created'=>null,'user_modified'=>null),
                array('1')
        );
        $DokumentH = new DokumentHistorie();
        $DokumentH->update(
                array('user_created'=>null,'user_modified'=>null),
                array('1')
        );


        $User2Role = new User2Role();
        $User2Role->deleteAll();


        parent::deleteAll();
    }
}

class User2Role extends BaseModel
{
    protected $name = 'user_to_role';
}

