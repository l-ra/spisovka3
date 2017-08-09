<?php

namespace Spisovka\Components;

use Nette;

/**
 * Ciselnik control.
 *
 * @author     Tomas Vancura
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 */
class Ciselnik extends Nette\Application\UI\Control
{

    protected $action;
    private $tableName;
    private $cols = array();
    private $orderBy;
    private $data;
    protected $enableDelete = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function setTable($table)
    {
        $this->tableName = $table;
    }

    public function disableDelete()
    {
        $this->enableDelete = false;
    }

    /* public function setParams($params)
      {
      if (empty($this->_params)) {
      $this->_params = $params;
      } else {
      $this->_params = array_merge($this->_params, $params);
      }
      }

      public function addParam($name, $value)
      {
      if (empty($this->_params)) {
      $this->_params = array($name => $value);
      } else {
      $this->_params[$name] = $value;
      }
      } */

    public function addColumn($col, $params)
    {
        if (empty($params['title']))
            $params['title'] = $col;

        $this->cols[$col] = $params;
        return $this;
    }

    public function orderBy($col)
    {
        $this->orderBy = $col;

        return $this;
    }

    public function render()
    {
        $model = new \Spisovka\Model($this->tableName);

        if ($this->getParameter('edit')) {
            // form - uprava
            $this->action = 'edit';

            $this->template->setFile(dirname(__FILE__) . '/template_form.phtml');
            $this->template->render();
        } else if ($this->getParameter('novy')) {
            // form - nova polozka
            $this->action = 'new';
            $this->template->setFile(dirname(__FILE__) . '/template_form.phtml');
            $this->template->render();
        } else {
            // seznam
            $cols = null;
            if (count($this->cols) > 0) {
                $cols = array();
                foreach ($this->cols as $col_name => $col_params) {
                    $cols[] = $col_name;

                    if (isset($col_params['view']) && !$col_params['view']) {
                        unset($this->cols[$col_name]);
                    }
                }
            }

            $data = $model->fetchAll($cols, $this->orderBy);
            $this->template->data = $data->fetchAll();
            $this->template->cols = $this->cols;

            $this->template->primaryKeyName = 'id';

            $this->template->setFile(dirname(__FILE__) . '/template.phtml');
            $this->template->render();
        }
    }

    protected function createComponentForm($name)
    {
        $form = new \Spisovka\Form($this, $name);
        $form->onSubmit[] = array($this, 'formSubmitHandler');

        if (count($this->cols) > 0) {

            if ($this->action == 'edit') {
                $model = new \Spisovka\Model($this->tableName);
                $this->data = $model->select([["[id] = ", $this->getParameter('edit')]])->fetch();
            }

            foreach ($this->cols as $col_name => $col_params) {

                if (isset($col_params['form']) && ( is_null($col_params['form']) || $col_params['form']
                        == 'none' )) {
                    continue; // sloupec se negeneruje
                }

                if ($col_params['form'] == "hidden") {
                    $form->addHidden($col_name);
                } else if ($col_params['form'] == "textArea") {
                    $form->addTextArea($col_name, $col_params['title'], 50, 4);
                } else if ($col_params['form'] == "password") {
                    $form->addPassword($col_name, $col_params['title']);
                } else if ($col_params['form'] == "selectStav") {
                    $select = array('0' => 'Neaktivní', '1' => 'Aktivní');
                    $form->addSelect($col_name, $col_params['title'], $select)->setValue(1);
                } else if ($col_params['form'] == "selectAnoNe") {
                    $select = array('0' => 'NE', '1' => 'ANO');
                    $form->addSelect($col_name, $col_params['title'], $select)->setValue(0);
                } else if ($col_params['form'] == "checkbox") {
                    $form->addCheckbox($col_name, $col_params['title']);
                } else if ($col_params['form'] == "select") {
                    if (isset($col_params['form_select'])) {
                        $form->addSelect($col_name, $col_params['title'],
                                $col_params['form_select']);
                    } else {
                        $form->addText($col_name, $col_params['title']);
                    }
                } else if ($col_params['form'] == "radio") {
                    if (!empty($col_params['form_radio']))
                        $form->addRadioList($col_name, $col_params['title'],
                                $col_params['form_radio']);
                    else
                        $form->addText($col_name, $col_params['title']);
                } else {
                    $form->addText($col_name, $col_params['title'], 50);
                }

                if ($this->action == 'edit' && isset($this->data->$col_name))
                    $form[$col_name]->setValue($this->data->$col_name);
            }

            if ($this->action == 'new') {
                $form->addHidden('ciselnik_new')->setValue(1);
                $form->addSubmit('novyCiselnik', 'Vytvořit');
            } else if ($this->action == 'edit') {
                $form->addHidden('primaryKey')->setValue($this->data->id);
                $form->addSubmit('upravitCiselnik', 'Upravit');
                $can_delete = $this->enableDelete && (!isset($this->data->fixed) || !$this->data->fixed);
                if ($can_delete) {
                    $form->addSubmit('odstranitCiselnik', 'Odstranit')
                                    ->getControlPrototype()->onclick = "return confirm('Opravdu chcete smazat tento záznam?');";
                }
            }
        }

        $form->addSubmit('stornoCiselnik', 'Zrušit')
                ->setValidationScope(FALSE);

        return $form;
    }

    /**
     * Ciselnik form submit handler.
     * @param  Nette\Application\UI\Form
     * @return void
     */
    public function formSubmitHandler(Nette\Application\UI\Form $form)
    {
        // was form submitted?
        if ($form->isSubmitted()) {

            $values = $form->getValues();
            $data = $form->getHttpData();

            if (isset($values['stav']) && isset($data['stav'])) {
                if ($values['stav'] != $data['stav']) {
                    $values['stav'] = $data['stav'];
                }
            }

            if (isset($data['novyCiselnik'])) {
                $this->handleNew($values);
            } else if (isset($data['upravitCiselnik'])) {
                $this->handleEdit($values, $data['primaryKey']);
            } else if (isset($data['odstranitCiselnik'])) {
                $this->handleDelete($data['primaryKey']);
            } else if (isset($data['stornoCiselnik'])) {
                $this->handleStorno();
            } else {
                throw new Nette\InvalidStateException("Unknown submit button.");
            }
        }

        if (!$this->presenter->isAjax())
            $this->presenter->redirect('this');
    }

    public function handleNew($values)
    {
        try {

            $model = new \Spisovka\Model($this->tableName);
            $model->insert($values);
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně vytvořen.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo vytvořit!', 'error');
            $this->presenter->flashMessage($e->getMessage(), 'error');
        }

        $this->redrawControl();

        if (!$this->presenter->isAjax())
            $this->redirect('this');
    }

    public function handleEdit($values, $id)
    {
        try {
            $model = new \Spisovka\Model($this->tableName);
            $model->update($values, [['%and', ['id' => $id]]]);
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně upraven.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo upravit!', 'error');
            $this->presenter->flashMessage($e->getMessage(), 'error');
        }

        $this->redrawControl();

        if (!$this->presenter->isAjax())
            $this->redirect('this');
    }

    public function handleDelete($id)
    {
        try {
            $model = new \Spisovka\Model($this->tableName);
            $model->delete([['%and', ['id' => $id]]]);
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně odstraněn.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo odstranit!', 'error');
            $this->presenter->flashMessage($e->getMessage(), 'error');
        }

        $this->redrawControl();
        if (!$this->presenter->isAjax())
            $this->redirect('this');
    }

    public function handleStorno()
    {
        $this->redrawControl();
        if (!$this->presenter->isAjax())
            $this->redirect('this');
    }

    // Určeno k přepsání v případné třídě potomka
    protected function dataChangedHandler()
    {
        
    }

}
