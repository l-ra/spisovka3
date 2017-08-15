<?php

namespace Spisovka;

use Nette;

class Spisovka_PrilohyPresenter extends BasePresenter
{

    protected $error_message;

    public function renderPridat()
    {
        
    }

    public function renderUpravit()
    {
        
    }

    /**
     * 
     * @param int $id  ID dokumentu
     */
    public function renderNacti($id)
    {
        $doc = new Document($id);
        $this->template->prilohy = $doc->getFiles();
        $this->template->dokument_id = $id;
        $this->template->AccessEdit = true;
    }

    // Je volano pres AJAX, takze volani flashMessage() postradaji smysl
    public function actionOdebrat()
    {
        $file_id = $this->getParameter('id', null);
        $dokument_id = $this->getParameter('dok_id', null);

        $file =  new FileRecord($file_id);
        $DokumentPrilohy = new DokumentPrilohy();
        $nazev = $file->nazev; $filename = $file->filename;
        
        if ($DokumentPrilohy->odebrat($dokument_id, $file_id)) {
            try {
                $this->storage->remove($file);
            } catch (Exception $e) {
                $e->getCode();
                // Priloha muze byt sdilena mezi dokumentem a odpovedi, tudiz nemusi
                // byt mozne ji fyzicky smazat
            }

            $Log = new LogModel();
            $Log->logDocument($dokument_id, LogModel::PRILOHA_ODEBRANA,
                    "Odebrána příloha \"$nazev\" ($filename)");
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
        $form1 = new Form();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onsubmit = "return AIM.submit(this, {'onComplete' : attachmentUploadCompleted})";

        $dok_id = $this->getParameter('dok_id');
        $form1->addHidden('dokument_id');
        if ($dok_id)
            $form1['dokument_id']->setValue($dok_id);

        $form1->addText('priloha_nazev', 'Název přílohy:', 50, 150)
                ->setRequired();
        $form1->addTextArea('priloha_popis', 'Popis:', 80, 5);
        $form1->addUpload('file', 'Soubor:');
        $form1->addSubmit('upload', 'Nahrát')
                ->onClick[] = array($this, 'uploadClicked');

        return $form1;
    }

    public function uploadClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        $dokument_id = $data['dokument_id'];

        $data['nazev'] = $data['priloha_nazev'];
        $data['popis'] = $data['priloha_popis'];
        unset($data['dokument_id'], $data['priloha_nazev'], $data['priloha_popis']);

        $data['dir'] = date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y');

        try {
            if ($file = $this->uploadDocumentHttp($data, $data['file'])) {
                // pripojit k dokumentu
                $DokumentPrilohy = new DokumentPrilohy();

                if ($DokumentPrilohy->pripojit($dokument_id, $file->id)) {

                    $Log = new LogModel();
                    $file_info = new FileRecord($file->id);
                    $Log->logDocument($dokument_id, LogModel::PRILOHA_PRIDANA,
                            'Přidána příloha "' . $file_info->nazev . ' (' . $file_info->filename . ')"');

                    echo '###nahrano###';
                    $this->terminate();
                } else {
                    $this->template->chyba = 1;
                }
            } else {
                $this->template->chyba = 2;
                $this->template->error_message = $this->error_message;
            }
        } catch (Nette\Application\AbortException $e) {
            throw $e;
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
        $file_id = $this->getParameter('id');

        $form = new Form();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form->getElementPrototype()->onsubmit = "return AIM.submit(this, {'onComplete' : attachmentUploadCompleted})";

        $dok_id = $this->getParameter('dok_id');
        $form->addHidden('dokument_id');
        if ($dok_id)
            $form['dokument_id']->setValue($dok_id);
        $form->addHidden('file_id')
                ->setValue($this->getParameter('id'));

        $form->addText('priloha_nazev', 'Název přílohy:', 50, 150);                
        $form->addTextArea('priloha_popis', 'Popis:', 80, 5);                
        $form->addUpload('file', 'Soubor:');
        $form->addSubmit('upload', 'Nahrát')
                ->onClick[] = array($this, 'reUploadClicked');

        if ($file_id) {
            $file_info = new FileRecord($file_id);
            $form['priloha_nazev']->setValue($file_info->nazev);
            $form['priloha_popis']->setValue($file_info->popis);
        }
        
        return $form;
    }

    public function reUploadClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        $upload = $data['file'];

        $dokument_id = $data['dokument_id'];
        $file_id = $data['file_id'];

        $data['nazev'] = $data['priloha_nazev'];
        $data['popis'] = $data['priloha_popis'];
        unset($data['priloha_nazev'], $data['priloha_popis']);
        unset($data['dokument_id']);
        unset($data['file_id']);

        $data['dir'] = date('Y') . '/DOK-' . sprintf('%06d', $dokument_id) . '-' . date('Y');

        if ($upload->error == 0) {
            if ($file = $this->uploadDocumentHttp($data, $data['file'])) {
                $old_record = new FileRecord($file_id);
                $new_record = new FileRecord($file->id);

                // pripojit k dokumentu
                $DokumentPrilohy = new DokumentPrilohy();
                if ($DokumentPrilohy->pripojit($dokument_id, $file->id)) {
                    $DokumentPrilohy->odebrat($dokument_id, $file_id);

                    $Log = new LogModel();
                    $Log->logDocument($dokument_id, LogModel::PRILOHA_ZMENENA,
                            'Změněna příloha z "' . $old_record->nazev . ' (' . $old_record->filename . ')" na "' . $new_record->nazev . ' (' . $new_record->filename . ')"');

                    $this->storage->remove($old_record);
                    
                    echo '###nahrano###';
                    $this->terminate();
                } else {
                    $this->template->chyba = 1;
                }
            } else {
                $this->template->chyba = 2;
                $this->template->error_message = $this->error_message;
            }
        } else {
            // zadny soubor
            $file = new FileRecord($file_id);
            $file->nazev = $data['nazev'];
            $file->popis = $data['popis'];
            $file->save();

            echo '###zmemeno###';
            $this->terminate();
        }
    }

    /**
     * Pro upload přílohy dokumentu z webového prohlížeče.
     * @param array $data  data formuláře doplněná o další informace
     * @return DibiRow|null
     */
    protected function uploadDocumentHttp(array $data, Nette\Http\FileUpload $upload)
    {
        if (!$upload->isOk()) {
            switch ($upload->error) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->error_message = 'Překročena velikost přílohy.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->error_message = 'Nevybrali jste žádný soubor.';
                    break;
                default:
                    $this->error_message = 'Soubor "' . $upload->getName() . '" se nepodařilo nahrát.';
                    break;
            }
            return null;
        }

        $data['filename'] = $upload->getName();
        $contents = file_get_contents($upload->getTemporaryFile());

        $storage = $this->storage;
        $row = $storage->uploadDocument($contents, $data, $this->user);
        if (!$row)
            $this->error_message = $storage->errorMessage();

        return $row;
    }

}
