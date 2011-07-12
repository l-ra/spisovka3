<?php

class Admin_EpodatelnaPresenter extends BasePresenter
{

    private $info;

    public function renderDefault()
    {
        // Klientske nastaveni
        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        // ISDS
        $this->template->n_isds = $ep['isds'];

        // Email
        if ( count($ep['email'])>0 ) {
            $e_mail = array();

            $typ_serveru = array(
                            ''=>'',
                            '/pop3/novalidate-cert'=>'POP3',
                            '/pop3/ssl/novalidate-cert'=>'POP3-SSL',
                            '/imap/novalidate-cert'=>'IMAP',
                            '/imap/ssl/novalidate-cert'=>'IMAP+SSL',
                            '/nntp'=>'NNTP'
                );
            foreach ($ep['email'] as $ei => $email) {
                $email['protokol'] = $typ_serveru[ $email['typ'] ];
                $e_mail[$ei] = $email;
            }

            $this->template->n_email = $e_mail;
        } else {
            $this->template->n_email = null;
        }

        // Odeslani
        if ( count($ep['odeslani'])>0 ) {
            $e_odes = array();
            $typ_odes = array(
                          '0'=>'klasicky bez kvalifikovaného podpisu/značky',
                          '1'=>'s kvalifikovaným podpisem/značky'
                );
            foreach ($ep['odeslani'] as $eo => $odes) {

                $odes['zpusob_odeslani'] = $typ_odes[ $odes['typ_odeslani'] ];
                $e_odes[$eo] = $odes;
            }

            $this->template->n_odeslani = $e_odes;
        } else {
            $this->template->n_odeslani = null;
        }

        // CA
        $esign = new esignature();
        $esign->setCACert(LIBS_DIR .'/email/ca_certifikaty');
        $this->template->n_ca = $esign->getCA();

    }

    public function renderDetail()
    {

        // Klientske nastaveni
        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1);

        switch ($typ) {
            case 'i': 
                $crt = $ep['isds'][$index]['certifikat'];
                if ( file_exists($crt) ) {
                    $ep['isds'][$index]['certifikat']['stav'] = 1;
                } else {
                    $ep['isds'][$index]['certifikat']['stav'] = 0;
                }
                

                $this->info = @$ep['isds'][$index];
                break;
            case 'e': 
                $typ_serveru = array(
                                ''=>'',
                                '/pop3/novalidate-cert'=>'POP3',
                                '/pop3/ssl/novalidate-cert'=>'POP3-SSL',
                                '/imap/novalidate-cert'=>'IMAP',
                                '/imap/ssl/novalidate-cert'=>'IMAP+SSL',
                                '/nntp'=>'NNTP'
                    );
                @$ep['email'][$index]['protokol'] = $typ_serveru[ @$ep['email'][$index]['typ'] ];

                $this->info = @$ep['email'][$index];
                break;
            case 'o': 

                $typ_odeslani = array(
                        '0'=>'klasicky bez kvalifikovaného podpisu/značky',
                        '1'=>'s kvalifikovaným podpisem/značkou'
                );
                $ep['odeslani'][$index]['typ'] = $typ_odeslani[ $ep['odeslani'][$index]['typ_odeslani'] ];

                if ( file_exists($ep['odeslani'][$index]['cert']) ) {

                    $esign = new esignature();

                    if ( file_exists($ep['odeslani'][$index]['cert_key']) ) {
                        $cert_status = $esign->setUserCert($ep['odeslani'][$index]['cert'], $ep['odeslani'][$index]['cert_key'], $ep['odeslani'][$index]['cert_pass']);
                    } else {
                        $cert_status = $esign->setUserCert($ep['odeslani'][$index]['cert'], null, $ep['odeslani'][$index]['cert_pass']);
                    }

                    if ( $cert_status ) {
                        $ep['odeslani'][$index]['certifikat']['stav'] = 2; // existuje a je to certifikat, ale neni overen

                        $cert_info = $esign->getInfo();
                        if ( is_array($cert_info) ) {

                            if ( ($cert_info['platnost_od'] <= time()) && ($cert_info['platnost_do'] >= time()) ) {
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
            default: $this->info = null; break;
        }

        $this->template->Info = $this->info;
        $this->template->Typ = $typ;
        $this->template->Index = $index;


        // Zmena udaju
        $this->template->FormUpravit = $this->getParam('upravit',null);

    }


    public function renderTest()
    {

        // Klientske nastaveni
        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1);

        $typ_array = array('i'=>'isds','e'=>'email','o'=>'odeslani');
        $typ = $typ_array[ $typ ];

        if ( isset($ep[$typ][$index]) ) {
            $config = $ep[$typ][$index];

            if ( $typ == 'isds' ) {
                $this->testISDS($config);
            } else if ( $typ == 'email' ) {
                $this->testEmail($config);
            } else if ( $typ == 'odeslani' ) {

            }

        } else {
            // nastaveni neexistuje
            echo "CHYBA!!! Požadované nastavení neexistuje!";
        }



        $this->terminate();

    }

    private function testISDS($config)
    {

        echo "<pre>";
        $isds_debug = 1;
        $isds = new ISDS_Spisovka();

        Debug::dump($config); //exit;

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH,false);
	curl_setopt($ch, CURLOPT_NOBODY,false);

	if (!empty($config['login'])) {
            curl_setopt($ch, CURLOPT_USERPWD,$config['login'].":".$config['password']);
	}

	// na Linuxu nastavit verzi 3, na Windows ne !
	if (stristr(PHP_OS,'WIN') === false) {
            curl_setopt($ch, CURLOPT_SSLVERSION,3);
	}

        $cert_data = file_get_contents($config['certifikat']);
        openssl_pkcs12_read($cert_data,$tmp_cert,$config['cert_pass']);
        file_put_contents(CLIENT_DIR ."/temp/cert_use.crt", $tmp_cert['cert']);
        file_put_contents(CLIENT_DIR ."/temp/cert_key.crt", $tmp_cert['pkey']);

        curl_setopt($ch, CURLOPT_SSLCERT, realpath(CLIENT_DIR ."/temp/cert_use.crt"));
        curl_setopt($ch, CURLOPT_SSLKEY, realpath(CLIENT_DIR ."/temp/cert_key.crt"));


        //curl_setopt($ch, CURLOPT_SSLCERT,$params['local_cert']);
        //curl_setopt($ch, CURLOPT_SSLCERTPASSWD,$params['passphrase']);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // kasle se na https certy

        $url = "https://ws1c.czebox.cz/certds/DS/dz";
        //$url = "https://bp/ssl/index.php";

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
            "    <soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\n".
            "  <soap:Body>\n".
            "    <DummyOperation xmlns=\"http://isds.czechpoint.cz\">\n".
            "    </DummyOperation>\n".
            "  </soap:Body>\n".
            "</soap:Envelope>\n");

        echo ">>>> CURL_EXEC \n";
        $response = curl_exec($ch);
        echo ">>>>    CurlErrNo: ". curl_errno($ch) ."\n";
        echo ">>>>    CurlError: ". curl_error($ch) ."\n";
        echo ">>>>    response: ". $response ."\n";


        /* ********** */



/*
        if ( $ISDSBox = $isds->pripojit($config) ) {

            echo "Připojeno k ISDS \n\n";

            $isds_info = $isds->informaceDS();
            print_r($isds_info);



        } else {

            echo "Připojení k ISDS selhalo <br />";
            echo $isds->error();
        }*/
        echo "</pre>";

    }

    private function testEmail($config)
    {
        
        $imap = new ImapClient();
        $email_mailbox = '{'. $config['server'] .':'. $config['port'] .''. $config['typ'] .'}'. $config['inbox'];

        $imap_connect = $imap->connect($email_mailbox,$config['login'],$config['password']);
        if ($imap_connect) {

            echo "<pre>";
            echo "Spojení se serverem '". $config['server'] ."' úspěšně navázáno.\n\n";

            if ( $pocet = $imap->count_messages() ) {
                echo "Nalezeno ". $pocet ." emailových zpráv.\n\n";

                $seznam = $imap->get_head_messages();
                $i=0;
                foreach ($seznam as $mess) {
                    
                    echo "\n\n==============================================\n\n";
                    echo "<strong>". $mess->id_part ." - ". htmlspecialchars($mess->subject) ."</strong>\n";
                    echo "   od ". htmlspecialchars($mess->from_address) ."\n";
                    echo "   pro ". htmlspecialchars($mess->to_address) ."\n";
                    echo "   ze dne ". date("j.n.Y G:i:s", $mess->udate) ."\n";
                    echo "   velikost ". number_format($mess->size,2,',',' ') ." bytů\n";
                    echo "\n";
                    
                    if ( $config['only_signature'] == true ) {
                        $mess = $imap->get_message($mess->id_part);
                        // pouze podepsane - obsahuje el.podpis
                        if ( count($mess->signature)>0 ) {
                            echo "   >> obsahuje epodpis\n";
                            if ( $config['qual_signature'] == true ) {
                                // pouze kvalifikovane
                                $esign = new esignature();
                                $esign->setCACert(LIBS_DIR .'/email/ca_certifikaty');
                                
                                $tmp_email = CLIENT_DIR .'/temp/emailtest_'. sha1($mess->message_id).'.tmp';
                                file_put_contents($tmp_email,$mess->source);
                                $esigned = $esign->verifySignature($tmp_email, $esign_cert, $esign_status);
                                if ( @$esigned['cert_info']['CA_is_qualified'] == 1 ) {
                                    echo "   >> email obsahuje kvalifikovaný podpis\n";
                                } else {
                                    echo "   >> email neobsahuje kvalifikovaný epodpis\n";
                                }
                            }
                        } else {
                            echo "   >> email není podepsaný\n";
                        }
                    }
                    
                    //print_r($mess);
                    
                    //if ( $i == 10 ) break;
                    $i++;
                }
                //print_r($seznam);
            } else {
                echo " Ve schránce nejsou žádné zprávy k přijetí.\n\n";
            }

            echo "</pre>";

        } else {
            echo 'nespojeno';
        }
    }

/**
 *
 * Formular a zpracovani pro zmenu udaju org. jednotky
 *
 */

    protected function createComponentNastavitISDSForm()
    {

        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1);
        $isds = $ep['isds'][$index];

        $org_select = array('0'=>'Kterákoli podatelna');
        $OrgJednotky = new Orgjednotka();
        if ( $orgjednotky_data = $OrgJednotky->seznam() ) {
            
        }

        $connect_type = array(
            '0' => 'Základní (jménem a heslem)',
            '1' => 'Spisovka (certifikátem)',
            '2' => 'Hostovaná spisovka (certifikátem + jménem a heslem)'
        );


        $form1 = new AppForm();
        $form1->addHidden('index')
                ->setValue($index);
        $form1->addHidden('ep_typ')
                ->setValue('i');
        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->setValue($isds['ucet'])
                ->addRule(Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?')
                ->setValue($isds['aktivni']);

        $form1->addSelect('typ_pripojeni', 'Typ přihlášení:', $connect_type)
                ->setValue($isds['typ_pripojeni']);


        $form1->addText('login', 'Přihlašovací jméno od ISDS:', 50, 100)
                ->setValue($isds['login'])
                ->addRule(Form::FILLED, 'Přihlašovací jméno musí být vyplněno.');
        $form1->addText('password', 'Přihlašovací heslo ISDS:', 50, 100)
                ->setValue($isds['password'])
                ->addRule(Form::FILLED, 'Přihlašovací heslo musí být vyplněno.');

        $form1->addFile('certifikat_file', 'Cesta k certifikátu (formát X.509):');
        $form1->addText('cert_pass', 'Heslo k klíči certifikátu:', 50, 100)
                ->setValue($isds['cert_pass']);

        $form1->addSelect('test', 'Režim:', array('0'=>'Realný provoz (mojedatovaschranka.cz)',
                                                  '1'=>'Testovací režim (czebox.cz)'
                                            )
                )->setValue($isds['test']);

        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select)
                ->setValue($isds['podatelna']);

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

    public function nastavitISDSClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $chyba = 0;

        $index = $data['index'];

        $config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $config_data = $config->toArray();

        $data['certifikat'] = "";
        if ( $data['typ_pripojeni'] == 1 || $data['typ_pripojeni'] == 2 ) {
            //nahrani certifikatu
            $upload = $data['certifikat_file'];
            if ( !file_exists(CLIENT_DIR ."/configs/files") ) {
                mkdir(CLIENT_DIR ."/configs/files");
            }

            if ( is_writeable(CLIENT_DIR ."/configs/files") ) {
                $fileName = CLIENT_DIR ."/configs/files/certifikat_isds". $index .".crt";
                if (!$upload instanceof HttpUploadedFile) {
                    $this->flashMessage('Certifikát se nepodařilo nahrát.','warning');
                } else if ( $upload->isOk() ) {
                    if ( $upload->move($fileName) ) {
                        $data['certifikat'] = $fileName;
                    } else {
                        $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.','warning');
                    }

                } else {
                    switch ($upload->error) {
                        case UPLOAD_ERR_INI_SIZE:
                            $this->flashMessage('Překročena velikost pro nahrání certifikátu.','warning');
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                            break;
                        default:
                            $this->flashMessage('Certifikát se nepodařilo nahrát.','warning');
                        break;
                    }
                }
            } else {
                // nelze nahrat
                $this->flashMessage('Certifikát nelze nahrát na cílové místo.','warning');
            }

        }
        unset($data['certifikat_file']);

        if ( $chyba == 0 ) {

            $config_data['isds'][$index]['ucet'] = $data['ucet'];
            $config_data['isds'][$index]['aktivni'] = $data['aktivni'];
            $config_data['isds'][$index]['typ_pripojeni'] = $data['typ_pripojeni'];
            $config_data['isds'][$index]['login'] = $data['login'];
            $config_data['isds'][$index]['password'] = $data['password'];
            if ( !empty($data['certifikat']) ) {
                $config_data['isds'][$index]['certifikat'] = $data['certifikat'];
            }
            $config_data['isds'][$index]['cert_pass'] = $data['cert_pass'];
            $config_data['isds'][$index]['test'] = $data['test'];
            $config_data['isds'][$index]['podatelna'] = $data['podatelna'];

            $idbox = "";
            $vlastnik = "";
            $stav = "";
            $chyba = 0;
            try {
                $ISDS = new ISDS_Spisovka();
                if ( $ISDS->pripojit($config_data['isds'][$index]) ) {
                    $info = $ISDS->informaceDS();
                    if ( !empty($info) ) {

                        $idbox = $info->dbID;
                        if ( empty($info->firmName) ) {
                            // jmeno prijmeni
                            $vlastnik = $info->pnFirstName ." ". $info->pnLastName ." [".$info->dbType."]";
                        } else {
                            // firma urad
                            $vlastnik = $info->firmName ." [".$info->dbType."]";
                        }
                        $stav = ISDS_Spisovka::stavDS($info->dbState) ." (kontrolováno dne ". date("j.n.Y G:i") .")";
                        $stav_hesla = $ISDS->GetPasswordInfo();
                    }
                } else {
                    $this->flashMessage('Nelze se připojit k ISDS! Chyba: '. $ISDS->error(),"warning");
                    //$this->redirect('this',array('id'=>('i' . $data['index']),'upravit'=>1));
                    $chyba = 1;
                }
            } catch (Exception $e) {
                $this->flashMessage('Nelze se připojit k ISDS! '. $e->getMessage(),"warning");
                //$this->redirect('this',array('id'=>('i' . $data['index']),'upravit'=>1));
                //$chyba = 1;
            }

            $config_data['isds'][$index]['idbox'] = $idbox;
            $config_data['isds'][$index]['vlastnik'] = $vlastnik;
            $config_data['isds'][$index]['stav'] = $stav;
            $config_data['isds'][$index]['stav_hesla'] = (empty($stav_hesla))?"(bez omezení)":$stav_hesla;

            $config_modify = new Config();
            $config_modify->import($config_data);
            $config_modify->save(CLIENT_DIR .'/configs/epodatelna.ini');

            Environment::setVariable('epodatelna_config', $config_modify);

            //if ( $chyba == 0 ) {
                $this->flashMessage('Nastavení datové schránky bylo upraveno.');
                $this->redirect('this',array('id'=>('i' . $data['index']) ));
            //}
        }
    }

    protected function createComponentNastavitEmailForm()
    {

        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1);
        $email = $ep['email'][$index];

        $org_select = array('0'=>'Kterákoli podatelna');
        $OrgJednotky = new Orgjednotka();
        if ( $orgjednotky_data = $OrgJednotky->seznam() ) {

        }

        $typ_serveru = array(
            '/pop3/novalidate-cert'=>'POP3',
            '/pop3/ssl/novalidate-cert'=>'POP3-SSL',
            '/imap/novalidate-cert'=>'IMAP',
            '/imap/ssl/novalidate-cert'=>'IMAP+SSL',
            '/nntp'=>'NNTP'
        );

        $form1 = new AppForm();
        $form1->addHidden('index')
                ->setValue($index);
        $form1->addHidden('ep_typ')
                ->setValue('e');

        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->setValue($email['ucet'])
                ->addRule(Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?')
                ->setValue($email['aktivni']);
        $form1->addSelect('typ', 'Protokol:', $typ_serveru)
                ->setValue($email['typ'])
                ->addRule(Form::FILLED, 'Vyberte protokol pro připojení k emailové schránce.');
        $form1->addText('server', 'Adresa serveru:', 50, 100)
                ->setValue($email['server'])
                ->addRule(Form::FILLED, 'Adresa poštovního serveru musí být vyplněna.');
        $form1->addText('port', 'Port:', 50, 100)
                ->setValue($email['port'])
                ->addRule(Form::FILLED, 'Port serveru musí být vyplněno.');
        $form1->addText('inbox', 'Složka:', 50, 100)
                ->setValue($email['inbox']);

        $form1->addText('login', 'Přihlašovací jméno:', 50, 100)
                ->setValue($email['login'])
                ->addRule(Form::FILLED, 'Přihlašovací jméno musí být vyplněno.');
        $form1->addText('password', 'Přihlašovací heslo:', 50, 100)
                ->setValue($email['password'])
                ->addRule(Form::FILLED, 'Přihlašovací heslo musí být vyplněno.');


        $form1->addSelect('podatelna', 'Podatelna pro příjem:', $org_select)
                ->setValue($email['podatelna']);

        $form1->addCheckbox('only_signature', 'přijímat pouze emaily s připojeným e-podpisem')
                ->setValue($email['only_signature']);
        $form1->addCheckbox('qual_signature', 'přijímat pouze emaily s ověřeným kvalifikovaným podpisem/značkou')
                ->setValue($email['qual_signature']);


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

    public function nastavitEmailClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $index = $data['index'];

        $config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $config_data = $config->toArray();

        $config_data['email'][$index]['ucet'] = $data['ucet'];
        $config_data['email'][$index]['aktivni'] = $data['aktivni'];
        $config_data['email'][$index]['typ'] = $data['typ'];
        $config_data['email'][$index]['server'] = $data['server'];
        $config_data['email'][$index]['port'] = $data['port'];
        $config_data['email'][$index]['inbox'] = $data['inbox'];
        $config_data['email'][$index]['login'] = $data['login'];
        $config_data['email'][$index]['password'] = $data['password'];
        $config_data['email'][$index]['podatelna'] = $data['podatelna'];
        $config_data['email'][$index]['only_signature'] = $data['only_signature'];
        $config_data['email'][$index]['qual_signature'] = $data['qual_signature'];

        //Debug::dump($config_data); exit;
        $config_modify = new Config();
        $config_modify->import($config_data);
        $config_modify->save(CLIENT_DIR .'/configs/epodatelna.ini');

        Environment::setVariable('epodatelna_config', $config_modify);

        $this->flashMessage('Nastavení emailové schránky byly upraveny.');
        $this->redirect('this',array('id'=>('e' . $data['index']) ));
    }

    protected function createComponentNastavitOdesForm()
    {

        $ep_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $ep = $ep_config->toArray();

        $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1);
        $odes = $ep['odeslani'][$index];

        $form1 = new AppForm();
        $form1->addHidden('index')
                ->setValue($index);
        $form1->addHidden('ep_typ')
                ->setValue($typ);
        $form1->addText('ucet', 'Název účtu:', 50, 100)
                ->setValue($odes['ucet'])
                ->addRule(Form::FILLED, 'Název účtu musí být vyplněno.');
        $form1->addCheckbox('aktivni', ' aktivní účet?')
                ->setValue($odes['aktivni']);
        $form1->addSelect('typ_odeslani', 'Jak odesílat:', array('0'=>'klasicky bez kvalifikovaného podpisu/značky',
                                                  '1'=>'s kvalifikovaným podpisem/značkou'
                                            )
                )->setValue($odes['typ_odeslani']);

        $form1->addText('email', 'Emailová adresa odesilatele:', 50, 100)
                ->setValue($odes['email']);

        $form1->addFile('cert_file', 'Cesta k certifikátu:');
        $form1->addFile('cert_key_file', 'Cesta k privátnímu klíči:');
        $form1->addText('cert_pass', 'Heslo k klíči certifikátu:', 50, 100)
                ->setValue($odes['cert_pass']);


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

    public function nastavitOdesClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $index = $data['index'];

        $config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $config_data = $config->toArray();

        $data['cert'] = "";
        //nahrani certifikatu
        $upload = $data['cert_file'];
        if ( !file_exists(CLIENT_DIR .'/configs/files') ) {
            mkdir(CLIENT_DIR .'/configs/files');
        }

        if ( is_writeable(CLIENT_DIR .'/configs/files') ) {
            $fileName = CLIENT_DIR ."/configs/files/certifikat_email_". $index .".crt";
            if (!$upload instanceof HttpUploadedFile) {
                $this->flashMessage('Certifikát se nepodařilo nahrát.','warning');
            } else if ( $upload->isOk() ) {
                if ( $upload->move($fileName) ) {
                    $data['cert'] = $fileName;
                } else {
                    $this->flashMessage('Certifikát se nepodařilo přenést na cílové místo.','warning');
                }
            } else {
                switch ($upload->error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $this->flashMessage('Překročena velikost pro nahrání certifikátu.','warning');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                        break;
                    default:
                        $this->flashMessage('Certifikát se nepodařilo nahrát.','warning');
                    break;
                }
            }
        } else {
            // nelze nahrat
            $this->flashMessage('Certifikát nelze nahrát na cílové místo.','warning');
        }
        unset($data['cert_file']);

        $data['cert_key'] = "";
        //nahrani privatniho klice
        $upload = $data['cert_key_file'];

        if ( is_writeable(CLIENT_DIR .'/configs/files') ) {
            $fileName = CLIENT_DIR ."/configs/files/certifikat_email_". $index .".key";
            if (!$upload instanceof HttpUploadedFile) {
                $this->flashMessage('Soubor privátního klíče se nepodařilo nahrát.','warning');
            } else if ( $upload->isOk() ) {
                if ( $upload->move($fileName) ) {
                    $data['cert_key'] = $fileName;
                } else {
                    $this->flashMessage('Soubor privátního klíče se nepodařilo přenést na cílové místo.','warning');
                }
            } else {
                switch ($upload->error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $this->flashMessage('Překročena velikost pro nahrání souboru privátního klíče.','warning');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        //$this->flashMessage('Nebyl vybrán žádný soubor.','warning');
                        break;
                    default:
                        $this->flashMessage('Soubor privátního klíče se nepodařilo nahrát.','warning');
                    break;
                }
            }
        } else {
            // nelze nahrat
            $this->flashMessage('Soubor privátního klíče nelze nahrát na cílové místo.','warning');
        }
        unset($data['cert_key_file']);

        $config_data['odeslani'][$index]['ucet'] = $data['ucet'];
        $config_data['odeslani'][$index]['aktivni'] = $data['aktivni'];
        $config_data['odeslani'][$index]['typ_odeslani'] = $data['typ_odeslani'];
        $config_data['odeslani'][$index]['email'] = $data['email'];

        if ( !empty($data['cert']) ) {
            $config_data['odeslani'][$index]['cert'] = $data['cert'];
            $config_data['odeslani'][$index]['cert_key'] = $data['cert_key'];
        }
        $config_data['odeslani'][$index]['cert_pass'] = $data['cert_pass'];

        //Debug::dump($config_data); exit;
        $config_modify = new Config();
        $config_modify->import($config_data);
        $config_modify->save(CLIENT_DIR .'/configs/epodatelna.ini');

        Environment::setVariable('epodatelna_config', $config_modify);

        $this->flashMessage('Nastavení odeslání emailu bylo upraveno.');
        $this->redirect('this',array('id'=>('o' . $data['index']) ));
    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $this->redirect('this',array('id'=>($data['ep_typ'] . $data['index']) ));
    }


}
