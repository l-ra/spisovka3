<?php

class CiselnikSpousteciUdalost extends Ciselnik
{

    protected function dataChangedHandler()
    {
        DbCache::delete('s3_Spousteci_udalost');
    }

}

class Admin_CiselnikyPresenter extends BasePresenter
{

    protected $ciselnik;

    public function renderDefault()
    {
        $this->template->title = " - Číselníky";
    }

    public function actionTypdokumentu()
    {
        $this->template->ciselnik_title = "Typ dokumentu";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new Ciselnik();
        $ciselnik->setTable('dokument_typ');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:typdokumentu'));

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('popis', array(
            'title' => 'Popis',
            'link' => false,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('smer', array(
            'title' => 'Směr',
            'link' => false,
            'form' => 'select',
            'form_select' => array('příchozí', 'odchozí'),
            'view' => true
                )
        );
        $ciselnik->addColumn('podatelna', array(
            'title' => 'Přístupné podatelnou?',
            'link' => false,
            'form' => 'checkbox',
            'view' => true
                )
        );
        $ciselnik->addColumn('referent', array(
            'title' => 'Přístupné referentovi?',
            'link' => false,
            'form' => 'checkbox',
            'view' => true
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'selectStav',
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionZpusobvyrizeni()
    {
        $this->template->ciselnik_title = "Způsob vyřízení";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new Ciselnik();
        $ciselnik->setTable('zpusob_vyrizeni');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:zpusobvyrizeni'));

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('note', array(
            'title' => 'Poznámka',
            'link' => false,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('fixed', array(
            'title' => 'Pevný záznam?',
            'link' => false,
            'form' => 'none',
            'view' => true
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'selectStav',
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionZpusobodeslani()
    {
        $this->template->ciselnik_title = "Způsob odeslání";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new Ciselnik();
        $ciselnik->setTable('zpusob_odeslani');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:zpusobodeslani'));
        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('note', array(
            'title' => 'Poznámka',
            'link' => false,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('fixed', array(
            'title' => 'Pevný záznam?',
            'link' => false,
            'form' => 'none',
            'view' => true,
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'selectStav',
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionZpusobdoruceni()
    {
        $this->template->ciselnik_title = "Způsob doručení";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new Ciselnik();
        $ciselnik->setTable('zpusob_doruceni');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:zpusobdoruceni'));

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('note', array(
            'title' => 'Poznámka',
            'link' => false,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('fixed', array(
            'title' => 'Pevný záznam?',
            'link' => false,
            'form' => 'none',
            'view' => true,
                )
        );
        $ciselnik->addColumn('epodatelna', array(
            'title' => 'Elektronické doručení?',
            'link' => false,
            'form' => 'checkbox',
            'view' => true
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'selectStav',
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionSpousteciudalost()
    {
        $this->template->ciselnik_title = "Spouštěcí událost";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new CiselnikSpousteciUdalost();
        $ciselnik->setTable('spousteci_udalost');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:spousteciudalost'));

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('poznamka', array(
            'title' => 'Poznámka',
            'link' => false,
            'form' => 'textArea',
            'view' => true
                )
        );
        $ciselnik->addColumn('poznamka_k_datumu', array(
            'title' => 'Poznámka k datumu',
            'link' => false,
            'form' => 'textArea',
            'view' => false
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'select',
            'form_select' => array('0' => 'Neaktivní', '1' => 'Aktivní', '2' => 'Automatická událost'),
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionStaty()
    {
        $this->template->ciselnik_title = "Státy";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new Ciselnik();
        $ciselnik->setTable('stat');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:staty'));
        $ciselnik->orderBy('nazev');

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('kod', array(
            'title' => 'Kód státu',
            'link' => false,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'selectStav',
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

    public function actionDruhzasilky()
    {
        $this->template->ciselnik_title = "Druh zásilky";
        $this->template->title = " - " . $this->template->ciselnik_title . " - číselník";

        $ciselnik = new CiselnikSpousteciUdalost();
        $ciselnik->setTable('druh_zasilky');
        $ciselnik->setParams($this->getParameters());
        $ciselnik->setLink($this->link(':Admin:Ciselniky:druhzasilky'));
        $ciselnik->addParam('no_delete', true);
        $ciselnik->orderBy('order');

        $ciselnik->addColumn('id', array(
            'title' => 'ID',
            'link' => false,
            'form' => 'hidden',
            'view' => false
                )
        );
        $ciselnik->addColumn('nazev', array(
            'title' => 'Název',
            'link' => true,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('order', array(
            'title' => 'Pořadí',
            'link' => false,
            'form' => 'text',
            'view' => true
                )
        );
        $ciselnik->addColumn('stav', array(
            'title' => 'Stav',
            'link' => false,
            'form' => 'select',
            'form_select' => array('0' => 'Neaktivní', '1' => 'Aktivní'),
            'view' => true
                )
        );

        $this->addComponent($ciselnik, 'ciselnik');

        $this->setView('detail');
    }

}
