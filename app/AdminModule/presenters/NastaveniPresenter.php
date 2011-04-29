<?php

class Admin_NastaveniPresenter extends BasePresenter
{

    public function renderDefault()
    {

        $CJ = new CisloJednaci();

        // Klientske nastaveni
        $user_config = Environment::getVariable('user_config');
        $this->template->Urad = $user_config->urad;

        $this->template->CisloJednaci = $user_config->cislo_jednaci;

        $this->template->Ukazka = $CJ->generuj();

        // Zmena udaju
        $this->template->FormUpravit = $this->getParam('upravit',null);

    }


/**
 *
 * Formular a zpracovani pro zmenu udaju org. jednotky
 *
 */

    protected function createComponentNastaveniUraduForm()
    {

        $user_config = Environment::getVariable('user_config');
        $Urad = $user_config->urad;
        $stat_select = Subjekt::stat();


        $form1 = new AppForm();
        $form1->addText('nazev', 'Název:', 50, 100)
                ->setValue($Urad->nazev)
                ->addRule(Form::FILLED, 'Název úřadu musí být vyplněn.');
        $form1->addText('plny_nazev', 'Plný název:', 50, 200)
                ->setValue($Urad->plny_nazev);
        $form1->addText('zkratka', 'Zkratka:', 15, 30)
                ->setValue($Urad->zkratka)
                ->addRule(Form::FILLED, 'Zkratka úřadu musí být vyplněna.');

        $form1->addText('ulice', 'Ulice:', 50, 100)
                ->setValue($Urad->adresa->ulice);
        $form1->addText('mesto', 'Město:', 50, 100)
                ->setValue($Urad->adresa->mesto);
        $form1->addText('psc', 'PSČ:', 12, 50)
                ->setValue($Urad->adresa->psc);
        $form1->addSelect('stat', 'Stát:', $stat_select)
                ->setValue($Urad->adresa->stat);


        $form1->addText('ic', 'IČ:', 20, 50)
                ->setValue($Urad->firma->ico);
        $form1->addText('dic', 'DIČ:', 20, 50)
                ->setValue($Urad->firma->dic);

        $form1->addText('telefon', 'Telefon:', 50, 100)
                ->setValue($Urad->kontakt->telefon);
        $form1->addText('email', 'Email:', 50, 100)
                ->setValue($Urad->kontakt->email);
        $form1->addText('www', 'URL:', 50, 150)
                ->setValue($Urad->kontakt->www);


        $form1->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'nastavitUradClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function nastavitUradClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
        $config_data = $config->toArray();
        //Debug::dump($config_data); exit;

        $config_data['urad']['nazev'] = $data['nazev'];
        $config_data['urad']['plny_nazev'] = $data['plny_nazev'];
        $config_data['urad']['zkratka'] = $data['zkratka'];

        $config_data['urad']['adresa']['ulice'] = $data['ulice'];
        $config_data['urad']['adresa']['mesto'] = $data['mesto'];
        $config_data['urad']['adresa']['psc'] = $data['psc'];
        $config_data['urad']['adresa']['stat'] = $data['stat'];

        $config_data['urad']['firma']['ico'] = $data['ic'];
        $config_data['urad']['firma']['dic'] = $data['dic'];

        $config_data['urad']['kontakt']['telefon'] = $data['telefon'];
        $config_data['urad']['kontakt']['email'] = $data['email'];
        $config_data['urad']['kontakt']['www'] = $data['www'];

        //Debug::dump($config_data); exit;
        $config_modify = new Config();
        $config_modify->import($config_data);
        $config_modify->save(CLIENT_DIR .'/configs/klient.ini');
        
        Environment::setVariable('user_config', $config_modify);

        $this->flashMessage('Informace o sobě byly upraveny.');
        $this->redirect('this');
    }

    public function stornoClicked(SubmitButton $button)
    {
        $this->redirect('this');
    }

    protected function createComponentNastaveniCJForm()
    {

        $user_config = Environment::getVariable('user_config');
        $CJ = $user_config->cislo_jednaci;

        $form1 = new AppForm();
        $form1->addText('maska', 'Maska:', 50, 100)
                ->setValue($CJ->maska)
                ->addRule(Form::FILLED, 'Maska čísla jednacího musí být vyplněna.');

        if ( $CJ->typ_evidence != 'priorace' ) {
            $form1->addText('oddelovac', 'Znak oddělovače pořadového čísla:', 3, 1)
                    ->setValue( !isset($CJ->oddelovac)?'/':$CJ->oddelovac );
        }

        //$form1->addText('typ', 'Metoda přičítání:', 50, 200)
        //        ->setValue($CJ->typ);

        $form1->addSubmit('upravit', 'Uložit')
                 ->onClick[] = array($this, 'nastavitCJClicked');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE)
                 ->onClick[] = array($this, 'stornoClicked');

        //$form1->onSubmit[] = array($this, 'upravitFormSubmitted');

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }


    public function nastavitCJClicked(SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
        $config_data = $config->toArray();
        $config_data['cislo_jednaci']['maska'] = $data['maska'];
        
        if ( $config_data['cislo_jednaci']['typ_evidence'] != "priorace" ) {
            $config_data['cislo_jednaci']['oddelovac'] = $data['oddelovac'];
        }

        $config_modify = new Config();
        $config_modify->import($config_data);
        $config_modify->save(CLIENT_DIR .'/configs/klient.ini');

        Environment::setVariable('user_config', $config_modify);

        $this->flashMessage('Nastavení čísla jednacího byly upraveny.');
        $this->redirect('this');
    }


}
