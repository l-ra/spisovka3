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
        $DokumentPrilohy = new DokumentPrilohy();
        $seznam = $DokumentPrilohy->prilohy($id);
        $this->template->prilohy = $seznam;
        $this->template->dokument_id = $id;
        $this->template->AccessEdit = true;
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
                $e->getCode();
                // Priloha muze byt sdilena mezi dokumentem a odpovedi, tudiz nemusi
                // byt mozne ji fyzicky smazat
            }

            $Log = new LogModel();
            $Log->logDocument($dokument_id, LogModel::PRILOHA_ODEBRANA,
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
                    $FileModel = new FileModel();
                    $file_info = $FileModel->getInfo($file->id);
                    $Log->logDocument($dokument_id, LogModel::PRILOHA_PRIDANA,
                            'Přidána příloha "' . $file_info->nazev . ' (' . $file_info->real_name . ')"');

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
        $File = new FileModel();
        $file_info = $File->getInfo($this->getParameter('id'));

        $form1 = new Form();
        //$form1->getElementPrototype()->id('priloha-upload');
        $form1->getElementPrototype()->onsubmit = "return AIM.submit(this, {'onComplete' : attachmentUploadCompleted})";

        $dok_id = $this->getParameter('dok_id');
        $form1->addHidden('dokument_id');
        if ($dok_id)
            $form1['dokument_id']->setValue($dok_id);
        $form1->addHidden('file_id')
                ->setValue($this->getParameter('id'));

        $form1->addText('priloha_nazev', 'Název přílohy:', 50, 150)
                ->setValue(@$file_info->nazev);
        $form1->addTextArea('priloha_popis', 'Popis:', 80, 5)
                ->setValue(@$file_info->popis);
        $form1->addUpload('file', 'Soubor:');
        $form1->addSubmit('upload', 'Nahrát')
                ->onClick[] = array($this, 'reUploadClicked');

        return $form1;
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
                $FileModel = new FileModel();
                $file_info1 = $FileModel->getInfo($file_id);
                $file_info2 = $FileModel->getInfo($file->id);

                // pripojit k dokumentu
                $DokumentPrilohy = new DokumentPrilohy();
                if ($DokumentPrilohy->pripojit($dokument_id, $file->id)) {
                    // tady by se melo mozna kontrolovat nejake uzivatelske nastaveni, jak se ma reupload prilohy chovat
                    if (false) {
                        $DokumentPrilohy->deaktivovat($dokument_id, $file_id); // deaktivujeme puvodni prilohu
                    } else {
                        $DokumentPrilohy->odebrat($dokument_id, $file_id);
                        $this->storage->remove($file_id);
                    }

                    $Log = new LogModel();
                    $Log->logDocument($dokument_id, LogModel::PRILOHA_ZMENENA,
                            'Změněna příloha z "' . $file_info1->nazev . ' (' . $file_info1->real_name . ')" na "' . $file_info2->nazev . ' (' . $file_info2->real_name . ')"');

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
            $File = new FileModel();
            if ($file = $File->upravitMetadata($data, $file_id)) {
                echo '###zmemeno###';
                $this->terminate();
            } else {
                $this->template->chyba = 3;
            }
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
        $row = $storage->uploadDocument($contents, $data);
        if (!$row)
            $this->error_message = $storage->errorMessage();
        
        return $row;
    }
}
