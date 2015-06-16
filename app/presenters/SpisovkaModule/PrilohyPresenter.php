<?php

class Spisovka_PrilohyPresenter extends BasePresenter
{

    protected $file_id;
    protected $dokument_id;

    public function renderPridat()
    {
        $this->file_id = $this->getParameter('id', null);
        $this->dokument_id = $this->getParameter('dok_id', null);
    }

    public function renderUpravit()
    {
        $this->file_id = $this->getParameter('id', null);
        $this->dokument_id = $this->getParameter('dok_id', null);
    }

    public function renderNacti()
    {
        $dokument_id = $this->getParameter('id', null); // tady jako dokument_id

        $DokumentPrilohy = new DokumentPrilohy();
        $seznam = $DokumentPrilohy->prilohy($dokument_id);
        $this->template->seznamPriloh = $seznam;
        $this->template->dokument_id = $dokument_id;
    }

    // Je volano pres AJAX, takze volani flashMessage() postradaji smysl
    public function actionOdebrat()
    {
        $file_id = $this->getParameter('id', null);
        $dokument_id = $this->getParameter('dok_id', null);

        $FileModel = new FileModel();
        $file_info = $FileModel->getInfo($file_id);

        $DokumentPrilohy = new DokumentPrilohy();

        if ($DokumentPrilohy->odebrat($dokument_id, $file_id)) {

            $UploadFile = $this->storage;

            try {
                $UploadFile->remove($file_id);
            } catch (Exception $e) {
                // Priloha muze byt sdilena mezi dokumentem a odpovedi, tudiz nemusi
                // byt mozne ji fyzicky smazat
            }

            $Log = new LogModel();
            $Log->logDokument($dokument_id, LogModel::PRILOHA_ODEBRANA,
                    'Odebrána příloha "' . $file_info->nazev . ' (' . $file_info->real_name . ')"');
        } else {
            
        }

        $this->terminate();
    }

    /**
     *
     * Formular pro nahrani priloh
     *
     */
    protected function createComponentUploadForm()
    {

        $form1 = new Nette\Application\UI\Form();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onSubmit = "return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})";

        if (isset($this->dokument_id)) {
            $form1->addHidden('dokument_id')
                    ->setValue($this->dokument_id);
        } else {
            $form1->addHidden('dokument_id')
                    ->setValue(0);
        }

        $form1->addText('priloha_nazev', 'Název přílohy:', 50, 150);
        $form1->addTextArea('priloha_popis', 'Popis:', 80, 5);
        $form1->addSelect('priloha_typ', 'Typ souboru', FileModel::typPrilohy());
        $form1->addUpload('file', 'Soubor:');
        $form1->addSubmit('upload', 'Nahrát')
                ->onClick[] = array($this, 'uploadClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function uploadClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $upload = $data['file'];

        $dokument_id = $data['dokument_id'];

        $data['nazev'] = $data['priloha_nazev'];
        $data['popis'] = $data['priloha_popis'];
        $data['typ'] = $data['priloha_typ'];
        $typ = $data['typ'];
        unset($data['dokument_id'], $data['priloha_nazev'], $data['priloha_popis'],
                $data['priloha_typ']);

        $data['dir'] = date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y');

        // Nacteni rozhrani pro upload dle nastaveni
        $UploadFile = $this->storage;

        try {


            if ($file = $UploadFile->uploadDokument($data)) {
                // pripojit k dokumentu
                $this->template->chyba = $file;
                $DokumentPrilohy = new DokumentPrilohy();

                if ($DokumentPrilohy->pripojit($dokument_id, $file->id)) {

                    $Log = new LogModel();
                    $FileModel = new FileModel();
                    $file_info = $FileModel->getInfo($file->id);
                    $Log->logDokument($dokument_id, LogModel::PRILOHA_PRIDANA,
                            'Přidána příloha "' . $file_info->nazev . ' (' . $file_info->real_name . ')"');

                    echo '###vybrano###' . $dokument_id;
                    $this->terminate();
                } else {
                    $this->template->chyba = 1;
                }
            } else {
                $this->template->chyba = 2;
                $this->template->error_message = $UploadFile->errorMessage();
            }
        } catch (Exception $e) {
            $this->template->chyba = 2;
            $this->template->error_message = $e->getMessage();
        }
    }

    /**
     *
     * Formular pro nahrani priloh
     *
     */
    protected function createComponentReUploadForm()
    {

        $File = new FileModel();
        $file_info = $File->getInfo($this->file_id);

        $form1 = new Nette\Application\UI\Form();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onSubmit = "return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})";

        if (isset($this->dokument_id)) {
            $form1->addHidden('dokument_id')
                    ->setValue($this->dokument_id);
        } else {
            $form1->addHidden('dokument_id')
                    ->setValue(0);
        }
        $form1->addHidden('file_id')
                ->setValue($this->file_id);

        $form1->addText('priloha_nazev', 'Název přílohy:', 50, 150)
                ->setValue(@$file_info->nazev);
        $form1->addTextArea('priloha_popis', 'Popis:', 80, 5)
                ->setValue(@$file_info->popis);
        $form1->addSelect('priloha_typ', 'Typ souboru', FileModel::typPrilohy())
                ->setValue(@$file_info->typ);
        $form1->addUpload('file', 'Soubor:');
        $form1->addSubmit('upload', 'Nahrát')
                ->onClick[] = array($this, 'reUploadClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }

    public function reUploadClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $upload = $data['file'];

        $dokument_id = $data['dokument_id'];
        $file_id = $data['file_id'];

        $data['nazev'] = $data['priloha_nazev'];
        $data['popis'] = $data['priloha_popis'];
        $data['typ'] = $data['priloha_typ'];
        $typ = $data['typ'];
        unset($data['priloha_nazev'], $data['priloha_popis'], $data['priloha_typ']);
        unset($data['dokument_id']);
        unset($data['file_id']);

        $data['dir'] = date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y');

        if ($upload->error == 0) {
            // Nacteni rozhrani pro upload dle nastaveni
            $UploadFile = $this->storage;

            if ($file = $UploadFile->uploadDokument($data)) {
                $FileModel = new FileModel();
                $file_info1 = $FileModel->getInfo($file_id);
                $file_info2 = $FileModel->getInfo($file->id);

                // pripojit k dokumentu
                $this->template->chyba = $file;
                $DokumentPrilohy = new DokumentPrilohy();
                if ($DokumentPrilohy->pripojit($dokument_id, $file->id)) {
                    // tady by se melo mozna kontrolovat nejake uzivatelske nastaveni, jak se ma reupload prilohy chovat
                    if (false) {
                        $DokumentPrilohy->deaktivovat($dokument_id, $file_id); // deaktivujeme puvodni prilohu
                    } else {
                        $DokumentPrilohy->odebrat($dokument_id, $file_id);
                        $UploadFile->remove($file_id);
                    }

                    $Log = new LogModel();
                    $Log->logDokument($dokument_id, LogModel::PRILOHA_ZMENENA,
                            'Změněna příloha z "' . $file_info1->nazev . ' (' . $file_info1->real_name . ')" na "' . $file_info2->nazev . ' (' . $file_info2->real_name . ')"');

                    echo '###vybrano###' . $dokument_id;
                    $this->terminate();
                } else {
                    $this->template->chyba = 1;
                }
            } else {
                $this->template->chyba = 2;
                $this->template->error_message = $UploadFile->errorMessage();
            }
        } else {
            // zadny soubor
            $File = new FileModel();
            if ($file = $File->upravitMetadata($data, $file_id)) {
                // pripojit k dokumentu
                $this->template->chyba = $file;
                //$DokumentPrilohy = new DokumentPrilohy();
                //if ($DokumentPrilohy->pripojit($dokument_id, $file->file_id)) {
                echo '###zmemeno###' . $dokument_id;
                $this->terminate();
                //} else {
                //    $this->template->chyba = 1;
                //}
            } else {
                $this->template->chyba = 2;
                $this->template->error_message = "Metadata přílohy se nepodařilo upravit.";
            }
        }
    }

}
