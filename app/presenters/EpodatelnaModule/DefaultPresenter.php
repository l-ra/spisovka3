<?php

namespace Spisovka;

use Nette;

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
        /**
         * Ošetři speciální případ, kdy zprávu v e-podatelně může zobrazit
         * i uživatel, který do e-podatelny jinak nemá přístup.
         */
        $msg_id = $this->getParameter('id');
        $doc_id = $this->getParameter('dok_id');
        if ($this->view == "detail" && $msg_id && $doc_id) {
            /**
             * Zkontroluj, že uživatel nepodvádí, aby nezískal neoprávněný přístup
             * ke všem zprávám.
             * Že má právo číst dokument a dokument je spojen se zprávou v e-podatelně.
             */
            $doc = new Document($doc_id);
            $perm = $doc->getUserPermissions();
            if (!$perm['view'])
                return false;
            $msg = EpodatelnaMessage::fromDocument($doc);
            return $msg_id == $msg->id;
        }

        return parent::isUserAllowed();
    }

    public function renderDefault()
    {
        $this->redirect('nove');
    }

    public function renderNove($hledat)
    {
        $this->renderList($hledat, true, false);
    }

    public function renderPrichozi($hledat)
    {
        $this->renderList($hledat, false, false);
    }

    public function renderOdchozi($hledat)
    {
        $this->renderList($hledat, false, true);
    }

    protected function renderList($hledat, $new, $outgoing)
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $args = ['where' => []];
        $args['where'][] = ['odchozi = %i', $outgoing];
        if ($new)
            $args['where'][] = 'stav = 1';
        if ($hledat) {
            $subject = $outgoing ? 'adresat' : 'odesilatel';
            $args['where'][] = ["predmet LIKE %s OR $subject LIKE %s", "%$hledat%", "%$hledat%"];
        }
        if ($outgoing)
            $args['order'] = ['odeslano_dne' => 'DESC'];

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

    public function renderDetail($id, $doruceni = false, $back = null, $dok_id = null)
    {
        $msg = EpodatelnaMessage::factory($id);

        $this->template->Zprava = $msg;
        $this->template->Prilohy = EpodatelnaPrilohy::getFileList($msg, $this->storage);
        $this->template->back = $this->getParameter('back', 'nove');

        if ($msg instanceof IsdsMessage) {
            $envelope = $msg->formatEnvelope($this->storage);
            if ($envelope)
                $this->template->Zprava->popis = $envelope;
            else if ($msg->odchozi) {
                // záložní řešení, takto fungovalo zobrazení datové zprávy dříve
                // odstraň z výpisu nepravdivé/zavádějící informace
                $popis = $this->template->Zprava->popis;
                $this->template->Zprava->popis = preg_replace('/Status.*Přibl/s', 'Přibl',
                        $popis);
            }

            if ($doruceni)
                try {
                    $msg = $this->template->Zprava; // zpráva načtena už v renderDetail()
                    $isds = new ISDS_Spisovka();
                    $delivery = $isds->GetDeliveryInfo($msg->isds_id);
                    $this->template->delivery = $delivery;
                } catch (\Exception $e) {
                    $this->flashMessage('Při zjišťování doručení došlo k chybě.', 'warning');
                    $this->flashMessage($e->getMessage());
                }
        }

        if ($msg instanceof EmailMessage)
            $this->addComponent(new Components\EmailSignature($msg, $this->storage),
                    'emailSignature');

        $this->template->Dokument = null;
        if (!empty($msg->dokument_id)) {
            $Dokument = new Dokument();
            $this->template->Dokument = $Dokument->getInfo($msg->dokument_id);
        }
    }

    public function renderOdetail($id, $doruceni = false)
    {
        $this->renderDetail($id, $doruceni);
    }

    public function renderZkontrolovat()
    {
        new Components\SeznamStatu($this, 'seznamstatu');

        $config = (new ConfigEpodatelna())->get();
        $this->template->RequestIsdsPassword = $config['isds']['aktivni'] && Settings::get(Admin_EpodatelnaPresenter::ISDS_INDIVIDUAL_LOGIN,
                        false) && empty(UserSettings::get('isds_password'));
    }

    /** Stáhne zprávy ze všech schránek a dá uživateli vědět výsledek
     * 
     * @param string $password
     */
    public function actionZkontrolovatAjax($password = null)
    {
        @set_time_limit(120); // je potreba zvysit timeout pro pripad vetsiho mnozstvi zprav

        $ou = $this->user->getOrgUnit();
        $ou_id = $ou ? $ou->id : null;

        $config_data = (new ConfigEpodatelna())->get();
        $nalezena_aktivni_schranka = 0;

        try {
            $lock = new LockNotBlocking('epodatelna');
            $lock = $lock;

            // odemkni session soubor, neblokuj ostatni pozadavky
            $this->getSession()->close();

            // kontrola ISDS
            $isds_config = $config_data['isds'];
            if ($isds_config['aktivni'] == 1) {
                // Kdyz uzivatel zadava pokazde sve heslo do datove schranky ($password != null),
                // ignoruj omezeni podatelny
                if ($password || !$isds_config['podatelna'] || $isds_config['podatelna'] == $ou_id) {
                    $nalezena_aktivni_schranka = 1;
                    $zprava = $this->downloadISDS($password);
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
        } catch (WouldBlockException $e) {
            $e->getMessage();
            echo "Stahování zpráv ze schránek právě probíhá. Opakujte operaci později.<br />";
        }

        $this->terminate();
    }

    public function renderNactiNoveAjax()
    {
        $SubjektModel = new Subjekt();
        $isds_subjekt_cache = [];
        $email_subjekt_cache = [];

        $args = array(
            'where' => array('(ep.stav = 0 OR ep.stav = 1) AND ep.odchozi = 0')
        );
        $result = $this->Epodatelna->seznam($args);
        $zpravy = $result->fetchAll();

        if (!$zpravy)
            $zpravy = [];
        else
            foreach ($zpravy as $zprava) {

                unset($zprava->identifikator);

                $prilohy = unserialize($zprava->prilohy);
                if ($zprava->typ == 'I' && $prilohy !== false)
                    $zprava->prilohy = $prilohy;
                else
                    $zprava->prilohy = false;

                $subjekt = new \stdClass();
                $subjekt->mesto = '';
                $subjekt->psc = '';
                $subjekt->ulice = '';
                $subjekt->cp = '';
                $subjekt->co = '';
                $subjekt->jmeno = '';
                $subjekt->prijmeni = '';

                $message = null;
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
                    $zprava->popis = '';
                    // Nacteni originalu DS
                    if (!empty($zprava->file_id)) {
                        $message = $this->storage->download($zprava->file_id, true);
                        $message = unserialize($message);
                        // $message je odpoved serveru ISDS na operaci DownloadMessage
                        // zprava samotna je v $message->dmDm;
                        if (isset($message->dmDm->dbIDSender)) {
                            $subjekt->id_isds = $message->dmDm->dbIDSender;
                            $subjekt->nazev_subjektu = $message->dmDm->dmSender;
                            $subjekt->type = ISDS_Spisovka::typDS($message->dmDm->dmSenderType);
                            if (isset($message->dmDm->dmSenderAddress)) {
                                $res = ISDS_Spisovka::parseAddress($message->dmDm->dmSenderAddress);
                                foreach ($res as $key => $value)
                                    $subjekt->$key = $value;
                            }

                            if (!isset($isds_subjekt_cache[$subjekt->id_isds]))
                                $isds_subjekt_cache[$subjekt->id_isds] = $SubjektModel->hledat($subjekt,
                                        'isds', true);
                            $nalezene_subjekty = $isds_subjekt_cache[$subjekt->id_isds];
                        }
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
    protected function downloadISDS($password)
    {
        try {
            $last_download_key = 'isds_last_download_time';
            $isds = new ISDS_Spisovka($password);

            $od = Settings::get($last_download_key); // novy zpusob od verze 3.5.0
            if ($od)
                $od -= 2 * 3600;
            else {
                $od = $this->Epodatelna->getLastISDSAcceptanceTime();
                $od = $od ? $od - 3 * 24 * 3600 : 0; // 3 dny zpet
            }
            $do = time() + 7200;

            $pocet_novych_zprav = 0;
            $error_count = 0;
            $zpravy = $isds->seznamPrijatychZprav($od, $do);

            if ($zpravy)
                foreach ($zpravy as $z)
                    if (!$this->Epodatelna->existuje($z->dmID, 'isds')) {
                        // nova zprava, ktera neni zaznamenana v epodatelne
                        $complete_msg = $isds->MessageDownload($z->dmID);
                        if (!$complete_msg) {
                            /* Pravděpodobně zpráva do vlastních rukou a uživatel, pod kterým
                             * se spisovka do ISDS připojuje, nemá v datové
                             * schránce nastaveno oprávnění číst zprávy do v.r.
                             */
                            if (!$z->dmPersonalDelivery) {
                                $error_count++;
                                continue;
                            }
                            $complete_msg = new \stdClass();
                        }

                        $new_record = array();
                        $new_record['odchozi'] = 0;
                        $new_record['typ'] = 'I';
                        $new_record['poradi'] = $this->Epodatelna->getMax();
                        $new_record['rok'] = date('Y');
                        $new_record['isds_id'] = $z->dmID;
                        $new_record['predmet'] = $z->dmAnnotation;
                        $new_record['popis'] = null;
                        $new_record['odesilatel'] = $z->dmSender . ', ' . $z->dmSenderAddress;
                        $new_record['adresat'] = 'Datová schránka';
                        $new_record['prijato_dne'] = new \DateTime();
                        $new_record['doruceno_dne'] = new \DateTime($z->dmAcceptanceTime);
                        $new_record['user_id'] = $this->user->id;
                        unset($z->dmOrdinal);
                        $new_record['isds_envelope'] = serialize($z);

                        $prilohy = array();
                        if (isset($complete_msg->dmDm->dmFiles->dmFile)) {
                            foreach ($complete_msg->dmDm->dmFiles->dmFile as $index => $file) {
                                $prilohy[] = array(
                                    'name' => $file->dmFileDescr,
                                    'size' => strlen($file->dmEncodedContent),
                                    'mimetype' => $file->dmMimeType,
                                    'id' => $index
                                );
                            }
                        }
                        $new_record['prilohy'] = serialize($prilohy);

                        $new_record['stav'] = 0;
                        $new_record['stav_info'] = '';

                        $msg = IsdsMessage::create($new_record);
                        $epod_id = $msg->id;

                        /* Ulozeni podepsane ISDS zpravy v ZFO formátu */
                        $file_data = array(
                            'filename' => 'ep-isds-' . $epod_id . '.zfo',
                            'dir' => 'EP-I-' . sprintf('%06d', $msg->poradi) . '-' . $msg->rok,
                            'popis' => null
                        );

                        $stav_info = '';
                        if ($z->dmMessageStatus >= 6) {
                            $signedmess = $isds->SignedMessageDownload($z->dmID);
                            $file_signed = $this->storage->uploadEpodatelna($signedmess,
                                    $file_data, $this->user);
                            if (!$file_signed)
                                $stav_info = 'Podepsanou zprávu se nepodařilo uložit';
                        } else
                            $stav_info = 'Nedoručenou zprávu není možné stáhnout';

                        /* Ulozeni nepodepsane zpravy */
                        $file_data = array(
                            'filename' => 'ep-isds-' . $epod_id . '.bsr',
                            'dir' => 'EP-I-' . sprintf('%06d', $msg->poradi) . '-' . $msg->rok,
                            'popis' => 'Byte-stream reprezentace ISDS zprávy z epodatelny ' . $msg->poradi . '-' . $msg->rok
                        );

                        if ($file = $this->storage->uploadEpodatelna(serialize($complete_msg),
                                $file_data, $this->user)) {
                            // ok
                            if (empty($stav_info))
                                $stav_info = 'Zpráva byla uložena';
                            $msg->stav = 1;
                            $msg->file_id = $file->id;
                        } else {
                            $stav_info = 'Reprezentaci zprávy se nepodařilo uložit';
                        }
                        $msg->stav_info = $stav_info;
                        $msg->save();

                        $pocet_novych_zprav++;
                    }

            // po úspěšném dokončení zaznamenej čas stažení zpráv
            Settings::set($last_download_key, time());

            $error_msg = $error_count ? " Chyba: $error_count zpráv se nepodařilo načíst." : '';
            if ($pocet_novych_zprav)
                return "Z datové schránky bylo přijato $pocet_novych_zprav nových zpráv.$error_msg";

            return "V datové schránce nebyly zjištěny žádné nové zprávy.$error_msg";
        } catch (\Exception $e) {
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
                $popis = mb_substr($popis, 0, 10000);

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
            $insert['prijato_dne'] = new \DateTime();
            $insert['doruceno_dne'] = new \DateTime(date('Y-m-d H:i:s', $message->udate));
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
                    $esign = new \esignature();
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
                    $mail = new Mail;
                    $mail->setFromConfig();
                    $mail->addTo($reply_address);
                    $mail->setSubject("Re: $predmet");
                    $mail->setBody($insert['stav_info']);
                    $mail->appendSignature($this->user);

                    $mail->send();
                } catch (\Exception $e) {
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

            if ($file = $UploadFile->uploadEpodatelna($raw_message, $data, $this->user)) {
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

    public function renderIsdsOvereni($id)
    {
        $output = "Nemohu najít soubor s datovou zprávou.";
        $message = new IsdsMessage($id);
        $zfo = $message->getZfoFile($this->storage, false);
        if ($zfo) {
            try {
                $isds = new ISDS_Spisovka();
                if ($isds->AuthenticateMessage($zfo)) {
                    $output = "Datová zpráva je platná.";
                } else {
                    $output = "Datová zpráva není platná!<br />" .
                            'ISDS zpráva: ' . $isds->GetStatusMessage();
                }
            } catch (\Exception $e) {
                $output = "Nepodařilo se připojit k ISDS schránce!<br />" .
                        'chyba: ' . $e->getMessage();
            }
        }

        $this->sendJson(['id' => 'snippet-isdsovereni', 'html' => $output]);
    }

    public function renderDownloadDm($id)
    {
        $message = new IsdsMessage($id);
        $result = $message->getZfoFile($this->storage, true);
        if ($result === null)
            echo "Soubor s podepsanou datovou zprávou chybí.";
        $this->terminate();
    }

}
