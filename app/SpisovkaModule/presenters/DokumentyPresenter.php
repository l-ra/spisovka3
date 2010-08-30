<?php

class Spisovka_DokumentyPresenter extends BasePresenter
{

    private $filtr;
    private $hledat;
    private $odpoved = null;
    private $typ_evidence = null;

    public function startup()
    {
        $user_config = Environment::getVariable('user_config');
        $this->typ_evidence = 0;
        if ( isset($user_config->cislo_jednaci->typ_evidence) ) {
            $this->typ_evidence = $user_config->cislo_jednaci->typ_evidence;
        } else {
            $this->typ_evidence = 'priorace';
        }

        parent::startup();
    }

    public function renderDefault($filtr = null, $hledat = null)
    {

        $this->template->Typ_evidence = $this->typ_evidence;

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $Dokument = new Dokument();

        $this->template->no_items = 1; // indikator pri nenalezeni dokumentu
        if ( isset($filtr) ) {
            // podrobne hledani = array
            $args = $Dokument->filtr($filtr);
            $this->filtr = $filtr;
            $this->template->no_items = 2; // indikator pri nenalezeni dokumentu po filtraci
        } else {
            // rychle hledani = string
            $args = $Dokument->filtr('moje');
            $this->filtr = 'moje';
        }

        if ( isset($hledat) ) {
            

            if (is_array($hledat) ) {
                $args = $hledat;
                $this->template->no_items = 4; // indikator pri nenalezeni dokumentu pri pokorčilem hledani
            } else {
                $args = $Dokument->hledat($hledat);
                $this->hledat = $hledat;
                $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
            }

        }

        $result = $Dokument->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        if ( count($seznam)>0 ) {
            foreach ($seznam as $index => $row) {
                $dok = $Dokument->getInfo($row->dokument_id);
                $seznam[$index] = $dok;
            }
        } 

        $this->template->seznam = $seznam;

    }


    public function renderPDF()
    {

        $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
        $ep = $ep_config->toArray();
        if ( isset($ep['odeslani'][0]) ) {
            if ( $ep['odeslani'][0]['aktivni'] == '1' ) {
                $config = $ep['odeslani'][0];
            } else {
                throw new InvalidStateException('Nebyl zjištěn aktivní účet pro odesilání emailů.');
            }
        } else {
            throw new InvalidStateException('Nebyl zjištěn účet pro odesilání emailů.');
        }

        $esign = new esignature();
        $esign->setUserCert($config['cert'], $config['cert_key'], $config['cert_pass']);



        // initiate PDF
        $pdf = new NettePDF();
        $pdf->inputPDF(WWW_DIR .'/test.pdf');

        //$buffer = $pdf->getPDFData();

        // set additional information
        $info = array(
            'Name' => 'TCPDF',
            'Location' => 'Office',
            'Reason' => 'Testing TCPDF',
            'ContactInfo' => 'http://www.tcpdf.org',
	);

       // set document signature
        $pdf->setSignature($esign->getUserCertificate(),
                           $esign->getUserPrivateKey(),
                           $esign->getUserPassphrase(),
                           '', 2, $info);

        $pdf->Output('test_sign.pdf', 'D');

        $this->terminate();
    }

    public function renderTest()
    {
        echo "<pre>";

        $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
        $ep = $ep_config->toArray();
        if ( isset($ep['odeslani'][0]) ) {
            if ( $ep['odeslani'][0]['aktivni'] == '1' ) {
                $config = $ep['odeslani'][0];
            } else {
                throw new InvalidStateException('Nebyl zjištěn aktivní účet pro odesilání emailů.');
            }
        } else {
            throw new InvalidStateException('Nebyl zjištěn účet pro odesilání emailů.');
        }

        $esign = new esignature();
        $esign->setUserCert($config['cert'], $config['cert_key'], $config['cert_pass']);

        
        //$signature = file_get_contents(WWW_DIR .'/test.pdf');
        //$tsModule = new SetaPDF_Signer_Module_Ts_Curl("http://tsa.swisssign.net");
        //$tsModule->setSignature($signature);
        //$time = $tsModule->createTimeStamp();
        //var_dump($time);


        $this->terminate();

    }

    public function actionDetail()
    {

        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $this->getParam('id',null);
        if ( strpos($dokument_id, '-')!==false ) {
            list($dokument_id, $dokument_version) = explode('-',$dokument_id);
        } else {
            $dokument_version = null;
        }

        $dokument = $Dokument->getInfo($dokument_id, $dokument_version,1);
        if ( $dokument ) {
            // dokument zobrazime
            $this->template->Dok = $dokument;

            $user = Environment::getUser();
            //Debug::dump($user);

            $user_id = $user->getIdentity()->user_id;

            $this->template->Pridelen = 0;
            $this->template->Predan = 0;
            $formUpravit = null;
            // Prideleny nebo predany uzivatel
            if ( @$dokument->prideleno->prideleno == $user_id ) {
                // prideleny
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( @$dokument->predano->prideleno == $user_id ) {
                // predany
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( empty($dokument->prideleno->prideleno)
                        && Orgjednotka::isInOrg(@$dokument->prideleno->orgjednotka_id, 'vedouci') ) {
                // prideleno organizacni jednotce
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else if ( empty($dokument->predano->prideleno)
                        && Orgjednotka::isInOrg(@$dokument->predano->orgjednotka_id, 'vedouci') ) {
                // predano organizacni jednotce
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
                $formUpravit = $this->getParam('upravit',null);
            } else {
                // byvaly muze aspon prohlednout
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 0;
                $formUpravit = null;
                if ( count($dokument->workflow)>0 ) {
                    foreach ($dokument->workflow as $wf) {
                        if ( ($wf->prideleno == $user_id) && ($wf->stav_osoby < 100 || $wf->stav_osoby !=0) ) {
                            $this->template->AccessView = 1;
                        }
                    }
                }
            }

            // Dokument se vyrizuje
            if ( $dokument->stav_dokumentu == 3 || $dokument->stav_dokumentu == 4 ) {
                $this->template->Vyrizovani = 1;
            } else {
                $this->template->Vyrizovani = 0;
            }
            // Dokument je vyrizeny
            if ( $dokument->stav_dokumentu == 4 ) {
                $this->template->AccessEdit = 0;
                $this->template->Pridelen = 0;
                $this->template->Predan = 0;
                $formUpravit = null;
            }

            // SuperAdmin - moznost zasahovat do dokumentu
            if ( $user->isInRole('superadmin') ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
                $formUpravit = $this->getParam('upravit',null);
            }

            $this->template->FormUpravit = $formUpravit;

            $SpisovyZnak = new SpisovyZnak();
            $this->template->SpisoveZnaky = $SpisovyZnak->seznam(null);

            $this->template->Typ_evidence = $this->typ_evidence;
            if ( $this->typ_evidence == 'priorace' ) {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument_id);
            }

            $this->invalidateControl('dokspis');
        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }
        
    }

    public function renderDetail()
    {
        $this->template->metadataForm = $this['metadataForm'];
        $this->template->vyrizovaniForm = $this['vyrizovaniForm'];
    }

    public function renderPrevzit()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->predany($dokument_id) ) {
            if ( $Workflow->prevzit($dokument_id, $user_id, $orgjednotka_id) ) {
                $this->flashMessage('Úspěšně jste si převzal tento dokument.');
            } else {
                $this->flashMessage('Převzetí dokumentu do vlastnictví se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění k převzetí dokumentu.','warning');
        }

        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderKvyrizeni()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            if ( $Workflow->vyrizuje($dokument_id, $user_id, $orgjednotka_id) ) {
               $Workflow->zrusit_prevzeti($dokument_id);

               $DokumentSpis = new DokumentSpis();
               $spisy = $DokumentSpis->spisy($dokument_id);
               if ( count($spisy)>0 ) {
                   $Dokument = new Dokument();
                   foreach ( $spisy as $spis ) {
                       $data = array(
                            "spisovy_znak" => $spis->spisovy_znak,
                            "skartacni_znak" => $spis->skartacni_znak,
                            "skartacni_lhuta" => $spis->skartacni_lhuta,
                            "spousteci_udalost" => $spis->spousteci_udalost
                       );
                       $dokument = $Dokument->ulozit($data,$dokument_id);
                       unset($data);
                   }
               }


               $this->flashMessage('Převzal jste tento dokument k vyřízení.');
            } else {
                $this->flashMessage('Označení dokumentu k vyřízení se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění označit dokument k vyřízení.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderVyrizeno()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);
        $orgjednotka_id = $this->getParam('org',null);

        $Workflow = new Workflow();
        if ( $Workflow->prirazeny($dokument_id) ) {
            if ( $Workflow->vyrizeno($dokument_id, $user_id, $orgjednotka_id) ) {
                $Workflow->zrusit_prevzeti($dokument_id);
                $this->flashMessage('Označil jste tento dokument za vyřízený!');
            } else {
                $this->flashMessage('Označení dokumentu za vyřízený se nepodařilo. Zkuste to znovu.','warning');
            }
        } else {
            $this->flashMessage('Nemáte oprávnění označit dokument za vyřízený.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));

    }

    public function renderCjednaci()
    {
        $this->template->dokument_id = $this->getParam('id',null);
    }

    public function renderCjednaciadd()
    {
        $dokument_id = $this->getParam('id',null);
        $dokument_spojit = $this->getParam('spojit_s',null);

        $Dokument = new Dokument();

        $dok_in = $Dokument->getBasicInfo($dokument_id);
        
        $dok_out = $Dokument->getBasicInfo($dokument_spojit);

        if ( $dok_in && $dok_out ) {

            // spojit s dokumentem
            $poradi = $Dokument->getMaxPoradi($dok_out->cislojednaci_id);

            echo '###vybrano###'. $dok_out->cislo_jednaci .'#'. $poradi .'#'. $dok_out->cislojednaci_id;//. $spis->nazev;
            $this->terminate();

        } else {
            // chyba
            $this->template->chyba = 1;
            $this->template->render('cjednaci');
        }
    }

    public function renderPridelitcj()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);

        $Dokument = new Dokument();

        $CJ = new CisloJednaci();
        $cjednaci = $CJ->generuj(1);

        $data = array();
        $data['cislojednaci_id'] = $cjednaci->cjednaci_id;
        $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
        $data['poradi'] = 1;
        $data['podaci_denik'] = $cjednaci->podaci_denik;
        $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
        $data['podaci_denik_rok'] = $cjednaci->rok;


        $dokument = $Dokument->update($data, array(array('dokument_id=%i',$dokument_id)));//   array('dokument_id'=>0);// $Dokument->ulozit($data);
        $this->flashMessage('číslo jednací přiděleno');
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

    public function renderNovy()
    {

        $Dokumenty = new Dokument();

        $args_rozd = array();
        $args_rozd['where'] = array(
                array('stav=%i',0),
                array('typ_dokumentu<>%i',4),
                array('user_created=%i',Environment::getUser()->getIdentity()->user_id)
        );
        $args_rozd['order'] = array('date_created'=>'DESC');

        $this->template->Typ_evidence = $this->typ_evidence;

        $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);

        if ( count($rozdelany_dokument)>0 ) {
            $dokument = $rozdelany_dokument[0];

            $DokumentSpis = new DokumentSpis();
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentPrilohy = new DokumentPrilohy();

            $spisy = $DokumentSpis->spisy($dokument->dokument_id);
            $this->template->Spisy = $spisy;

            $subjekty = $DokumentSubjekt->subjekty($dokument->dokument_id);
            $this->template->Subjekty = $subjekty;

            $prilohy  = $DokumentPrilohy->prilohy($dokument->dokument_id,null,1);
            $this->template->Prilohy = $prilohy;

            if ( $this->typ_evidence == 'priorace' ) {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument->dokument_id);
            }

        } else {
            $pred_priprava = array(
                "nazev" => "",
                "popis" => "",
                "stav" => 0,
                "typ_dokumentu" => "1",
                "zpusob_doruceni" => "",
                "cislo_jednaci_odesilatele" => "",
                "datum_vzniku" => '',
                "lhuta" => "30",
                "poznamka" => "",
                "zmocneni" => "0"
            );
            $dokument = $Dokumenty->ulozit($pred_priprava);

            $this->template->Spisy = null;
            $this->template->Subjekty = null;
            $this->template->Prilohy = null;
            $this->template->SouvisejiciDokumenty = null;
            $this->template->Typ_evidence = $this->typ_evidence;

        }

        $UserModel = new UserModel();
        $user = $UserModel->getUser(Environment::getUser()->getIdentity()->user_id, 1);
        $this->template->Prideleno = Osoba::displayName($user->identity);

        $CJ = new CisloJednaci();
        $this->template->cjednaci = $CJ->generuj();


        if ( $dokument ) {
            $this->template->Dok = $dokument;
        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument není připraven k vytvoření','warning');
        }
        
        $this->template->novyForm = $this['novyForm'];
    }

    public function renderOdpoved()
    {

        $Dokumenty = new Dokument();
        
        $dokument_id = $this->getParam('id',null);
        if( strpos($dokument_id,'-') !== false ) {
            list($dokument_id, $dokument_version) = explode('-',$dokument_id);
        } else {
            $dokument_version = null;
        }

        $dok = $Dokumenty->getInfo($dokument_id,$dokument_version);

        if ( $dok ) {

            $args_rozd = array();
            $args_rozd['where'] = array(
                array('stav=%i',0),
                array('typ_dokumentu=%i',4),
                array('cislo_jednaci=%s',"odpoved_". $dok->dokument_id),
                array('user_created=%i',Environment::getUser()->getIdentity()->user_id)
            );
            $args_rozd['order'] = array('date_created'=>'DESC');

            $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);

            if ( count($rozdelany_dokument)>0 ) {
                $dok_odpoved = $rozdelany_dokument[0];
                // odpoved jiz existuje, tak ji nacteme
                $DokumentSpis = new DokumentSpis();
                $DokumentSubjekt = new DokumentSubjekt();
                $DokumentPrilohy = new DokumentPrilohy();

                $spisy = $DokumentSpis->spisy($dok_odpoved->dokument_id);
                $this->template->Spisy = $spisy;

                $subjekty = $DokumentSubjekt->subjekty($dok_odpoved->dokument_id);
                $this->template->Subjekty = $subjekty;

                $prilohy  = $DokumentPrilohy->prilohy($dok_odpoved->dokument_id,null,1);
                $this->template->Prilohy = $prilohy;

                $UserModel = new UserModel();
                $user = $UserModel->getUser(Environment::getUser()->getIdentity()->user_id, 1);
                $this->template->Prideleno = Osoba::displayName($user->identity);

                $CJ = new CisloJednaci();
                $this->template->Typ_evidence = $this->typ_evidence;
                if ( $this->typ_evidence == 'priorace' ) {
                    // Nacteni souvisejicicho dokumentu
                    $Souvisejici = new SouvisejiciDokument();
                    $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dok_odpoved->dokument_id);

                    $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);
                } else if ( $this->typ_evidence == 'sberny_arch' ) {
                    // sberny arch
                    $dok_odpoved->poradi = $dok_odpoved->poradi;
                    $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);
                } else {
                    $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);
                }

                $this->template->Dok = $dok_odpoved;


            } else {
                // totozna odpoved neexistuje
                $pred_priprava = array(
                    "nazev" => $dok->nazev,
                    "popis" => $dok->popis,
                    "stav" => 0,
                    "typ_dokumentu" => "4",
                    "zpusob_doruceni" => "",
                    "cislo_jednaci" => ("odpoved_". $dok->dokument_id),
                    "poradi" => ($dok->poradi + 1),
                    "cislo_jednaci_odesilatele" => $dok->cislo_jednaci_odesilatele,
                    "datum_vzniku" => '',
                    "lhuta" => "30",
                    "poznamka" => $dok->poznamka,
                    "zmocneni" => "0"
                );
                $dok_odpoved = $Dokumenty->ulozit($pred_priprava);

                if ( $dok_odpoved ) {
                
                    $DokumentSpis = new DokumentSpis();
                    $DokumentSubjekt = new DokumentSubjekt();
                    $DokumentPrilohy = new DokumentPrilohy();

                    // kopirovani spisu
                    $spisy_old = $DokumentSpis->spisy($dokument_id);
                    if ( count($spisy_old)>0 ) {
                        foreach ( $spisy_old as $spis ) {
                            $DokumentSpis->pripojit($dok_odpoved->dokument_id, $spis->spis_id, $stav = 1, $dok_odpoved->dokument_version);
                        }
                    }
                    $spisy_new = $DokumentSpis->spisy($dok_odpoved->dokument_id);
                    $this->template->Spisy = $spisy_new;

                    // kopirovani subjektu
                    $subjekty_old = $DokumentSubjekt->subjekty($dokument_id);
                    if ( count($subjekty_old)>0 ) {
                        foreach ( $subjekty_old as $subjekt ) {
                            if ( $subjekt->type != 'O' ) {
                                $DokumentSubjekt->pripojit($dok_odpoved->dokument_id, $subjekt->subjekt_id, $subjekt->rezim_subjektu, $dok_odpoved->dokument_version, $subjekt->subjekt_version);
                            }
                        }
                    }
                    $subjekty_new = $DokumentSubjekt->subjekty($dokument_id);
                    $this->template->Subjekty = $subjekty_new;

                    // kopirovani prilohy
                    $prilohy_old  = $DokumentPrilohy->prilohy($dokument_id,null,1);

                    if ( count($prilohy_old)>0 ) {
                        foreach ( $prilohy_old as $priloha ) {
                            if ( $priloha->typ == 1 || $priloha->typ == 2 || $priloha->typ == 3 ) {
                                $DokumentPrilohy->pripojit($dok_odpoved->dokument_id, $priloha->file_id, $dok_odpoved->dokument_version, $priloha->file_version);
                            }
                        }
                    }
                    $prilohy_new  = $DokumentPrilohy->prilohy($dok_odpoved->dokument_id,$dok_odpoved->dokument_version,1);
                    $this->template->Prilohy = $prilohy_new;

                    $UserModel = new UserModel();
                    $user = $UserModel->getUser(Environment::getUser()->getIdentity()->user_id, 1);
                    $this->template->Prideleno = Osoba::displayName($user->identity);

                    $CJ = new CisloJednaci();
                    $this->template->Typ_evidence = $this->typ_evidence;
                    $this->template->SouvisejiciDokumenty = null;
                    if ( $this->typ_evidence == 'priorace' ) {
                        // priorace - Nacteni souvisejicicho dokumentu
                        $Souvisejici = new SouvisejiciDokument();
                        $Souvisejici->spojit($dok_odpoved->dokument_id,$dokument_id);
                        $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dok_odpoved->dokument_id);
                        
                        $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);
                    } else if ( $this->typ_evidence == 'sberny_arch' ) {
                        // sberny arch
                        //$dok_odpoved->poradi = $dok_odpoved->poradi;
                        $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);

                    } else {
                        $this->template->cjednaci = $CJ->nacti($dok->cislojednaci_id);
                    }

                    $this->template->Dok = $dok_odpoved;
                } else {
                    $this->template->Dok = null;
                    $this->flashMessage('Dokument není připraven k vytvoření','warning');
                }
            }

            $this->odpoved = $dok->cislojednaci_id;

            $this->template->novyForm = $this['novyForm'];
            $this->setView('novy');

        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument neexistuje','warning');
            $this->redirect(':Spisovka:Dokumenty:default');
        }

    }


    public function renderDownload() 
    {

        $dokument_id = $this->getParam('id',null);
        if( strpos($dokument_id,'-') !== false ) {
            list($dokument_id, $dokument_version) = explode('-',$dokument_id);
        } else {
            $dokument_version = null;
        }
        
        $file_id = $this->getParam('file',null);
        if( strpos($file_id,'-') !== false ) {
            list($file_id, $file_version) = explode('-',$file_id);
        } else {
            $file_version = null;
        }

        $DokumentPrilohy = new DokumentPrilohy();
        $prilohy = $DokumentPrilohy->prilohy($dokument_id, $dokument_version);
        if ( key_exists($file_id, $prilohy) ) {

            $storage_conf = Environment::getConfig('storage');
            eval("\$DownloadFile = new ".$storage_conf->type."();");
            $FileModel = new FileModel();
            $file = $FileModel->getInfo($file_id, $file_version);
            $res = $DownloadFile->download($file);
            if ( $res == 0 ) {
                $this->terminate();
            } else if ( $res == 1 ) {
                // not found
                $this->flashMessage('Požadovaný soubor nenalezen!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 2 ) {
                $this->flashMessage('Chyba při stahování!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else if ( $res == 3 ) {
                $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnut zmíněný soubor!','warning');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            }
        } else {
            $this->flashMessage('Neoprávněné stahování! Nemáte povolení stáhnut cizí soubor!','warning');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        }
        
    }

    public function renderZmocneni()
    {
        
    }

    public function renderHistorie()
    {

        $dokument_id = $this->getParam('id',null);

        $Log = new LogModel();
        $historie = $Log->historieDokumentu($dokument_id);
        $this->template->historie = $historie;

        //$Workflow = new Workflow();
        //$this->template->workflow = $Workflow->dokument($dokument_id);

    }

    public function actionOdeslat()
    {

        $Dokument = new Dokument();

        // Nacteni parametru
        $dokument_id = $this->getParam('id',null);
        if ( strpos($dokument_id, '-')!==false ) {
            list($dokument_id, $dokument_version) = explode('-',$dokument_id);
        } else {
            $dokument_version = null;
        }

        $dokument = $Dokument->getInfo($dokument_id, $dokument_version,1);
        if ( $dokument ) {
            // dokument zobrazime
            $this->template->Dok = $dokument;

            $user = Environment::getUser();
            //Debug::dump($user);

            $user_id = $user->getIdentity()->user_id;
            $this->template->Pridelen = 0;
            $this->template->Predan = 0;
            // Prideleny nebo predany uzivatel
            if ( @$dokument->prideleno->prideleno == $user_id ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
            } else if ( @$dokument->predano->prideleno == $user_id ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Predan = 1;
            } else {
                $this->template->AccessEdit = 0;
                $this->template->AccessView = 0;
                if ( count($dokument->workflow)>0 ) {
                    foreach ($dokument->workflow as $wf) {
                        if ( ($wf->prideleno == $user_id) && ($wf->stav_osoby < 100) ) {
                            $this->template->AccessView = 1;
                        }
                    }
                }
            }

            // Prilohy
            $prilohy_celkem = 0;
            if ( count($dokument->prilohy) > 0 ) {
                foreach ($dokument->prilohy as $p) {
                    $prilohy_celkem = $prilohy_celkem + $p->size;
                }
            }
            $this->template->PrilohyCelkovaVelikost = $prilohy_celkem;

            $this->template->ChybaPredOdeslani = 0;


            // Dokument se vyrizuje
            if ( $dokument->stav_dokumentu == 3 ) {
                $this->template->Vyrizovani = 1;
            } else {
                $this->template->Vyrizovani = 0;
            }

            // SuperAdmin - moznost zasahovat do dokumentu
            if ( $user->isInRole('superadmin') ) {
                $this->template->AccessEdit = 1;
                $this->template->AccessView = 1;
                $this->template->Pridelen = 1;
            }

            $this->template->FormUpravit = $this->getParam('upravit',null);

            $SpisovyZnak = new SpisovyZnak();
            $this->template->SpisoveZnaky = $SpisovyZnak->seznam(null);


            $this->invalidateControl('dokspis');
        } else {
            // dokument neexistuje nebo se nepodarilo nacist
            $this->setView('noexist');
        }

    }

    public function renderOdeslat()
    {
        $this->template->odeslatForm = $this['odeslatForm'];
    }

    protected function createComponentNovyForm()
    {

        $dok = null;
        if( isset($this->template->Dok) ) {
            $dokument_id = isset($this->template->Dok->dokument_id)?$this->template->Dok->dokument_id:0;
            $dok = $this->template->Dok;
        } else {
            $dokument_id = 0;
        }

        $typ_dokumentu = Dokument::typDokumentu(null,1);

        $form = new AppForm();
        $form->addHidden('dokument_id')
                ->setValue($dokument_id);
        $form->addHidden('odpoved')
                ->setValue($this->odpoved);

        if ( $this->typ_evidence == 'sberny_arch' ) {
            $form->addText('poradi', 'Pořadí dokumentu ve sberném archu:', 4, 4)
                    ->setValue(@$dok->poradi)
                    ->controlPrototype->readonly = TRUE;
        }

        $form->addText('nazev', 'Věc:', 80, 100)
                ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!')
                ->setValue(@$dok->nazev);
        $form->addTextArea('popis', 'Stručný popis:', 80, 3)
                ->setValue(@$dok->popis);
        $form->addSelect('typ_dokumentu', 'Typ Dokumentu:', $typ_dokumentu)
                ->setValue(@$dok->typ_dokumentu->id);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$dok->cislo_jednaci_odesilatele);

        $datum = date('d.m.Y');
        $cas = date('H:i:s');

        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10)
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);

        //$form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->addRule(Form::FILLED, 'Lhůta k vyřízení musí být vyplněna!')
                ->setValue('30');
        $form->addTextArea('poznamka', 'Poznámka:', 80, 6)
                ->setValue(@$dok->poznamka);

        $form->addTextArea('predani_poznamka', 'Poznámka:', 80, 3);
        
        $form->addHidden('zmocneni')->setValue(0);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$dok->pocet_listu);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$dok->pocet_priloh);


        $form->addSubmit('novy', 'Vytvořit')
                 ->onClick[] = array($this, 'vytvoritClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoSeznamClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function vytvoritClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Dokument = new Dokument();

        $dokument_id = $data['dokument_id'];
        $data['stav'] = 1;

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] ." ". $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        // predani
        $predani_poznamka = $data['predani_poznamka'];

        unset($data['predani_poznamka'],$data['dokument_id'],$data['dokument_version']);

        try {

            //Debug::dump($data); exit;
            $CJ = new CisloJednaci();

            if ( !empty($data['odpoved']) ) {
                $cjednaci = $CJ->nacti($data['odpoved']);
                unset($data['odpoved']);
            } else {
                $cjednaci = $CJ->generuj(1);
            }

            $data['jid'] = $cjednaci->app_id.'-ESS-'.$dokument_id;
            $data['cislojednaci_id'] = $cjednaci->cjednaci_id;
            $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
            $data['podaci_denik'] = $cjednaci->podaci_denik;
            $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
            $data['podaci_denik_rok'] = $cjednaci->rok;

            $dokument = $Dokument->ulozit($data, $dokument_id, 1);//   array('dokument_id'=>0);// $Dokument->ulozit($data);

            if ( $dokument ) {
                $Workflow = new Workflow();
                $Workflow->vytvorit($dokument_id,$predani_poznamka);

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

                $this->flashMessage('Dokument byl vytvořen.');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else {
                $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            }
        } catch (DibiException $e) {
            $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }

    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['dokument_id'];
        $dokument_version = $data['dokument_version'];
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Spisovka:Dokumenty:default');
    }

    protected function createComponentMetadataForm()
    {

        $typ_dokumentu = Dokument::typDokumentu(null,1);
        $Dok = @$this->template->Dok;

        $form = new AppForm();
        $form->addHidden('dokument_id')
                ->setValue(@$Dok->dokument_id);
        $form->addHidden('dokument_version')
                ->setValue(@$Dok->dokument_version);
        $form->addText('nazev', 'Věc:', 80, 100)
                ->setValue(@$Dok->nazev)
                ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!');
        $form->addTextArea('popis', 'Stručný popis:', 80, 3)
                ->setValue(@$Dok->popis);
        $form->addSelect('typ_dokumentu', 'Typ Dokumentu:', $typ_dokumentu)
                ->setValue(@$Dok->typ_dokumentu->id);
        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$Dok->cislo_jednaci_odesilatele);

        $unixtime = strtotime(@$Dok->datum_vzniku);
        if ( $unixtime == 0 ) {
            $datum = date('d.m.Y');
            $cas = date('H:i:s');
        } else {
            $datum = date('d.m.Y',$unixtime);
            $cas = date('H:i:s',$unixtime);
        }

        $form->addDatePicker('datum_vzniku', 'Datum doručení/vzniku:', 10)
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);

        $form->addTextArea('poznamka', 'Poznámka:', 80, 6)
                ->setValue(@$Dok->poznamka);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$Dok->pocet_listu);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$Dok->pocet_priloh);

        $form->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'upravitMetadataClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function upravitMetadataClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        //Debug::dump($data); exit;

        $dokument_id = $data['dokument_id'];
        $dokument_version = $data['dokument_version'];

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] ." ". $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        //Debug::dump($data); exit;

        $Dokument = new Dokument();

        $dok = $Dokument->getInfo($dokument_id, $dokument_version);

        try {

            $dokument = $Dokument->ulozit($data,$dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upravena metadata dokumentu.');

            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'"  byl upraven.');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'" se nepodařilo upravit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }

    protected function createComponentVyrizovaniForm()
    {

        $zpusob_vyrizeni = Dokument::zpusobVyrizeni(null, 1);

        $SpisovyZnak = new SpisovyZnak();
        $spisznak_seznam = array();
        $spisznak_seznam[0] = 'vyberte z nabídky ...';
        $spisznak_seznam = @array_merge($spisznak_seznam, $SpisovyZnak->seznam(null,1));

        $Dok = @$this->template->Dok;

        $form = new AppForm();
        $form->addHidden('dokument_id')
                ->setValue(@$Dok->dokument_id);
        $form->addHidden('dokument_version')
                ->setValue(@$Dok->dokument_version);
        $form->addSelect('zpusob_vyrizeni', 'Způsob vyřízení:', $zpusob_vyrizeni)
                ->setValue(@$Dok->zpusob_vyrizeni_id);

        $unixtime = strtotime(@$Dok->datum_vyrizeni);
        if ( $unixtime == 0 ) {
            $datum = date('d.m.Y');
            $cas = date('H:i:s');
        } else {
            $datum = date('d.m.Y',$unixtime);
            $cas = date('H:i:s',$unixtime);
        }
        
        $form->addDatePicker('datum_vyrizeni', 'Datum vyřízení:', 10)
                ->setValue($datum);
        $form->addText('datum_vyrizeni_cas', 'Čas vyřízení:', 10, 15)
                ->setValue($cas);

        $form->addSelect('spisovy_znak', 'spisový znak:', $spisznak_seznam)
                ->setValue(@$Dok->spisovy_znak_id)
                ->controlPrototype->onchange("vybratSpisovyZnak();");
        $form->addTextArea('ulozeni_dokumentu', 'Uložení dokumentu:', 80, 6)
                ->setValue(@$Dok->ulozeni_dokumentu);
        $form->addTextArea('poznamka_vyrizeni', 'Poznámka k vyřízení:', 80, 6)
                ->setValue(@$Dok->poznamka_vyrizeni);

        $form->addText('skartacni_znak','Skartační znak: ', 3, 3)
                ->setValue(@$Dok->skartacni_znak)
                ->controlPrototype->readonly = TRUE;
        $form->addText('skartacni_lhuta','Skartační lhuta: ', 5, 5)
                ->setValue(@$Dok->skartacni_lhuta)
                ->controlPrototype->readonly = TRUE;
        $form->addTextArea('spousteci_udalost','Spouštěcí událost: ', 80, 3)
                ->setValue(@$Dok->spousteci_udalost)
                ->controlPrototype->readonly = TRUE;

        $form->addText('vyrizeni_pocet_listu', 'Počet listů:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_listu);
        $form->addText('vyrizeni_pocet_priloh', 'Počet příloh:', 5, 10)
                ->setValue(@$Dok->vyrizeni_pocet_priloh);


//                ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!');

        $form->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'upravitVyrizeniClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function upravitVyrizeniClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $dokument_id = $data['dokument_id'];
        $dokument_version = $data['dokument_version'];

        // uprava casu
        $data['datum_vyrizeni'] = $data['datum_vyrizeni'] ." ". $data['datum_vyrizeni_cas'];
        unset($data['datum_vzniku_cas']);

        // spisovy znak


        //Debug::dump($data); exit;

        $Dokument = new Dokument();

        $dok = $Dokument->getInfo($dokument_id, $dokument_version);

        try {

            $dokument = $Dokument->ulozit($data,$dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_ZMENEN, 'Upravena data vyřízení.');

            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'"  byl upraven.');
            $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
        } catch (DibiException $e) {
            $this->flashMessage('Dokument "'. $dok->cislo_jednaci .'" se nepodařilo upravit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }

    protected function createComponentOdeslatForm()
    {

        $typ_dokumentu = Dokument::typDokumentu(null,1);
        $Dok = @$this->template->Dok;


        $zprava = "";


        $sznacka = "";
        if ( isset( $this->template->Dok->spisy ) ) {
            $sznacka_A = array();
            foreach ($this->template->Dok->spisy as $spis) {
                $sznacka_A[] = $spis->nazev;
            }
            $sznacka = implode(", ",$sznacka_A);
        }

        // odesilatele
        $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
        $ep = $ep_config->toArray();
        $odesilatele = array();

        if ( count($ep['odeslani'])>0 ) {
            foreach ($ep['odeslani'] as $odes_id => $odes) {
                if ( $odes['aktivni']==1 ) {
                    if ( empty($odes['jmeno']) ) {
                        $odesilatele['epod'.$odes_id] = $odes['email'] ."[".$odes['ucet']."]";
                    } else {
                        $odesilatele['epod'.$odes_id] = $odes['jmeno'] ." <".$odes['email']."> [".$odes['ucet']."]";
                    }
                }
            }
        }
        $user_info = Environment::getUser()->getIdentity();
        if ( !empty($user_info->identity->email) ) {
            $key = "user#". Subjekt::displayName($user_info->identity, 'jmeno') ."#". $user_info->identity->email;
            $odesilatele[$key] = Subjekt::displayName($user_info->identity, 'jmeno') ." <". $user_info->identity->email ."> [zaměstnanec]";
        }

        $form = new AppForm();
        $form->addHidden('dokument_id')
                ->setValue(@$Dok->dokument_id);
        $form->addHidden('dokument_version')
                ->setValue(@$Dok->dokument_version);
        $form->addSelect('email_from', 'Odesílatel:', $odesilatele);
        $form->addText('email_predmet', 'Předmět zprávy:', 80, 100)
                ->setValue(@$Dok->nazev);
        $form->addTextArea('email_text', 'Text:', 80, 15)
                ->setValue($zprava);

        $form->addText('isds_predmet', 'Předmět zprávy:', 80, 100)
                ->setValue(@$Dok->nazev);
        $form->addText('isds_cjednaci_odes', 'Číslo jendací odesílatele:', 40, 100)
                ->setValue(@$Dok->cislo_jednaci);
        $form->addText('isds_spis_odes', 'Spisová značka odesílatele:', 40, 100)
                ->setValue($sznacka);
        $form->addText('isds_cjednaci_adres', 'Číslo jendací adresáta:', 40, 100)
                ->setValue(@$Dok->cislo_jednaci_odesilatele);
        $form->addText('isds_spis_adres', 'Spisová značka adresáta:', 40, 100);
        $form->addCheckbox('isds_dvr', 'Do vlastních rukou? :')
                ->setValue(false);
        $form->addCheckbox('isds_fikce', 'Doručit fikcí? :')
                ->setValue(true);



        $form->addSubmit('odeslat', 'Odeslat zprávu adresátům')
                 ->onClick[] = array($this, 'odeslatClicked');
        $form->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');



        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function odeslatClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        //Debug::dump($data); exit;

        $dokument_id = $data['dokument_id'];
        $dokument_version = $data['dokument_version'];
        $Dokument = new Dokument();
        $Subjekt = new Subjekt();
        $File = new FileModel();
        //$dok = $Dokument->getInfo($dokument_id, $dokument_version);

        $post_data = Environment::getHttpRequest()->getPost();

        //Debug::dump($post_data);
        //Debug::dump($data);

        //exit;

        //echo "<pre>";
        //echo "\n\nPřílohy:\n\n";

        $prilohy = array();
        if ( isset($post_data['prilohy'])) {
            if ( count($post_data['prilohy'])>0 ) {

                $storage_conf = Environment::getConfig('storage');
                eval("\$DownloadFile = new ".$storage_conf->type."();");

                foreach ($post_data['prilohy'] as $file_id => $status) {
                    $priloha = $File->getInfo($file_id);
                    $priloha->tmp_file = $DownloadFile->download($priloha,2);

                    $prilohy[ $file_id ] = $priloha;
                }
            } else {
                // zadne prilohy
            }
        } else {
            // zadne prilohy
        }


        //echo "\n\nAdresati:\n\n";

        if ( isset($post_data['subjekt'])) {
            if ( count($post_data['subjekt'])>0 ) {
                foreach ($post_data['subjekt'] as $subjekt_id => $metoda_odeslani) {
                    $adresat = $Subjekt->getInfo($subjekt_id);
                    //echo Subjekt::displayName($adresat) ."\n";
                    //Debug::dump($adresat);

                    $datum_odeslani = new DateTime();

                    if ( $metoda_odeslani == 0 ) {
                        // neodesilat - nebudeme delat nic
                        //echo "  => neodesilat";
                        continue;
                    } elseif ( $metoda_odeslani == 1 ) {
                        // emailem
                        //echo "  => emailem";
                        if ( !empty($adresat->email) ) {
                            if ( $zprava = $this->odeslatEmailem($adresat, $data, $prilohy) ) {
                                $Log = new LogModel();
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán emailem na adresu "'. Subjekt::displayName($adresat,'email') .'".');
                                $this->flashMessage('Zpráva na emailovou adresu "'. Subjekt::displayName($adresat,'email') .'" byla úspěšně odeslána.');
                            } else {
                                $this->flashMessage('Zprávu na emailovou adresu "'. Subjekt::displayName($adresat,'email') .'" se nepodařilo odeslat!','warning');
                                continue;
                            }
                        } else {
                            $this->flashMessage('Subjekt "'. Subjekt::displayName($adresat,'email') .'" nemá emailovou adresu. Zprávu tomuto adresátovi nelze poslat přes email!','warning');
                            continue;
                        }
                    } elseif ( $metoda_odeslani == 2 ) {
                        // isds
                        //echo "  => isds";
                        if ( !empty($adresat->id_isds) ) {
                            if ( $zprava = $this->odeslatISDS($adresat, $data, $prilohy) ) {
                                $Log = new LogModel();
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán datovou zprávou na adresu "'. Subjekt::displayName($adresat,'isds') .'".');
                                $this->flashMessage('Datová zpráva pro "'. Subjekt::displayName($adresat,'isds') .'" byla úspěšně odeslána do systému ISDS.');
                            } else {
                                $this->flashMessage('Datoovu zprávu pro "'. Subjekt::displayName($adresat,'isds') .'" se nepodařilo odeslat do systému ISDS!','warning');
                                continue;
                            }
                        } else {
                            $this->flashMessage('Subjekt "'. Subjekt::displayName($adresat,'jmeno') .'" nemá ID datové schránky. Zprávu tomuto adresátovi nelze poslat přes datovou schránku!','warning');
                            continue;
                        }

                    } else {
                        // jinak - externe (posta, fax, osobne, ...)
                        //echo "  => jinak";

                        if ( isset($post_data['datum_odeslani'][$subjekt_id]) ) {
                            $datum_odeslani = new DateTime( $post_data['datum_odeslani'][$subjekt_id] );
                        }

                        $Log = new LogModel();
                        switch ($metoda_odeslani) {
                            case 1:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán emailem na adresu "'. Subjekt::displayName($adresat,'email') .'".');
                                break;
                            case 2:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán datovou zprávou na adresu "'. Subjekt::displayName($adresat,'isds') .'".');
                                break;
                            case 3:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán poštou na adresu "'. Subjekt::displayName($adresat) .'".');
                                break;
                            case 4:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán faxem na číslo "'. Subjekt::displayName($adresat,'telefon') .'".');
                                break;
                            case 5:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán osobně "'. Subjekt::displayName($adresat,'jmeno') .'".');
                                break;
                            case 6:
                                $Log->logDokument($dokument_id, LogModel::DOK_ODESLAN,'Dokument odeslán telefonicky na číslo "'. Subjekt::displayName($adresat,'telefon') .'".');
                                break;
                            default: break;
                        }

                    }

                    $epodatelna_id = null;
                    if ( isset($zprava['epodatelna_id']) ) {
                        $epodatelna_id = $zprava['epodatelna_id'];
                    }
                    $zprava_odes = '';
                    if ( isset($zprava['zprava']) ) {
                        $zprava_odes = $zprava['zprava'];
                    }

                    // Zaznam do DB (dokument_odeslani)
                    $DokumentOdeslani = new DokumentOdeslani();
                    $row = array(
                        'dokument_id' => $dokument_id,
                        'dokument_version' => $dokument_version,
                        'subjekt_id' => $adresat->subjekt_id,
                        'subjekt_version' => $adresat->subjekt_version,
                        'zpusob_odeslani' => $metoda_odeslani,
                        'epodatelna_id' => $epodatelna_id,
                        'datum_odeslani' => $datum_odeslani,
                        'zprava' => $zprava_odes
                    );
                    $DokumentOdeslani->ulozit($row);
                    
                }

                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else {
                // zadni adresati
            }
        } else {
            // zadni adresati
        }

    }


    protected function odeslatEmailem($adresat, $data, $prilohy)
    {

        $mail = new ESSMail;
        $mail->signed(1);

        if ( !empty($data['email_from']) ) {

            if ( strpos($data['email_from'],"epod")!==false ) {
                $id_odes = substr($data['email_from'],4);
                $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
                $ep = $ep_config->toArray();
                if ( isset( $ep['odeslani'][$id_odes] ) ) {
                    $mail->setFromConfig($ep['odeslani'][$id_odes]);
                } else {
                    $mail->setFromConfig();
                }
            } else if ( strpos($data['email_from'],"user")!==false ) {

                $user_part = explode("#",$data['email_from']);
                $mail->setFrom($user_part[2],$user_part[1]);

            } else {
                $mail->setFromConfig();
            }
        } else {
            $mail->setFromConfig();
        }

        if ( strpos($adresat->email,';') !== false ) {
            $email_parse = explode(';',$adresat->email);
            foreach($email_parse as $emp) {
                $email = trim($emp);
                $mail->addTo($email);
            }
        } elseif ( strpos($adresat->email,',') !== false ) {
            $email_parse = explode(',',$adresat->email);
            foreach($email_parse as $emp) {
                $email = trim($emp);
                $mail->addTo($email);
            }
        } else {
            $mail->addTo($adresat->email);
        }

        $mail->setSubject($data['email_predmet']);
        $mail->setBody($data['email_text']);

        if ( count($prilohy)>0 ) {
            foreach ($prilohy as $p) {
                $mail->addAttachment($p->tmp_file);
            }
        }

        //if ( $mail->send() ) { /* TODO opravit detekci odeslani */
            $mail->send();

            $source = "";
            if ( file_exists(WWW_DIR .'/files/tmp_email.eml') ) {
                //if ( $fp = @fopen(WWW_DIR .'/files/tmp_email.eml','rb') ) {
                //    $source = fread($fp, filesize(WWW_DIR .'/files/tmp_email.eml') );
                //    @fclose($fp);
                //}
                $source = WWW_DIR .'/files/tmp_email.eml';
            }

            // Do epodatelny
            $storage_conf = Environment::getConfig('storage');
            eval("\$UploadFile = new ".$storage_conf->type."();");

            // nacist email z ImapClient
            $imap = new ImapClientFile();
            if ( $imap->open($source) ) {
                $email_mess = $imap->get_head_message(0);
            } else {
                $email_mess = null;
            }


            //Debug::dump($email_mess);
            //exit;

            
            $email_config = $mail->getFromConfig();
            if ( is_null($email_config) ) {
                if ( isset($user_part) ) {
                    $email_config['ucet'] = $user_part[1];
                    $email_config['email'] = $user_part[2];
                } else {
                    $email_config['ucet'] = "uživatel";
                    $email_config['email'] = $email_mess->from_address;
                }
            }

            $user = Environment::getUser()->getIdentity();


            // zapis do epodatelny
            $Epodatelna = new Epodatelna();
            $zprava = array();
            $zprava['epodatelna_typ'] = 1;
            $zprava['poradi'] = $Epodatelna->getMax(1);
            $zprava['rok'] = date('Y');
            $zprava['email_signature'] = $email_mess->message_id;
            $zprava['predmet'] = $email_mess->subject;
            $zprava['popis'] = $data['email_text'];
            $zprava['odesilatel'] = $email_mess->to_address;
            $zprava['odesilatel_id'] = $adresat->subjekt_id;
            $zprava['adresat'] = $email_config['ucet'] .' ['. $email_config['email'] .']';
            $zprava['prijato_dne'] = new DateTime();
            $zprava['doruceno_dne'] = new DateTime();
            $zprava['prijal_kdo'] = $user->user_id;
            $zprava['prijal_info'] = serialize($user->identity);

            $zprava['sha1_hash'] = sha1_file($source);

            $prilohy = array();
            if( isset($email_mess->attachments) ) {
                foreach ($email_mess->attachments as $ipr => $pr) {

                    $base_name = basename($pr['DataFile']);
                    //echo $base_name ."<br>";

                    $prilohy[] = array(
                        'name'=> $pr['FileName'],
                        'size'=> filesize($pr['DataFile']),
                        'mimetype'=> FileModel::mimeType($pr['FileName']),
                        'id'=>$base_name
                    );
                }
            }
            $zprava['prilohy'] = serialize($prilohy);

            $zprava['evidence'] = 'spisovka';
            $zprava['dokument_id'] = $data['dokument_id'];
            $zprava['stav'] = 0;
            $zprava['stav_info'] = '';
            //$zprava['source'] = $z;
            //unset($mess->source);
            //$zprava['source'] = $mess;
            $zprava['source_id'] = null;

                        if ( $epod_id = $Epodatelna->insert($zprava) ) {

                            $data_file = array(
                                'filename'=>'ep_email_'.$epod_id .'.eml',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Emailová zpráva z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            $mess_source = "";
                            if ( $fp = @fopen($source ,'rb') ) {
                                $mess_source = fread($fp, filesize($source) );
                                @fclose($fp);
                            }

                            if ( $file = $UploadFile->uploadEpodatelna($mess_source, $data_file) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['source_id'] = $file->file_id;
                                $Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'source_id'=>$file->file_id
                                            ),
                                        array(array('epodatelna_id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }
                        } else {
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }




            return array(
                'source'=>$source,
                'epodatelna_id'=>$epod_id,
                'email_signature'=>$email_mess->message_id,
                'zprava'=>$data['email_text']
            );
        //} else {
        //    return false;
        //}
    }

    protected function odeslatISDS($adresat, $data, $prilohy)
    {

        //$isds_debug = 1;
        $isds = new ISDS_Spisovka();
        if ( $ISDSBox = $isds->pripojit() ) {

            $dmEnvelope = array(
                "dbIDRecipient"=>$adresat->id_isds,
                "cislo_jednaci"=>$data['isds_cjednaci_odes'],
                "spisovy_znak"=>$data['isds_spis_odes'],
                "vase_cj"=>$data['isds_cjednaci_adres'],
                "vase_sznak"=>$data['isds_spis_adres'],
                "k_rukam"=>$data['isds_dvr'],
                "anotace"=>$data['isds_predmet'],
                //"zmocneni_law"=>$_POST['dok_zmo_law'],
                //"zmocneni_year"=>$_POST['dok_zmo_year'],
                //"zmocneni_sect"=>$_POST['dok_zmo_sect'],
                //"zmocneni_par"=>$_POST['dok_zmo_par'],
                //"zmocneni_point"=>$_POST['dok_zmo_point'],
                "do_vlastnich"=>($data['isds_dvr']==true)?1:0,
                "doruceni_fikci"=>($data['isds_fikce']==true)?0:1
            );

            if ( $id_mess = $isds->odeslatZpravu($dmEnvelope, $prilohy) ) {

                sleep(3);
                $odchozi_zpravy = $isds->seznamOdeslanychZprav( time()-3600 , time() );
                //Debug::dump($odchozi_zpravy);
                $mess = null;
                if ( count($odchozi_zpravy)>0 ) {
                    foreach ($odchozi_zpravy as $oz) {
                        if ( $oz->dmID == $id_mess ) {
                            $mess = $oz;
                            break;
                        }
                    }
                }
                if ( is_null($mess) ) {
                    return false;
                }

                $popis  = '';
                $popis .= "ID datové zprávy    : ". $mess->dmID ."\n";// = 342682
                $popis .= "Věc, předmět zprávy : ". $mess->dmAnnotation ."\n";//  = Vaše datová zpráva byla přijata
                $popis .= "\n";
                $popis .= "Číslo jednací odeslatele   : ". $mess->dmSenderRefNumber ."\n";//  = AB-44656
                $popis .= "Spisová značka odesílatele : ". $mess->dmSenderIdent ."\n";//  = ZN-161
                $popis .= "Číslo jednací příjemce     : ". $mess->dmRecipientRefNumber ."\n";//  = KAV-34/06-ŘKAV/2010
                $popis .= "Spisová značka příjemce    : ". $mess->dmRecipientIdent ."\n";//  = 0.06.00
                $popis .= "\n";
                $popis .= "Do vlastních rukou? : ". (!empty($mess->dmPersonalDelivery)?"ano":"ne") ."\n";//  =
                $popis .= "Doručeno fikcí?     : ". (!empty($mess->dmAllowSubstDelivery)?"ano":"ne") ."\n";//  =
                $popis .= "Zpráva určena pro   : ". $mess->dmToHands ."\n";//  =
                $popis .= "\n";
                $popis .= "Odesílatel:\n";
                $popis .= "            ". $mess->dbIDSender ."\n";//  = hjyaavk
                $popis .= "            ". $mess->dmSender ."\n";//  = Město Milotice
                $popis .= "            ". $mess->dmSenderAddress ."\n";//  = Kovářská 14/1, 37612 Milotice, CZ
                $popis .= "            ". $mess->dmSenderType ." - ". ISDS_Spisovka::typDS($mess->dmSenderType) ."\n";//  = 10
                $popis .= "            org.jednotka: ". $mess->dmSenderOrgUnit ." [". $mess->dmSenderOrgUnitNum ."]\n";//  =
                $popis .= "\n";
                $popis .= "Příjemce:\n";
                $popis .= "            ". $mess->dbIDRecipient ."\n";//  = pksakua
                $popis .= "            ". $mess->dmRecipient ."\n";//  = Společnost pro výzkum a podporu OpenSource
                $popis .= "            ". $mess->dmRecipientAddress ."\n";//  = 40501 Děčín, CZ
                        //$popis .= "Je příjemce ne-OVM povýšený na OVM: ". $mess->dmDm->dmAmbiguousRecipient ."\n";//  =
                $popis .= "            org.jednotka: ". $mess->dmRecipientOrgUnit ." [". $mess->dmRecipientOrgUnitNum ."]\n";//  =
                $popis .= "\n";
                $popis .= "Status: ". $mess->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) ."\n";
                $dt_dodani = strtotime($mess->dmDeliveryTime);
                $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                $popis .= "Datum a čas dodání   : ". date("j.n.Y G:i:s",$dt_dodani) ." (". $mess->dmDeliveryTime .")\n";//  =
                if ( $dt_doruceni == 0) {
                    $popis .= "Datum a čas doručení : (příjemce zprávu zatím nepřijal)\n";//  =    
                } else {
                    $popis .= "Datum a čas doručení : ". date("j.n.Y G:i:s",$dt_doruceni) ." (". $mess->dmAcceptanceTime .")\n";//  =                    
                }
                $popis .= "Přiblížná velikost všech příloh : ". $mess->dmAttachmentSize ."kB\n";//  =


                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleLaw ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleYear ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleSect ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePar ."\n";//  =
                //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePoint ."\n";//  =

                // Do epodatelny
                $storage_conf = Environment::getConfig('storage');
                eval("\$UploadFile = new ".$storage_conf->type."();");

                $Epodatelna = new Epodatelna();
                $config = $isds->getConfig();
                $user = Environment::getUser()->getIdentity();

                $zprava = array();
                $zprava['epodatelna_typ'] = 1;
                $zprava['poradi'] = $Epodatelna->getMax(1);
                $zprava['rok'] = date('Y');
                $zprava['isds_signature'] = $mess->dmID;
                $zprava['predmet'] = $mess->dmAnnotation;
                $zprava['popis'] = $popis;
                $zprava['odesilatel'] = $mess->dmRecipient .', '. $mess->dmRecipientAddress;
                $zprava['odesilatel_id'] = $adresat->subjekt_id;
                $zprava['adresat'] = $config['ucet'] .' ['. $config['idbox'] .']';
                $zprava['prijato_dne'] = new DateTime();

                $zprava['doruceno_dne'] = new DateTime($mess->dmAcceptanceTime);

                $zprava['prijal_kdo'] = $user->user_id;
                $zprava['prijal_info'] = serialize($user->identity);

                $zprava['sha1_hash'] = '';

                $aprilohy = array();
                if ( count($prilohy)>0 ) {
                    foreach( $prilohy as $index => $file ) {
                        $aprilohy[] = array(
                                    'name'=>$file->real_name,
                                    'size'=>$file->size,
                                    'mimetype'=> $file->mime_type,
                                    'id'=>$index
                        );
                    }
                }
                $zprava['prilohy'] = serialize($aprilohy);

                $zprava['evidence'] = 'spisovka';
                $zprava['dokument_id'] = $data['dokument_id'];
                $zprava['stav'] = 0;
                $zprava['stav_info'] = '';

                        //print_r($zprava);
                        //exit;

                        if ( $epod_id = $Epodatelna->insert($zprava) ) {

                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.zfo',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Podepsaný originál ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            $signedmess = $isds->SignedSentMessageDownload($id_mess);

                            if ( $file_o = $UploadFile->uploadEpodatelna($signedmess, $data) ) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.bsr',
                                'dir'=>'EP-O-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>' Byte-stream reprezentace ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            if ( $file = $UploadFile->uploadEpodatelna(serialize($mess), $data) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['source_id'] = $file->file_id ."-". $file_o->file_id;
                                $Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'source_id'=>$file->file_id ."-". $file_o->file_id
                                            ),
                                        array(array('epodatelna_id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }

                        } else {
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }
                
                return array(
                    'source'=>$mess,
                    'epodatelna_id'=>$epod_id,
                    'isds_signature' => $zprava,
                    'zprava'=>$popis
                );
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function createComponentSearchForm()
    {

        $hledat =  !is_null($this->hledat)?$this->hledat:'';

        $form = new AppForm();
        $form->addText('dotaz', 'Hledat:', 20, 100)
                 ->setValue($hledat);
        $form['dotaz']->getControlPrototype()->title = "Hledat lze dle věci, popisu, čísla jednacího a JID";

        $form->addSubmit('hledat', 'Hledat')
                 ->onClick[] = array($this, 'hledatSimpleClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function hledatSimpleClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->forward('this', array('hledat'=>$data['dotaz']));

    }

    protected function createComponentFiltrForm()
    {

        //$args = $Dokument->filtr('moje');
        //$args = $Dokument->filtr('predane');
        //$args = $Dokument->filtr('pracoval');
        //$args = $Dokument->filtr('moje_nove');
        //$args = $Dokument->filtr('vsichni_nove');
        //$args = $Dokument->filtr('moje_vyrizuje');
        //$args = $Dokument->filtr('vsichni_vyrizuji');

        $filtr =  !is_null($this->filtr)?$this->filtr:'moje';

        $select = array(
                    'Základní' => array(
                        'moje'=>'Dokumenty na mé jméno',
                        'predane'=>'Dokumenty, které mi byly předány',
                        'pracoval'=>'Dokumenty, nakterých jsem kdy pracoval',
                        'moje_nove'=>'Vlastní dokumenty, které jsem ještě nepředal',
                        'moje_vyrizuje'=>'Dokumenty, které vyřízuji',
                        'vsichni_nove'=>'Všechny nové dokumenty, které nebyly ještě předány',
                        'vsichni_vyrizuji'=>'Všechny dokumenty, které se vyřizuji',
                        'vse'=>'Zobrazit všechny dokumenty'
                    )
                  );

        $form = new AppForm();
        $form->addSelect('filtr', 'Filtr:', $select)
                ->setValue($filtr)
                ->getControlPrototype()->onchange("return document.forms['frm-filtrForm'].submit();");
        $form->addSubmit('go_filtr', 'Filtrovat')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'filtrClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function filtrClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $this->forward('this', array('filtr'=>$data['filtr']));

    }


}

