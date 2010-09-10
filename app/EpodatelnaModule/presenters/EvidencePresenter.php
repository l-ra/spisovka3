<?php

class Epodatelna_EvidencePresenter extends BasePresenter
{

    private $filtr;
    private $hledat;
    private $Epodatelna;
    private $typ_evidence = null;
    private $odpoved = null;

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

    public function renderPridelitcj()
    {

        $dokument_id = $this->getParam('id',null);
        $user_id = $this->getParam('user',null);

        $Dokument = new Dokument();

        $CJ = new CisloJednaci();
        $cjednaci = $CJ->generuj(1);

        $data = array();
        $data['cislojednaci_id'] = $cjednaci->id;
        $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
        $data['podaci_denik'] = $cjednaci->podaci_denik;
        $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
        $data['podaci_denik_rok'] = $cjednaci->rok;


        $dokument = $Dokument->update($data, array(array('id=%i',$dokument_id)));//   array('dokument_id'=>0);// $Dokument->ulozit($data);
        $this->flashMessage('číslo jednací přiděleno');
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

    public function renderNovy()
    {

        /* Nacteni zpravy */
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $epodatelna_id = $this->getParam('id',null);
        $zprava = $this->Epodatelna->getInfo($epodatelna_id);

        $this->template->Typ_evidence = $this->typ_evidence;

        if ( $zprava ) {

            $this->template->Zprava = $zprava;
            $subjekt = new stdClass();

            if ($prilohy = unserialize($zprava->prilohy) ) {
                $this->template->Prilohy = $prilohy;
            } else {
                $this->template->Prilohy = null;
            }

            $original = null;
            if ( !empty($zprava->email_signature) ) {
                // Nacteni originalu emailu
                if ( !empty( $zprava->source_id ) ) {
                    $DefaultPresenter = new Epodatelna_DefaultPresenter();
                    $original = $DefaultPresenter->nactiEmail($zprava->source_id);
                }

                $subjekt->nazev_subjektu = $zprava->odesilatel;
                $subjekt->prijmeni = $original['zprava']->from->personal;
                $subjekt->email = $original['zprava']->from->email;

                if ( $original['signature']['signed'] == 1 ) {
                    $subjekt->nazev_subjektu = $original['signature']['cert_info']['organizace'];
                    $subjekt->prijmeni = $original['signature']['cert_info']['jmeno'];
                    $subjekt->email = $original['signature']['cert_info']['email'];
                    $subjekt->adresa_ulice = $original['signature']['cert_info']['adresa'];
                }

                $SubjektModel = new Subjekt();
                $subjekt_databaze = $SubjektModel->hledat($subjekt,'email');
                $this->template->Subjekt = array('original'=>$subjekt,'databaze'=>$subjekt_databaze);

            } else if ( !empty($zprava->isds_signature) ) {
                // Nacteni originalu DS
                if ( !empty( $zprava->source_id ) ) {
                    $DefaultPresenter = new Epodatelna_DefaultPresenter();
                    $source_id = explode("-",$zprava->source_id);
                    $original = $DefaultPresenter->nactiISDS($source_id[0]);
                    $original = unserialize($original);

                    // odebrat obsah priloh, aby to neotravovalo
                    unset($original->dmDm->dmFiles);

                    //echo "<pre>"; print_r($original); exit;

                }

                $subjekt->id_isds = $original->dmDm->dbIDSender;
                $subjekt->nazev_subjektu = $original->dmDm->dmSender;
                $subjekt->type = ISDS_Spisovka::typDS($original->dmDm->dmSenderType);
                $subjekt->adresa_ulice = $original->dmDm->dmSenderAddress;

                $SubjektModel = new Subjekt();
                $subjekt_databaze = $SubjektModel->hledat($subjekt,'isds');
                $this->template->Subjekt = array('original'=>$subjekt,'databaze'=>$subjekt_databaze);


            } else {
                // zrejme odchozi zprava ven
            }
            $this->template->Original = $original;

        } else {
            $this->flashMessage('Požadovaná zpráva neexistuje!','warning');
            $this->redirect('nove');
        }



        /* Priprava dokumentu */

        $Dokumenty = new Dokument();

        $args_rozd = array();
        $args_rozd['where'] = array(
                array('stav=%i',0),
                array('typ_dokumentu_id<>%i',4),
                array('user_created=%i',Environment::getUser()->getIdentity()->id)
        );
        $args_rozd['order'] = array('date_created'=>'DESC');

        $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);
        if ( count($rozdelany_dokument)>0 ) {
            $dokument = $rozdelany_dokument[0];

            $DokumentSpis = new DokumentSpis();
            $DokumentSubjekt = new DokumentSubjekt();
            $DokumentPrilohy = new DokumentPrilohy();

            $spisy = $DokumentSpis->spisy($dokument->id);
            $this->template->Spisy = $spisy;

            $subjekty = $DokumentSubjekt->subjekty($dokument->id);
            $this->template->Subjekty = $subjekty;

            if ( $this->typ_evidence == 'priorace' ) {
                // Nacteni souvisejicicho dokumentu
                $Souvisejici = new SouvisejiciDokument();
                $this->template->SouvisejiciDokumenty = $Souvisejici->souvisejici($dokument->id);
            }

        } else {
            $pred_priprava = array(
                "nazev" => '',
                "popis" => "",
                "stav" => 0,
                "typ_dokumentu_id" => "1",
                "cislo_jednaci_odesilatele" => "",
                "datum_vzniku" => '',
                "lhuta" => "30",
                "poznamka" => "",
                "zmocneni_id" => "0"
            );
            $dokument = $Dokumenty->ulozit($pred_priprava);

            $this->template->Spisy = null;
            $this->template->Subjekty = null;
            //$this->template->Prilohy = null;
            $this->template->SouvisejiciDokumenty = null;
            $this->template->Typ_evidence = $this->typ_evidence;

        }

        $UserModel = new UserModel();
        $user = $UserModel->getUser(Environment::getUser()->getIdentity()->id, 1);
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

    public function renderOdmitnout()
    {

        /* Nacteni zpravy */
        $Epodatelna = new Epodatelna();

        $epodatelna_id = $this->getParam('id',null);
        $zprava = $Epodatelna->getInfo($epodatelna_id);

        if ( !empty($zprava->isds_signature) ) {
            if ( !empty( $zprava->source_id ) ) {
                $DefaultPresenter = new Epodatelna_DefaultPresenter();
                $source_id = explode("-",$zprava->source_id);
                $original = $DefaultPresenter->nactiISDS($source_id[0]);
                $original = unserialize($original);
                // odebrat obsah priloh, aby to neotravovalo
                unset($original->dmDm->dmFiles);
                $this->template->original = $original->dmDm;
            }
        } else {
            $this->template->original = null;
        }

        $this->template->Zprava = $zprava;
        
    }


    public function renderZmocneni()
    {
        
    }

    protected function createComponentNovyForm()
    {


        if( isset($this->template->Dok) ) {
            $dokument_id = isset($this->template->Dok->id)?$this->template->Dok->id:0;
        } else {
            $dokument_id = 0;
        }

        if( isset($this->template->Zprava) ) {
            $zprava = isset($this->template->Zprava)?$this->template->Zprava:null;
        } else {
            $zprava = null;
        }
        if( isset($this->template->Original) ) {
            $original = isset($this->template->Original)?$this->template->Original:null;
        } else {
            $original = null;
        }

        $typ_dokumentu = Dokument::typDokumentu(null,1);
        $typ_dokumentu_extra = Dokument::typDokumentu();

        $form = new AppForm();
        $form->addHidden('dokument_id')
                ->setValue($dokument_id);
        $form->addHidden('epodatelna_id')
                ->setValue(@$zprava->id);

        $form->addHidden('odpoved')
                ->setValue($this->odpoved);
        if ( $this->typ_evidence == 'sberny_arch' ) {
            $form->addText('poradi', 'Pořadí dokumentu ve sberném archu:', 4, 4)
                    ->setValue(1)
                    ->controlPrototype->readonly = TRUE;
        }


        $form->addText('nazev', 'Věc:', 80, 100)
                ->setValue(@$zprava->predmet)
                ->addRule(Form::FILLED, 'Název dokumentu (věc) musí být vyplněno!');
        $form->addTextArea('popis', 'Stručný popis:', 80, 3);

        if ( !empty($zprava->email_signature) ) {
            foreach ($typ_dokumentu_extra as $tde) {
                if ( $tde->typ == 1 && $tde->smer == 0 ) {
                    $id_tde = $tde->id;
                    break;
                }
            }
            $form->addSelect('typ_dokumentu_id', 'Typ Dokumentu:', $typ_dokumentu)
                    ->setValue($id_tde);
        } else if ( !empty($zprava->isds_signature) ) {
            foreach ($typ_dokumentu_extra as $tde) {
                if ( $tde->typ == 2 && $tde->smer == 0 ) {
                    $id_tde = $tde->id;
                    break;
                }
            }
            $form->addSelect('typ_dokumentu_id', 'Typ Dokumentu:', $typ_dokumentu)
                    ->setValue($id_tde);
        } else {
            $form->addSelect('typ_dokumentu_id', 'Typ Dokumentu:', $typ_dokumentu)
                    ->setValue(1);
        }

        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50)
                ->setValue(@$original->zprava['cislo_jednaci_odesilatele']);

        $unixtime = strtotime(@$zprava->doruceno_dne);
        $datum = date('d.m.Y',$unixtime);
        $cas = date('H:i:s',$unixtime);
        $form->addDatePicker('datum_vzniku', 'Datum doručení:', 10)
                ->setValue($datum);
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15)
                ->setValue($cas);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->setValue('30');
        $form->addTextArea('poznamka', 'Poznámka:', 80, 6)
                ->setValue(@$zprava->popis);

        $form->addTextArea('predani_poznamka', 'Poznámka:', 80, 3)
                ->setValue('Předáno z e-podatelny');
        
        $form->addHidden('zmocneni')->setValue(0);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10);
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10);


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
        $epodatelna_id = $data['epodatelna_id'];
        $data['stav'] = 1;

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] ." ". $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
        $zprava = $this->Epodatelna->getInfo($epodatelna_id);

        $post_data = Environment::getHttpRequest()->post;
        $subjekty = isset($post_data['subjekt'])?$post_data['subjekt']:null;

        // predani
        $predani_poznamka = $data['predani_poznamka'];

        unset($data['predani_poznamka'],$data['dokument_id'],$data['epodatelna_id'],$data['dokument_version']);

        try {

            //Debug::dump($_POST);
            //Debug::dump($data); exit;
            
            $CJ = new CisloJednaci();
            if ( !empty($data['odpoved']) ) {
                $cjednaci = $CJ->nacti($data['odpoved']);
                unset($data['odpoved']);
            } else {
                $cjednaci = $CJ->generuj(1);
            }

            $data['jid'] = $cjednaci->app_id.'-ESS-'.$dokument_id;
            $data['cislojednaci_id'] = $cjednaci->id;
            $data['cislo_jednaci'] = $cjednaci->cislo_jednaci;
            $data['podaci_denik'] = $cjednaci->podaci_denik;
            $data['podaci_denik_poradi'] = $cjednaci->poradove_cislo;
            $data['podaci_denik_rok'] = $cjednaci->rok;

            $dokument = $Dokument->ulozit($data, $dokument_id, 1);//   array('dokument_id'=>0);// $Dokument->ulozit($data);

            if ( $dokument ) {

                // Ulozeni prilohy
                if ( !empty($zprava->email_signature) ) {
                    $this->emailPrilohy($epodatelna_id, $dokument_id);
                } else if ( !empty($zprava->isds_signature) ) {
                    $this->isdsPrilohy($epodatelna_id, $dokument_id);
                }

                // Ulozeni adresy
                if ( $subjekty ) {
                    $DokumentSubjekt = new DokumentSubjekt();
                    foreach( $subjekty as $subjekt_id => $subjekt_status ) {
                        if ( $subjekt_status == 'on' ) {
                            $DokumentSubjekt->pripojit($dokument_id, $subjekt_id);
                        }
                    }
                }

                // Pridani informaci do epodatelny
                if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
                $info = array(
                        'dokument_id'=>$dokument_id,
                        'evidence'=>'spisovka',
                        'stav'=>'10',
                        'stav_info'=>'Zpráva zaevidována ve spisové službě jako '.$dokument->cislo_jednaci
                    );
                $this->Epodatelna->update($info, array( array('id=%i',$epodatelna_id) ));


                // Vytvoreni cyklu
                $Workflow = new Workflow();
                $Workflow->vytvorit($dokument_id,$predani_poznamka);

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

                $this->flashMessage('Dokument byl vytvořen a zaevidován.');
                $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));
            } else {
                $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            }
        } catch (DibiException $e) {
            $this->flashMessage('Dokument se nepodařilo vytvořit.','warning');
            $this->flashMessage('CHYBA: '. $e->getMessage(),'warning');
        }

    }

    private function emailPrilohy($epodatelna_id, $dokument_id)
    {
        $EvidencePrilohy = new Epodatelna_PrilohyPresenter();
        $prilohy = $EvidencePrilohy->emailPrilohy($epodatelna_id);
        
        $storage_conf = Environment::getConfig('storage');
        eval("\$UploadFile = new ".$storage_conf->type."();");

        $DokumentFile = new DokumentPrilohy();

        // nahrani originalu
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
        $info = $this->Epodatelna->getInfo($epodatelna_id);
        $source = explode("-",$info->source_id);
        if ( isset($source[0]) ) {

            $DefaultPresenter = new Epodatelna_DefaultPresenter();
            $res = $DefaultPresenter->nactiEmail($source[0],1);
            if ($fp = @fopen($res,'rb') ) {
                $res_data = fread($fp, filesize($res));
                @fclose($fp);
            }

            $data = array(
               'filename'=>'emailova_zprava.eml',
               'dir'=> date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y'),
               'typ'=>'5',
               'popis'=>'Originální emailová zpráva'
               //'popis'=>'Emailová zpráva'
            );

            if ( $filep = $UploadFile->uploadDokumentSource($res_data, $data) ) {
                // zapiseme i do
                $DokumentFile->pripojit($dokument_id, $filep->id);
            } else {
                // false
            }

        }


        // nahrani prilohy
        if ( count($prilohy)>0 ) {
            foreach ($prilohy as $file) {

                // prekopirovani na pozadovane misto
                $data = array(
                    'filename'=>$file['file_name'],
                    'dir'=> date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y'),
                    'typ'=>'2',
                    'popis'=>''
                    //'popis'=>'Emailová zpráva'
                );

                if ( $fp = fopen($file['file'],'rb') ) {
                    $source = fread($fp, filesize($file['file']) );
                    if ( $file = $UploadFile->uploadDokumentSource($source, $data) ) {
                        // zapiseme i do
                        $DokumentFile->pripojit($dokument_id, $file->id);

                    } else {
                        // false
                    }
                    @fclose($fp);
                } else {
                    // nelze kopirovat
                }
            }
            return true;
        } else {
            return null;
        }
    }

    private function isdsPrilohy($epodatelna_id, $dokument_id)
    {
        $EvidencePrilohy = new Epodatelna_PrilohyPresenter();
        $prilohy = $EvidencePrilohy->isdsPrilohy($epodatelna_id);

        $storage_conf = Environment::getConfig('storage');
        eval("\$UploadFile = new ".$storage_conf->type."();");

        $DokumentFile = new DokumentPrilohy();

        // nahrani originalu
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
        $info = $this->Epodatelna->getInfo($epodatelna_id);
        $source = explode("-",$info->source_id);
        if ( isset($source[1]) ) {

            $DefaultPresenter = new Epodatelna_DefaultPresenter();
            $res = $DefaultPresenter->nactiISDS($source[1]);

            $data = array(
               'filename'=>'datova_zprava_'.$info->isds_signature.'.zfo',
               'dir'=> date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y'),
               'typ'=>'5',
               'popis'=>'Podepsaná originální datová zpráva'
               //'popis'=>'Emailová zpráva'
            );

            if ( $filep = $UploadFile->uploadDokumentSource($res, $data) ) {
                // zapiseme i do
                $DokumentFile->pripojit($dokument_id, $filep->id);
            } else {
                // false
            }

        }


        // nahrani priloh
        if ( count($prilohy)>0 ) {

            foreach ($prilohy as $file) {

                // prekopirovani na pozadovane misto
                $data = array(
                    'filename'=>$file['file_name'],
                    'dir'=> date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y'),
                    'typ'=>'2',
                    'popis'=>''
                    //'popis'=>'Emailová zpráva'
                );

                if ( $filep = $UploadFile->uploadDokumentSource($file['file'], $data) ) {
                     // zapiseme i do
                     $DokumentFile->pripojit($dokument_id, $filep->id);
                } else {
                        // false
                }
            }
            return true;
        } else {
            return null;
        }
    }


    public function stornoClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['dokument_id'];
        //$dokument_version = $data['dokument_version'];
        $this->redirect('this',array('id'=>$dokument_id));
    }

    public function stornoSeznamClicked(SubmitButton $button)
    {
        $this->redirect(':Epodatelna:Default:nove');
    }

    protected function createComponentEvidenceForm()
    {

        $epodatelna_id = $this->getParam('id',null);

        $form = new AppForm();
        $form->addHidden('id')
                ->setValue($epodatelna_id);
        $form->addText('evidence', 'Evidence:', 50, 100)
                ->addRule(Form::FILLED, 'Název evidence musí být vyplněno!');
        $form->addSubmit('evidovat', 'Zaevidovat')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'zaevidovatClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function zaevidovatClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        try {

            if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
            $info = array(
                    'evidence'=>$data['evidence'],
                    'stav'=>'11',
                    'stav_info'=>'Zpráva zaevidována v evidenci '.$data['evidence']
            );
            $this->Epodatelna->update($info, array( array('id=%i',$data['id']) ));
            

            $this->flashMessage('Zpráva byla zaevidována v evidenci "'.$data['evidence'].'".');
            $this->redirect(':Epodatelna:Default:detail',array('id'=>$data['id']));
            if (!$this->isAjax()) {
                //$this->redirect('this');
            } else {
                $this->invalidateControl('epodevidence');
            }

            //$this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zprávu se nepodařilo zaevidovat do jiné evidence.','warning');
        }

    }

    protected function createComponentOdmitnoutEmailForm()
    {

        $epodatelna_id = $this->getParam('id',null);
        $zprava = @$this->template->Zprava;

        $mess = "\n\n--------------------\n";
        $mess .= @$zprava->popis;


        $form = new AppForm();
        $form->addHidden('id')
                ->setValue($epodatelna_id);
        $form->addTextArea('stav_info', 'Důvod odmítnutí:', 80, 6)
                ->addRule(Form::FILLED, 'Důvod odmítnutí musí být vyplněno!');


        $form->addCheckbox('upozornit', 'Poslat upozornění odesilateli?')
                ->setValue(true);
        $form->addText('email','Komu:',80,100)
                ->setValue(@$zprava->odesilatel);
        $form->addText('predmet','Předmět:',80,100)
                ->setValue('RE: '. @$zprava->predmet);
        $form->addTextArea('zprava', 'Zpráva pro odesilatele:', 80, 6)
                ->setValue($mess);


        $form->addSubmit('odmitnout', 'Provést')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'odmitnoutEmailClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function odmitnoutEmailClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        //Debug::dump($data); exit;

        try {

            if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
            $info = array(
                    'stav'=>'100',
                    'stav_info'=>$data['stav_info']
            );
            $this->Epodatelna->update($info, array( array('id=%i',$data['id']) ));

            // odeslat email odesilateli?
            if ( $data['upozornit'] == true ) {

                $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
                $ep = $ep_config->toArray();
                if ( isset($ep['odeslani'][0]) ) {
                    if ( $ep['odeslani'][0]['aktivni'] == '1' ) {

                        $mail = new ESSMail;
                        $mail->setFromConfig();
                        $mail->signed(1);
                        $mail->addTo($data['email']);
                        $mail->setSubject($data['predmet']);
                        $mail->setBodySign($data['zprava']);
                        $mail->send();                        

                        $this->flashMessage('Upozornění odesilateli na adresu "'. $data['email'] .'" bylo úspěšně odesláno.');
                    } else {
                        $this->flashMessage('Upozornění odesilateli se nepodařilo odeslat. Nebyl zjištěn aktivní účet pro odesilání emailových zpráv ze spisové služby.','warning');
                    }
                } else {
                    $this->flashMessage('Upozornění odesilateli se nepodařilo odeslat. Nebyl zjištěn adresát pro odesilání emailových zpráv ze spisové služby.','warning');
                }

            }


            $this->flashMessage('Zpráva byla odmítnuta.');
            $this->redirect(':Epodatelna:Default:detail',array('id'=>$data['id']));
            if (!$this->isAjax()) {
                //$this->redirect('this');
            } else {
                $this->invalidateControl('epododmitnuti');
            }

            //$this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zprávu se nepodařilo odmítnout.','warning');
        }

    }

    protected function createComponentOdmitnoutISDSForm()
    {

        $epodatelna_id = $this->getParam('id',null);
        $zprava = @$this->template->Zprava;
        $original = @$this->template->original;


        $form = new AppForm();
        $form->addHidden('id')
                ->setValue($epodatelna_id);
        $form->addTextArea('stav_info', 'Důvod odmítnutí:', 80, 6)
                ->addRule(Form::FILLED, 'Důvod odmítnutí musí být vyplněno!');


        $form->addCheckbox('upozornit', 'Poslat upozornění odesilateli?')
                ->setValue(false);
        $form->addText('isds','Komu:',80,100)
                ->setValue(@$original->dbIDSender);
        $form->addText('predmet','Předmět:',80,100)
                ->setValue('[odmítnuto] '. @$zprava->predmet);

        $form->addSubmit('odmitnout', 'Provést')
                 ->setRendered(TRUE)
                 ->onClick[] = array($this, 'odmitnoutISDSClicked');


        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;
    }

    public function odmitnoutISDSClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        //Debug::dump($data); exit;

        try {

            if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
            $info = array(
                    'stav'=>'100',
                    'stav_info'=>$data['stav_info']
            );
            $this->Epodatelna->update($info, array( array('id=%i',$data['id']) ));

            // odeslat email odesilateli?
            if ( $data['upozornit'] == true ) {

                $ep_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
                $ep = $ep_config->toArray();
                if ( isset($ep['isds'][0]) ) {
                    if ( $ep['isds'][0]['aktivni'] == '1' ) {


                        // komu$data['isds']
                        // predmet $data['predmet']
                        //$mail->generateMessage();
                        //$mail->send();

                        $this->flashMessage('Upozornění odesilateli bylo úspěšně odesláno.');
                    } else {
                        $this->flashMessage('Upozornění odesilateli se nepodařilo odeslat. Nebyl zjištěn aktivní účet pro odesilání datové zprávy ze spisové služby.','warning');
                    }
                } else {
                    $this->flashMessage('Upozornění odesilateli se nepodařilo odeslat. Nebyl zjištěn adresát pro odesilání datových zpráv ze spisové služby.','warning');
                }

            }


            $this->flashMessage('Zpráva byla odmítnuta.');
            $this->redirect(':Epodatelna:Default:detail',array('id'=>$data['id']));
            if (!$this->isAjax()) {
                //$this->redirect('this');
            } else {
                $this->invalidateControl('epododmitnuti');
            }

            //$this->redirect(':Admin:Spisy:detail',array('id'=>$spis_id));
        } catch (DibiException $e) {
            $this->flashMessage('Zprávu se nepodařilo odmítnout.','warning');
        }

    }




}

