<?php

class Spisovka_UzivatelPresenter extends BasePresenter {

    public $backlink = '';

    public function actionLogin($backlink)
    {

        $authenticator = (array) Environment::getConfig('service');
        $authenticator = $authenticator['Nette-Security-IAuthenticator'];
        $Auth = new $authenticator();
        $Auth->setAction('login');
        $this->addComponent($Auth, 'auth');

        $this->backlink = $backlink;
        $this->template->title = "Přihlásit se";
    }


    public function renderLogout()
    {
        Environment::getUser()->signOut();
        $this->flashMessage('Byl jste úspěšně odhlášen.');
        $this->redirect(':Default:default');
    }

    public function actionDefault()
    {

        $authenticator = (array) Environment::getConfig('service');
        $authenticator = $authenticator['Nette-Security-IAuthenticator'];

        $Osoba = new Osoba();
        $User = new UserModel();

        $user = Environment::getUser()->getIdentity();
        
        //Debug::dump($user);


        $osoba_id = $user->identity->id;
        $this->template->Osoba = $Osoba->getInfo($osoba_id);

        // Zmena osobnich udaju
        $this->template->FormUpravit = $this->getParam('upravit',null);

        $uzivatel = $User->getUser($user->id);
        $this->template->Uzivatel = $uzivatel;

        // Zmena hesla
        $this->template->ZmenaHesla = $this->getParam('zmenitheslo',null);
        Environment::setVariable('auth_params_change', array('osoba_id'=>$osoba_id,'user_id'=>$user->id));
        $Auth1 = new $authenticator();
        $Auth1->setAction('change_password');
        $this->addComponent($Auth1, 'auth_change_password');


        $role = $User->getRoles($uzivatel->id);
        $this->template->Role = $role;

    }

/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    protected function createComponentUpravitForm()
    {

        $osoba = $this->template->Osoba;

        $form1 = new AppForm();
        $form1->addHidden('osoba_id')
                ->setValue(@$osoba->id);
        $form1->addText('jmeno', 'Jméno:', 50, 150)
                ->setValue(@$osoba->jmeno);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->setValue(@$osoba->prijmeni);
        $form1->addText('titul_pred', 'Titul před:', 50, 150)
                ->setValue(@$osoba->titul_pred);
        $form1->addText('titul_za', 'Titul za:', 50, 150)
                ->setValue(@$osoba->titul_za);
        $form1->addText('email', 'Email:', 50, 150)
                ->setValue(@$osoba->email);
        $form1->addText('telefon', 'Telefon:', 50, 150)
                ->setValue(@$osoba->telefon);
        $form1->addText('pozice', 'Funkce:', 50, 150)
                ->setValue(@$osoba->pozice);
        $form1->addSubmit('upravit', 'Upravit')
                 ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function upravitClicked(SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();
        $osoba_id = $data['osoba_id'];
        unset($data['osoba_id']);

        try {
            $osoba_id = $Osoba->ulozit($data, $osoba_id);
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl upraven.');
        } catch (DibiException $e) {
            $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  se nepodařilo upravit.','warning');
        }
        $this->redirect('this');
    }

    public function stornoClicked(SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $this->redirect('this');
    }

    public function renderVyber()
    {

        $this->template->uzivatel_id = $this->getParam('id',null);
        $this->template->dokument_id = $this->getParam('dok_id',null);
        $this->template->novy = $this->getParam('novy',0);

        $Zamestnanci = new Osoba2User();
        $seznam = $Zamestnanci->seznam(1);
        $this->template->seznam = $seznam;

        $OrgJednotky = new Orgjednotka();
        $oseznam = $OrgJednotky->seznam();
        $this->template->org_seznam = $oseznam;


    }

    public function actionSeznamAjax()
    {
        
        $Zamestnanci = new Osoba2User();
        $OrgJednotky = new Orgjednotka();

        $seznam = array();

        $term = $this->getParam('term');

        if ( !empty($term) ) {
            $seznam_zamestnancu = $Zamestnanci->hledat($term);
            $seznam_orgjednotek = $OrgJednotky->nacti(null, true, true, 
                    array('where'=>array( array('LOWER(tb.ciselna_rada) LIKE LOWER(%s)','%'.$term.'%',' OR LOWER(tb.zkraceny_nazev) LIKE LOWER(%s)','%'.$term.'%') )));
        } else {
            $seznam_zamestnancu = $Zamestnanci->seznam(1);
            $seznam_orgjednotek = $OrgJednotky->nacti();
        }

        if ( count($seznam_orgjednotek)>0 ) {
            //$seznam[ ] = array('id'=>'o',"type" => 'part','name'=>'Předat organizační jednotce');
            foreach( $seznam_orgjednotek as $org ) {
                $seznam[ ] = array(
                    "id"=> 'o'. $org->id,
                    "type" => 'item',
                    "value"=> '<strong style="color:blue;">'.$org->ciselna_rada.'</strong> - '.$org->zkraceny_nazev,
                    "nazev"=> $org->ciselna_rada ." - ". $org->zkraceny_nazev
                );
            }
        }


        if ( count($seznam_zamestnancu)>0 ) {
            //$seznam[ ] = array('id'=>'o',"type" => 'part','name'=>'Předat zaměstnanci');
            foreach( $seznam_zamestnancu as $user ) {
                if ( !empty($user->name) ) {
                    $role = " ( ".$user->name." )";
                } else {
                    $role = "";
                }
                $seznam[ ] = array(
                    "id"=> 'u'. $user->user_id,
                    "type" => 'item',
                    "value"=> ('<strong>'.Osoba::displayName($user, 'full_item')."</strong>". $role),
                    "nazev"=> (Osoba::displayName($user, 'full_item') . $role)
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }

    public function actionUserSeznamAjax()
    {
        
        $Zamestnanci = new Osoba2User();

        $seznam = array();

        $term = $this->getParam('term');

        if ( !empty($term) ) {
            $seznam_zamestnancu = $Zamestnanci->hledat($term);
        } else {
            $seznam_zamestnancu = $Zamestnanci->seznam(1);
        }

        if ( count($seznam_zamestnancu)>0 ) {
            //$seznam[ ] = array('id'=>'o',"type" => 'part','name'=>'Předat zaměstnanci');
            foreach( $seznam_zamestnancu as $user ) {
                if ( !empty($user->name) ) {
                    $role = " ( ".$user->name." )";
                } else {
                    $role = "";
                }
                $seznam[ ] = array(
                    "id"=> $user->user_id,
                    "type" => 'item',
                    "value"=> ('<strong>'.Osoba::displayName($user, 'full_item')."</strong>". $role),
                    "nazev"=> (Osoba::displayName($user, 'full_item') . $role)
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }
    
    
    public function renderVybrano()
    {

        $osoba_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);
        $user_id = $this->getParam('user',null);
        $role_id = $this->getParam('role',null);
        $orgjednotka_id = $this->getParam('orgjednotka',null);
        $poznamka = $this->getParam('poznamka',null);
        $novy = $this->getParam('novy',0);


        if ( $novy == 1 ) {
          echo '###predano###'. $dokument_id .'#'.$user_id.'#'.$orgjednotka_id.'#'.$poznamka;

          $UserModel = new UserModel();
          $osoba = $UserModel->getIdentity($user_id);
          $Orgjednotka = new Orgjednotka();
          $org = $Orgjednotka->getInfo($orgjednotka_id);

          echo '#'. Osoba::displayName($osoba) .'#'. @$org->zkraceny_nazev;

          $this->terminate();
        } else {
            $Workflow = new Workflow();
            if ( $Workflow->priradit($dokument_id, $user_id, $orgjednotka_id, $poznamka) ) {
                $link = $this->link(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
                echo '###vybrano###'. $link;
                $this->terminate();
                //$this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else {
                // chyba
                $this->template->uzivatel_id = 0;
                $this->template->dokument_id = $dokument_id;
                $Zamestnanci = new Osoba2User();
                $seznam = $Zamestnanci->seznam(1);
                $this->template->seznam = $seznam;
                $this->template->chyba = 1;
                $this->template->render('vyber');
            }
        }

    }


}

