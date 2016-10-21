<?php

class Epodatelna_EvidencePresenter extends BasePresenter
{

    private $Epodatelna; // model db tabulky

    public function startup()
    {
        parent::startup();
        $this->Epodatelna = new Epodatelna();
    }

    /**
     * Zajistí, že zpráva je ve stavu "nová". Pro zamezení evidence jedné zprávy vícekrát.
     * @param EpodatelnaMessage $msg
     * @throws Exception
     */
    protected function assertMessageNotProcessed(EpodatelnaMessage $msg)
    {
        if ($msg->stav >= 10)
            throw new Exception($msg->stav != 100 ? 'Zpráva již je zaevidovaná.' : 'Zpráva již byla odmítnuta.');
    }

    public function renderNovy($id)
    {
        $zprava = new EpodatelnaMessage($id);
        $this->template->Zprava = $zprava;

        try {
            $this->assertMessageNotProcessed($zprava);
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
            $this->redirect('Default:detail', $id);
        }

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
            $dm = $this->storage->download($zprava->file_id, true);
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
    }

    public function renderOdmitnout($id)
    {
        $zprava = new EpodatelnaMessage($id);
        $this->template->Zprava = $zprava;

        if ($zprava->typ == 'I') {
            if (!empty($zprava->file_id)) {
                $original = $this->storage->download($zprava->file_id, true);
                $original = unserialize($original);
                // odebrat obsah priloh, aby to neotravovalo
                unset($original->dmDm->dmFiles);
                $this->template->original = $original->dmDm;
            }
        } else {
            $this->template->original = null;
        }
    }

    public function actionAjaxHandler()
    {
        try {
            $data = $this->getHttpRequest()->getPost();

            $id = $data['id'];
            switch ($data['volba_evidence']) {
                case 1: // evidovat
                    $evidence = $data;
                    $evidence['message_id'] = $id;

                    if (isset($data['predat'])) {
                        $predat_typ = substr($data['predat'], 0, 1);
                        if ($predat_typ == "u") {
                            $evidence['predano_user'] = (int) substr($data['predat'], 1);
                        } else if ($predat_typ == "o") {
                            $evidence['predano_org'] = (int) substr($data['predat'], 1);
                        }
                    }

                    $document = $this->evidovat($evidence);
                    echo 'Zpráva byla zaevidována ve spisové službě pod číslem <a href="'
                    . $this->link(':Spisovka:Dokumenty:detail', $document->id) . '" target="_blank">' . $document->jid . '</a>.';
                    break;

                case 2: // evidovat v jinem evidenci
                    $evidence = array(
                        'id' => $id,
                        'evidence' => $data['evidence']
                    );
                    $this->jinaEvidence($evidence);
                    echo 'Zpráva byla zaevidována v evidenci "' . $data['evidence'] . '".';
                    break;

                case 3: // odmitnout
                    $typ = $data['odmitnout_typ'];
                    if ($typ == 1) {
                        $evidence = array(
                            'id' => $id,
                            'stav_info' => $data['duvod_odmitnuti'],
                            'upozornit' => (isset($data['odmitnout']) ? 1 : 0),
                            'email' => $data['zprava_email'],
                            'predmet' => $data['zprava_predmet'],
                            'zprava' => $data['zprava_odmitnuti'],
                        );
                        $this->odmitnoutEmail($evidence);
                    } else if ($typ == 2) {
                        $evidence = array(
                            'id' => $id,
                            'stav_info' => $data['duvod_odmitnuti']
                        );
                        $this->odmitnoutISDS($evidence);
                    }
                    echo 'Zpráva byla odmítnuta.';
                    break;
            }
        } catch (Exception $e) {
            echo "###Operace se nezdařila.\nText výjimky: " . $e->getMessage();
        }

        $this->terminate();
    }

    protected function createComponentNovyForm()
    {
        $form = new Spisovka\Form();

        $form->addHidden('predano_user');
        $form->addHidden('predano_org');

        $form->addText('nazev', 'Věc:', 80, 100);
        $form->addTextArea('popis', 'Popis:', 80, 3);

        $typy_dokumentu = TypDokumentu::prichozi();
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

        $zprava = isset($this->template->Zprava) ? $this->template->Zprava : null;
        if ($zprava) {
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
                ->onClick[] = array($this, 'evidovatClicked');
        $form->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form;
    }

    public function evidovatClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        try {
            $data = $button->getForm()->getValues();
            $data['message_id'] = $this->getParameter('id');
            $dokument = $this->evidovat($data);
            $this->redirect(':Spisovka:Dokumenty:detail', array('id' => $dokument->id));
        } catch (Nette\Application\AbortException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->flashMessage('Dokument se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('CHYBA: ' . $e->getMessage(), 'warning');
        }
    }

    protected function evidovat($data)
    {
        // dump($data); die;
        $zprava = new EpodatelnaMessage($data['message_id']);
        $this->assertMessageNotProcessed($zprava);

        if (isset($data['datum_vzniku']))
            $datum_vzniku = $data['datum_vzniku'] . " " . $data['datum_vzniku_cas'];
        else
            $datum_vzniku = $zprava->doruceno_dne;

        // predani
        $predani = ['user' => isset($data['predano_user']) ? $data['predano_user'] : null,
            'org' => isset($data['predano_org']) ? $data['predano_org'] : null,
            'poznamka' => $data['predani_poznamka']];

        $document_created = false;
        try {
            dibi::begin();

            $d = [
                'dokument_typ_id' => isset($data['dokument_typ_id']) ? $data['dokument_typ_id']
                            : 1,
                'zpusob_doruceni_id' => $zprava->typ == 'E' ? 1 : 2,
                'poradi' => 1,
                'stav' => 1,
                'nazev' => $data['nazev'],
                'popis' => $data['popis'],
                'poznamka' => $data['poznamka'],
                'datum_vzniku' => $datum_vzniku,
            ];
            if (isset($data['cislo_jednaci_odesilatele']))
                $d['cislo_jednaci_odesilatele'] = $data['cislo_jednaci_odesilatele'];
            if (isset($data['pocet_listu']))
                $d['pocet_listu'] = $data['pocet_listu'];
            if (isset($data['pocet_listu_priloh']))
                $d['pocet_listu_priloh'] = $data['pocet_listu_priloh'];
            if (isset($data['lhuta']))
                $d['lhuta'] = $data['lhuta'];

            $model = new Dokument();
            $dokument_id = $model->vytvorit($d);
            $document = new Document($dokument_id);

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::DOK_NOVY);

            // Ulozeni souboru
            if ($zprava->typ == 'E') {
                $this->evidujEmailSoubory($zprava->id, $dokument_id);
            } else if ($zprava->typ == 'I') {
                $this->evidujIsdsSoubory($zprava->id, $dokument_id);
            }

            // Pripojeni subjektu
            // Subjekty nejsou soucasti Nette formulare, musime pouzit POST data
            $subjekty = null;
            $post_data = $this->getHttpRequest()->post;
            if (isset($post_data['subjekt']))
                $subjekty = $post_data['subjekt'];
            if ($subjekty) {
                $DokumentSubjekt = new DokumentSubjekt();
                foreach ($subjekty as $subjekt_id => $subjekt_status)
                    if ($subjekt_status == 'on')
                        $DokumentSubjekt->pripojit($document, new Subject($subjekt_id), 'O');
            }

            // Pridani informaci do epodatelny
            $zprava->dokument_id = $dokument_id;
            $zprava->stav = 10;
            $zprava->stav_info = 'Zpráva přidána do spisové služby jako ' . $document->jid;
            $zprava->save();

            dibi::commit();
            $document_created = true;

            $this->flashMessage('Dokument byl vytvořen.');

            if (!empty($predani['user']) || !empty($predani['org'])) {
                /* Dokument predan */
                $doc = new Document($dokument_id);
                $doc->forward($predani['user'], $predani['org'], $predani['poznamka']);
                $this->flashMessage('Dokument předán zaměstnanci nebo organizační jednotce.');

                if (!empty($predani['user'])) {
                    $osoba = Person::fromUserId($predani['user']);
                    $predano = $osoba->displayName();
                } else {
                    $orgjednotka = new OrgUnit($predani['org']);
                    $predano = $orgjednotka->ciselna_rada . " - " . $orgjednotka->plny_nazev;
                }
            } else {
                $predano = $this->user->displayName;
            }

            if ($zprava->typ == 'E') {
                $email_info = array(
                    'jid' => $document->jid,
                    'nazev' => $zprava->predmet,
                    'predano' => $predano
                );
                EmailAvizo::epodatelna_zaevidovana($zprava->odesilatel, $email_info);
            }

            return $document;
        } catch (Exception $e) {
            if (!$document_created)
                dibi::rollback();
            throw $e;
        }
    }

    /**
     *  Metoda zkopíruje email a jeho přílohy do dokumentu.
     * @param int $epodatelna_id
     * @param int $dokument_id
     * @return boolean
     */
    protected function evidujEmailSoubory($epodatelna_id, $dokument_id)
    {
        $storage = $this->storage;
        $DokumentFile = new DokumentPrilohy();

        // nahrani originalu
        $info = new EpodatelnaMessage($epodatelna_id);
        if (!$info->file_id)
            throw new Exception('Chybí originál e-mailu, zprávu nelze evidovat.');

        if (false) { // [P.L.] zruseno
            $email_contents = $storage->download($info->file_id, true);

            $data = array(
                'filename' => 'emailova_zprava.eml',
                'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                'typ' => '5',
                'popis' => 'Originální e-mailová zpráva'
            );

            if ($filep = $storage->uploadDocument($email_contents, $data)) {
                $DokumentFile->pripojit($dokument_id, $filep->id);
            }

            unset($email_contents);
        }

        $message = new EpodatelnaMessage($epodatelna_id);
        $filename = $message->getEmailFile($storage);

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
            if ($uploaded = $storage->uploadDocument($text, $upload_info))
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

            if ($uploaded = $storage->uploadDocument($data, $upload_info)) {
                $DokumentFile->pripojit($dokument_id, $uploaded->id);
            }
        }

        $imap->close();
        return true;
    }

    protected function evidujIsdsSoubory($epodatelna_id, $dokument_id)
    {
        $prilohy = EpodatelnaPrilohy::getIsdsFiles($epodatelna_id, $this->storage);

        $storage = $this->storage;

        $DokumentFile = new DokumentPrilohy();

//        $message = new EpodatelnaMessage($epodatelna_id);
//        $zfo = $message->getZfoFile($storage);
//        
//                $data = array(
//                    'filename' => 'datova_zprava_' . $message->isds_id . '.zfo',
//                    'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
//                    'typ' => '5',
//                    'popis' => 'Podepsaná originální datová zpráva'
//                );
//
//                if ($filep = $storage->uploadDocument($zfo, $data)) {
//                    // zapiseme i do
//                    $DokumentFile->pripojit($dokument_id, $filep->id);
//                } else {
//                    // false
//                }
        // nahrani priloh
        if (!count($prilohy))
            return null;

        foreach ($prilohy as $file) {
            // prekopirovani na pozadovane misto
            $data = array(
                'filename' => $file['file_name'],
                'dir' => date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y'),
                'typ' => '2',
                'popis' => ''
            );

            if ($filep = $storage->uploadDocument($file['file'], $data)) {
                $DokumentFile->pripojit($dokument_id, $filep->id);
            }
        }

        return true;
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
            $this->jinaEvidence($data);
            $this->flashMessage('Zpráva byla zaevidována v evidenci "' . $data['evidence'] . '".');
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Zprávu se nepodařilo zaevidovat do jiné evidence.', 'warning');
        }

        $this->redirect(':Epodatelna:Default:detail', array('id' => $data['id']));
    }

    protected function jinaEvidence($data)
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

    protected function odmitnoutEmail($data)
    {
        $info = array(
            'stav' => '100',
            'stav_info' => $data['stav_info']
        );
        $this->Epodatelna->update($info, array(array('id=%i', $data['id'])));

        $ajax = $this->isAjax();
        // odeslat email odesilateli?
        if ($data['upozornit'] == true) {
            $mail = new ESSMail;
            $mail->setFromConfig();
            $mail->addTo($data['email']);
            $mail->setSubject($data['predmet']);
            $mail->setBody($data['zprava']);
            $mail->appendSignature($this->user);

            $mail->send();

            if ($ajax) {
                echo 'Upozornění odesílateli na adresu "' . htmlentities($data['email']) . '" bylo úspěšně odesláno.<br />';
            } else {
                $this->flashMessage("Upozornění odesílateli na adresu \"{$data['email']}\" bylo úspěšně odesláno.");
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

    protected function odmitnoutISDS($data)
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
