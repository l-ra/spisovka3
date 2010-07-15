<?php

class Spisovka_PrilohyPresenter extends BasePresenter
{

    protected $file_id;
    protected $dokument_id;


    public function renderPridat()
    {
        $this->file_id = $this->getParam('id',null);
        $this->dokument_id = $this->getParam('dok_id',null);
    }

    public function renderUpravit()
    {
        $this->file_id = $this->getParam('id',null);
        $this->dokument_id = $this->getParam('dok_id',null);
    }

    public function renderNacti()
    {
        $dokument_id = $this->getParam('id',null); // tady jako dokument_id

        $DokumentPrilohy = new DokumentPrilohy();
        $seznam = $DokumentPrilohy->prilohy($dokument_id,null,1);
        $this->template->seznamPriloh = $seznam;
        $this->template->dokument_id = $dokument_id;

    }

    public function renderOdebrat()
    {
        $file_id = $this->getParam('id',null);
        $dokument_id = $this->getParam('dok_id',null);

        $DokumentPrilohy = new DokumentPrilohy();
        $param = array( array('file_id=%i',$file_id),array('dokument_id=%i',$dokument_id) );

        if ( $DokumentPrilohy->odebrat($param) ) {
            $Log = new LogModel();
            $FileModel = new FileModel();
            $file_info = $FileModel->getInfo($file_id);
            $Log->logDokument($dokument_id, LogModel::PRILOHA_ODEBRANA,'Odebrána příloha "'. $file_info->nazev .' ('. $file_info->real_name .')"');
            $this->flashMessage('Příloha byla úspěšně odebrána.');
        } else {
            $this->flashMessage('Přílohu se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

/**
 *
 * Formular pro nahrani priloh
 *
 */

    protected function createComponentUploadForm()
    {

        $form1 = new AppForm();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onSubmit = "return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})";

        if(isset($this->dokument_id)) {
            $form1->addHidden('dokument_id')
                    ->setValue($this->dokument_id);
        } else {
            $form1->addHidden('dokument_id')
                    ->setValue(0);
        }

        $form1->addText('nazev', 'Název přílohy:', 50, 150);
        $form1->addTextArea('popis', 'Popis:', 80, 5);
        $form1->addSelect('typ', 'Typ souboru', FileModel::typPrilohy());
        $form1->addFile('file', 'Soubor:');
        $form1->addSubmit('upload', 'Upload')
                 ->onClick[] = array($this, 'uploadClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function uploadClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $upload = $data['file'];

        $dokument_id = $data['dokument_id'];
        $typ = $data['typ'];
        unset($data['dokument_id']);

        $data['dir'] = date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y');

        // Nacteni rozhrani pro upload dle nastaveni
        $storage_conf = Environment::getConfig('storage');
        eval("\$UploadFile = new ".$storage_conf->type."();");

        if ( $file = $UploadFile->uploadDokument($data) ) {
            // pripojit k dokumentu
            $this->template->chyba = $file;
            $DokumentPrilohy = new DokumentPrilohy();
            if ($DokumentPrilohy->pripojit($dokument_id, $file->file_id)) {

                $Log = new LogModel();
                $FileModel = new FileModel();
                $file_info = $FileModel->getInfo($file->file_id);
                $Log->logDokument($dokument_id, LogModel::PRILOHA_PRIDANA,'Přidána příloha "'. $file_info->nazev .' ('. $file_info->real_name .')"');

                echo '###vybrano###'. $dokument_id;
                $this->terminate();
            } else {
                $this->template->chyba = 1;
            }
        } else {
            $this->template->chyba = 2;
            $this->template->error_message = $UploadFile->errorMessage();
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

        $form1 = new AppForm();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onSubmit = "return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})";

        if(isset($this->dokument_id)) {
            $form1->addHidden('dokument_id')
                    ->setValue($this->dokument_id);
        } else {
            $form1->addHidden('dokument_id')
                    ->setValue(0);
        }
        $form1->addHidden('file_id')
                ->setValue($this->file_id);
        
        $form1->addText('nazev', 'Název přílohy:', 50, 150)
                ->setValue(@$file_info->nazev);
        $form1->addTextArea('popis', 'Popis:', 80, 5)
                ->setValue(@$file_info->popis);
        $form1->addSelect('typ', 'Typ souboru', FileModel::typPrilohy())
                ->setValue(@$file_info->typ);
        $form1->addFile('file', 'Soubor:');
        $form1->addSubmit('upload', 'Upload')
                 ->onClick[] = array($this, 'reUploadClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function reUploadClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $upload = $data['file'];

        $dokument_id = $data['dokument_id'];
        $file_id = $data['file_id'];
        $typ = $data['typ'];
        unset($data['dokument_id']);
        unset($data['file_id']);

        $data['dir'] = date('Y') .'/DOK-'. sprintf('%06d',$dokument_id) .'-'.date('Y');

        if ( $upload->error == 0 ) {
            // Nacteni rozhrani pro upload dle nastaveni
            $storage_conf = Environment::getConfig('storage');
            eval("\$UploadFile = new ".$storage_conf->type."();");

            if ( $file = $UploadFile->uploadDokument($data) ) {
                // pripojit k dokumentu
                $this->template->chyba = $file;
                $DokumentPrilohy = new DokumentPrilohy();
                if ($DokumentPrilohy->pripojit($dokument_id, $file->file_id)) {
                    $DokumentPrilohy->deaktivovat($dokument_id, $file_id); // deaktivujeme puvodni prilohu

                    $Log = new LogModel();
                    $FileModel = new FileModel();
                    $file_info1 = $FileModel->getInfo($file_id);
                    $file_info2 = $FileModel->getInfo($file->file_id);
                    $Log->logDokument($dokument_id, LogModel::PRILOHA_ZMENENA,'Změněna příloha z "'. $file_info1->nazev .' ('. $file_info1->real_name .')" na "'. $file_info2->nazev .' ('. $file_info2->real_name .')"');

                    echo '###vybrano###'. $dokument_id;
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
            if ( $file = $File->upravitMetadata($data,$file_id) ) {
                // pripojit k dokumentu
                $this->template->chyba = $file;
                //$DokumentPrilohy = new DokumentPrilohy();
                //if ($DokumentPrilohy->pripojit($dokument_id, $file->file_id)) {
                    echo '###zmemeno###'. $dokument_id;
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
