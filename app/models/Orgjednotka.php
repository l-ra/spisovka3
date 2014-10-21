<?php

class Orgjednotka extends TreeModel
{

    protected $name = 'orgjednotka';
    protected $nazev = 'zkraceny_nazev';
    protected $nazev_sekvence = 'zkraceny_nazev';
    protected $primary = 'id';
    
    
    public function getInfo($orgjednotka_id)
    {       
        $result = $this->select(array(array('id=%i',$orgjednotka_id)));
        if (count($result) == 0)   
            throw new InvalidArgumentException("Organizační jednotka id '$orgjednotka_id' neexistuje.");
            
        return $result->fetch();
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

        return $this->nacti(null, true, true, $params);
    }

    public function linearniSeznam()
    {
        $result = $this->nacti(null, true, true, array(
                    'order' => 'ciselna_rada',
                    'where' => array('stav != 0')
        ));
        return $result !== NULL ? $result : array();
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
            
            $this->upravitH($data, $orgjednotka_id);
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

    public function  deleteAllOrg() {

        $Workflow = new Workflow();
        $Workflow->update(array('orgjednotka_id'=>null),array('orgjednotka_id IS NOT NULL'));

        $CJ = new CisloJednaci();
        $CJ->update(array('orgjednotka_id'=>null),array('orgjednotka_id IS NOT NULL'));
        
        parent::deleteAll();
    }
    
    // Tato funkce je pouzita pro kontrolu pristupu k dokumentum a spisum
    // Testuje, zda ma uzivatel pravo k urcene org. jednotce
    // TODO: Metoda potrebuje prejmenovat
    // TODO: Testovat na opravneni vedouciho
    public static function isInOrg($orgjednotka_id) {

        if ( empty($orgjednotka_id) )
            return false;
        
        // docasne omezeni, ze uzivatel muze byt jen v jedne o.j.
        $oj_uzivatele = self::dejOrgUzivatele();
        if ($oj_uzivatele === false)
            return false;
            
        return ($oj_uzivatele === $orgjednotka_id) 
               && Environment::getUser()->isAllowed('Dokument', 'cist_moje_oj');
    }

    public static function childOrg($orgjednotka_id) {

        if ( empty($orgjednotka_id) ) return null;

        $org = array();
        $org[] = $orgjednotka_id;

        $OrgJednotka = new Orgjednotka();
        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
        if ( $org_info ) {
            $fetch = $OrgJednotka->select(array( array('sekvence LIKE %s', $org_info->sekvence .'.%'), array('sekvence')));
            $result = $fetch->fetchAll();
            if ( count($result)>0 ) {
                foreach ( $result as $res ) {
                    $org[] = $res->id;
                }
                
            }
        }

        return $org;
    }

    // Vrátí id org. jednotky aktuálního/zvoleného uživatele
    // nebo null, neni-li uzivatel zarazen do zadne jednotky nebo kdyz nema identitu
    public static function dejOrgUzivatele($user_id = null) {
    
        if ($user_id === null) {
            $identity = Environment::getUser()->getIdentity();
            if ($identity === null)
                // nepřihlášený uživatel
                return null;

            return $identity->orgjednotka_id;
        }

        $user = UserModel::getUser($user_id);
        return $user === null ? null : $user->orgjednotka_id;
    }

    public static function getName($id) {
        
        $o = new self;
        $oj = $o->getInfo($id);
        return $oj->zkraceny_nazev;
    }

}
