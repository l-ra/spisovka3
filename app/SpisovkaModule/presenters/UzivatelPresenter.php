<?php

class Spisovka_UzivatelPresenter extends BasePresenter {

    public $backlink = '';

    public function actionLogin($backlink)
    {
        $form = new AppForm($this, 'form');
        $form->addText('username', 'Uživatelské jméno:')
            ->addRule(Form::FILLED, 'Zadejte uživatelské jméno, nebo e-mail.');

        $form->addPassword('password', 'Heslo:')
            ->addRule(Form::FILLED, 'Zadejte přihlašovací heslo.');

        $form->addSubmit('login', 'Přihlásit');
        $form->onSubmit[] = array($this, 'loginFormSubmitted');

        $form->addProtection('Prosím přihlašte se znovu.');

        $this->backlink = $backlink;
        $this->template->form = $form;
        $this->template->title = "Přihlásit se";
    }

    public function loginFormSubmitted($form)
    {
        try {
            $user = Environment::getUser();
            $user->setNamespace(KLIENT);
            $user->authenticate($form['username']->value, $form['password']->value);
            $this->getApplication()->restoreRequest($this->backlink);
            //$this->redirect(':Spisovka:Default:default');

        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'warning');
            //$form->addError($e->getMessage());
        }
    }

        public function renderLogout()
        {
                Environment::getUser()->signOut();
                $this->flashMessage('Byl jste úspěšně odhlášen.');
                $this->redirect(':Default:default');
        }

    public function actionDefault()
    {
        $Osoba = new Osoba();
        $User = new UserModel();

        $user = Environment::getUser()->getIdentity();
        
        //Debug::dump($user);


        $osoba_id = $user->identity->osoba_id;
        $this->template->Osoba = $Osoba->getInfo($osoba_id);

        // Zmena osobnich udaju
        $this->template->FormUpravit = $this->getParam('upravit',null);

        $uzivatel = $User->getUser($user->user_id);
        $this->template->Uzivatel = $uzivatel;

        // Zmena hesla
        $this->template->ZmenaHesla = $this->getParam('zmenitheslo',null);

        $role = $User->getRoles($uzivatel->user_id);
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
                ->setValue(@$osoba->osoba_id);
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
        $data['date_modified'] = new DateTime();
        unset($data['osoba_id']);

        $Osoba->update($data,array('osoba_id = %i',$osoba_id));

        $this->flashMessage('Zaměstnanec  "'. Osoba::displayName($data) .'"  byl upraven.');
        $this->redirect('this');
    }

    public function stornoClicked(SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $this->redirect('this');
    }

/**
 *
 * Formular a zpracovani pro zmenu hesla
 *
 */

    protected function createComponentUserForm()
    {
        $form1 = new AppForm();
        $form1->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.');
        $form1->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form1["heslo"], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form1["heslo"]);

        $form1->addSubmit('upravit', 'Změnit heslo')
                 ->onClick[] = array($this, 'zmenitHesloClicked');
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


    public function zmenitHesloClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $zmeneno = 0;
        $User = new UserModel();
        $user = Environment::getUser()->getIdentity();

        if ( $User->zmenitHeslo($user->user_id, $data['heslo']) ) {
            $zmeneno = 1;
        }

        if ( $zmeneno == 1 ) {
            $this->flashMessage('Heslo uživatele "'. $user->username .'"  bylo úspěšně změněno.');
        } else {
            $this->flashMessage('Nedošlo k žádné změně.');
        }
        $this->redirect('this');
    }


    public function renderVyber()
    {

        $this->template->uzivatel_id = $this->getParam('id',null);
        $this->template->dokument_id = $this->getParam('dok_id',null);

        $Zamestnanci = new Osoba2User();
        $seznam = $Zamestnanci->seznam(1);
        $this->template->seznam = $seznam;
    }

    public function renderVybrano()
    {

        $osoba_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);
        $user_id = $this->getParam('user',null);
        $role_id = $this->getParam('role',null);
        $orgjednotka_id = $this->getParam('orgjednotka',null);
        $poznamka = $this->getParam('poznamka',null);

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

