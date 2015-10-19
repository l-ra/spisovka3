<?php

use Spisovka\Form;

class Admin_EpodatelnaPresenter extends BasePresenter
{

    private $info;

    public function renderDefault()
    {
        // Klientske nastaveni
        $ep = self::nactiNastaveni();

        // ISDS
        $this->template->n_isds = $ep['isds'];
        $this->template->vice_datovych_schranek = ISDS_Spisovka::vice_datovych_schranek();

        // Email
        if (count($ep['email']) == 0)
            $this->template->n_email = null;
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

            $this->template->n_email = $e_mail;
        }

        // Odeslani
        if (count($ep['odeslani']) > 0) {
            $e_odes = array();
            $typ_odes = array(
                '0' => 'klasicky bez kvalifikovaného podpisu/značky',
                '1' => 's kvalifikovaným podpisem/značky'
            );
            foreach ($ep['odeslani'] as $eo => $odes) {

                $odes['zpusob_odeslani'] = $typ_odes[$odes['typ_odeslani']];
                $e_odes[$eo] = $odes;
            }

            $this->template->n_odeslani = $e_odes;
        } else {
            $this->template->n_odeslani = null;
        }

        // CA
        $esign = new esignature();
        $esign->setCACert(LIBS_DIR . '/email/ca_certifikaty');
        //$this->template->n_ca = $esign->getCA();
        $this->template->n_ca = $esign->getCASimple();
    }

    public function renderDetail()
    {
        $this->template->vice_datovych_schranek = ISDS_Spisovka::vice_datovych_schranek();

        // Klientske nastaveni
        $ep = self::nactiNastaveni();
        $ep = $ep->toArray(); // Je nutne kvuli zpusobu modifikace objektu nastaveni
        
        $id_alter = null;
        $do = $this->getParameter('do');
        if ($do) {
            $id_index = $this->getHttpRequest()->getPost('index');
            $id_typ = $this->getHttpRequest()->getPost('ep_typ', 'i');
            $id_alter = $id_typ . $id_index;
        }


        $id = $this->getParameter('id', $id_alter);
        $typ = substr($id, 0, 1);
        $index = substr($id, 1);

        switch ($typ) {
            case 'i':
                $crt = $ep['isds'][$index]['certifikat'];
                if (file_exists($crt)) {
                    $ep['isds'][$index]['certifikat_stav'] = 1;
                } else {
                    $ep['isds'][$index]['certifikat_stav'] = 0;
                }


                $this->info = @$ep['isds'][$index];
                break;
            case 'e':
                $typ_serveru = array(
                    '' => '',
                    '/pop3/novalidate-cert' => 'POP3',
                    '/pop3/ssl/novalidate-cert' => 'POP3-SSL',
                    '/imap/novalidate-cert' => 'IMAP',
                    '/imap/ssl/novalidate-cert' => 'IMAP+SSL',
                    '/nntp' => 'NNTP'
                );
                @$ep['email'][$index]['protokol'] = $typ_serveru[@$ep['email'][$index]['typ']];

                $this->info = @$ep['email'][$index];
                break;
            
            case 'o':
                $typ_odeslani = array(
                    '0' => 'klasicky bez kvalifikovaného podpisu/značky',
                    '1' => 's kvalifikovaným podpisem/značkou'
                );
                $ep['odeslani'][$index]['typ'] = $typ_odeslani[$ep['odeslani'][$index]['typ_odeslani']];

                if (file_exists($ep['odeslani'][$index]['cert'])) {

                    $esign = new esignature();

                    if (file_exists($ep['odeslani'][$index]['cert_key'])) {
                        $cert_status = $esign->setUserCert($ep['odeslani'][$index]['cert'], $ep['odeslani'][$index]['cert_key'], $ep['odeslani'][$index]['cert_pass']);
                    } else {
                        $cert_status = $esign->setUserCert($ep['odeslani'][$index]['cert'], null, $ep['odeslani'][$index]['cert_pass']);
                    }

                    if ($cert_status) {
                        $ep['odeslani'][$index]['certifikat']['stav'] = 2; // existuje a je to certifikat, ale neni overen

                        $cert_info = $esign->getInfo();
                        if (is_array($cert_info)) {

                            if (($cert_info['platnost_od'] <= time()) && ($cert_info['platnost_do'] >= time())) {
                                $ep['odeslani'][$index]['certifikat']['stav'] = 4; // overen
                            } else {
                                $ep['odeslani'][$index]['certifikat']['stav'] = 3; // vyprsela platnost
                            }
                            $ep['odeslani'][$index]['certifikat']['info'] = $cert_info;
                        }
                    } else {
                        $ep['odeslani'][$index]['certifikat']['stav'] = 1; // existuje, ale neni to certifikat
                    }
                } else {
                    $ep['odeslani'][$index]['certifikat']['stav'] = 0; // neexistuje
                }


                $this->info = @$ep['odeslani'][$index];
                break;
            default: $this->info = null;
                break;
        }

        if (isset($this->info['podatelna']) && !empty($this->info['podatelna'])) {
            $this->info['podatelna'] = Orgjednotka::getName($this->info['podatelna']);
        }

        $this->template->Info = $this->info;
        $this->template->Typ = $typ;
        $this->template->Index = $index;


        // Zmena udaju
        $this->template->FormUpravit = $this->getParameter('upravit', null);
        $this->template->FormHesloISDS = $this->getParameter('zmenit_heslo_isds', null);

        if ($do) {
            $this->template->Index = $this->getHttpRequest()->getPost('index');
            $this->template->Typ = $this->getHttpRequest()->getPost('ep_typ', 'i');

            if ($do == 'zmenit_heslo_isds') {
                $this->template->FormHesloISDS = 1;
            }
        }
    }

    /**
     *
     * Formular a zpracovani pro zmenu udaju org. jednotky
     *
     */
    protected function createComponentNastavitISDSForm()
    {
        $ep = self::nactiNastaveni();

        $id = $this->getParameter('id', null);
        $index = substr($id, 1);
        $isds = !empty($id) ? $ep['isds'][$index] : array();

        $org_select = array('0' => 'Kterákoli podatelna');
        $OrgJednotky = new Orgjednotka();
        if ($orgjednotky_data = $OrgJednotky->seznam())
            foreach ($orgjednotky_data as $oj)
                $org_select[$oj->id] = $oj->ciselna_rada . ' - ' . $oj->zkraceny_nazev;

        $connect_type = array(
            '0' => 'Základní (jménem a heslem)',
            '1' => 'Spisovka (certifikátem)',
            '2' => 'Hostovaná spisovka (certifikátem + jménem a heslem)'
        );

        $form1 = new Nette\Application\UI\Form();
        $form1->addHidden('index');
                
        $form1->addHidden('ep_typ')
                ->setValue('i');
        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?');

        $form1->addSelect('typ_pripojeni', 'Typ přihlášení:', $connect_type);

        $form1->addText('login', 'Přihlašovací jméno od ISDS:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Přihlašovací jméno musí být vyplněno.');
        $form1->addPassword('password', 'Přihlašovací heslo ISDS:', 50, 100);
        // ->setValue($isds['password'])
        // ->addRule(Form::FILLED, 'Přihlašovací heslo musí být vyplněno.');

        $form1->addUpload('certifikat_file', 'Cesta k certifikátu (formát X.509):');
        $form1->addText('cert_pass', 'Heslo k klíči certifikátu:', 50, 100);

        $form1->addSelect('test', 'Režim:',
                ['0' => 'Reálný provoz (mojedatovaschranka.cz)',
                 '1' => 'Testovací režim (czebox.cz)']
        );

        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select);

        if ($isds) {
            $form1['index']->setValue($index);
            $form1['ucet']->setValue($isds['ucet']);
            $form1['aktivni']->setValue($isds['aktivni']);
            $form1['typ_pripojeni']->setValue($isds['typ_pripojeni']);
            $form1['login']->setValue($isds['login']);
            $form1['cert_pass']->setValue($isds['cert_pass']);
            $form1['test']->setValue($isds['test']);
            $form1['podatelna']->setValue($isds['podatelna']);
        }
        
        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitISDSClicked');
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

    public function nastavitISDSClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $chyba = 0;

        $index = $data['index'];

        $config_data = self::nactiNastaveni();

        $data['certifikat'] = "";
        if ($data['typ_pripojeni'] == 1 || $data['typ_pripojeni'] == 2) {
            //nahrani certifikatu
            $upload = $data['certifikat_file'];
            if (!file_exists(CLIENT_DIR . "/configs/files")) {
                mkdir(CLIENT_DIR . "/configs/files");
            }

            if (is_writeable(CLIENT_DIR . "/configs/files")) {
                $fileName = CLIENT_DIR . "/configs/files/certifikat_isds" . $index . ".crt";
                if (!$upload instanceof Nette\Http\FileUpload) {
                    $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
                } else if ($upload->isOk()) {
                    if ($upload->move($fileName)) {
                        $data['certifikat'] = $fileName;
                    } else {
                        $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.', 'warning');
                    }
                } else {
                    switch ($upload->error) {
                        case UPLOAD_ERR_INI_SIZE:
                            $this->flashMessage('Překročena velikost pro nahrání certifikátu.', 'warning');
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

        if ($chyba == 0) {

            $config_data['isds'][$index]['ucet'] = $data['ucet'];
            $config_data['isds'][$index]['aktivni'] = $data['aktivni'];
            $config_data['isds'][$index]['typ_pripojeni'] = $data['typ_pripojeni'];
            $config_data['isds'][$index]['login'] = $data['login'];
            if (!empty($data['password']))
                $config_data['isds'][$index]['password'] = $data['password'];
            if (!empty($data['certifikat']))
                $config_data['isds'][$index]['certifikat'] = $data['certifikat'];
            $config_data['isds'][$index]['cert_pass'] = $data['cert_pass'];
            $config_data['isds'][$index]['test'] = $data['test'];
            $config_data['isds'][$index]['podatelna'] = $data['podatelna'];

            $idbox = "";
            $vlastnik = "";
            $stav = "";
            $chyba = 0;
            try {
                $ISDS = new ISDS_Spisovka();
                if ($ISDS->pripojit($config_data['isds'][$index])) {
                    $info = $ISDS->informaceDS();
                    if (!empty($info)) {

                        $idbox = $info->dbID;
                        if (empty($info->firmName)) {
                            // jmeno prijmeni
                            $vlastnik = $info->pnFirstName . " " . $info->pnLastName . " [" . $info->dbType . "]";
                        } else {
                            // firma urad
                            $vlastnik = $info->firmName . " [" . $info->dbType . "]";
                        }
                        $stav = ISDS_Spisovka::stavDS($info->dbState) . " (kontrolováno dne " . date("j.n.Y G:i") . ")";
                        $stav_hesla = $ISDS->GetPasswordInfo();
                    }
                } else {
                    $this->flashMessage('Nelze se připojit k ISDS! Chyba: ' . $ISDS->error(), "warning");
                    //$this->redirect('this',array('id'=>('i' . $data['index']),'upravit'=>1));
                    $chyba = 1;
                }
            } catch (Exception $e) {
                $this->flashMessage('Nelze se připojit k ISDS! ' . $e->getMessage(), "warning");
                //$this->redirect('this',array('id'=>('i' . $data['index']),'upravit'=>1));
                //$chyba = 1;
            }

            $config_data['isds'][$index]['idbox'] = $idbox;
            $config_data['isds'][$index]['vlastnik'] = $vlastnik;
            $config_data['isds'][$index]['stav'] = $stav;
            $config_data['isds'][$index]['stav_hesla'] = (empty($stav_hesla)) ? "(bez omezení)"
                        : date("j.n.Y G:i", strtotime($stav_hesla));

            self::ulozNastaveni($config_data);

            //if ( $chyba == 0 ) {
            $this->flashMessage('Nastavení datové schránky bylo upraveno.');
            $this->redirect('this', array('id' => ('i' . $data['index'])));
            //}
        }
    }

    function ruleContains($item, $args)
    {
        return (strpos($item->value, $args) !== false);
    }

    function ruleNoEqual($item, $args)
    {
        return (strpos($item->value, $args) !== false);
    }

    protected function createComponentZmenitHesloISDSForm()
    {
        $id = $this->getParameter('id', null);
        $index = substr($id, 1);

        $id = $this->getParameter('id', null);
        $index = substr($id, 1);

        $form = new Nette\Application\UI\Form();
        $form->addHidden('index')
                ->setValue($index);
        $form->addHidden('zmenit_heslo_isds')
                ->setValue(1);
        $form->addHidden('ep_typ')
                ->setValue('i');

        $form->addPassword('password', 'Přihlašovací heslo ISDS:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addRule(Nette\Forms\Form::MIN_LENGTH, 'Heslo do datové schránky musí být minimálně %d znaků dlouhé.', 8)
                ->addRule(Nette\Forms\Form::MAX_LENGTH, 'Heslo do datové schránky musí být maximálně %d znaků dlouhé.', 32)
        /* ->addRule(callback($this, 'ruleNoEqual'),'Heslo mesmí obsahovat id (login) uživatele, jemuž se heslo mění.',$isds['login'])
          ->addRule(callback($this, 'ruleNoEqual'),'Heslo se nesmí shodovat s původním heslem.',$isds['password'])
          ->addRule(callback($this, 'ruleContains'),'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','qwert')
          ->addRule(callback($this, 'ruleContains'),'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','asdgf')
          ->addRule(callback($this, 'ruleContains'),'Heslo nesmí začínat na "qwert", "asdgf", "12345"!','12345')
         */;

        $form->addPassword('password_confirm', 'Přihlašovací heslo ještě jednou:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné. Pokud nechcete změnit heslo, klikněte na tlačítko zrušit.')
                ->addConditionOn($form["password"], Nette\Forms\Form::FILLED)
                ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat !", $form["password"]);

        $form->addSubmit('zmenit', 'Změnit heslo')
                ->onClick[] = array($this, 'zmenitHesloISDSClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function zmenitHesloISDSClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        //echo "<pre>Data: "; print_r($data); echo "</pre>"; exit;

        $chyba = 0;

        $index = $data['index'];

        $config_data = self::nactiNastaveni();

        $old_pass = $config_data['isds'][$index]['password'];

        if ($chyba == 0) {

            $idbox = "";
            $vlastnik = "";
            $stav = "";
            try {
                $ISDS = new ISDS_Spisovka();
                if ($ISDS->pripojit($config_data['isds'][$index])) {

                    // zmena hesla
                    if ($ISDS->ChangeISDSPassword($old_pass, $data['password'])) {
                        //if ( false ) {

                        $info = $ISDS->informaceDS();
                        if (!empty($info)) {

                            $idbox = $info->dbID;
                            if (empty($info->firmName)) {
                                // jmeno prijmeni
                                $vlastnik = $info->pnFirstName . " " . $info->pnLastName . " [" . $info->dbType . "]";
                            } else {
                                // firma urad
                                $vlastnik = $info->firmName . " [" . $info->dbType . "]";
                            }
                            $stav = ISDS_Spisovka::stavDS($info->dbState) . " (kontrolováno dne " . date("j.n.Y G:i") . ")";
                            $stav_hesla = $ISDS->GetPasswordInfo();
                        }

                        $config_data['isds'][$index]['password'] = $data['password'];
                        $config_data['isds'][$index]['idbox'] = $idbox;
                        $config_data['isds'][$index]['vlastnik'] = $vlastnik;
                        $config_data['isds'][$index]['stav'] = $stav;
                        $config_data['isds'][$index]['stav_hesla'] = (empty($stav_hesla)) ? "(bez omezení)"
                                    : date("j.n.Y G:i", strtotime($stav_hesla));

                        self::ulozNastaveni($config_data);

                        $this->flashMessage('Heslo k datové schránky bylo úspěšně změněno.');
                        $this->redirect(':Admin:Epodatelna:detail', array('id' => ('i' . $data['index'])));
                    } else {
                        $this->flashMessage('Heslo k datové schránky se nepodařilo změnit.', 'warning');
                        $this->flashMessage('Chyba ISDS: ' . $ISDS->error(), 'warning');
                        //$this->redirect(':Admin:Epodatelna:detail',array('id'=>('i' . $data['index']),'zmenit_heslo_isds'=>'1' ));
                    }
                } else {
                    $this->flashMessage('Nelze se připojit k ISDS! Chyba: ' . $ISDS->error(), "warning");
                    $this->redirect(':Admin:Epodatelna:detail', array('id' => ('i' . $data['index'])));
                }
            } catch (Exception $e) {
                $this->flashMessage('Při pokusu o změnu hesla došlo k chybě: '. $e->getMessage(), "warning");
            }
        }
    }

    protected function createComponentNastavitEmailForm()
    {
        $ep = self::nactiNastaveni();

        $id = $this->getParameter('id', null);
        $index = substr($id, 1);
        $email = !empty($id) ? $ep['email'][$index] : array();

        $org_select = array('0' => 'Kterákoli podatelna');
        $OrgJednotky = new Orgjednotka();
        if ($orgjednotky_data = $OrgJednotky->seznam())
            foreach ($orgjednotky_data as $oj)
                $org_select[$oj->id] = $oj->ciselna_rada . ' - ' . $oj->zkraceny_nazev;

        $typ_serveru = array(
            '/pop3/novalidate-cert' => 'POP3',
            '/pop3/ssl/novalidate-cert' => 'POP3-SSL',
            '/imap/novalidate-cert' => 'IMAP',
            '/imap/ssl/novalidate-cert' => 'IMAP+SSL',
        );

        $form1 = new Nette\Application\UI\Form();
        $form1->addHidden('index');
        $form1->addHidden('ep_typ')
                ->setValue('e');

        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?');
        $form1->addSelect('typ', 'Protokol:', $typ_serveru)
                ->addRule(Nette\Forms\Form::FILLED, 'Vyberte protokol pro připojení k emailové schránce.');
        $form1->addText('server', 'Adresa serveru:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Adresa poštovního serveru musí být vyplněna.');
        $form1->addText('port', 'Port:', 5, 50)
                ->addRule(Nette\Forms\Form::FILLED, 'Port serveru musí být vyplněno.');
        $form1->addText('inbox', 'Složka:', 50, 100);

        $form1->addText('login', 'Přihlašovací jméno:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Přihlašovací jméno musí být vyplněno.');
        $form1->addPassword('password', 'Přihlašovací heslo:', 50, 100);
        // ->setValue($email['password'])
        // ->addRule(Form::FILLED, 'Přihlašovací heslo musí být vyplněno.');

        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select);

        $form1->addCheckbox('only_signature', 'přijímat pouze emaily s elektronickým podpisem/značkou');
        $form1->addCheckbox('qual_signature', 'přijímat pouze emaily s uznávaným elektronickým podpisem/značkou');

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

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

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

        $this->flashMessage('Nastavení emailové schránky bylo upraveno.');
        $this->redirect('this', array('id' => ('e' . $data['index'])));
    }

    protected function createComponentNastavitOdesForm()
    {
        $ep = self::nactiNastaveni();

        $id = $this->getParameter('id', null);
        $typ = substr($id, 0, 1);
        $index = substr($id, 1);
        $odes = !empty($id) ? $ep['odeslani'][$index] : array();

        $form1 = new Form();
        $form1->addHidden('index');
        $form1->addHidden('ep_typ')
                ->setValue($typ);
        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?');
        $form1->addSelect('typ_odeslani', 'Jak odesílat:',
                ['0' => 'klasicky bez kvalifikovaného podpisu/značky',
                 '1' => 's kvalifikovaným podpisem/značkou'
                ]
        );

        $form1->addText('email', 'Emailová adresa odesilatele:', 50, 100)
                ->addCondition(Form::FILLED)
                    ->addRule(Form::EMAIL);

        $form1->addUpload('cert_file', 'Cesta k certifikátu:');
        $form1->addUpload('cert_key_file', 'Cesta k privátnímu klíči:');
        $form1->addText('cert_pass', 'Heslo k klíči certifikátu:', 50, 100);

        if ($odes) {
            $form1['index']->setValue($index);
            $form1['ucet']->setValue($odes['ucet']);
            $form1['aktivni']->setValue($odes['aktivni']);
            $form1['typ_odeslani']->setValue($odes['typ_odeslani']);
            $form1['email']->setValue($odes['email']);
            $form1['cert_pass']->setValue($odes['cert_pass']);
        }

        $form1->addSubmit('upravit', 'Uložit')
                ->onClick[] = array($this, 'nastavitOdesClicked');
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

    public function nastavitOdesClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $index = $data['index'];

        $config_data = self::nactiNastaveni();

        $data['cert'] = "";
        //nahrani certifikatu
        $upload = $data['cert_file'];
        if (!file_exists(CLIENT_DIR . '/configs/files')) {
            mkdir(CLIENT_DIR . '/configs/files');
        }

        $chyba_pri_uploadu = 0;
        $fileName = CLIENT_DIR . "/configs/files/certifikat_email_" . $index . ".crt";
        if (!$upload instanceof Nette\Http\FileUpload) {
            $this->flashMessage('Certifikát se nepodařilo nahrát.', 'warning');
        } else if ($upload->isOk()) {
            try {
                $upload->move($fileName);
                $data['cert'] = $fileName;
            } catch (Exception $e) {
                $chyba_pri_uploadu = 1;
                $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.', 'warning');
            }
        } else {
            switch ($upload->error) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->flashMessage('Překročena velikost pro nahrání certifikátu.', 'warning');
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

        $data['cert_key'] = "";
        //nahrani privatniho klice
        $upload = $data['cert_key_file'];
        {
            $fileName = CLIENT_DIR . "/configs/files/certifikat_email_" . $index . ".key";
            if (!$upload instanceof Nette\Http\FileUpload) {
                $this->flashMessage('Soubor privátního klíče se nepodařilo nahrát.', 'warning');
            } else if ($upload->isOk()) {
                try {
                    $upload->move($fileName);
                    $data['cert_key'] = $fileName;
                } catch (Exception $e) {
                    $chyba_pri_uploadu = 1;
                    $this->flashMessage('Soubor privátního klíče se nepodařilo přenést na cílové místo.', 'warning');
                }
            } else {
                switch ($upload->error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $this->flashMessage('Překročena velikost pro nahrání souboru privátního klíče.', 'warning');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                        break;
                    default:
                        $this->flashMessage('Soubor privátního klíče se nepodařilo nahrát.', 'warning');
                        break;
                }
            }
        }
        unset($data['cert_key_file']);

        if ($chyba_pri_uploadu && !is_writeable(CLIENT_DIR . '/configs/files'))
            $this->flashMessage('Nemohu zapisovat do adresáře client/configs/files/.', 'warning');

        $config_data['odeslani'][$index]['ucet'] = $data['ucet'];
        $config_data['odeslani'][$index]['aktivni'] = $data['aktivni'];
        $config_data['odeslani'][$index]['typ_odeslani'] = $data['typ_odeslani'];
        $config_data['odeslani'][$index]['email'] = $data['email'];

        if (!empty($data['cert'])) {
            $config_data['odeslani'][$index]['cert'] = $data['cert'];
            $config_data['odeslani'][$index]['cert_key'] = $data['cert_key'];
        }
        $config_data['odeslani'][$index]['cert_pass'] = $data['cert_pass'];

        self::ulozNastaveni($config_data);

        $this->flashMessage('Nastavení odeslání emailu bylo upraveno.');
        $this->redirect('this', array('id' => ('o' . $data['index'])));
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $this->redirect('this', array('id' => ($data['ep_typ'] . $data['index'])));
    }

    public function actionSmazat()
    {
        $id_schranky = $this->getParameter('id');

        $config_data = self::nactiNastaveni();

        $por_cislo = substr($id_schranky, 1);
        if (substr($id_schranky, 0, 1) == 'i')
            unset($config_data['isds'][$por_cislo]);
        else if (substr($id_schranky, 0, 1) == 'e')
            unset($config_data['email'][$por_cislo]);

        self::ulozNastaveni($config_data);
        $this->flashMessage('Schránka byla smazána.');
        $this->redirect('default');
    }

    public function actionNovaschranka()
    {
        $typ = $this->getParameter('typ', 'e');
        $config_data = self::nactiNastaveni();
        $config_data = $config_data->toArray();

        if ($typ == 'i') {
            $index = 0;
            foreach ($config_data['isds'] as $i => $val)
                $index = max($index, $i);
            $index++;

            $config_data['isds'][$index]['ucet'] = 'Nová datová schránka';
            $config_data['isds'][$index]['aktivni'] = false;
            $config_data['isds'][$index]['idbox'] = '';
            $config_data['isds'][$index]['vlastnik'] = '';
            $config_data['isds'][$index]['stav'] = '';
            $config_data['isds'][$index]['typ_pripojeni'] = 0;
            $config_data['isds'][$index]['login'] = '';
            $config_data['isds'][$index]['password'] = '';
            $config_data['isds'][$index]['certifikat'] = '';
            $config_data['isds'][$index]['cert_pass'] = '';
            $config_data['isds'][$index]['test'] = 0;
            $config_data['isds'][$index]['podatelna'] = 0;
            $config_data['isds'][$index]['stav_hesla'] = '';
        } else {
            $index = 0;
            foreach ($config_data['email'] as $i => $val)
                $index = max($index, $i);
            $index++;

            $config_data['email'][$index]['ucet'] = 'Nová emailová schránka';
            $config_data['email'][$index]['aktivni'] = false;
            $config_data['email'][$index]['typ'] = "/pop3/ssl/novalidate-cert";
            $config_data['email'][$index]['server'] = '';
            $config_data['email'][$index]['port'] = 995;
            $config_data['email'][$index]['inbox'] = "INBOX";
            $config_data['email'][$index]['login'] = '';
            $config_data['email'][$index]['password'] = '';
            $config_data['email'][$index]['podatelna'] = 0;
            $config_data['email'][$index]['only_signature'] = '';
            $config_data['email'][$index]['qual_signature'] = '';
        }

        self::ulozNastaveni($config_data);
        $this->flashMessage('Schránka přidána.');
        $this->redirect('detail', array('id' => "$typ$index"));
    }

    /**
     * 
     * @return Spisovka\ArrayHash
     */
    public static function nactiNastaveni()
    {
        $res = (new Spisovka\ConfigEpodatelna())->get();

        // oprav boolean hodnoty z konfiguracniho souboru
        // kvuli bugu v parse_ini_file()
        $i = reset($res->isds);
        $i->aktivni = (bool) $i->aktivni;
        $o = reset($res->odeslani);
        $o->aktivni = (bool) $o->aktivni;
        foreach ($res->email as $e) {
            $e->aktivni = (bool) $e->aktivni;
            $e->only_signature = (bool) $e->only_signature;
            $e->qual_signature = (bool) $e->qual_signature;
        }

        return $res;
    }

    protected static function ulozNastaveni($config_data)
    {
        (new Spisovka\ConfigEpodatelna())->save($config_data);
    }

}
