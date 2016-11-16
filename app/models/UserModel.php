<?php

class UserModel extends BaseModel
{

    protected $name = 'user';

    /**
     * Zjisti informace o uzivateli na základe username.
     * Použito pouze pro přihlášení.
     *
     * @param string $username 
     * @return UserAccount
     */
    public static function searchUser($username)
    {
        /*
         * Ziskání uzivatele
         */
        $where = [['username = %s', $username]];
        $instance = new self;
        $row = $instance->select($where)->fetch();
        if (!$row)
            return null;

        return new UserAccount($row->id);
    }

    /**
     * @param int     $osoba_id
     * @param array   $data
     * @param boolean $use_transaction
     * @return boolean
     * @throws Exception
     */
    public static function pridatUcet($osoba_id, $data, $use_transaction)
    {
        $insert_data = array(
            'username' => $data['username'],
            // heslo chybí při externí autentizaci
            'heslo' => isset($data['heslo']) ? $data['heslo'] : null,
        );
        if (isset($data['orgjednotka_id']))
            $insert_data['orgjednotka_id'] = $data['orgjednotka_id'];
        if (isset($data['external_auth']))
            $insert_data['external_auth'] = $data['external_auth'];

        $role_id = $data['role'];

        if (empty($osoba_id))
            throw new Exception('UserModel::pridatUcet() - neplatné ID osoby');

        try {
            if ($use_transaction)
                dibi::begin();

            $rown = array('username' => $insert_data['username'],
                'password' => isset($insert_data['heslo']) ? sha1($insert_data['username'] . $insert_data['heslo'])
                    : null,
                'date_created' => new DateTime(),
                'external_auth' => (isset($insert_data['external_auth']) ? $insert_data['external_auth']
                    : 0),
                'orgjednotka_id' => isset($insert_data['orgjednotka_id']) && !empty($insert_data['orgjednotka_id'])
                    ? $insert_data['orgjednotka_id'] : NULL,
                'osoba_id' => $osoba_id,
                'active' => 1
            );
            $UserModel = new self;
            $user_id = $UserModel->insert($rown);

            if (!empty($role_id)) {
                // přiřad účtu roli, jen pokud byla zadána
                $User2Role = new User2Role();
                $rowur = array('role_id' => $role_id,
                    'user_id' => $user_id,
                    'date_added' => new DateTime()
                );
                $User2Role->insert($rowur);
            }

            if ($use_transaction)
                dibi::commit();

            return true;
        } catch (Exception $e) {
            if ($use_transaction)
                dibi::rollback();
            throw $e;
        }
    }

    public function skrytUcet($user_id)
    {
        if ($user_id == self::getUser()->id) {
            throw new Exception('Nemůžete smazat účet, pod kterým jste přihlášen!');
        }

        dibi::begin();
        try {
            $account = new UserAccount($user_id);
            $account->active = 0;
            $account->username = $account->username . "_" . time();
            $account->save();

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    /* Nebezpecne funkci nechat v programu, prestoze se nikde nevola
      public function deleteAll()
      {
      $Workflow = new Workflow();
      $Workflow->update(
      array('user_id' => null, 'prideleno_id' => null), array('1')
      );

      $CJ = new CisloJednaci();
      $CJ->update(array('user_id' => null), array('user_id IS NOT NULL'));

      $Dokument = new Dokument();
      $Dokument->update(
      array('user_created' => null, 'user_modified' => null), array('1')
      );
      $DokumentH = new DokumentHistorie();
      $DokumentH->update(
      array('user_created' => null, 'user_modified' => null), array('1')
      );

      $User2Role = new User2Role();
      $User2Role->deleteAll();

      parent::deleteAll();
      }
     */
}

class User2Role extends BaseModel
{

    protected $name = 'user_to_role';
    protected $autoIncrement = false;

}
