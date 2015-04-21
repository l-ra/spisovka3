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

    public static function getUser($mixed,$identity = FALSE)
    {
        $instance = new self;

        /*
         * Ziskání uzivatele
         */
        if ( is_numeric($mixed) ) {
            // $mixed is numeric -> ID
            $row = $instance->select(array(array('id=%i',$mixed)))->fetch();
        } else {
            // $mixed is string -> username
            $row = $instance->select(array(array('username=%s',$mixed)))->fetch();
        }

        if ( is_null($row) || !$row)
            return null;

        /*
         * Nacteni identity uzivatele
         */
        if ( $identity == TRUE ) {
            $row->identity = self::getIdentity($row->id);
            $row->display_name = Osoba::displayName($row->identity);
            
            $row->org_nazev = $row->orgjednotka_id !== null 
                ? Orgjednotka::getName($row->orgjednotka_id) : '';
        } else {
            $row->display_name = $row->username;
        }

        /*
         * Nacteni roli uzivatele
         */
        $row->user_roles = self::getRoles($row->id);

        return $row;
    }


    /**
     * Zjisti osobni informace o uzivateli na základe ID
     *
     * @param int|string $mixed
     * @return DibiRow
     */

    public static function getIdentity($user_id)
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

    public function insert($data)
    {       
        $rown = array('username'=>$data['username'],
                      'password'=>sha1($data['username'] . $data['heslo']),
                      'date_created'=> new DateTime(),
                      'local' => (isset($data['local']) ? $data['local'] : 0),
                      'orgjednotka_id' => isset($data['orgjednotka_id']) && !empty($data['orgjednotka_id']) ? $data['orgjednotka_id'] : NULL,
                      'active'=>1
                );

        return parent::insert($rown);
    }

    public static function pridatUcet($osoba_id, $data) {

        $insert_data = array(
            'username' => $data['username'],
            'heslo' => $data['heslo'],
        );
        if (isset($data['orgjednotka_id']))
            $insert_data['orgjednotka_id'] = $data['orgjednotka_id'];
        if (isset($data['local']))
            $insert_data['local'] = $data['local'];
        
        $role_id = $data['role'];

        if (empty($osoba_id))
            throw new Exception('UserModel::pridatUcet() - neplatné ID osoby');

        try {
            dibi::begin();
            
            $UserModel = new UserModel();
            $user_id = $UserModel->insert($insert_data);
            
            $Osoba2User = new Osoba2User();
            $rowou = array( 'osoba_id'=>$osoba_id, 
                            'user_id'=>$user_id,
                            'date_added'=>new DateTime()
                    );
            $Osoba2User->insert_basic($rowou);

            if (!empty($role_id)) {
                // přiřad účtu roli, jen pokud byla zadána
                $User2Role = new User2Role();
                $rowur = array( 'role_id'=>$role_id,
                                'user_id'=>$user_id,
                                'date_added'=>new DateTime()
                        );
                $User2Role->insert_basic($rowur);
            }
            
            dibi::commit();
            
            return true;
        }
        catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function odebratUcet($osoba_id, $user_id) {

        if ($user_id == Nette\Environment::getUser()->id) {
            throw new Exception('Nemůžete smazat účet, pod kterým jste přihlášen!');
        }

        dibi::begin();
        try {
            $Osoba2User = new Osoba2User();
            $Osoba2User->update(array('active'=>0),
                                array(
                                   array('user_id=%i',$user_id),
                                   array('osoba_id=%i',$osoba_id)
                                ));

            $this->update(array(
                            'active'=>0,
                            'username%sql'=>"CONCAT(username,'_',".time().")"
                          ),
                          array('id=%i',$user_id)
                    );

            dibi::commit();
        }
        catch(Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function changePassword($user_id, $password) {

        $user = $this->select([['id=%i', $user_id]])->fetch();

        // zabran, aby uzivatel mohl u dema menit heslo k urcitym uctum
        if (Demo::isDemo() && !Demo::canChangePassword($user))
            return false;
            
        $row = array();
        $row['last_modified'] = new DateTime();

        // pokud je heslo prazdne = zadna zmena, jinak zmena
        if ( !empty($password) ) {
            $row['password'] = sha1($user->username . $password);
        }

        $this->update($row, array('id=%i', $user_id));
        return true;
    }

    public function changeAuthType($user_id, $auth_type) {
        
        $change = ['local' => $auth_type];
        $change['last_modified'] = new DateTime();
        $this->update($change, array('id=%i', $user_id));        
    }
    
    public function zalogovan($user_id) {

        $row = array('last_login' => new DateTime(),
                     'last_ip' => Nette\Environment::getHttpRequest()->getRemoteAddress()
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
    protected $autoIncrement = false;
}

