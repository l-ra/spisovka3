<?php

//netteloader=Epodatelna_EvidencePresenter

class Epodatelna_EvidencePresenter extends BasePresenter
{

    private $Epodatelna; // model db tabulky

    public function startup()
    {
        parent::startup();
        $this->Epodatelna = new Epodatelna();
    }

    public function renderNovy($id)
    {
        $zprava = new EpodatelnaMessage($id);
        $this->template->Zprava = $zprava;

        $this->template->Prilohy = EpodatelnaPrilohy::getFileList($zprava, $this->storage);

        if ($zprava->typ == 'E') {
            $sender = $zprava->odesilatel;
            $matches = [];
            if (preg_match('/(.*)<(.*)>/', $sender, $matches)) {
                $email = $matches[2];
                $nazev_subjektu = trim($matches[1]);
            } else {
                $email = $sender;
                $nazev_subjektu = null;
            }
            $search = ['email' => $email, 'nazev_subjektu' => $nazev_subjektu];
            $SubjektModel = new Subjekt();
            $found_subjects = $SubjektModel->hledat($search, 'email');
            $message_subject = $search;
        }
        if ($zprava->typ == 'I') {
            $dm = Epodatelna_DefaultPresenter::nactiISDS($this->storage, $zprava->file_id);
            $dm = unserialize($dm);

            $message_subject = new stdClass();
            $message_subject->id_isds = $dm->dmDm->dbIDSender;
            $message_subject->nazev_subjektu = $dm->dmDm->dmSender;
            $message_subject->type = ISDS_Spisovka::typDS($dm->dmDm->dmSenderType);
            if (isset($dm->dmDm->dmSenderAddress)) {
                $res = ISDS_Spisovka::parseAddress($dm->dmDm->dmSenderAddress);
                foreach ($res as $key => $value)
                    $message_subject->$key = $value;
            }

            $SubjektModel = new Subjekt();
            $found_subjects = $SubjektModel->hledat($message_subject, 'isds');
        }
        
        $this->template->Subjekt = array('message' => $message_subject, 'databaze' => $found_subjects);


        /* Priprava dokumentu */
        $Dokumenty = new Dokument();

        $rozdelany = $this->getSession('s3_rozdelany');
        $rozdelany_dokument = null;

        if (isset($rozdelany->is)) {
            $args_rozd = array();
            $args_rozd['where'] = array(
                array('id=%i', $rozdelany->dokument_id)
            );
            $rozdelany_dokument = $Dokumenty->seznamKlasicky($args_rozd);
        }

        if (count($rozdelany_dokument) > 0) {
            $dokument = $rozdelany_dokument[0];

            $DokumentSpis = new DokumentSpis();
            $DokumentSubjekt = new DokumentSubjekt();

            $spisy = $DokumentSpis->spisy($dokument->id);
            $this->template->Spisy = $spisy;

            $subjekty = $DokumentSubjekt->subjekty($dokument->id);
            $this->template->Subjekty = $subjekty;
        } else {
            $pred_priprava = array(
                "nazev" => '',
                "popis" => "",
                "stav" => 0,
                "dokument_typ_id" => "1",
                "cislo_jednaci_odesilatele" => "",
                "datum_vzniku" => $zprava->doruceno_dne,
                "lhuta" => "30",
                "poznamka" => "",
            );
            $dokument = $Dokumenty->ulozit($pred_priprava);

            $rozdelany->is = 1;
            $rozdelany->dokument_id = $dokument->id;

            $this->template->Spisy = null;
            $this->template->Subjekty = null;
            //$this->template->Prilohy = null;
            $this->template->SouvisejiciDokumenty = null;
        }

        $CJ = new CisloJednaci();
        $this->template->cjednaci = $CJ->generuj();


        if ($dokument) {
            $this->template->Dok = $dokument;
        } else {
            $this->template->Dok = null;
            $this->flashMessage('Dokument není připraven k vytvoření', 'warning');
        }

        new SeznamStatu($this, 'seznamstatu');
    }

    public function renderOdmitnout($id)
    {
        $zprava = new EpodatelnaMessage($id);
        $this->template->Zprava = $zprava;

        if ($zprava->typ == 'I') {
            if (!empty($zprava->file_id)) {
                $original = Epodatelna_DefaultPresenter::nactiISDS($this->storage, $zprava->file_id);
                $original = unserialize($original);
                // odebrat obsah priloh, aby to neotravovalo
                unset($original->dmDm->dmFiles);
                $this->template->original = $original->dmDm;
            }
        } else {
            $this->template->original = null;
        }
    }

    public function actionHromadna()
    {
        $data = $this->getHttpRequest()->getPost();

        $seznam = array();
        if (isset($data['id'])) {
            // pouze jedno
            $seznam[$data['id']] = (isset($data['volba_evidence'][$data['id']])) ? $data['volba_evidence'][$data['id']]
                        : 0;
        } else if (isset($data['volba_evidence'])) {
            // vsechny
            $seznam = $data['volba_evidence'];
        }

        if (count($seznam) > 0) {
            foreach ($seznam as $id => $typ_evidence) {
                switch ($typ_evidence) {
                    case 1: // evidovat

                        $evidence = array(
                            'epodatelna_id' => $id,
                            'nazev' => $data['vec'][$id],
                            /* 'cislo_jednaci_odesilatele' => $data['cjednaci_odesilatele'][$id], */
                            'popis' => $data['popis'][$id],
                            'poznamka' => $data['poznamka'][$id],
                            'predano_poznamka' => $data['predat_poznamka'][$id],
                            'predano_user' => null,
                            'predano_org' => null,
                            'subjekt' => isset($data['subjekt'][$id]) ? $data['subjekt'][$id] : null,
                        );

                        if (isset($data['predat'][$id])) {
                            $predat_typ = substr($data['predat'][$id], 0, 1);
                            if ($predat_typ == "u") {
                                $evidence['predano_user'] = (int) substr($data['predat'][$id],
                                                1);
                            } else if ($predat_typ == "o") {
                                $evidence['predano_org'] = (int) substr($data['predat'][$id], 1);
                            }
                        }

                        // try {
                        $cislo = $this->vytvorit($evidence);
                        echo '<div class="evidence_report">Zpráva byla zaevidována ve spisové službě pod číslem "<a href="' . $this->link(':Spisovka:Dokumenty:detail',
                                array("id" => $cislo['id'])) . '" target="_blank">' . $cislo['jid'] . '</a>".</div>';
                        /* } catch (Exception $e) {
                          echo '###Zprávu číslo '.$id.' se nepodařilo zaevidovat do spisové služby.';
                          echo ' CHYBA: '. $e->getMessage();
                          } */

                        break;
                    case 2: // evidovat v jinem evidenci
                        $evidence = array(
                            'id' => $id,
                            'evidence' => $data['evidence'][$id]
                        );
                        try {
                            $this->zaevidovat($evidence);
                            echo '<div class="evidence_report">Zpráva byla zaevidována v evidenci "' . $data['evidence'][$id] . '".</div>';
                        } catch (Exception $e) {
                            echo '###Zprávu číslo ' . $id . ' se nepodařilo zaevidovat do jiné evidence.';
                            echo ' CHYBA: ' . $e->getMessage();
                        }
                        break;
                    case 3: // odmitnout
                        $typ = $data['odmitnout_typ'][$id];
                        try {
                            if ($typ == 1) {
                                $evidence = array(
                                    'id' => $id,
                                    'stav_info' => $data['duvod_odmitnuti'][$id],
                                    'upozornit' => (isset($data['odmitnout'][$id]) ? 1 : 0),
                                    'email' => $data['zprava_email'][$id],
                                    'predmet' => $data['zprava_predmet'][$id],
                                    'zprava' => $data['zprava_odmitnuti'][$id],
                                );
                                $this->odmitnoutEmail($evidence, true);
                            } else if ($typ == 2) {
                                $evidence = array(
                                    'id' => $id,
                                    'stav_info' => $data['duvod_odmitnuti'][$id]
                                );
                                $this->odmitnoutISDS($evidence);
                            }
                            echo '<div class="evidence_report">Zpráva byla odmítnuta.</div>';
                        } catch (Exception $e) {
                            echo '###Zprávu číslo ' . $id . ' se nepodařilo odmítnout.';
                            echo ' CHYBA: ' . $e->getMessage();
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        exit;
    }

    protected function createComponentNovyForm()
    {
        $form = new Spisovka\Form();

        $form->addHidden('dokument_id');
        if (isset($this->template->Dok))
            $form['dokument_id']->setValue($this->template->Dok->id);

        $form->addHidden('epodatelna_id');
        $form->addHidden('predano_user');
        $form->addHidden('predano_org');
        $form->addHidden('predano_poznamka');

        $form->addText('nazev', 'Věc:', 80, 100);
        $form->addTextArea('popis', 'Popis:', 80, 3);

        $typy_dokumentu = TypDokumentu::dostupneUzivateli();
        $form->addSelect('dokument_typ_id', 'Typ dokumentu:', $typy_dokumentu);
        if (isset($typy_dokumentu[1]))
            $form['dokument_typ_id']->setDefaultValue(1);

        $form->addText('cislo_jednaci_odesilatele', 'Číslo jednací odesilatele:', 50, 50);

        $form->addDatePicker('datum_vzniku', 'Datum doručení:');
        $form->addText('datum_vzniku_cas', 'Čas doručení:', 10, 15);

        $form->addText('lhuta', 'Lhůta k vyřízení:', 5, 15)
                ->setValue('30')
                ->setOption('description', 'dní')
                ->addRule(Nette\Forms\Form::NUMERIC, 'Lhůta k vyřízení musí být číslo');

        $form->addTextArea('poznamka', 'Poznámka:', 80, 6);

        $form->addTextArea('predani_poznamka', 'Poznámka:', 80, 3);

        $form->addText('pocet_listu', 'Počet listů:', 5, 10)->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet listů musí být číslo.');
        $form->addText('pocet_priloh', 'Počet příloh:', 5, 10)->addCondition(Nette\Forms\Form::FILLED)->addRule(Nette\Forms\Form::NUMERIC,
                'Počet příloh musí být číslo.');

        $zprava = isset($this->template->Zprava) ? $this->template->Zprava : null;
        if ($zprava) {
            $form['epodatelna_id']->setValue($zprava->id);
            $form['nazev']->setValue($zprava->predmet);
            if ($zprava->typ == 'E' && Settings::get('epodatelna_copy_email_into_documents_note'))
                $form['poznamka']->setValue(@html_entity_decode($zprava->popis));
            $unixtime = strtotime($zprava->doruceno_dne);
            $datum = date('d.m.Y', $unixtime);
            $cas = date('H:i:s', $unixtime);
            $form['datum_vzniku']
                    ->setValue($datum);
            $form['datum_vzniku_cas']
                    ->setValue($cas);
        }

        $form->addSubmit('novy', 'Vytvořit')
                ->onClick[] = array($this, 'vytvoritClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $Dokument = new Dokument();

        $dokument_id = $data['dokument_id'];
        $epodatelna_id = $data['epodatelna_id'];
        $data['stav'] = 1;

        // uprava casu
        $data['datum_vzniku'] = $data['datum_vzniku'] . " " . $data['datum_vzniku_cas'];
        unset($data['datum_vzniku_cas']);

        $zprava = new EpodatelnaMessage($epodatelna_id);

        $post_data = $this->getHttpRequest()->post;
        $subjekty = isset($post_data['subjekt']) ? $post_data['subjekt'] : null;

        // predani
        $predani_poznamka = $data['predani_poznamka'];

        unset($data['predani_poznamka'], $data['dokument_id'], $data['epodatelna_id']);

        $document_created = false;
        try {
            dibi::begin();

            $data['poradi'] = 1;

            // 1-email, 2-isds
            $data['zpusob_doruceni_id'] = $zprava->typ == 'E' ? 1 : 2;

            $predani = ['predano_user' => $data->predano_user, 'predano_org' => $data->predano_org,
                'predano_poznamka' => $data->predano_poznamka];
            unset($data->predano_user, $data->predano_org, $data->predano_poznamka);

            $dokument = $Dokument->ulozit($data, $dokument_id);

            if ($dokument) {

                // Ulozeni prilohy
                if ($zprava->typ == 'E') {
                    $this->evidujEmailSoubory($epodatelna_id, $dokument_id);
                } else if ($zprava->typ == 'I') {
                    $this->evidujIsdsSoubory($epodatelna_id, $dokument_id);
                }

                // Ulozeni adresy
                if ($subjekty) {
                    $DokumentSubjekt = new DokumentSubjekt();
                    foreach ($subjekty as $subjekt_id => $subjekt_status)
                        if ($subjekt_status == 'on')
                            $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, 'O');
                }

                // Pridani informaci do epodatelny
                $info = array(
                    'dokument_id' => $dokument_id,
                    'evidence' => 'spisovka',
                    'stav' => '10',
                    'stav_info' => 'Zpráva přidána do spisové služby jako ' . $dokument->jid
                );
                $this->Epodatelna->update($info, array(array('id=%i', $epodatelna_id)));

                $Workflow = new Workflow();
                $Workflow->vytvorit($dokument_id, $predani_poznamka);

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

                dibi::commit();
                $document_created = true;

                $this->flashMessage('Dokument byl vytvořen.');

                $rozdelany = $this->getSession('s3_rozdelany');
                unset($rozdelany->is, $rozdelany->dokument_id, $rozdelany);

                if (!empty($predani['predano_user']) || !empty($predani['predano_org'])) {
                    /* Dokument predan */
                    $Workflow->predat($dokument_id, $predani['predano_user'],
                            $predani['predano_org'], $predani['predano_poznamka']);
                    $this->flashMessage('Dokument předán zaměstnanci nebo organizační jednotce.');

                    if (!empty($predani['predano_user'])) {
                        $osoba = Person::fromUserId($predani['predano_user']);
                        $predano = Osoba::displayName($osoba);
                    } else if (!empty($predani['predano_org'])) {
                        $Org = new OrgJednotka();
                        $orgjednotka = $Org->getInfo($predani['predano_org']);
                        $predano = @$orgjednotka->ciselna_rada . " - " . @$orgjednotka->plny_nazev;
                    }
                } else {
                    $predano = $this->user->displayName;
                }

                if ($zprava->typ == 'E') {
                    $email_info = array(
                        'jid' => $dokument->jid,
                        'nazev' => $zprava->predmet,
                        'predano' => $predano
                    );
                    EmailAvizo::epodatelna_zaevidovana($zprava->odesilatel, $email_info);
                }

                $this->redirect(':Spisovka:Dokumenty:detail', array('id' => $dokument_id));
            } else {
                $this->flashMessage('Dokument se nepodařilo vytvořit.', 'warning');
            }
        } catch (Nette\Application\AbortException $e) {
            throw $e;
        } catch (Exception $e) {
            if (!$document_created)
                dibi::rollback();
            $this->flashMessage('Dokument se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Zaeviduje zprávu po odeslání hromadného formuláře.
     * Aplikační logika je duplikovaná v metodě vytvoritClicked()
     * @param array $data
     * @return array informace o čísle, pod kterým byl vytvořen dokument v modulu spisovka
     * @throws Exception
     */
    public function vytvorit($data)
    {
        $epodatelna_id = $data['epodatelna_id'];
        $zprava = new EpodatelnaMessage($epodatelna_id);

        if (!$zprava)
            throw new Exception('Nelze získat informace o zprávě!');

        $Dokument = new Dokument();

        $dokument_data = array(
            "nazev" => $data['nazev'],
            "popis" => $data['popis'],
            "stav" => 1,
            "dokument_typ_id" => "1",
            /* "cislo_jednaci_odesilatele" => $data['cislo_jednaci_odesilatele'], */
            "datum_vzniku" => $zprava['doruceno_dne'],
            "lhuta" => "30",
            "poznamka" => $data['poznamka'],
            'zpusob_doruceni_id' => $zprava->typ == 'E' ? 1 : 2
        );
        $dokument = $Dokument->ulozit($dokument_data);

        if (!$dokument)
            throw new Exception('Dokument se nepodařilo vytvořit!');

        $dokument_id = $dokument->id;

        // Ulozeni prilohy
        if ($zprava->typ == 'E') {
            $this->evidujEmailSoubory($epodatelna_id, $dokument_id);
        } else if ($zprava->typ == 'I') {
            $this->evidujIsdsSoubory($epodatelna_id, $dokument_id);
        }

        // Ulozeni adresy
        if ($data['subjekt']) {
            $DokumentSubjekt = new DokumentSubjekt();
            foreach ($data['subjekt'] as $subjekt_id => $subjekt_status) {
                if ($subjekt_status == 'on') {
                    $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, 'O');
                }
            }
        }

        // Pridani informaci do epodatelny
        $epod_info = array(
            'dokument_id' => $dokument_id,
            'evidence' => 'spisovka',
            'stav' => '10',
            'stav_info' => 'Zpráva přidána do spisové služby jako ' . $dokument->jid
        );
        $this->Epodatelna->update($epod_info, array(array('id=%i', $epodatelna_id)));

        $Workflow = new Workflow();
        $Workflow->vytvorit($dokument_id, '');

        $Log = new LogModel();
        $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

        if (!empty($data['predano_user']) || !empty($data['predano_org'])) {
            /* Dokument predan */
            $Workflow->predat($dokument_id, $data['predano_user'], $data['predano_org'],
                    $data['predano_poznamka']);
            $this->flashMessage('Dokument předán zaměstnanci nebo organizační jednotce.');

            if (!empty($data['predano_user'])) {
                $osoba = Person::fromUserId($data['predano_user']);
                $predano = Osoba::displayName($osoba);
            } else if (!empty($data['predano_org'])) {
                $Org = new OrgJednotka();
                $orgjednotka = $Org->getInfo($data['predano_org']);
                $predano = @$orgjednotka->ciselna_rada . " - " . @$orgjednotka->plny_nazev;
            }
        } else {
            $predano = $this->user->displayName;
        }

        if ($zprava->typ == 'E') {
            $email_info = array(
                'jid' => $dokument->jid,
                'nazev' => $zprava->predmet,
                'predano' => $predano
            );
            EmailAvizo::epodatelna_zaevidovana($zprava->odesilatel, $email_info);
        }

        return array('jid' => $dokument->jid, 'id' => $dokument_id);
    }

    /**
     *  Metoda zkopíruje email a jeho přílohy do dokumentu.
     * @param int $epodatelna_id
     * @param int $dokument_id
     * @return boolean
     */
    private function evidujEmailSoubory($epodatelna_id, $dokument_id)
    {
        $storage = $this->storage;
        $DokumentFile = new DokumentPrilohy();

        // nahrani originalu
        $info = new EpodatelnaMessage($epodatelna_id);
        if (!$info->file_id)
            throw new Exception('Chybí originál e-mailu, zprávu nelze evidovat.');

        if (false) { // [P.L.] zruseno
            $email_contents = $storage->download($info->file_id, 1);

            $data = array(
                'filename' => 'emailova_zprava.eml',
                'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                'typ' => '5',
                'popis' => 'Originální emailová zpráva'
            );

            if ($filep = $storage->uploadDokumentSource($email_contents, $data)) {
                $DokumentFile->pripojit($dokument_id, $filep->id);
            }

            unset($email_contents);
        }

        $message = new EpodatelnaMessage($epodatelna_id);
        $filename = $message->getMessageSource($storage);

        $imap = new ImapClient();
        $imap->open($filename);
        $structure = $imap->get_message_structure(1);
        
        $text = $imap->find_plain_text(1, $structure);
        if ($text) {
            $upload_info = array(
                'filename' => 'zprava.txt',
                'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                'typ' => '1',
                'popis' => 'Text e-mailové zprávy'
            );
            if ($uploaded = $storage->uploadDokumentSource($text, $upload_info))
                $DokumentFile->pripojit($dokument_id, $uploaded->id);            
        }
        
        $attachments = $imap->get_attachments($structure);
        foreach ($attachments as $part_number => $attachment) {

            $filename = $attachment->dparameters['FILENAME'];
            if ($filename == 'smime.p7s')
                continue;
            
            $data = $imap->fetch_body_part(1, $part_number);
            $data = $imap->decode_data($data, $attachment);            

            // prekopirovani na pozadovane misto
            $upload_info = array(
                'filename' => $filename,
                'nazev' => $filename,
                'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                'typ' => '2',
                'popis' => ''
            );

            if ($uploaded = $storage->uploadDokumentSource($data, $upload_info)) {
                $DokumentFile->pripojit($dokument_id, $uploaded->id);
            }
        }

        $imap->close();
        return true;
    }

    private function evidujIsdsSoubory($epodatelna_id, $dokument_id)
    {
        $prilohy = EpodatelnaPrilohy::getIsdsFiles($epodatelna_id, $this->storage);

        $UploadFile = $this->storage;

        $DokumentFile = new DokumentPrilohy();

        $info = new EpodatelnaMessage($epodatelna_id);
        if ($info) {
            $FileModel = new FileModel();
            $file_info = $FileModel->select(array(array('real_name=%s', 'ep-isds-' . $epodatelna_id . '.zfo')))->fetch();
            if ($file_info) {
                $res = Epodatelna_DefaultPresenter::nactiISDS($this->storage, $file_info->id);

                $data = array(
                    'filename' => 'datova_zprava_' . $info->isds_id . '.zfo',
                    'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                    'typ' => '5',
                    'popis' => 'Podepsaná originální datová zpráva'
                        //'popis'=>'Emailová zpráva'
                );

                if ($filep = $UploadFile->uploadDokumentSource($res, $data)) {
                    // zapiseme i do
                    $DokumentFile->pripojit($dokument_id, $filep->id);
                } else {
                    // false
                }
            }
        }

        // nahrani priloh
        if (count($prilohy) > 0) {

            foreach ($prilohy as $file) {

                // prekopirovani na pozadovane misto
                $data = array(
                    'filename' => $file['file_name'],
                    'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                    'typ' => '2',
                    'popis' => ''
                        //'popis'=>'Emailová zpráva'
                );

                if ($filep = $UploadFile->uploadDokumentSource($file['file'], $data)) {
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

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $dokument_id = $data['dokument_id'];
        //$dokument_version = $data['dokument_version'];
        $this->redirect('this', array('id' => $dokument_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Epodatelna:Default:nove');
    }

    protected function createComponentJinaEvidenceForm()
    {
        $form = new Spisovka\Form();
        $form->addHidden('id')
                ->setValue($this->getParameter('id'));
        $form->addText('evidence', 'Evidence:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název evidence musí být vyplněn!');
        $form->addSubmit('evidovat', 'Zaevidovat')
                ->onClick[] = array($this, 'zaevidovatClicked');
        $form->onError[] = array($this, 'validationFailed');

        return $form;
    }

    public function validationFailed()
    {
        $this->redirect('Default:detail', ['id' => $this->getParameter('id')]);
    }

    public function zaevidovatClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        try {
            $this->zaevidovat($data);
            $this->flashMessage('Zpráva byla zaevidována v evidenci "' . $data['evidence'] . '".');
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Zprávu se nepodařilo zaevidovat do jiné evidence.', 'warning');
        }

        $this->redirect(':Epodatelna:Default:detail', array('id' => $data['id']));
    }

    private function zaevidovat($data)
    {
        $info = array(
            'evidence' => $data['evidence'],
            'stav' => '11',
            'stav_info' => 'Zpráva zaevidována v evidenci: ' . $data['evidence']
        );
        return $this->Epodatelna->update($info, array(array('id = %i', $data['id'])));
    }

    protected function createComponentOdmitnoutEmailForm()
    {
        $epodatelna_id = $this->getParameter('id', null);
        $zprava = @$this->template->Zprava;

        $mess = "\n\n--------------------\n";
        $mess .= @$zprava->popis;

        $form = new Spisovka\Form();
        $form->addHidden('id')
                ->setValue($epodatelna_id);
        $form->addTextArea('stav_info', 'Důvod odmítnutí:', 80, 6)
                ->addRule(Nette\Forms\Form::FILLED, 'Důvod odmítnutí musí být vyplněn!');

        $form->addCheckbox('upozornit', 'Poslat upozornění odesilateli?')
                ->setValue(true);
        $form->addText('email', 'Komu:', 80, 100)
                ->setValue(@$zprava->odesilatel);
        $form->addText('predmet', 'Předmět:', 80, 100)
                ->setValue('RE: ' . @$zprava->predmet);
        $form->addTextArea('zprava', 'Zpráva pro odesilatele:', 80, 6)
                ->setValue($mess);

        $form->addSubmit('odmitnout', 'Provést')
                ->onClick[] = array($this, 'odmitnoutEmailClicked');
        $form->onError[] = array($this, 'validationFailed');

        return $form;
    }

    public function odmitnoutEmailClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        try {
            $this->odmitnoutEmail($data);
            $this->flashMessage('Zpráva byla odmítnuta.');
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Zprávu se nepodařilo odmítnout.', 'warning');
        }

        $this->redirect(':Epodatelna:Default:detail', array('id' => $data['id']));
    }

    private function odmitnoutEmail($data, $hromadna = false)
    {
        $info = array(
            'stav' => '100',
            'stav_info' => $data['stav_info']
        );
        $this->Epodatelna->update($info, array(array('id=%i', $data['id'])));

        // odeslat email odesilateli?
        if ($data['upozornit'] == true) {

            $ep = (new Spisovka\ConfigEpodatelna())->get();
            if (isset($ep['odeslani'][0])) {

                $mail = new ESSMail;
                $mail->setFromConfig();
                $mail->addTo($data['email']);
                $mail->setSubject($data['predmet']);
                $zprava = ESSMail::appendSignature($data['zprava'], $this->user);
                $mail->setBody($zprava);
                $mail->send();

                if ($hromadna) {
                    echo 'Upozornění odesílateli na adresu "' . htmlentities($data['email']) . '" bylo úspěšně odesláno.';
                } else {
                    $this->flashMessage('Upozornění odesílateli na adresu "' . htmlentities($data['email']) . '" bylo úspěšně odesláno.');
                }
            } else {
                if ($hromadna) {
                    echo '###Upozornění odesílateli se nepodařilo odeslat. Nebyl zjištěn adresát pro odesílání emailových zpráv ze spisové služby.';
                } else {
                    $this->flashMessage('Upozornění odesílateli se nepodařilo odeslat. Nebyl zjištěn adresát pro odesílání emailových zpráv ze spisové služby.',
                            'warning');
                }
            }
        }
    }

    protected function createComponentOdmitnoutISDSForm()
    {
        $epodatelna_id = $this->getParameter('id', null);
//        $zprava = @$this->template->Zprava;
//        $original = @$this->template->original;

        $form = new Spisovka\Form();
        $form->addHidden('id')
                ->setValue($epodatelna_id);
        $form->addTextArea('stav_info', 'Důvod odmítnutí:', 80, 6)
                ->addRule(Nette\Forms\Form::FILLED, 'Důvod odmítnutí musí být vyplněn!');

        /* $form->addCheckbox('upozornit', 'Poslat upozornění odesilateli?')
          ->setValue(false);
          $form->addText('isds', 'Komu:', 80, 100)
          ->setValue(@$original->dbIDSender);
          $form->addText('predmet', 'Předmět:', 80, 100)
          ->setValue('[odmítnuto] ' . @$zprava->predmet); */

        $form->addSubmit('odmitnout', 'Provést')
                ->onClick[] = array($this, 'odmitnoutISDSClicked');
        $form->onError[] = array($this, 'validationFailed');

        return $form;
    }

    public function odmitnoutISDSClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        try {
            $this->odmitnoutISDS($data);
            $this->flashMessage('Zpráva byla odmítnuta.');
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Zprávu se nepodařilo odmítnout.', 'warning');
        }

        $this->redirect(':Epodatelna:Default:detail', array('id' => $data['id']));
    }

    private function odmitnoutISDS($data)
    {
        $info = array(
            'stav' => '100',
            'stav_info' => $data['stav_info']
        );
        $this->Epodatelna->update($info, array(array('id=%i', $data['id'])));
    }

    public function renderJiny($id)
    {
        
    }

}
