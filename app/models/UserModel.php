<?php

class UserModel extends BaseModel
{

    protected $name = 'user';
    protected $primary = 'id';

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
     * Zjisti osobni informace o uzivateli na základe ID
     *
     * @param int $user_id
     * @return DibiRow
     */
    public static function getPerson($user_id)
    {
        /*        static $cache = [];

          if (isset($cache[$user_id]))
          return $cache[$user_id];

          $row = dibi::fetch('SELECT o.*
          FROM [:PREFIX:' . self::OSOBA2USER_TABLE . '] ou
          LEFT JOIN [:PREFIX:' . self::OSOBA_TABLE . '] o ON (o.id = ou.osoba_id)
          WHERE ou.user_id = %i AND o.stav < 10', $user_id);

          return $cache[$user_id] = $row ? $row : NULL; */

        // docasne reseni, nez se tato tabulka zrusi
        $res = dibi::query("SELECT [osoba_id] FROM [:PREFIX:" . self::OSOBA2USER_TABLE . "] WHERE [user_id] = %i",
                        $user_id);
        $osoba_id = $res->fetchSingle();

        return new Person($osoba_id);
    }

    // TODO: prepsat
    public static function pridatUcet($osoba_id, $data)
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
            dibi::begin();

            $rown = array('username' => $insert_data['username'],
                'password' => isset($insert_data['heslo']) ? sha1($insert_data['username'] . $insert_data['heslo'])
                            : null,
                'date_created' => new DateTime(),
                'external_auth' => (isset($insert_data['external_auth']) ? $insert_data['external_auth']
                            : 0),
                'orgjednotka_id' => isset($insert_data['orgjednotka_id']) && !empty($insert_data['orgjednotka_id'])
                            ? $insert_data['orgjednotka_id'] : NULL,
                'active' => 1
            );
            $UserModel = new self;
            $user_id = $UserModel->insert($rown);

            $Osoba2User = new Osoba2User();
            $rowou = array('osoba_id' => $osoba_id,
                'user_id' => $user_id,
                'date_added' => new DateTime()
            );
            $Osoba2User->insert_basic($rowou);

            if (!empty($role_id)) {
                // přiřad účtu roli, jen pokud byla zadána
                $User2Role = new User2Role();
                $rowur = array('role_id' => $role_id,
                    'user_id' => $user_id,
                    'date_added' => new DateTime()
                );
                $User2Role->insert_basic($rowur);
            }

            dibi::commit();

            return true;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function odebratUcet($osoba_id, $user_id)
    {

        if ($user_id == Nette\Environment::getUser()->id) {
            throw new Exception('Nemůžete smazat účet, pod kterým jste přihlášen!');
        }

        dibi::begin();
        try {
            $Osoba2User = new Osoba2User();
            $Osoba2User->update(array('active' => 0),
                    array(
                array('user_id=%i', $user_id),
                array('osoba_id=%i', $osoba_id)
            ));

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
