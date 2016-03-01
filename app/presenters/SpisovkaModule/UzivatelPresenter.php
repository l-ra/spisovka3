<?php

class Spisovka_UzivatelPresenter extends BasePresenter
{

    public function actionLogin()
    {
        $Auth = $this->context->createService('authenticatorUI');
        $Auth->setAction('login');
        $this->addComponent($Auth, 'auth');

        $this->template->title = "Přihlásit se";
    }

    public function renderLogout()
    {
        $user = $this->user;
        $user->logout();
        $this->flashMessage('Byl jste úspěšně odhlášen.');
        
        // Hack - cookie v minulosti kontroloval SSO autentikator. Nyni neni reseno vubec.
        // $this->getHttpResponse()->setCookie('s3_logout', $username, strtotime('10 minute'));        

        // P.L. Je-li to mozne, vrat se presne na stranku, kde byl uzivatel, nez kliknul na odhlasit
        // Zpravu o uspesnem odhlaseni nebude mozne v tom pripade zobrazit
        $referer = $this->getHttpRequest()->getHeader('referer');
        if ($referer) {
            // odstran query cast URL, nutne aby se ne obrazovce s prihlasovacim dialogem nezobrazovala posledni flash zprava
            $uri = new Nette\Http\Url($referer);
            $uri->setQuery('');
            $this->redirectUrl($uri);
        } else
            $this->redirect('login');
    }

    public function actionDefault()
    {
        $user = $this->user;
        $osoba = UserModel::getPerson($user->id);
        $this->template->Osoba = $osoba;

        // Kterou sekci editovat
        $this->template->FormUpravit = $this->getParameter('upravit', '');

        $account = UserModel::getUser($user->id);
        $this->template->Uzivatel = $account;
        $this->template->Org_jednotka = $account->orgjednotka_id !== null ? Orgjednotka::getName($account->orgjednotka_id)
                        : 'žádná';

        $Auth1 = $this->context->createService('authenticatorUI');
        $Auth1->setAction('change_password');
        $Auth1->setParams(['osoba_id' => $osoba->id, 'user_id' => $user->id]);
        $this->addComponent($Auth1, 'auth_change_password');

        $role = UserModel::getRoles($account->id);
        $this->template->Role = $role;

        $this->template->notification_receive_document = Notifications::isUserNotificationEnabled(Notifications::RECEIVE_DOCUMENT);
    }

    /**
     *
     * Formular a zpracovani pro udaju osoby
     *
     */
    protected function createComponentUpravitForm()
    {
        $form = Admin_ZamestnanciPresenter::createOsobaForm();
        $form->addHidden('osoba_id');

        $osoba = $this->template->Osoba;
        if ($osoba) {
            $form['osoba_id']->setValue($osoba->id);
            $form['jmeno']->setValue($osoba->jmeno);
            $form['prijmeni']->setValue($osoba->prijmeni);
            $form['titul_pred']->setValue($osoba->titul_pred);
            $form['titul_za']->setValue($osoba->titul_za);
            $form['email']->setValue($osoba->email);
            $form['telefon']->setValue($osoba->telefon);
            $form['pozice']->setValue($osoba->pozice);
        }

        $form->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        $Osoba = new Osoba();
        $osoba_id = $data['osoba_id'];
        unset($data['osoba_id']);

        try {
            $osoba_id = $Osoba->ulozit($data, $osoba_id);
            $this->flashMessage('Informace o uživateli byly upraveny.');
        } catch (DibiException $e) {
            $this->flashMessage('Informace o uživateli se nepodařilo upravit. ' . $e->getMessage(),
                    'warning');
        }
        $this->redirect('this');
    }

    public function stornoClicked()
    {
        $this->redirect('this');
    }

    protected function _renderVyber()
    {
        if ($this->getParameter('chyba', null))
            $this->template->chyba = 1;

        $this->template->novy = $this->getParameter('novy', 0);

        $Zamestnanci = new Osoba2User();
        $seznam = $Zamestnanci->seznam();
        $this->template->seznam = $seznam;

        $OrgJednotky = new Orgjednotka();
        $oseznam = $OrgJednotky->linearniSeznam();
        $this->template->org_seznam = $oseznam;
    }

    public function renderVyber()
    {
        $this->template->dokument_id = $dok_id = $this->getParameter('dok_id', null);

        $model = new Dokument();
        $dok = $model->getInfo($dok_id);
        $this->template->dokument_je_ve_spisu = isset($dok->spisy);

        $this->_renderVyber();
    }

    public function renderVyberspis()
    {
        $this->template->spis_id = $spis_id = $this->getParameter('spis_id', null);
        $this->_renderVyber();
        // Zvazit do budoucna - jednotnou sablonu pro predani dokumentu i spisu
        // $this->setView('vyber');
    }

    // Autocomplete callback
    // Hleda jak uzivatele, tak org. jednotky
    public function actionSeznamAjax()
    {
        $term = $this->getParameter('term');

        $a1 = $this->_ojSeznam($term);
        $a2 = $this->_userSeznam($term);
        foreach ($a1 as &$value)
            $value['id'] = 'o' . $value['id'];
        foreach ($a2 as &$value)
            $value['id'] = 'u' . $value['id'];

        echo json_encode(array_merge($a1, $a2));

        exit;
    }

    // Autocomplete callback
    // Hleda pouze uzivatele, ne org. jednotky
    // Volano z modulu spisovna
    public function actionUserSeznamAjax()
    {
        $term = $this->getParameter('term');

        echo json_encode($this->_userSeznam($term));
        exit;
    }

    protected function _ojSeznam($term)
    {
        $OrgJednotky = new Orgjednotka();

        $args = empty($term) ? null : ['where' => [['LOWER(tb.ciselna_rada) LIKE LOWER(%s)', '%' . $term . '%', ' OR LOWER(tb.zkraceny_nazev) LIKE LOWER(%s)', '%' . $term . '%']]];
        $seznam_orgjednotek = $OrgJednotky->nacti($args);

        $seznam = array();

        if (count($seznam_orgjednotek) > 0) {
            //$seznam[] = array('id' => 'o', "type" => 'part', 'name' => 'Předat organizační jednotce');
            foreach ($seznam_orgjednotek as $org)
                $seznam[] = array(
                    "id" => $org->id,
                    "type" => 'item',
                    "value" => $org->ciselna_rada . ' - ' . $org->zkraceny_nazev,
                    "nazev" => $org->ciselna_rada . " - " . $org->zkraceny_nazev
                );
        }

        return $seznam;
    }

    protected function _userSeznam($term)
    {
        $Zamestnanci = new Osoba2User();

        if (!empty($term))
            $seznam_zamestnancu = $Zamestnanci->hledat($term);
        else
            $seznam_zamestnancu = $Zamestnanci->seznam();

        $seznam = array();

        if (count($seznam_zamestnancu) > 0) {
            //$seznam[ ] = array('id'=>'o',"type" => 'part','name'=>'Předat zaměstnanci');
            foreach ($seznam_zamestnancu as $user) {
                $additional_info = '';
                if ($user->pocet_uctu > 1)
                    $additional_info = " ( {$user->username} )";
                $seznam[] = array(
                    "id" => $user->user_id,
                    "type" => 'item',
                    "value" => (Osoba::displayName($user, 'full_item') . "$additional_info"),
                    "nazev" => Osoba::displayName($user, 'full_item')
                );
            }
        }

        return $seznam;
    }

    public function renderSpisvybrano()
    {
        $spis_id = $this->getParameter('spis_id', null);
        $user_id = $this->getParameter('user', null);
        $orgjednotka_id = $this->getParameter('orgjednotka', null);
        $poznamka = $this->getParameter('poznamka', null);
        $novy = $this->getParameter('novy', 0);

        if ($orgjednotka_id === null)
            $orgjednotka_id = OrgJednotka::dejOrgUzivatele($user_id);

        if ($novy == 1) {
            echo '###predano###' . $spis_id . '#' . $user_id . '#' . $orgjednotka_id . '#' . $poznamka;

            $osoba = UserModel::getPerson($user_id);
            $Orgjednotka = new Orgjednotka();
            $org = $Orgjednotka->getInfo($orgjednotka_id);

            echo '#' . Osoba::displayName($osoba) . '#' . @$org->zkraceny_nazev;

            $this->terminate();
        } else {
            $Workflow = new Workflow();

            // Predat Spis

            $DokSpis = new DokumentSpis();
            $dokumenty = $DokSpis->dokumenty($spis_id);

            if (count($dokumenty) > 0) {
                // obsahuje dokumenty - predame i dokumenty
                $dokument = current($dokumenty);

                if ($Workflow->predat($dokument->id, $user_id, $orgjednotka_id, $poznamka)) {
                    $link = $this->link(':Spisovka:Spisy:detail', array('id' => $spis_id));
                    echo '###vybrano###' . $link;
                    $this->terminate();
                } else {
                    $this->forward('vyberspis', array('chyba' => 1, 'spis_id' => $spis_id));
                }
            } else {
                // pouze spis
                $Spis = new Spis;
                if ($Spis->predatOrg($spis_id, $orgjednotka_id)) {
                    $link = $this->link(':Spisovka:Spisy:detail', array('id' => $spis_id));
                    echo '###vybrano###' . $link;
                    $this->terminate();
                } else {
                    // forwarduj pozadavek na novy render dialogu a dej mu informaci, ze ma upozornit uzivatele, ze doslo k chybe
                    $this->forward('vyberspis', array('chyba' => 1, 'spis_id' => $spis_id));
                }
            }
        }
    }

    public function renderVybrano()
    {
        $dokument_id = $this->getParameter('dok_id', null);
        $user_id = $this->getParameter('user', null);
        $orgjednotka_id = $this->getParameter('orgjednotka', null);
        $poznamka = $this->getParameter('poznamka', null);
        $novy = $this->getParameter('novy', 0);

        if ($novy == 1) {
            echo "###predano###$dokument_id#$user_id#$orgjednotka_id#";

            if ($user_id !== null) {
                $osoba = UserModel::getPerson($user_id);
                echo Osoba::displayName($osoba);
            } else {
                $Orgjednotka = new Orgjednotka();
                $org = $Orgjednotka->getInfo($orgjednotka_id);
                echo "organizační jednotce<br/>" . $org->zkraceny_nazev;
            }
            $this->terminate();
        } else {
            $Workflow = new Workflow();
            if ($Workflow->predat($dokument_id, $user_id, $orgjednotka_id, $poznamka)) {
                $link = $this->link(':Spisovka:Dokumenty:detail', array('id' => $dokument_id));
                echo '###vybrano###' . $link;
                $this->terminate();
            } else {
                // forwarduj pozadavek na novy render dialogu a dej mu informaci, ze ma upozornit uzivatele, ze doslo k chybe
                $this->forward('vyber', array('chyba' => 1, 'dok_id' => $dokument_id));
            }
        }
    }

    protected function createComponentNotificationsForm()
    {
        $form1 = new Spisovka\Form();

        $form1->addCheckBox(Notifications::RECEIVE_DOCUMENT,
                        'Poslat e-mail, když mně je předán dokument')
                ->setValue(Notifications::isUserNotificationEnabled(Notifications::RECEIVE_DOCUMENT));

        $form1->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitNotificationsClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitNotificationsClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        // Ulozi hodnoty a vytvori dalsi verzi
        $data = $button->getForm()->getValues();

        Notifications::enableUserNotification(Notifications::RECEIVE_DOCUMENT,
                $data[Notifications::RECEIVE_DOCUMENT]);

        $this->flashMessage('Nastavení bylo upraveno.');
        $this->redirect('this');
    }

}
