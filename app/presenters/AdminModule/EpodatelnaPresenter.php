<?php

namespace Spisovka;

use Nette;

class Admin_EpodatelnaPresenter extends BasePresenter
{

    /**
     *  Klíč do globálního nastavení
     */
    const ISDS_INDIVIDUAL_LOGIN = 'isds_individual_login';

    public function renderDefault()
    {
        // Klientske nastaveni
        $ep = self::nactiNastaveni();

        // ISDS
        $this->template->isds = $ep['isds'];
        // $this->template->vice_datovych_schranek = ISDS_Spisovka::vice_datovych_schranek();
        // Email
        if (count($ep['email']) == 0)
            $this->template->email_config = null;
        else {
            $e_mail = array();

            $typ_serveru = array(
                '' => '',
                '/pop3/novalidate-cert' => 'POP3',
                '/pop3/ssl/novalidate-cert' => 'POP3-SSL',
                '/imap/novalidate-cert' => 'IMAP',
                '/imap/ssl/novalidate-cert' => 'IMAP+SSL',
                '/nntp' => 'NNTP'
            );
            foreach ($ep['email'] as $ei => $email) {
                $email['protokol'] = $typ_serveru[$email['typ']];
                $e_mail[$ei] = $email;
            }

            $this->template->email_config = $e_mail;
        }

        // Odeslani
        $sending = $ep['odeslani'];
        if (!$ep['odeslani']['podepisovat'])
            $state = 'vypnuto';
        else if (file_exists($ep['odeslani']['cert'])) {
            $esign = new esignature();
            $ok = $esign->setUserCert($ep['odeslani']['cert'], $ep['odeslani']['cert_pass']);
            if ($ok) {
                $state = 'Certifikát je nahrán.';
                $cert_info = $esign->parseCertificate();
                if (is_array($cert_info)) {
                    $state = $cert_info['platnost_od'] <= time() && $cert_info['platnost_do'] >= time()
                                ? 'Certifikát je platný.' : 'Certifikát je neplatný! Vypršela jeho platnost.';
                    $sending['certificate_info'] = $cert_info;
                }
            } else 
                $state = "Nahraný soubor s certifikátem je neplatný nebo nesouhlasí heslo.\n" . $esign->getError();
        } else
            $state = 'Certifikát není nahrán.';

        $sending['signing_state'] = $state;
        $this->template->sending = $sending;
    }

    public function renderDetail($id)
    {
        $ep = self::nactiNastaveni();

        $typ = substr($id, 0, 1);
        $this->template->Typ = $typ;
        switch ($typ) {
            case 'i':
                $isds = clone $ep['isds'];
                $crt = $isds['certifikat'];
                $isds['certifikat_stav'] = file_exists($crt) ? 1 : 0;
                $this->template->Info = $isds;
                break;
        }
    }

    /**
     *
     * Formular a zpracovani pro zmenu udaju org. jednotky
     *
     */
    protected function createComponentNastavitISDSForm()
    {
        $org_select = array('0' => 'Kterákoli podatelna');
        $OrgJednotky = new OrgJednotka();
        if ($orgjednotky_data = $OrgJednotky->seznam()->fetchAll())
            foreach ($orgjednotky_data as $oj)
                $org_select[$oj->id] = $oj->ciselna_rada . ' - ' . $oj->zkraceny_nazev;

        $connect_type = array(
            '0' => 'Základní (jménem a heslem)',
            '1' => 'Systémový certifikát'
        );

        $form1 = new Form();
        $form1->addHidden('ep_typ')
                ->setValue('i');

        $form1->addGroup();
        $form1->addCheckbox('aktivni', ' aktivní účet?');

        $form1->addRadioList('zpusob_prihlaseni', 'Způsob přihlášení:',
                        ['společný přístup', 'každý uživatel má své přihlašovací údaje'])
                ->setHtmlId('isds-form-zpusob-prihlaseni')
                ->getControlPrototype()->onchange(
                'var el = $("#isds-form-login-group");'
                . '$("#isds-form-zpusob-prihlaseni-0").prop(\'checked\') ? el.show() : el.hide();');
        $form1['zpusob_prihlaseni']->generateId = true;    // Nette 2.3

        $group = $form1->addGroup();
        $group->setOption('container', Nette\Utils\Html::el('div id=isds-form-login-group'));

        $form1->addRadioList('typ_pripojeni', 'Typ přihlášení:', $connect_type);

        $form1->addText('login', 'Uživatelské jméno:', 50, 100);
        $form1->addPassword('password', 'Heslo:', 50, 100);

        $form1->addUpload('certifikat_file', 'Soubor s certifikátem a klíčem (v PEM kódování):');
        $form1->addText('cert_pass', 'Heslo k soukromému klíči (pokud je zašifrovaný):', 50,
                100);

        $form1->addGroup();
        $form1->addSelect('test', 'Režim:',
                ['0' => 'Reálný provoz (mojedatovaschranka.cz)',
            '1' => 'Testovací režim (czebox.cz)']
        );
        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select);
        $form1->addSelect('log_level', 'Úroveň protokolování:',
                [
            'vypnuto', 'stručné', '+ vstupní parametry', '+ odpovědi serveru'
        ]);

        $ep = self::nactiNastaveni();
        $isds = $ep['isds'];
        $individual_login = Settings::get(self::ISDS_INDIVIDUAL_LOGIN, false);
        if ($individual_login)
            $group->getOption('container')->style('display: none');
        $form1['zpusob_prihlaseni']->setDefaultValue((int) $individual_login);

        $form1['aktivni']->setDefaultValue($isds['aktivni']);
        try {
            $form1['typ_pripojeni']->setDefaultValue($isds['typ_pripojeni']);
        } catch (\Exception $e) {
            $e->getMessage();
        }
        $form1['login']->setDefaultValue($isds['login']);
        $form1['cert_pass']->setDefaultValue($isds['cert_pass']);
        $form1['test']->setDefaultValue($isds['test']);
        $form1['podatelna']->setDefaultValue($isds['podatelna']);
        $form1['log_level']->setDefaultValue(Settings::get('isds_log_level', 0));

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitISDSClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['group']['container'] = null;

        return $form1;
    }

    public function nastavitISDSClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = self::nactiNastaveni();

        $data['certifikat'] = "";
        if ($data['typ_pripojeni'] == 1 || $data['typ_pripojeni'] == 2) {
            //nahrani certifikatu
            $upload = $data['certifikat_file'];

            if (is_writeable(CLIENT_DIR . "/configs")) {
                $fileName = CLIENT_DIR . "/configs/certifikat_isds.crt";
                if (!$upload instanceof Nette\Http\FileUpload) {
                    $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
                } else if ($upload->isOk()) {
                    if ($upload->move($fileName)) {
                        $data['certifikat'] = $fileName;
                    } else {
                        $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.',
                                'warning');
                    }
                } else {
                    switch ($upload->error) {
                        case UPLOAD_ERR_INI_SIZE:
                            $this->flashMessage('Překročena velikost pro nahrání certifikátu.',
                                    'warning');
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                            break;
                        default:
                            $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
                            break;
                    }
                }
            } else {
                // nelze nahrat
                $this->flashMessage('Certifikát nelze nahrát na cílové místo.', 'warning');
            }
        }
        unset($data['certifikat_file']);

        $config_data['isds']['aktivni'] = $data['aktivni'];
        $individual_login = (bool) $data['zpusob_prihlaseni'];
        Settings::set(self::ISDS_INDIVIDUAL_LOGIN, $individual_login);
        $config_data['isds']['typ_pripojeni'] = $data['typ_pripojeni'];
        $config_data['isds']['login'] = $data['login'];
        if (!empty($data['password']))
            $config_data['isds']['password'] = $data['password'];
        if (!empty($data['certifikat']))
            $config_data['isds']['certifikat'] = $data['certifikat'];
        $config_data['isds']['cert_pass'] = $data['cert_pass'];
        $config_data['isds']['test'] = $data['test'];
        $config_data['isds']['podatelna'] = $data['podatelna'];
        Settings::set('isds_log_level', intval($data['log_level']));

        $idbox = "";
        $vlastnik = "";
        if (!$individual_login) {
            // Musime nejprve zadane udaje ulozit! Jinak se mohou zobrazovat zavadejici
            // chybova hlaseni
            self::ulozNastaveni($config_data);

            try {
                $ISDS = new ISDS_Spisovka();
                $info = $ISDS->GetOwnerInfoFromLogin();
                if (!empty($info)) {

                    $idbox = $info->dbID;
                    if (empty($info->firmName)) {
                        // jmeno prijmeni
                        $vlastnik = $info->pnFirstName . " " . $info->pnLastName . " [" . $info->dbType . "]";
                    } else {
                        // firma urad
                        $vlastnik = $info->firmName . " [" . $info->dbType . "]";
                    }
                }
            } catch (\Exception $e) {
                $this->flashMessage('Nelze se připojit k ISDS! ' . $e->getMessage(), "warning");
            }
        }

        $config_data['isds']['idbox'] = $idbox;
        $config_data['isds']['vlastnik'] = $vlastnik;

        self::ulozNastaveni($config_data);

        $this->flashMessage('Nastavení datové schránky bylo upraveno.');
        $this->redirect('default');
    }

    public function actionIsdsTest()
    {
        try {
            $ISDS = new ISDS_Spisovka();
            $info = $ISDS->GetOwnerInfoFromLogin();
            $stav_hesla = $ISDS->GetPasswordInfo();
            $this->flashMessage(ISDS_Spisovka::stavDS($info->dbState));
            if ($stav_hesla)
                $this->flashMessage("Heslo expiruje " . date("j.n.Y G:i",
                                strtotime($stav_hesla)));
        } catch (\Exception $e) {
            $this->flashMessage("Připojení se nepodařilo: " . $e->getMessage(), 'warning');
        }

        $this->redirect('default');
    }

    function ruleContains($item, $args)
    {
        return (strpos($item->value, $args) !== false);
    }

    function ruleNoEqual($item, $args)
    {
        return (strpos($item->value, $args) !== false);
    }

//    protected function createComponentZmenitHesloISDSForm()
//    {
//        $id = $this->getParameter('id', null);
//        $index = substr($id, 1);
//
//        $id = $this->getParameter('id', null);
//        $index = substr($id, 1);
//
//        $form = new Form();
//        $form->addHidden('index')
//                ->setValue($index);
//        $form->addHidden('zmenit_heslo_isds')
//                ->setValue(1);
//        $form->addHidden('ep_typ')
//                ->setValue('i');
//
//        $form->addPassword('password', 'Přihlašovací heslo ISDS:', 30, 30)
//                ->addRule(Nette\Forms\Form::FILLED,
//                        'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
//                ->addRule(Nette\Forms\Form::MIN_LENGTH,
//                        'Heslo do datové schránky musí být minimálně %d znaků dlouhé.', 8)
//                ->addRule(Nette\Forms\Form::MAX_LENGTH,
//                        'Heslo do datové schránky musí být maximálně %d znaků dlouhé.', 32)
//        /* ->addRule([$this, 'ruleNoEqual'], 'Heslo mesmí obsahovat id (login) uživatele, jemuž se heslo mění.',$isds['login'])
//          ->addRule([$this, 'ruleNoEqual'], 'Heslo se nesmí shodovat s původním heslem.',$isds['password'])
//          ->addRule([$this, 'ruleContains'], 'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','qwert')
//          ->addRule([$this, 'ruleContains'], 'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','asdgf')
//          ->addRule([$this, 'ruleContains'], 'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','12345')
//         */;
//
//        $form->addPassword('password_confirm', 'Přihlašovací heslo ještě jednou:', 30, 30)
//                ->addRule(Nette\Forms\Form::FILLED,
//                        'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
//                ->addConditionOn($form["password"], Nette\Forms\Form::FILLED)
//                ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat !",
//                        $form["password"]);
//
//        $form->addSubmit('zmenit', 'Změnit heslo')
//                ->onClick[] = array($this, 'zmenitHesloISDSClicked');
//        $form->addSubmit('storno', 'Zrušit')
//                        ->setValidationScope(FALSE)
//                ->onClick[] = array($this, 'stornoClicked');
//
//        return $form;
//    }
//    public function zmenitHesloISDSClicked(Nette\Forms\Controls\SubmitButton $button)
//    {
//        $data = $button->getForm()->getValues();
//        //echo "<pre>Data: "; print_r($data); echo "</pre>"; exit;
//
//        $chyba = 0;
//
//        $index = $data['index'];
//
//        $config_data = self::nactiNastaveni();
//
//        $old_pass = $config_data['isds'][$index]['password'];
//
//        if ($chyba == 0) {
//
//            $idbox = "";
//            $vlastnik = "";
//            $stav = "";
//            try {
//                $ISDS = new ISDS_Spisovka();
//
//                // zmena hesla
//                if ($ISDS->ChangeISDSPassword($old_pass, $data['password'])) {
//                    //if ( false ) {
//
//                    $info = $ISDS->GetOwnerInfoFromLogin();
//                    if (!empty($info)) {
//
//                        $idbox = $info->dbID;
//                        if (empty($info->firmName)) {
//                            // jmeno prijmeni
//                            $vlastnik = $info->pnFirstName . " " . $info->pnLastName . " [" . $info->dbType . "]";
//                        } else {
//                            // firma urad
//                            $vlastnik = $info->firmName . " [" . $info->dbType . "]";
//                        }
//                        $stav = ISDS_Spisovka::stavDS($info->dbState) . " (kontrolováno dne " . date("j.n.Y G:i") . ")";
//                        $stav_hesla = $ISDS->GetPasswordInfo();
//                    }
//
//                    $config_data['isds'][$index]['password'] = $data['password'];
//                    $config_data['isds'][$index]['idbox'] = $idbox;
//                    $config_data['isds'][$index]['vlastnik'] = $vlastnik;
//                    $config_data['isds'][$index]['stav'] = $stav;
//                    $config_data['isds'][$index]['stav_hesla'] = (empty($stav_hesla)) ? "(bez omezení)"
//                                : date("j.n.Y G:i", strtotime($stav_hesla));
//
//                    self::ulozNastaveni($config_data);
//
//                    $this->flashMessage('Heslo k datové schránky bylo úspěšně změněno.');
//                    $this->redirect(':Admin:Epodatelna:detail',
//                            array('id' => ('i' . $data['index'])));
//                } else {
//                    $this->flashMessage('Heslo k datové schránce se nepodařilo změnit.',
//                            'warning');
//                    $this->flashMessage('Chyba ISDS: ' . $ISDS->GetStatusMessage(), 'warning');
//                }
//            } catch (\Exception $e) {
//                $this->flashMessage('Při pokusu o změnu hesla došlo k chybě: ' . $e->getMessage(),
//                        "warning");
//            }
//        }
//    }

    protected function createComponentNastavitEmailForm()
    {
        $ep = self::nactiNastaveni();

        $id = $this->getParameter('id', null);
        $index = substr($id, 1);
        $email = !empty($id) ? $ep['email'][$index] : array();

        $org_select = array('0' => 'Kterákoli podatelna');
        $OrgJednotky = new OrgJednotka();
        if ($orgjednotky_data = $OrgJednotky->seznam()->fetchAll())
            foreach ($orgjednotky_data as $oj)
                $org_select[$oj->id] = $oj->ciselna_rada . ' - ' . $oj->zkraceny_nazev;

        $typ_serveru = array(
            '/pop3/novalidate-cert' => 'POP3',
            '/pop3/ssl/novalidate-cert' => 'POP3-SSL',
            '/imap/novalidate-cert' => 'IMAP',
            '/imap/ssl/novalidate-cert' => 'IMAP+SSL',
        );

        $form1 = new Form();
        $form1->addHidden('index');
        $form1->addHidden('ep_typ')
                ->setValue('e');

        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?');
        $form1->addSelect('typ', 'Protokol:', $typ_serveru)
                ->addRule(Nette\Forms\Form::FILLED,
                        'Vyberte protokol pro připojení k e-mailové schránce.');
        $form1->addText('server', 'Adresa serveru:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED,
                        'Adresa poštovního serveru musí být vyplněna.');
        $form1->addText('port', 'Port:', 5, 50)
                ->addRule(Nette\Forms\Form::FILLED, 'Port serveru musí být vyplněno.');
        $form1->addText('inbox', 'Složka:', 50, 100);

        $form1->addText('login', 'Přihlašovací jméno:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Přihlašovací jméno musí být vyplněno.');
        $form1->addPassword('password', 'Přihlašovací heslo:', 50, 100);
        // ->setValue($email['password'])
        // ->addRule(Form::FILLED, 'Přihlašovací heslo musí být vyplněno.');

        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select);

        $form1->addCheckbox('only_signature',
                'přijímat pouze emaily s elektronickým podpisem/značkou');
        $form1->addCheckbox('qual_signature',
                'přijímat pouze emaily s uznávaným elektronickým podpisem/značkou');

        if ($email) {
            $form1['index']->setValue($index);
            $form1['ucet']->setValue($email['ucet']);
            $form1['aktivni']->setValue($email['aktivni']);
            $form1['typ']->setValue($email['typ']);
            $form1['server']->setValue($email['server']);
            $form1['port']->setValue($email['port']);
            $form1['inbox']->setValue($email['inbox']);
            $form1['login']->setValue($email['login']);
            $form1['podatelna']->setValue($email['podatelna']);
            $form1['only_signature']->setValue($email['only_signature']);
            $form1['qual_signature']->setValue($email['qual_signature']);
        }

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitEmailClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function nastavitEmailClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $index = $data['index'];

        $config_data = self::nactiNastaveni();

        $config_data['email'][$index]['ucet'] = $data['ucet'];
        $config_data['email'][$index]['aktivni'] = $data['aktivni'];
        $config_data['email'][$index]['typ'] = $data['typ'];
        $config_data['email'][$index]['server'] = $data['server'];
        $config_data['email'][$index]['port'] = $data['port'];
        $config_data['email'][$index]['inbox'] = $data['inbox'];
        $config_data['email'][$index]['login'] = $data['login'];
        if (!empty($data['password']))
            $config_data['email'][$index]['password'] = $data['password'];
        $config_data['email'][$index]['podatelna'] = $data['podatelna'];
        $config_data['email'][$index]['only_signature'] = $data['only_signature'];
        $config_data['email'][$index]['qual_signature'] = $data['qual_signature'];

        self::ulozNastaveni($config_data);

        $this->flashMessage('Nastavení e-mailové schránky bylo upraveno.');
        $this->redirect('default');
    }

    protected function createComponentNastavitOdesForm()
    {
        $ep = self::nactiNastaveni();

        $id = $this->getParameter('id');
        $typ = substr($id, 0, 1);

        $form1 = new Form();

        $group = $form1->addGroup();
        $group->setOption('container', '');

        $form1->addHidden('index');
        $form1->addHidden('ep_typ')
                ->setValue($typ);

        $form1->addText('email', 'E-mailová adresa odesilatele:', 50, 100)
                ->addCondition(Form::FILLED)
                ->addRule(Form::EMAIL);
        $form1->addText('bcc', 'Posílat kopii na:', 50, 100)
                ->addCondition(Form::FILLED)
                ->addRule(Form::EMAIL);
        
        $form1->addCheckbox('podepisovat', 'Elektronicky podepisovat:')
                ->setHtmlId('odes-form-podepisovat')
                ->getControlPrototype()->onchange(
                'var el = $("#odes-form-certificate-group");'
                . '$("#odes-form-podepisovat").prop("checked") ? el.show() : el.hide();');

        $group = $form1->addGroup();
        $group->setOption('container',
                Nette\Utils\Html::el('div id=odes-form-certificate-group'));

        $form1->addUpload('cert_file', 'Soubor s certifikátem a klíčem ve formátu PKCS #12:');
        $form1->addText('cert_pass', 'Heslo k souboru:', 50, 100);
        $form1->setCurrentGroup(null);

        $odes = $ep['odeslani'];
        $form1['podepisovat']->setDefaultValue($odes['podepisovat']);
        if (!$odes->podepisovat)
            $group->getOption('container')->style('display: none');
        $form1['email']->setDefaultValue($odes['email']);
        if (!empty($odes['bcc']))
            $form1['bcc']->setDefaultValue($odes['bcc']);

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitOdesClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function nastavitOdesClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = self::nactiNastaveni();

        $data['cert'] = "";
        //nahrani certifikatu
        $upload = $data['cert_file'];

        $chyba_pri_uploadu = 0;
        if (!$upload instanceof Nette\Http\FileUpload) {
            $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
        } else if ($upload->isOk()) {
            try {
                $fileName = CLIENT_DIR . "/configs/certifikat_email.crt";
                $upload->move($fileName);
                $data['cert'] = $fileName;
            } catch (\Exception $e) {
                $e->getMessage();
                $chyba_pri_uploadu = 1;
                $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.',
                        'warning');
            }
        } else {
            switch ($upload->error) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->flashMessage('Překročena velikost pro nahrání certifikátu.',
                            'warning');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                    break;
                default:
                    $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
                    break;
            }
        }
        unset($data['cert_file']);

        if ($chyba_pri_uploadu && !is_writeable(CLIENT_DIR . '/configs'))
            $this->flashMessage('Nemohu zapisovat do adresáře client/configs/.', 'warning');

        $config_data['odeslani']['aktivni'] = true;
        $config_data['odeslani']['podepisovat'] = $data['podepisovat'];
        $config_data['odeslani']['email'] = $data['email'];
        $config_data['odeslani']['bcc'] = $data['bcc'];

        if (!empty($data['cert'])) {
            $config_data['odeslani']['cert'] = $data['cert'];
        }
        // heslo měň, pouze když jej uživatel zadá
        // současné heslo ve formuláři nezobrazuj
        if (!empty($data['cert_pass']))
            $config_data['odeslani']['cert_pass'] = $data['cert_pass'];

        self::ulozNastaveni($config_data);

        $this->flashMessage('Nastavení odesílání e-mailů bylo upraveno.');
        $this->redirect('default');
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $this->redirect('default');
    }

    public function actionSmazat($id)
    {
        $id_schranky = $id;

        $config_data = self::nactiNastaveni();

        $por_cislo = substr($id_schranky, 1);
        if (substr($id_schranky, 0, 1) == 'e')
            unset($config_data['email'][$por_cislo]);
        else
            throw new \InvalidArgumentException();

        self::ulozNastaveni($config_data);
        $this->flashMessage('Schránka byla smazána.');
        $this->redirect('default');
    }

    public function actionNovaschranka()
    {
        $config_data = self::nactiNastaveni();
        $config_data = $config_data->toArray();

        $ucet = [];
        $ucet['ucet'] = 'Nová e-mailová schránka';
        $ucet['aktivni'] = false;
        $ucet['typ'] = "/pop3/ssl/novalidate-cert";
        $ucet['server'] = '';
        $ucet['port'] = 995;
        $ucet['inbox'] = "INBOX";
        $ucet['login'] = '';
        $ucet['password'] = '';
        $ucet['podatelna'] = 0;
        $ucet['only_signature'] = '';
        $ucet['qual_signature'] = '';
        $config_data['email'][] = $ucet;

        self::ulozNastaveni($config_data);
        $this->flashMessage('Schránka přidána.');
        $this->redirect('default');
    }

    /**
     * 
     * @return ArrayHash
     */
    public static function nactiNastaveni()
    {
        $cfg = (new ConfigEpodatelna())->get();
        return $cfg;
    }

    protected static function ulozNastaveni($config_data)
    {
        (new ConfigEpodatelna())->save($config_data);
    }

}
