<?php

/**
 * Ciselnik control.
 *
 * @author     Tomas Vancura
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 */
class Ciselnik extends Control {

    /** @var string */
    protected $receivedSignal;

    /** @var bool  was method render() called? */
    protected $wasRendered = FALSE;

    protected $action;

    private $table;
    private $primary;
    private $link;
    private $_params;
    private $cols = array();
    private $order;
    private $data;

    public function __construct()
    {
        parent::__construct();
    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function setPrimaryID($col_id)
    {
        $this->primary = $col_id;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function setParams($params)
    {
        if ( empty($this->_params) ) {
            $this->_params = $params;
        } else {
            $this->_params = array_merge($this->_params, $params);
        }
    }
    public function addParam($name,$value)
    {
        if ( empty($this->_params) ) {
            $this->_params = array($name => $value);
        } else {
            $this->_params[ $name ] = $value;
        }
    }

    public function addColumn($col, $params)
    {

        if ( empty($params['title']) ) $params['title'] = $col;

        $this->cols[ $col ] = $params;
        return $this;
    }

    public function orderBy($col)
    {
        $this->order = $col;

        return $this;
    }

    public static function alink($link,$params)
    {
        
        if ( strpos($link,"?") !== false ) {
            return $link ."&". $params;
        } else {
            return $link ."?". $params;
        }
        
    }
    
    public function render()
    {

        $model = new Model($this->table);

        $this->template->params = $this->_params;

        if ( isset($this->_params['primary']) ) {
            // form - uprava
            $this->action = 'edit';

            $this->template->setFile(dirname(__FILE__) . '/template_form.phtml');
            $this->template->render();
        } else if ( isset($this->_params['ciselnik_new']) ) {
            // form - nova polozka
            $this->action = 'new';
            $this->template->setFile(dirname(__FILE__) . '/template_form.phtml');
            $this->template->render();
        } else {
            // seznam
            $cols = null;
            if ( count($this->cols)>0 ) {
                $cols = array();
                foreach( $this->cols as $col_name => $col_params ) {
                    $cols[] = $col_name;

                    if ( isset($col_params['view']) && !$col_params['view'] ) {
                        unset($this->cols[$col_name]);
                    }
                }
            }

            $data = $model->fetchAll($cols, $this->order);
            $this->template->data = $data->fetchAll();
            $this->template->cols = $this->cols;

            $this->template->primary = !empty($this->primary)?$this->primary:'id';
            $this->template->link = $this->link;

            $this->template->setFile(dirname(__FILE__) . '/template.phtml');
            $this->template->render();

        }
    }

    protected function createComponentForm($name)
    {
        if (!$this->wasRendered) {
            $this->receivedSignal = 'submit';
	}

        $form = new AppForm($this, $name);
        $form->onSubmit[] = array($this, 'formSubmitHandler');

        if ( count($this->cols)>0 ) {

            if ( $this->action == 'edit' ) {
                $model = new Model($this->table);
                $id = !empty($this->primary)?$this->primary:'id';
                $this->data = $model->fetchRow(array('%and', array($id => $this->_params['primary'] )))->fetch();
                
                $is_fixed = 0;
                if ( isset($this->data->fixed) ) {
                    if ( $this->data->fixed == 1 ) {
                        $is_fixed = 1;
                    }
                }
                
            }

            foreach( $this->cols as $col_name => $col_params ) {

                if ( isset($col_params['form']) && ( is_null($col_params['form']) || $col_params['form'] == 'none' ) ) {
                    continue; // sloupec se negeneruje
                }

                if ( $col_params['form'] == "hidden" ) {
                    if ( $this->action == 'edit' ) {
                        $form->addHidden($col_name)
                                ->setValue( @$this->data->$col_name );
                    }
                } else if ( $col_params['form'] == "textArea" ) {
                    if ( $this->action == 'edit' ) {
                        $form->addTextArea($col_name, $col_params['title'])
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addTextArea($col_name, $col_params['title']);
                    }
                } else if ( $col_params['form'] == "password" ) {
                    if ( $this->action == 'edit' ) {
                        $form->addPassword($col_name, $col_params['title'])
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addPassword($col_name, $col_params['title']);
                    }
                } else if ( $col_params['form'] == "selectStav" ) {
                    $select = array('0'=>'Neaktivní', '1'=>'Aktivní');
                    if ( $this->action == 'edit' ) {
                        $form->addSelect($col_name, $col_params['title'], $select)
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addSelect($col_name, $col_params['title'], $select)
                                ->setValue(1);
                    }
                } else if ( $col_params['form'] == "selectAnoNe" ) {
                    $select = array('0'=>'NE', '1'=>'ANO');
                    if ( $this->action == 'edit' ) {
                        $form->addSelect($col_name, $col_params['title'], $select)
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addSelect($col_name, $col_params['title'], $select)
                                ->setValue(0);
                    }
                } else if ( $col_params['form'] == "checkbox" ) {
                    if ( $this->action == 'edit' ) {
                        $form->addCheckbox($col_name, $col_params['title'])
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addCheckbox($col_name, $col_params['title']);
                    }
                } else if ( $col_params['form'] == "select" ) {
                    if ( isset($col_params['form_select']) ) {
                        if ( $this->action == 'edit' ) {
                            $form->addSelect($col_name, $col_params['title'], $col_params['form_select'])
                                    ->setValue( @$this->data->$col_name );
                        } else {
                            $form->addSelect($col_name, $col_params['title'], $col_params['form_select']);
                        }
                    } else {
                        if ( $this->action == 'edit' ) {
                            $form->addText($col_name, $col_params['title'])
                                    ->setValue( @$this->data->$col_name );
                        } else {
                            $form->addText($col_name, $col_params['title']);
                        }
                    }
                } else if ( $col_params['form'] == "radio" ) {
                    if ( !empty($col_params['form_radio']) ) {
                        if ( $this->action == 'edit' ) {
                            $form->addRadioList($col_name, $col_params['title'], $col_params['form_radio'])
                                    ->setValue( @$this->data->$col_name );
                        } else {
                            $form->addRadioList($col_name, $col_params['title'], $col_params['form_radio']);
                        }
                    } else {
                        if ( $this->action == 'edit' ) {
                            $form->addText($col_name, $col_params['title'])
                                    ->setValue( @$this->data->$col_name );
                        } else {
                            $form->addText($col_name, $col_params['title']);
                        }
                    }
                } else {
                    if ( $this->action == 'edit' ) {
                        $form->addText($col_name, $col_params['title'])
                                ->setValue( @$this->data->$col_name );
                    } else {
                        $form->addText($col_name, $col_params['title']);
                    }
                }
            }
            
            if ( $this->action == 'new' ) {
                $form->addHidden('ciselnik_new')->setValue(1);
                $form->addSubmit('novyCiselnik', 'Vytvořit');
            } else if ( $this->action == 'edit' ) {
                $form->addHidden('primary')->setValue( $this->data->$id );
                $form->addSubmit('upravitCiselnik', 'Upravit');
                if ( !$is_fixed ) {
                    $form->addSubmit('odstranitCiselnik', 'Odstranit')
                            ->getControlPrototype()->onclick = "return confirm('Opravdu chcete smazat tento záznam?');";
                }
            }

        }
        
        $form->addSubmit('stornoCiselnik', 'Zrušit')
                 ->setValidationScope(FALSE);

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form;

    }

    /**
     * Checks if component is signal receiver.
     * @param  string  signal name
     * @return bool
     */
    public function isSignalReceiver($signal = TRUE)
    {
        if ($signal == 'submit') {
            return $this->receivedSignal === 'submit';
	} else {
            return $this->getPresenter()->isSignalReceiver($this, $signal);
	}
    }


    /**
     * Ciselnik form submit handler.
     * @param  AppForm
     * @return void
     */
    public function formSubmitHandler(AppForm $form)
    {
        $this->receivedSignal = 'submit';

	// was form submitted?
	if ($form->isSubmitted()) {
            
            $values = $form->getValues();
            $data = $form->getHttpData();

            // stav fixed
            if ( isset($values['stav']) && isset($data['stav']) ) {
                if ( $values['stav'] != $data['stav'] ) {
                    $values['stav'] = $data['stav'];
                }
            }

            if ( isset($data['novyCiselnik']) ) {
                $this->handleNew($values);
            } else if ( isset($data['upravitCiselnik']) ) {
                $this->handleEdit($values, $data['primary']);
            } else if ( isset($data['odstranitCiselnik']) ) {
                $this->handleDelete($data['primary']);
            } else if ( isset($data['stornoCiselnik']) ) {
                $this->handleStorno();
            } else {
                throw new InvalidStateException("Unknown submit button.");
            }

	}
	if (!$this->presenter->isAjax()) $this->presenter->redirect('this');
    }

    public function handleNew($values)
    {
        try {

            $model = new Model( $this->table );
            $model->insert($values);
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně vytvořen.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo vytvořit!','error');
            $this->presenter->flashMessage($e->getMessage(),'error');
        }

	$this->invalidateControl();
        if (!$this->presenter->isAjax()) $this->redirect('this');
    }

    public function handleEdit($values, $id)
    {
        try {

            $model = new Model( $this->table );
            $id_name = !empty($this->primary)?$this->primary:'id';
            $model->update($values, array( array('%and', array($id_name => $id)) ) );
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně upraven.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo upravit!','error');
            $this->presenter->flashMessage($e->getMessage(),'error');
        }

	$this->invalidateControl();
        if (!$this->presenter->isAjax()) $this->redirect('this');
    }

    public function handleDelete($id)
    {
        try {

            $model = new Model( $this->table );
            $id_name = !empty($this->primary)?$this->primary:'id';
            $model->delete(array( array('%and', array($id_name => $id)) ) );
            $this->dataChangedHandler();

            $this->presenter->flashMessage('Záznam byl úspěšně odstraněn.');
        } catch (Exception $e) {
            $this->presenter->flashMessage('Záznam se nepodařilo odstranit!','error');
            $this->presenter->flashMessage($e->getMessage(),'error');
        }

	$this->invalidateControl();
        if (!$this->presenter->isAjax()) $this->redirect('this');
    }

    public function handleStorno()
    {
	$this->invalidateControl();
        if (!$this->presenter->isAjax()) $this->redirect('this');
    }

    // Určeno k přepsání v případné třídě potomka
    protected function dataChangedHandler()
    {        
    }
}