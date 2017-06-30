<?php

namespace Spisovka;

use Nette;

class Admin_OrgjednotkyPresenter extends BasePresenter
{

    public function renderSeznam($hledat = null)
    {
        $client_config = GlobalVariables::get('client_config');
        $vp = new Components\VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        // hledani
        $this->template->no_items = 0;
        $args = null;
        if (isset($hledat)) {
            $args = array(array(
                    'plny_nazev LIKE %s OR', '%' . $hledat . '%',
                    'zkraceny_nazev LIKE %s OR', '%' . $hledat . '%',
                    'ciselna_rada LIKE %s', '%' . $hledat . '%'
                )
            );

            $this->template->no_items = 3; // indikator pri nenalezeni dokumentu pri hledani
        }

        $OrgJednotka = new OrgJednotka();

        $result = $OrgJednotka->seznam($args);
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

        $this->template->seznam = $seznam;
    }

    public function renderNovy()
    {
        $this->template->title = " - Nová organizační jednotka";
    }

    public function renderDetail($id)
    {
        $this->template->title = " - Detail organizační jednotky";
        $this->template->OrgJednotka = new OrgUnit($id);

        // Zmena udaju organizacni jednotky
        $this->template->FormUpravit = $this->getParameter('upravit', null);
    }

    /**
     *
     * Formular a zpracovani pro zmenu udaju org. jednotky
     *
     */
    protected function createComponentUpravitForm()
    {

        $org = isset($this->template->OrgJednotka) ? $this->template->OrgJednotka : null;
        $OrgJednotka = new OrgJednotka();
        $org_seznam = $OrgJednotka->selectBox(1, ['exclude_id' => @$org->id]);

        $form1 = new Form();
        $form1->addHidden('id');
                
        $form1->addText('zkraceny_nazev', 'Zkrácený název:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Zkrácený název org. jednotky musí být vyplněno.');
        $form1->addText('plny_nazev', 'Úplný název jednotky:', 50, 200);
        $form1->addText('ciselna_rada', 'Zkratka / číselná řada:', 15, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Číselná řada org. jednotky musí být vyplněno.');
        $form1->addTextArea('note', 'Poznámka:', 50, 5);
        $form1->addSelect('stav', 'Stav:', array(0 => 'neaktivní', 1 => 'aktivní'));
        $form1->addSelect('parent_id', 'Nadřazená složka:', $org_seznam);

        if ($org !== null) {
            $form1['id']->setValue($org->id);
            $form1['zkraceny_nazev']->setValue($org->zkraceny_nazev);
            $form1['plny_nazev']->setValue($org->plny_nazev);
            $form1['ciselna_rada']->setValue($org->ciselna_rada);
            $form1['note']->setValue($org->note);
            $form1['stav']->setValue($org->stav);
            $form1['parent_id']->setValue($org->parent_id);
        }
        
        $form1->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $OrgJednotka = new OrgJednotka();
        $orgjednotka_id = $data['id'];
        unset($data['id']);

        try {
            $OrgJednotka->ulozit($data, $orgjednotka_id);
            $this->flashMessage('Organizační jednotka  "' . $data['zkraceny_nazev'] . '"  byla upravena.');
        } catch (Exception $e) {
            $this->flashMessage('Organizační jednotku  "' . $data['zkraceny_nazev'] . '" se nepodařilo upravit.', 'warning');
            $this->flashMessage($e->getMessage(), 'warning');
        }
        $this->redirect('this', array('id' => $orgjednotka_id));
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $orgjednotka_id = $data['id'];
        $this->redirect('this', array('id' => $orgjednotka_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Admin:Orgjednotky:seznam');
    }

    protected function createComponentNovyForm()
    {
        $OrgJednotka = new OrgJednotka();
        $org_seznam = $OrgJednotka->selectBox(1);

        $form1 = new Form();
        $form1->addText('zkraceny_nazev', 'Zkrácený název:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Zkrácený název org. jednotky musí být vyplněno.');
        $form1->addText('plny_nazev', 'Úplný název jednotky:', 50, 200);
        $form1->addText('ciselna_rada', 'Zkratka / číselná řada:', 15, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Číselná řada org. jednotky musí být vyplněno.');
        $form1->addTextArea('note', 'Poznámka:', 50, 5);
        $form1->addSelect('parent_id', 'Nadřazená složka:', $org_seznam);
        $form1->addSubmit('novy', 'Vytvořit')
                ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form1;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        try {
            $OrgJednotka = new OrgJednotka();
            $orgjednotka_id = $OrgJednotka->vytvorit($data);
            if (is_object($orgjednotka_id)) {
                $this->flashMessage('Organizační jednotku "' . $data['zkraceny_nazev'] . '" se nepodařilo vytvořit.', 'warning');
                $this->flashMessage($orgjednotka_id->getMessage(), 'warning');
            } else {
                $this->flashMessage('Organizační jednotka  "' . $data['zkraceny_nazev'] . '" byla vytvořena.');
                $this->redirect(':Admin:Orgjednotky:detail', array('id' => $orgjednotka_id));
            }
        } catch (DibiException $e) {
            $this->flashMessage('Organizační jednotku "' . $data['zkraceny_nazev'] . '" se nepodařilo vytvořit.', 'warning');
            $this->flashMessage($e->getMessage(), 'warning');
        }
    }

}
