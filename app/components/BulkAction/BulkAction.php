<?php

namespace Spisovka\Components;

use Nette;

class BulkAction extends Nette\Application\UI\Control
{

    protected $actions;
    protected $callback;
    
    public $text_checkbox_title = 'Vybrat tento dokument';
    public $text_empty_selection = 'Nebyl vybrán žádný dokument.';

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }
    
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }
    
    public function render()
    {
        throw new \Exception(__METHOD__ . '() - do not call this method directly.');
    }

    public function renderStart()
    {        
        $this->template->component_name = $this->getName();
        $this->template->setFile(dirname(__FILE__) . '/formStart.latte');
        $this->template->render();
    }

    public function renderCheckbox($id)
    {
        if (empty($id))
            throw new \Exception(__METHOD__ . '() - missing parameter "id"');

        $this->template->id = $id;
        $this->template->title = $this->text_checkbox_title;
        $this->template->setFile(dirname(__FILE__) . '/checkbox.latte');
        $this->template->render();
    }

    public function renderEnd()
    {
        $this->template->component_name = $this->getName();
        $this->template->actions = $this->actions;
        $this->template->setFile(dirname(__FILE__) . '/formEnd.latte');
        $this->template->render();
    }
    
    public function handleSubmit()
    {
        if ($this->callback === null)
            throw new \Exception(__METHOD__ . '() - callback was not set');
        
        $presenter = $this->getPresenter();
        $data = $presenter->getRequest()->getPost();
        if (!isset($data['bulk_submit']))
            return;
        
        if (empty($data['selection'])) {
            $presenter->flashMessage($this->text_empty_selection);
            $presenter->redirect('this');
        }
        $selection = $data['selection'];
        
        $action = $data['action'];
        if (empty($action)) {
            $presenter->flashMessage('Nebyla zvolena žádná akce.');
            $presenter->redirect('this');            
        }
        
        Nette\Utils\Callback::invoke($this->callback, $action, $selection);
        $presenter->redirect('this');            
    }

}
