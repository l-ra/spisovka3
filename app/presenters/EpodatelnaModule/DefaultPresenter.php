<?php

class Epodatelna_DefaultPresenter extends BasePresenter
{

    protected $Epodatelna;

    public function __construct()
    {
        parent::__construct();
        $this->Epodatelna = new Epodatelna();
    }

    protected function isUserAllowed()
    {
        if ($this->view == "detail")
            return true;

        return parent::isUserAllowed();
    }

    public function startup()
    {
        parent::startup();
        $this->template->original_view = $this->view;
    }

    public function renderDefault()
    {
        $this->redirect('nove');
    }

    public function renderNove()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = array(
            'where' => array('ep.stav = 1 AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }
        $this->template->seznam = $seznam;

        $this->setView('seznam');
    }

    public function renderPrichozi()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = array(
            'where' => array('ep.stav >= 1 AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }
        $this->template->seznam = $seznam;

        $this->setView('seznam');
    }

    public function renderOdchozi()
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = [
            'where' => ['ep.odchozi = 1'],
            'order' => ['doruceno_dne' => 'DESC']
        ];
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);

        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParameter('print');
        $pdf = $this->getParameter('pdfprint');
        if ($tisk || $pdf) {
            $seznam = $result->fetchAll();
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }

        $this->template->seznam = $seznam;
    }

    public function renderDetail($id)
    {
        $zprava = new EpodatelnaMessage($id);

        $this->template->Zprava = $zprava;
        $this->template->Prilohy = $zprava->file_id ? EpodatelnaPrilohy::getFileList($zprava,
                        $this->storage) : [];
        $this->template->back = $this->getParameter('back', 'nove');

        if ($zprava->typ == 'E') {
            $this->addComponent(new Spisovka\Components\EmailSignature($zprava, $this->storage),
                    'emailSignature');
        }

        $this->template->Dokument = null;
        if (!empty($zprava->dokument_id)) {
            $Dokument = new Dokument();
            $this->template->Dokument = $Dokument->getInfo($zprava->dokument_id);
        }
    }

    public function renderOdetail($id)
    {
        $this->renderDetail($id);
    }

    public function renderZkontrolovat()
    {
        new SeznamStatu($this, 'seznamstatu');
    }

    // Stáhne zprávy ze všech schránek a dá uživateli vědět výsledek

    public function actionZkontrolovatAjax()
    {
        @set_time_limit(120); // z moznych dusledku vetsich poctu polozek je nastaven timeout

        /* $id = $this->getParameter('id',null);
          $typ = substr($id,0,1);
          $index = substr($id,1); */

        $ou = $this->user->getOrgUnit();
        $ou_id = $ou ? $ou->id : null;

        $config_data = (new Spisovka\ConfigEpodatelna())->get();
        $nalezena_aktivni_schranka = 0;

        // kontrola ISDS
        $isds_config = $config_data['isds'];
        if ($isds_config['aktivni'] == 1) {
            if (!$isds_config['podatelna'] || $isds_config['podatelna'] == $ou_id) {
                $nalezena_aktivni_schranka = 1;
                $zprava = $this->downloadISDS();
                echo "$zprava<br />";
            }
        }

        // kontrola emailu
        if (count($config_data['email']) > 0) {
            foreach ($config_data['email'] as $email_config) {
                if ($email_config['aktivni'] != 1)
                    continue;
                if ($email_config['podatelna'] && $email_config['podatelna'] != $ou_id)
                    continue;

                $nalezena_aktivni_schranka = 1;
                $result = $this->downloadEmails($email_config);
                if (is_string($result))
                    echo $result . '<br />';
                else if ($result > 0) {
                    echo "Z e-mailové schránky \"" . $email_config['ucet'] . "\" bylo přijato $result nových zpráv.<br />";
                } else {
                    echo 'V e-mailové schránce "' . $email_config['ucet'] . '" nebyly zjištěny žádné nové zprávy.<br />';
                }
            }
        }

        if (!$nalezena_aktivni_schranka)
            echo 'Žádná schránka není definována nebo nastavena jako aktivní.<br />';

        $this->terminate();
    }

    public function actionNactiNoveAjax()
    {
        $SubjektModel = new Subjekt();
        $isds_subjekt_cache = [];
        $email_subjekt_cache = [];

        //$client_config = Environment::getVariable('client_config');
        //$vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        //$paginator = $vp->getPaginator();
        //$paginator->itemsPerPage = 2;// isset($client_config->nastaveni->pocet_polozek)?$client_config->nastaveni->pocet_polozek:20;

        $args = array(
            'where' => array('(ep.stav = 0 OR ep.stav = 1) AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        //$paginator->itemCount = count($result);
        $zpravy = $result->fetchAll(); //$paginator->offset, $paginator->itemsPerPage);

        if (!$zpravy)
            $zpravy = null;
        else
            foreach ($zpravy as $zprava) {

                unset($zprava->identifikator);

                $prilohy = unserialize($zprava->prilohy);
                if ($zprava->typ == 'I' && $prilohy !== false)
                    $zprava->prilohy = $prilohy;
                else
                    $zprava->prilohy = false;

                $subjekt = new stdClass();
                $subjekt->mesto = '';
                $subjekt->psc = '';
                $subjekt->ulice = '';
                $subjekt->cp = '';
                $subjekt->co = '';
                $subjekt->jmeno = '';
                $subjekt->prijmeni = '';

                $original = null;
                $nalezene_subjekty = null;
                if ($zprava->typ == 'E') {
                    // Nacteni originalu emailu
                    if (!empty($zprava->file_id)) {
                        $sender = $zprava->odesilatel;
                        $matches = [];
                        if (preg_match('/(.*)<(.*)>/', $sender, $matches)) {
                            $subjekt->email = $matches[2];
                            $subjekt->nazev_subjektu = trim($matches[1]);
                            $subjekt->prijmeni = $subjekt->nazev_subjektu;
                        } else {
                            $subjekt->email = $sender;
                            $subjekt->nazev_subjektu = null;
                        }
                        $matches = [];
                        if (preg_match('/^(.*) ([^ ]*)$/', $subjekt->prijmeni, $matches)) {
                            $subjekt->jmeno = $matches[1];
                            $subjekt->prijmeni = $matches[2];
                        }

                        if (!isset($email_subjekt_cache[$subjekt->email])) {
                            $search = ['email' => $subjekt->email, 'nazev_subjektu' => $subjekt->nazev_subjektu];
                            $search = \Nette\Utils\ArrayHash::from($search);
                            $email_subjekt_cache[$subjekt->email] = $SubjektModel->hledat($search,
                                    'email', true);
                        }
                        $nalezene_subjekty = $email_subjekt_cache[$subjekt->email];
                    }
                } else if ($zprava->typ == 'I') {
                    // Nacteni originalu DS
                    if (!empty($zprava->file_id)) {
                        $original = self::nactiISDS($this->storage, $zprava->file_id);
                        $original = unserialize($original);

                        // odebrat obsah priloh, aby to neotravovalo
                        unset($original->dmDm->dmFiles);

                        $subjekt->id_isds = $original->dmDm->dbIDSender;
                        $subjekt->nazev_subjektu = $original->dmDm->dmSender;
                        $subjekt->type = ISDS_Spisovka::typDS($original->dmDm->dmSenderType);
                        if (isset($original->dmDm->dmSenderAddress)) {
                            $res = ISDS_Spisovka::parseAddress($original->dmDm->dmSenderAddress);
                            foreach ($res as $key => $value)
                                $subjekt->$key = $value;
                        }

                        if (!isset($isds_subjekt_cache[$subjekt->id_isds]))
                            $isds_subjekt_cache[$subjekt->id_isds] = $SubjektModel->hledat($subjekt,
                                    'isds', true);
                        $nalezene_subjekty = $isds_subjekt_cache[$subjekt->id_isds];
                    }
                }

                $zprava->subjekt = ['original' => $subjekt, 'databaze' => $nalezene_subjekty];

                $doruceno_dne = strtotime($zprava->doruceno_dne);
                $zprava->doruceno_dne_datum = date("j.n.Y", $doruceno_dne);
                $zprava->doruceno_dne_cas = date("G:i:s", $doruceno_dne);
                $zprava->odesilatel = htmlspecialchars($zprava->odesilatel);
            }

        $this->sendJson($zpravy);
    }

    /**
     * @return string  Zprava pro uzivatele
     */
    protected function downloadISDS()
    {
        try {
            $isds = new ISDS_Spisovka();

            $od = $this->Epodatelna->getLastISDS();
            $do = time() + 7200;

            $UploadFile = $this->storage;

            $pocet_novych_zprav = 0;
            $zpravy = $isds->seznamPrijatychZprav($od, $do);

            if ($zpravy)
                foreach ($zpravy as $z)
                // kontrola existence v epodatelny
                    if (!$this->Epodatelna->existuje($z->dmID, 'isds')) {
                        // nova zprava, ktera neni nahrana v epodatelne
                        $mess = $isds->MessageDownload($z->dmID);

                        $annotation = empty($mess->dmDm->dmAnnotation) ? "(Datová zpráva č. " . $mess->dmDm->dmID . ")"
                                    : $mess->dmDm->dmAnnotation;

                        $popis = '';
                        $popis .= "ID datové zprávy    : " . $mess->dmDm->dmID . "\n"; // = 342682
                        $popis .= "Věc, předmět zprávy : " . $annotation . "\n"; //  = Vaše datová zpráva byla přijata
                        $popis .= "\n";
                        $popis .= "Číslo jednací odesílatele   : " . $mess->dmDm->dmSenderRefNumber . "\n"; //  = AB-44656
                        $popis .= "Spisová značka odesílatele : " . $mess->dmDm->dmSenderIdent . "\n"; //  = ZN-161
                        $popis .= "Číslo jednací příjemce     : " . $mess->dmDm->dmRecipientRefNumber . "\n"; //  = KAV-34/06-ŘKAV/2010
                        $popis .= "Spisová značka příjemce    : " . $mess->dmDm->dmRecipientIdent . "\n"; //  = 0.06.00
                        $popis .= "\n";
                        $popis .= "Do vlastních rukou? : " . (!empty($mess->dmDm->dmPersonalDelivery)
                                            ? "ano" : "ne") . "\n"; //  =
                        $popis .= "Doručeno fikcí?     : " . (!empty($mess->dmDm->dmAllowSubstDelivery)
                                            ? "ano" : "ne") . "\n"; //  =
                        $popis .= "Zpráva určena pro   : " . $mess->dmDm->dmToHands . "\n"; //  =
                        $popis .= "\n";
                        $popis .= "Odesílatel:\n";
                        $popis .= "            " . $mess->dmDm->dbIDSender . "\n"; //  = hjyaavk
                        $popis .= "            " . $mess->dmDm->dmSender . "\n"; //  = Město Milotice
                        $popis .= "            " . $mess->dmDm->dmSenderAddress . "\n"; //  = Kovářská 14/1, 37612 Milotice, CZ
                        $popis .= "            " . $mess->dmDm->dmSenderType . " - " . ISDS_Spisovka::typDS($mess->dmDm->dmSenderType) . "\n"; //  = 10
                        if ($mess->dmDm->dmSenderOrgUnit)
                            $popis .= "            org.jednotka: " . $mess->dmDm->dmSenderOrgUnit . " [" . $mess->dmDm->dmSenderOrgUnitNum . "]\n"; //  =
                        $popis .= "\n";
                        $popis .= "Příjemce:\n";
                        $popis .= "            " . $mess->dmDm->dbIDRecipient . "\n"; //  = pksakua
                        $popis .= "            " . $mess->dmDm->dmRecipient . "\n"; //  = Společnost pro výzkum a podporu OpenSource
                        $popis .= "            " . $mess->dmDm->dmRecipientAddress . "\n"; //  = 40501 Děčín, CZ
                        //$popis .= "Je příjemce ne-OVM povýšený na OVM: ". $mess->dmDm->dmAmbiguousRecipient ."\n";//  =
                        if ($mess->dmDm->dmRecipientOrgUnit)
                            $popis .= "            org.jednotka: " . $mess->dmDm->dmRecipientOrgUnit . " [" . $mess->dmDm->dmRecipientOrgUnitNum . "]\n"; //  =
                        $popis .= "\n";
                        $popis .= "Status: " . $mess->dmMessageStatus . " - " . ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) . "\n";
                        $dt_dodani = strtotime($mess->dmDeliveryTime);
                        $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                        $popis .= "Datum a čas dodání   : " . date("j.n.Y G:i:s", $dt_dodani) . "\n";
                        $popis .= "Datum a čas doručení : " . date("j.n.Y G:i:s", $dt_doruceni) . "\n";
                        $popis .= "Přibližná velikost všech příloh : " . $mess->dmAttachmentSize . "kB\n";

                        $zprava = array();
                        $zprava['odchozi'] = 0;
                        $zprava['typ'] = 'I';
                        $zprava['poradi'] = $this->Epodatelna->getMax();
                        $zprava['rok'] = date('Y');
                        $zprava['isds_id'] = $z->dmID;
                        $zprava['predmet'] = $annotation;
                        $zprava['popis'] = $popis;
                        $zprava['odesilatel'] = $z->dmSender . ', ' . $z->dmSenderAddress;
                        $zprava['adresat'] = 'Datová schránka';
                        $zprava['prijato_dne'] = new DateTime();
                        $zprava['doruceno_dne'] = new DateTime($z->dmAcceptanceTime);
                        $zprava['user_id'] = $this->user->id;

                        $prilohy = array();
                        if (isset($mess->dmDm->dmFiles->dmFile)) {
                            foreach ($mess->dmDm->dmFiles->dmFile as $index => $file) {
                                $prilohy[] = array(
                                    'name' => $file->dmFileDescr,
                                    'size' => strlen($file->dmEncodedContent),
                                    'mimetype' => $file->dmMimeType,
                                    'id' => $index
                                );
                            }
                        }
                        $zprava['prilohy'] = serialize($prilohy);

                        $zprava['stav'] = 0;
                        $zprava['stav_info'] = '';

                        if ($epod_id = $this->Epodatelna->insert($zprava)) {

                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename' => 'ep_isds_' . $epod_id . '.zfo',
                                'dir' => 'EP-I-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                                'typ' => '5',
                                'popis' => 'Podepsaný originál ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                            );

                            $signedmess = $isds->SignedMessageDownload($z->dmID);

                            if ($file_o = $UploadFile->uploadEpodatelna($signedmess, $data)) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename' => 'ep_isds_' . $epod_id . '.bsr',
                                'dir' => 'EP-I-' . sprintf('%06d', $zprava['poradi']) . '-' . $zprava['rok'],
                                'typ' => '5',
                                'popis' => 'Byte-stream reprezentace ISDS zprávy z epodatelny ' . $zprava['poradi'] . '-' . $zprava['rok']
                            );

                            if ($file = $UploadFile->uploadEpodatelna(serialize($mess), $data)) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id;
                                $this->Epodatelna->update(
                                        array('stav' => 1,
                                    'stav_info' => $zprava['stav_info'],
                                    'file_id' => $file->id,
                                        ), array(array('id=%i', $epod_id))
                                );
                            } else {
                                // toto se nikam neulozi!
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }
                        } else {
                            // a toto rovnez ne
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }

                        $pocet_novych_zprav++;
                        unset($zprava);
                    }

            if ($pocet_novych_zprav)
                return "Z datové schránky bylo přijato $pocet_novych_zprav nových zpráv.";

            return "V datové schránce nebyly zjištěny žádné nové zprávy.";
        } catch (Exception $e) {
            return "Při kontrole datové schránky došlo k chybě: " . $e->getMessage();
        }
    }

    /** Stáhne nové zprávy z e-mailové schránky a uloží je do e-podatelny.
     * @param array $mailbox
     * @return string|int  počet nových zpráv nebo řetězec s popisem chyby
     */
    protected function downloadEmails($mailbox)
    {
        $imap = new ImapClient();
        $connection_string = '{' . $mailbox['server'] . ':' . $mailbox['port'] . '' . $mailbox['typ'] . '}' . $mailbox['inbox'];

        $success = $imap->connect($connection_string, $mailbox['login'], $mailbox['password']);
        if (!$success) {
            $msg = 'Nepodařilo se připojit k e-mailové schránce "' . $mailbox['ucet'] . '"!<br />
                    IMAP chyba: ' . $imap->error();
            return $msg;
        }

        if (!$imap->count_messages()) {
            //  nejsou žádné zprávy k přijetí
            $imap->close();
            return 0;
        }


        $UploadFile = $this->storage;

        $messages = $imap->get_all_messages();
        $messages_recorded = 0;

        foreach ($messages as $message) {
            // kontrola existence v epodatelne
            // chybi-li Message ID, jedna se pravdepodobne o Spam
            if (!isset($message->message_id) || $this->Epodatelna->existuje($message->message_id,
                            'email'))
                continue;

            // nova zprava, ktera neni nahrana v epodatelne
            // Nejprve uvolni pamet predchozi zpravy
            $raw_message = null;

            // Nacteni kompletni zpravy
            $structure = $imap->get_message_structure($message->Msgno);
            $raw_message = $imap->get_raw_message($message->Msgno);
            // Preved do formatu mailbox, jinak nebude IMAP knihovna fungovat
            $raw_message = "From unknown  Sat Jan  1 00:00:00 2000\r\n" . $raw_message;

            $popis = $imap->find_plain_text($message->Msgno, $structure);
            if (!$popis)
                $popis = '';
            if (strlen($popis) > 10000)
                $popis = substr($popis, 0, 10000);

            if (empty($message->subject)) {
                $predmet = "[Bez předmětu] E-mailová zpráva";
                if (!empty($message->fromaddress))
                    $predmet .= " od $message->fromaddress";
            } else
                $predmet = $message->subject;

            $insert = array();
            $insert['odchozi'] = 0;
            $insert['typ'] = 'E';
            $insert['poradi'] = $this->Epodatelna->getMax();
            $insert['rok'] = date('Y');
            $insert['email_id'] = $message->message_id;
            $insert['predmet'] = $predmet;
            $insert['popis'] = $popis;
            $insert['odesilatel'] = $message->fromaddress;
            $insert['adresat'] = $mailbox['ucet']; // označení uživatele pro e-mailovou schránku
            $insert['prijato_dne'] = new DateTime();
            $insert['doruceno_dne'] = new DateTime(date('Y-m-d H:i:s', $message->udate));
            $insert['user_id'] = $this->user->id;

            // Prilohy zjistujeme pokazde, kdyz je to potreba, aby bylo mozno zmenit/opravit
            // chovani aplikace
            $insert['prilohy'] = null;

            $insert['stav'] = 0;
            $insert['stav_info'] = '';
            $insert['file_id'] = null;

            // Test na pritomnost digitalniho podpisu
            $insert['email_signed'] = $imap->is_signed($structure);
            if ($mailbox['only_signature'] == true) {
                if (!$insert['email_signed']) {
                    // email neobsahuje epodpis
                    $insert['stav'] = 100;
                    $insert['stav_info'] = 'E-mailová zpráva byla odmítnuta. Neobsahuje elektronický podpis.';
                } else if ($mailbox['qual_signature'] == true) {
                    // pouze kvalifikovane
                    $tmp_filename = tempnam(TEMP_DIR, 'emailtest');
                    file_put_contents($tmp_filename, $raw_message);
                    $esign = new esignature();
                    $result = $esign->verifySignature($tmp_filename);
                    unlink($tmp_filename);
                    if (!$result['ok']) {
                        // neobsahuje kvalifikovany epodpis
                        $insert['stav'] = 100;
                        $insert['stav_info'] = 'E-mailová zpráva byla odmítnuta. Neobsahuje kvalifikovaný elektronický podpis';
                    }
                }
            }

            $epod_id = $this->Epodatelna->insert($insert);

            if ($insert['stav'] == 100) {
                try {
                    $reply_address = !empty($message->reply_toaddress) ? $message->reply_toaddress
                                : $message->fromaddress;
                    $mail = new ESSMail;
                    $mail->setFromConfig();
                    $mail->addTo($reply_address);
                    $mail->setSubject("Re: $predmet");
                    $mail->setBody($insert['stav_info']);
                    $mail->appendSignature($this->user);

                    $mail->send();
                } catch (Exception $e) {
                    // ignoruj pripadnou chybu
                    $e->getMessage();
                }
                continue; // odmitnout, nepokracovat dale.
            }

            $data = array(
                'filename' => 'ep_email_' . $epod_id . '.eml',
                'dir' => 'EP-I-' . sprintf('%06d', $insert['poradi']) . '-' . $insert['rok'],
                'typ' => '5',
                'popis' => 'E-mailová zpráva z epodatelny ' . $insert['poradi'] . '-' . $insert['rok']
            );

            if ($file = $UploadFile->uploadEpodatelna($raw_message, $data)) {
                $update_data = ['stav' => 1,
                    'stav_info' => 'Zpráva byla uložena',
                    'file_id' => $file->id
                ];
            } else {
                $update_data = ['stav_info' => 'Originál zprávy se nepodařilo uložit'];
            }
            $this->Epodatelna->update(
                    $update_data, [['id = %i', $epod_id]]
            );

            $messages_recorded++;
        }

        $imap->close();

        return $messages_recorded;
    }

    public static function nactiISDS($storage, $file_id)
    {
        $res = $storage->download($file_id, 1);
        return $res;
    }

    public function renderIsdsovereni($id)
    {
        $output = "Nemohu najít soubor s datovou zprávou.";
        $message = new EpodatelnaMessage($id);
        $zfo = $message->getZfoFile($this->storage);
        if ($zfo) {
            try {
                $isds = new ISDS_Spisovka();
                if ($isds->AuthenticateMessage($zfo)) {
                    $output = "Datová zpráva je platná.";
                } else {
                    $output = "Datová zpráva není platná!<br />" .
                            'ISDS zpráva: ' . $isds->GetStatusMessage();
                }
            } catch (Exception $e) {
                $output = "Nepodařilo se připojit k ISDS schránce!<br />" .
                        'chyba: ' . $e->getMessage();
            }
        }

        $this->sendJson(['id' => 'snippet-isdsovereni', 'html' => $output]);
    }

    public function actionDownloadDm($id)
    {
        $message = new EpodatelnaMessage($id);
        $data = $message->getZfoFile($this->storage);
        if (!$data)
            $this->terminate();

        $httpResponse = $this->getHttpResponse();
        $httpResponse->setContentType('application/octet-stream');
        $httpResponse->setHeader('Content-Length', strlen($data));
        $httpResponse->setHeader('Content-Description', 'File Transfer');
        $httpResponse->setHeader('Content-Disposition',
                'attachment; filename="' . "$id.zfo" . '"');
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
        $httpResponse->setHeader('Expires', '0');
        $httpResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $httpResponse->setHeader('Pragma', 'public');

        echo $data;
        $this->terminate();
    }

}
