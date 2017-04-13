<?php

class Admin_NastaveniPresenter extends BasePresenter
{
    /* array indices */

    const ST_DB_NAME = 0;
    const ST_DEFAULT_VALUE = 1;
    const ST_DESCRIPTION = 2;

    static protected $settings = [
        'force_https' =>
        [ 'router_force_https', false, 'Vynutit zabezpečené připojení protokolem HTTPS'],
        'users_can_change_their_data' =>
        [ 'users_can_change_their_data', null, 'Uživatelé mohou měnit své osobní údaje'],
        'users_can_change_their_password' =>
        [ 'users_can_change_their_password', true, 'Uživatelé mohou měnit své heslo'],
        'login_redirect_homepage' =>
        [ 'login_redirect_homepage', false, 'Po přihlášení přesměrovat na domovskou stránku'],
        'povolit_predani' =>
        [ 'spisovka_allow_forward_finished_documents', false, 'Povolit předání vyřízeného dokumentu'],
        'change_creation_date' =>
        [ 'spisovka_allow_change_creation_date', false, 'Povolit změnu data doručení/vzniku dokumentu'],
        'zobrazit_zapujcene' =>
        [ 'spisovna_display_borrowed_documents', null, 'Zobrazit zapůjčené dokumenty ve spisovně'],
        'automaticky_nacist_zpravy' =>
        [ 'epodatelna_auto_load_new_messages', null, 'Automaticky načíst zprávy po kontrole schránek'],
        'kopirovat_email_do_poznamky' =>
        [ 'epodatelna_copy_email_into_documents_note', null, 'Kopírovat obsah e-mailu do poznámky dokumentu'],
    ];
    static public $ciselnik_zpusoby_uhrad = array(
        '' => 'neurčeno - vyplním ručně na každém archu',
        'v hotovosti',
        'převodem',
        'výplatním strojem',
        'Kreditem',
        'poštovními známkami',
    );

    public function renderDefault()
    {
        $CJ = new CisloJednaci();

        // Klientske nastaveni
        $client_config = GlobalVariables::get('client_config');
        $this->template->Urad = $client_config->urad;

        $this->template->CisloJednaci = $client_config->cislo_jednaci;

        $this->template->Nastaveni = $client_config->nastaveni;

        $this->template->Ukazka = $CJ->generuj();

        $this->template->settings = self::$settings;

        foreach (array_keys(self::$settings) as $short_name) {
            $this->stTemplateVar($short_name);
        }

        $this->template->cislo_zakaznicke_karty = Settings::get('Ceska_posta_cislo_zakaznicke_karty',
                        '');
        $this->template->zpusob_uhrady = Settings::get('Ceska_posta_zpusob_uhrady', '');

        $this->template->notification_receive_document = Notifications::isNotificationEnabled(Notifications::RECEIVE_DOCUMENT);

        // Zmena udaju
        $this->template->FormUpravit = $this->getParameter('upravit', null);
    }

    /**
     *
     * Formular a zpracovani pro zmenu udaju org. jednotky
     *
     */
    protected function createComponentNastaveniUraduForm()
    {

        $client_config = GlobalVariables::get('client_config');
        $Urad = $client_config->urad;
        $stat_select = Subjekt::stat();


        $form1 = new Spisovka\Form();
        $form1->addText('nazev', 'Název:', 50, 100)
                ->setValue($Urad->nazev)
                ->addRule(Nette\Forms\Form::FILLED, 'Název úřadu musí být vyplněn.');
        $form1->addText('plny_nazev', 'Plný název:', 50, 200)
                ->setValue($Urad->plny_nazev);
        $form1->addText('zkratka', 'Zkratka:', 15, 30)
                ->setValue($Urad->zkratka)
                ->addRule(Nette\Forms\Form::FILLED, 'Zkratka úřadu musí být vyplněna.');

        $form1->addText('ulice', 'Ulice:', 50, 100)
                ->setValue($Urad->adresa->ulice);
        $form1->addText('mesto', 'Město:', 50, 100)
                ->setValue($Urad->adresa->mesto);
        $form1->addText('psc', 'PSČ:', 12, 50)
                ->setValue($Urad->adresa->psc);
        $form1->addSelect('stat', 'Stát:', $stat_select)
                ->setValue($Urad->adresa->stat);

        $form1->addText('ic', 'IČ:', 20, 50)
                ->setValue($Urad->firma->ico);
        $form1->addText('dic', 'DIČ:', 20, 50)
                ->setValue($Urad->firma->dic);

        $form1->addText('telefon', 'Telefon:', 50, 100)
                ->setValue($Urad->kontakt->telefon);
        $form1->addText('email', 'E-mail:', 50, 100)
                ->setValue($Urad->kontakt->email);
        $form1->addText('www', 'URL:', 50, 150)
                ->setValue($Urad->kontakt->www);


        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitUradClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function nastavitUradClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = GlobalVariables::get('client_config');

        $config_data['urad']['nazev'] = $data['nazev'];
        $config_data['urad']['plny_nazev'] = $data['plny_nazev'];
        $config_data['urad']['zkratka'] = $data['zkratka'];

        $config_data['urad']['adresa']['ulice'] = $data['ulice'];
        $config_data['urad']['adresa']['mesto'] = $data['mesto'];
        $config_data['urad']['adresa']['psc'] = $data['psc'];
        $config_data['urad']['adresa']['stat'] = $data['stat'];

        $config_data['urad']['firma']['ico'] = $data['ic'];
        $config_data['urad']['firma']['dic'] = $data['dic'];

        $config_data['urad']['kontakt']['telefon'] = $data['telefon'];
        $config_data['urad']['kontakt']['email'] = $data['email'];
        $config_data['urad']['kontakt']['www'] = $data['www'];

        (new Spisovka\ConfigClient())->save($config_data);

        $this->flashMessage('Informace o sobě byly upraveny.');
        $this->redirect('this');
    }

    public function stornoClicked()
    {
        $this->redirect('this');
    }

    protected function createComponentNastaveniCJForm()
    {
        $client_config = GlobalVariables::get('client_config');
        $CJ = $client_config->cislo_jednaci;

        $form1 = new Spisovka\Form();
        $form1->addText('maska', 'Maska:', 50, 100)
                ->setValue($CJ->maska)
                ->addRule(Nette\Forms\Form::FILLED, 'Maska čísla jednacího musí být vyplněna.');

        if ($CJ->typ_evidence != 'priorace') {
            $form1->addText('oddelovac', 'Znak oddělovače pořadového čísla:', 3, 1)
                    ->setValue($CJ->oddelovac);
        }

        $form1->addRadioList('typ_deniku', 'Podací deník:',
                        array('urad' => 'společný pro celý úřad', 'org' => 'samostatný pro každou organizační jednotku'))
                ->setValue(isset($CJ->typ_deniku) ? $CJ->typ_deniku : 'urad' );

        $CJ = new CisloJednaci;
        $form1->addCheckbox('minuly_rok', 'Evidovat dokumenty do minulého roku')
                ->setValue($CJ->get_minuly_rok())
                ->setOption('description',
                        Nette\Utils\Html::el()
                        ->setHtml('<br /><small>Tuto volbu použijte jen ve výjimečných případech. Zaškrtnutí ovlivní generování všech č.j. v aplikaci.</small>'));

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitCJClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function nastavitCJClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = GlobalVariables::get('client_config');

        $config_data['cislo_jednaci']['maska'] = $data['maska'];

        if ($config_data['cislo_jednaci']['typ_evidence'] != "priorace") {
            $config_data['cislo_jednaci']['oddelovac'] = $data['oddelovac'];
        }

        $config_data['cislo_jednaci']['typ_deniku'] = $data['typ_deniku'];

        $min_rok = $data['minuly_rok'] ? 1 : 0;
        $config_data['cislo_jednaci']['minuly_rok'] = $min_rok;

        (new Spisovka\ConfigClient())->save($config_data);

        $this->flashMessage('Nastavení čísla jednacího bylo upraveno.');
        $this->redirect('this');
    }

    protected function createComponentNastaveniForm()
    {
        $client_config = GlobalVariables::get('client_config');
        $nastaveni = $client_config->nastaveni;

        $form1 = new Spisovka\Form();
        $form1->addGroup('Ostatní nastavení');
        $form1->addText('pocet_polozek', 'Počet položek v seznamu:', 10, 10)
                ->setValue($nastaveni->pocet_polozek)
                ->addRule(Nette\Forms\Form::INTEGER, 'Počet položek v seznamu musí být číslo.')
                ->addRule(Nette\Forms\Form::RANGE,
                        'Počet položek v seznamu musí být v rozsahu od 1 do 500', array(1, 500));

        foreach (array_keys(self::$settings) as $short_name) {
            if ($short_name == 'automaticky_nacist_zpravy')
                $form1->addGroup('E-podatelna');
            $this->stCheckBox($form1, $short_name);
        }

        $form1->addGroup('Odeslání Českou poštou');
        $form1->addText('cislo_zakaznicke_karty', 'Číslo Zákaznické karty:', 13, 13)
                ->setValue(Settings::get('Ceska_posta_cislo_zakaznicke_karty', ''))
                ->addCondition(Nette\Forms\Form::FILLED)
                ->addRule(Nette\Forms\Form::INTEGER, 'Chybné číslo karty.');

        $form1->addRadioList('zpusob_uhrady', 'Způsob úhrady:', self::$ciselnik_zpusoby_uhrad)
                ->setValue(array_search(Settings::get('Ceska_posta_zpusob_uhrady', ''),
                                self::$ciselnik_zpusoby_uhrad));

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['group']['label'] = 'div class="detail_hlavicka"';

        return $form1;
    }

    public function nastavitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = GlobalVariables::get('client_config');
        $config_data['nastaveni']['pocet_polozek'] = $data['pocet_polozek'];

        (new Spisovka\ConfigClient())->save($config_data);

        foreach (self::$settings as $short_name => $set) {
            Settings::set($set[self::ST_DB_NAME], $data[$short_name]);
        }

        Settings::set('Ceska_posta_cislo_zakaznicke_karty', $data['cislo_zakaznicke_karty']);
        Settings::set('Ceska_posta_zpusob_uhrady',
                $data['zpusob_uhrady'] === '' ? '' : self::$ciselnik_zpusoby_uhrad[$data['zpusob_uhrady']]);

        $this->flashMessage('Obecná nastavení byla upravena.');
        $this->redirect('this');
    }

    public function createComponentNotificationsForm()
    {
        $form = new Spisovka\Form();

        $form->addCheckBox(Notifications::RECEIVE_DOCUMENT,
                        'Poslat e-mail při předání dokumentu')
                ->setValue(Notifications::isNotificationEnabled(Notifications::RECEIVE_DOCUMENT));

        $form->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitNotificationsClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form;
    }

    public function nastavitNotificationsClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        Notifications::enableNotification(Notifications::RECEIVE_DOCUMENT,
                $data[Notifications::RECEIVE_DOCUMENT]);
        $this->flashMessage('Nastavení upozornění bylo upraveno.');
        $this->redirect('this');
    }

    /**
     * Adds a checkbox to the form
     * @param Spisovka\Form $form
     * @param string $short_name
     */
    protected function stCheckBox(Spisovka\Form $form, $short_name)
    {
        $s = self::$settings[$short_name];

        $form->addCheckBox($short_name, $s[self::ST_DESCRIPTION])
                ->setValue(Settings::get($s[self::ST_DB_NAME], $s[self::ST_DEFAULT_VALUE]));
    }

    /**
     * Sets template variable for display
     * @param string $short_name
     */
    protected function stTemplateVar($short_name)
    {
        $s = self::$settings[$short_name];

        $this->template->$short_name = Settings::get($s[self::ST_DB_NAME],
                        $s[self::ST_DEFAULT_VALUE]);
    }

}
