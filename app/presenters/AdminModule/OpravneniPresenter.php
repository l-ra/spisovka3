<?php

class Admin_OpravneniPresenter extends BasePresenter
{

    protected $authorizator;
    
    public function __construct(\Nette\Security\IAuthorizator $authorizator)
    {
        parent::__construct();
        $this->authorizator = $authorizator;        
    }
    public function renderSeznam()
    {
        $this->template->title = " - Seznam rolí";

        $client_config = GlobalVariables::get('client_config');
        $vp = new VisualPaginator($this, 'vp', $this->getHttpRequest());
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($client_config->nastaveni->pocet_polozek) ? $client_config->nastaveni->pocet_polozek
                    : 20;

        $RoleModel = new RoleModel();
        $result = $RoleModel->nacti();
        $paginator->itemCount = count($result);
        $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        $this->template->seznam = $seznam;
    }

    public function actionNovy()
    {
        $this->template->title = " - Nová role";
    }

    public function actionDetail($id)
    {
        $role = new Role($id);
        $this->template->Role = $role;

        // Opravneni
        $AclModel = new AclModel();
        $opravneni = $AclModel->seznamOpravneni($role->code);
        $pravidla = $AclModel->seznamPravidel($role, $this->authorizator);
        $this->template->seznamOpravneni = $opravneni;
        $this->template->seznamPravidel = $pravidla;
    }

    public function renderDetail()
    {
        $this->template->title = " - Detail role";

        // Zmena udaju role
        $this->template->FormUpravit = $this->getParameter('upravit', null);

        $this->template->lzeMenitOpravneni = self::lzeMenitRoli($this->template->Role);
    }

    public function actionSmazat($id)
    {
        try {
            $role = new Role($id);
            $role->delete();
            $this->flashMessage('Role byla smazána.');
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Roli nebylo možné smazat.', 'error');
        }

        $this->redirect(':Admin:Opravneni:seznam');
    }

    /**
     *
     * Formular a zpracovani pro zmenu udaju role
     *
     */
    protected function createComponentUpravitForm()
    {

        $role = @$this->template->Role;
        $RoleModel = new RoleModel();

        // hack - udaje ze sablony jsou dostupne jen pri vykresleni formulare, ne kdyz se zpracovava odeslany formular
        // pri zpracovani submitu musime pouzit nadmnozinu roli, co byla pouzita pro vykresleni
        $role_select = $RoleModel->seznamProDedeni(isset($role->id) ? $role->id : null);

        if (isset($role->id)) {
            unset($role_select[$role->id]);
        }
        $role_select[0] = "(nedědí)";

        $form1 = new Spisovka\Form();
        $form1->addHidden('id')
                ->setValue(@$role->id);
        $form1->addHidden('fixed')
                ->setValue(@$role->fixed);
        $form1->addText('name', 'Název role:', 50, 100)
                ->setValue(@$role->name)
                ->addRule(Nette\Forms\Form::FILLED, 'Název role musí být vyplněno!');
        $input = $form1->addText('code', 'Kódové označení role:', 50, 150)
                ->setValue(@$role->code);
        // ->addRule(Form::FILLED, 'Kódové označení musí být vyplněno!');
        if (isset($role->fixed) && $role->fixed != 0)
            $input->setDisabled();

        $form1->addTextArea('note', 'Popis role:', 50, 5)
                ->setValue(@$role->note);
        $form1->addSelect('parent_id', 'Dědí z role:', $role_select)
                ->setValue(is_null(@$role->parent_id) ? 0 : @$role->parent_id );

        $form1->addSubmit('upravit', 'Upravit')
                ->onClick[] = array($this, 'upravitClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function upravitClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        $role_id = $data['id'];
        $data['date_modified'] = date('Y-m-d H:i:s');
        unset($data['id']);

        // Zabran menit kod preddefinovanych roli
        if ($data['fixed'] != 0)
            unset($data['code']);
        else if (empty($data['code'])) {
            // Uzivatel se snazi shodit program zadanim prazdneho kodu uzivatelske role
            //$this->flashMessage('Chyba - kódové označení role musí být vyplněno', 'warning');
            //$this->redirect('this',array('id'=>$role_id, 'upravit'=>'info'));
            //return;
            // ignoruj uzivateluv pokus a ponechej v db puvodni kod
            unset($data['code']);
        }

        try {
            if (!isset($data['code'])) {
                // 'code' prvek nemuze byt prazdny i kdyz nema byt zmenen. Slouzi pro vypocet 'sekvence_string'
                $old_role = new Role($role_id);
                $data['code'] = $old_role->code;
            }
            $role = new Role($role_id);
            $role->modify($data);
            $role->save();
            $this->flashMessage('Role  "' . $data['name'] . '"  byla upravena.');
        } catch (Exception $e) {
            $this->flashMessage('Chyba - ' . $e->getMessage(), 'warning');
        }
        $this->redirect('this', array('id' => $role_id));
    }

    public function stornoClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();
        $role_id = $data['id'];
        $this->redirect('this', array('id' => $role_id));
    }

    public function stornoSeznamClicked()
    {
        $this->redirect(':Admin:Opravneni:seznam');
    }

    protected function createComponentNovyForm()
    {

        $RoleModel = new RoleModel();
        $role_select = $RoleModel->seznamProDedeni();
        $role_select[0] = "(nedědí)";

        $form1 = new Spisovka\Form();
        $form1->addText('name', 'Název role:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název role musí být vyplněno!');
        $form1->addText('code', 'Kódové označení role:', 50, 150)
                ->addRule(Nette\Forms\Form::FILLED, 'Kódové označení role musí být vyplněno!');
        $form1->addTextArea('note', 'Popis role:', 50, 5);
        $form1->addSelect('parent_id', 'Dědí z role:', $role_select)
                ->setValue(0);
        $form1->addSubmit('novy', 'Vytvořit')
                ->onClick[] = array($this, 'vytvoritClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoSeznamClicked');

        return $form1;
    }

    public function vytvoritClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues(true);

        $data['active'] = 1;
        if (empty($data['parent_id']))
            $data['parent_id'] = null;
        $data['date_created'] = new DateTime();

        try {
            $role = Role::create($data);
            $this->flashMessage('Role  "' . $data['name'] . '" byla vytvořena.');
            $this->redirect('detail', ['id' => $role->id]);
        } catch (DibiException $e) {
            $this->flashMessage('Roli "' . $data['name'] . '" se nepodařilo vytvořit.', 'warning');
            $this->flashMessage('Chyba - ' . $e->getMessage(), 'warning');
        }
    }

    /**
     *
     * Formular a zpracovani pro zmenu opraveni role
     *
     */
    protected function createComponentOpravneniForm()
    {

        $role = isset($this->template->Role) ? $this->template->Role : null;

        $form1 = new Nette\Application\UI\Form();
        $form1->addHidden('id')
                ->setValue(@$role->id);

        $opravneni = $this->template->seznamOpravneni;
        $pravidla = $this->template->seznamPravidel;

        foreach ($pravidla as $blok) {
            foreach ($blok['pravidla'] as $rule_id => $rule) {

                $form1->addGroup('rule_id_' . $rule_id);
                $subForm = $form1->addContainer('perm' . $rule_id);
                $subForm->addCheckbox("opravneni_allow" /* , 'povolit' */)
                        ->setValue((@$opravneni[$rule_id]->allowed == 'Y') ? 1 : 0 );
                $chk = $subForm->addCheckbox("opravneni_deny" /* , 'zakázat' */)
                        ->setValue((@$opravneni[$rule_id]->allowed == 'N') ? 1 : 0 );
                // zakaž možnost odepřít oprávnění administátora a vedoucího
                if ($rule['resource'] == NULL) {
                    $chk->setDisabled();
                }
            }
        }

        $form1->addSubmit('upravit', 'Upravit oprávnění')
                ->onClick[] = array($this, 'upravitOpravneniClicked');

        return $form1;
    }

    public function upravitOpravneniClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $AclModel = new AclModel();
        $role_id = (int) $data['id'];
        unset($data['id']);

        $role = new Role($role_id);
        $opravneni = $AclModel->seznamOpravneni($role->code);

        // Zkontroluj, zda lze roli menit
        if (!self::lzeMenitRoli($role)) {
            $this->redirect('this', array('id' => $role_id));
            return;
        }

        // Predkontrola - vyrazeni nemenici opravneni a nedefinovanych opravneni
        foreach ($data as $id => $stav) {

            $rule_id = (int) substr($id, 4);

            // porovnat s puvodnim daty = opravneni, ktere se nemenily, vyradime
            if (isset($opravneni[$rule_id])) {
                $bool = ($opravneni[$rule_id]->allowed == 'Y');
                if (($bool == TRUE) && ($stav['opravneni_allow'] == TRUE)) {
                    unset($data[$id]);
                    unset($opravneni[$rule_id]);
                } else if ($bool == FALSE && isset($stav['opravneni_deny']) && $stav['opravneni_deny'] == TRUE) {
                    unset($data[$id]);
                    unset($opravneni[$rule_id]);
                }
            }
            if ($stav['opravneni_allow'] == FALSE && (!isset($stav['opravneni_deny']) || $stav['opravneni_deny'] == FALSE)) {
                // Vyradime opravneni, ktera nejsou ani udelena ani odeprena
                unset($data[$id]);
            }
        }

        // Odebrani zbyvajicich opravneni = oznaceny k odebrani
        if (count($opravneni) > 0) {
            foreach (array_keys($opravneni) as $orid) {
                $AclModel->deleteAcl(array(
                    array('rule_id=%i', $orid),
                    array('role_id=%i', $role_id)
                        )
                );
            }
        }

        // Pridani novych opravneni
        if (count($data) > 0) {
            foreach ($data as $id => $stav) {
                $rule_id = (int) substr($id, 4);

                if ($stav['opravneni_allow'] == TRUE) {
                    $allowed = 'Y';
                } else {
                    $allowed = 'N';
                }
                $new = array('role_id' => $role_id,
                    'rule_id' => $rule_id,
                    'allowed' => $allowed);

                $AclModel->insertAcl($new);
            }
        }

        $this->flashMessage('Oprávnění role "' . $role->name . '" bylo upraveno.');
        $this->redirect('this', array('id' => $role_id));
    }

    /**
     * Formulare pro programatory
     */
    protected function createComponentNovyResourceForm()
    {
        $role = $this->template->Role;

        $form1 = new Spisovka\Form();
        $form1->addHidden('id')
                ->setValue(@$role->id);
        $form1->addText('name', 'Název zdroje:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název zdroje musí být vyplněno!');
        $form1->addText('code', 'Systémové označení zdroje:', 50, 150)
                ->addRule(Nette\Forms\Form::FILLED, 'Systémové označení zdroje musí být vyplněno!');
        $form1->addTextArea('note', 'Popis zdroje:', 50, 5);

        $form1->addSubmit('novyresource', 'Vytvořit')
                ->onClick[] = array($this, 'novyResourceClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function novyResourceClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $role_id = $data['id'];
        unset($data['id']);
        $AclModel = new AclModel();

        try {
            $AclModel->insertResource($data);
            $this->flashMessage('Resource  "' . $data['name'] . '" byl vytvořen.');
            $this->redirect(':Admin:Opravneni:detail', array('id' => $role_id));
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Resource "' . $data['name'] . '" se nepodařilo vytvořit.', 'warning');
        }
    }

    protected function createComponentNovePravidloForm()
    {

        $role = $this->template->Role;

        $AclModel = new AclModel();
        $resource_data = $AclModel->getResources(1);
        $resource = array();
        foreach ($resource_data as $r) {
            $resource[$r->id] = $r->code;
        }

        $form1 = new Spisovka\Form();
        $form1->addHidden('id')
                ->setValue(@$role->id);
        $form1->addText('name', 'Název pravidla:', 50, 100)
                ->addRule(Nette\Forms\Form::FILLED, 'Název pravidla musí být vyplněno!');
        $form1->addTextArea('note', 'Popis pravidla:', 50, 5);
        $form1->addSelect('resource_id', 'Resource:', $resource);
        $form1->addText('privilege', 'Privilege:', 50, 100);
        $form1->addSubmit('novepravidlo', 'Vytvořit')
                ->onClick[] = array($this, 'novePravidloClicked');
        $form1->addSubmit('storno', 'Zrušit')
                        ->setValidationScope(FALSE)
                ->onClick[] = array($this, 'stornoClicked');

        return $form1;
    }

    public function novePravidloClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $role_id = $data['id'];
        unset($data['id']);
        $AclModel = new AclModel();

        try {
            $AclModel->insertRule($data);
            $this->flashMessage('Pravidlo  "' . $data['name'] . '" bylo vytvořeno.');
            $this->redirect(':Admin:Opravneni:detail', array('id' => $role_id));
        } catch (DibiException $e) {
            $e->getMessage();
            $this->flashMessage('Pravidlo "' . $data['name'] . '" se nepodařilo vytvořit.', 'warning');
        }
    }

    // Vraci:  0 - nelze menit
    //  1 - lze menit
    //  2 - lze, ale zobraz varovani
    protected static function lzeMenitRoli($role)
    {
        if (!is_object($role))
            throw new InvalidArgumentException("Parametr 'role' není objekt.");
        return in_array($role->code, array("admin", "superadmin")) ? 2 : 1;
    }

}
