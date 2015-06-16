<?php

class Admin_PrilohyPresenter extends BasePresenter
{

    public function renderDefault()
    {
        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek) ? $user_config->nastaveni->pocet_polozek
                    : 20;

        $FileModel = new FileModel();
        $result = $FileModel->seznam();
        $paginator->itemCount = count($result);

        $seznam = $FileModel->fetchPart($result, $paginator->offset, $paginator->itemsPerPage);
        $this->template->seznam = $seznam;
    }

    public function actionDownload()
    {
        $DownloadFile = $this->storage;

        $FileModel = new FileModel();
        $file_id = $this->getParameter('id', null);
        if (strpos($file_id, '-') !== false) {
            list($file_id, $file_version) = explode('-', $file_id);
            $file = $FileModel->getInfo($file_id, $file_version);
        } else {
            $file = $FileModel->getInfo($file_id);
        }

        //Nette\Diagnostics\Debugger::dump($file);

        $res = $DownloadFile->download($file);

        if ($res == 0) {
            $this->terminate();
        } else if ($res == 1) {
            // not found
            $this->flashMessage('Požadovaný soubor nenalezen!', 'warning');
            $this->redirect(':Admin:Prilohy:default');
        } else if ($res == 2) {
            $this->flashMessage('Chyba při stahování!', 'warning');
            $this->redirect(':Admin:Prilohy:default');
        } else if ($res == 3) {
            $this->flashMessage('Neoprávněný přístup! Nemáte povolení stáhnut zmíněný soubor!', 'warning');
            $this->redirect(':Admin:Prilohy:default');
        }
    }

    /**
     *
     * Formular pro nahrani priloh
     *
     */
    protected function createComponentUploadForm()
    {

        $form1 = new Nette\Application\UI\Form();
        $form1->addText('nazev', 'Název přílohy:', 50, 150);
        $form1->addTextArea('popis', 'Popis:', 80, 5);
        $form1->addSelect('typ', 'Typ souboru', FileModel::typPrilohy());
        $form1->addUpload('file', 'Soubor:');
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

    public function uploadClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $upload = $data['file'];

        // Nacteni rozhrani pro upload dle nastaveni
        $UploadFile = $this->storage;

        if ($file = $UploadFile->uploadDokument($data)) {
            $this->flashMessage('Soubor "' . $file->nazev . '" úspěšně nahrán.');
            $this->redirect('this');
        } else {
            $this->flashMessage($UploadFile->errorMessage(), 'warning');
        }
    }

}
