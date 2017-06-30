<?php

namespace Spisovka;

use Nette;

class Spisovka_UzivatelPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }
    
    public function actionLogout()
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

    public function createComponentLogin()
    {
        $component = $this->context->createService('authenticatorUI');
        $component->setAction('login');
        return $component;
    }

    public function createComponentChangePassword()
    {
        $account = new UserAccount($this->user);
        $person = $account->getPerson();

        $comp = $this->context->createService('authenticatorUI');
        $comp->setAction('change_password');
        $comp->setParams(['osoba_id' => $person->id, 'user_id' => $this->user->id]);
        return $comp;
    }

    public function renderDefault($upravit)
    {
        $this->template->FormUpravit = $upravit; // Kterou sekci editovat

        $user = $this->user;
        $account = new UserAccount($user);
        $this->template->Osoba = $person = $account->getPerson();
        $this->template->Uzivatel = $account;
        $this->template->Org_jednotka = $account->orgjednotka_id !== null ? new OrgUnit($account->orgjednotka_id)
                    : 'žádná';

        $roles = $account->getRoles();
        $this->template->Role = $roles;

        $this->template->notification_receive_document = Notifications::isUserNotificationEnabled(Notifications::RECEIVE_DOCUMENT);
    }

    /**
     *
     * Formular a zpracovani pro udaju osoby
     *
     */
    protected function createComponentUpravitForm()
    {
        if (Settings::get('users_can_change_their_data') == false)
            throw new \Exception('neoprávněný přístup');

        $form = Admin_ZamestnanciPresenter::createOsobaForm();
        $form->addHidden('osoba_id');

        $account = new UserAccount($this->user);
        $person = $account->getPerson();

        $form['osoba_id']->setDefaultValue($person->id);
        $form['jmeno']->setDefaultValue($person->jmeno);
        $form['prijmeni']->setDefaultValue($person->prijmeni);
        $form['titul_pred']->setDefaultValue($person->titul_pred);
        $form['titul_za']->setDefaultValue($person->titul_za);
        $form['email']->setDefaultValue($person->email);
        $form['telefon']->setDefaultValue($person->telefon);
        $form['pozice']->setDefaultValue($person->pozice);

        $form->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        $osoba_id = $data['osoba_id'];
        unset($data['osoba_id']);

        try {
            $osoba = new Person($osoba_id);
            $osoba->modify($data);
            $osoba->save();
            $this->flashMessage('Informace o uživateli byly upraveny.');
        } catch (DibiException $e) {
            $this->flashMessage('Informace o uživateli se nepodařilo upravit. ' . $e->getMessage(),
                    'warning');
        }
        $this->redirect('default');
    }

    public function stornoClicked()
    {
        $this->redirect('default');
    }

    public function renderVyber()
    {
        $Zamestnanci = new Osoba();
        $this->template->user_list = $Zamestnanci->seznamOsobSUcty();

        $OrgJednotky = new OrgJednotka();
        $this->template->orgunit_list = $OrgJednotky->linearniSeznam();
    }

    /**
     * Autocomplete callback. Hleda jak uzivatele, tak org. jednotky.
     */
    public function renderSeznamAjax($term)
    {
        $a1 = $this->_ojSeznam($term);
        $a2 = $this->_userSeznam($term);
        foreach ($a1 as &$value)
            $value['id'] = 'o' . $value['id'];
        foreach ($a2 as &$value)
            $value['id'] = 'u' . $value['id'];

        $this->sendJson(array_merge($a1, $a2));
    }

    /** Autocomplete callback
     * Hleda pouze uzivatele, ne org. jednotky
     * Volano z modulu spisovna
     */
    public function renderUserSeznamAjax($term)
    {
        $this->sendJson($this->_userSeznam($term));
    }

    protected function _ojSeznam($term)
    {
        $OrgJednotky = new OrgJednotka();

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
        $Zamestnanci = new Osoba();
        $seznam_zamestnancu = $Zamestnanci->seznamOsobSUcty($term);

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

    protected function createComponentNotificationsForm()
    {
        $form1 = new Form();

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
        $data = $button->getForm()->getValues();

        Notifications::enableUserNotification(Notifications::RECEIVE_DOCUMENT,
                $data[Notifications::RECEIVE_DOCUMENT]);

        $this->flashMessage('Nastavení bylo upraveno.');
        $this->redirect('default');
    }

    protected function createComponentIsdsBoxForm()
    {
        $form1 = new Form();

        $form1->addText('login', 'Uživatelské jméno:')
                ->setValue(UserSettings::get('isds_login'));
        $form1->addPassword('password', 'Heslo:');

        $form1->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitIsdsBoxClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitIsdsBoxClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        UserSettings::set('isds_login', $data->login);
        UserSettings::set('isds_password', $data->password);

        $this->flashMessage('Nastavení bylo upraveno.');
        $this->redirect('default');
    }

}
