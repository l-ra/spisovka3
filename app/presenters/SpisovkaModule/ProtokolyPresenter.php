<?php

namespace Spisovka;

use Nette;

class Spisovka_ProtokolyPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }

    public function renderDefault($filtr = LogModel::DOK_NOVY, $sestupne = false, $od = null, $datum_od
    = null, $datum_do = null)
    {
        $lm = new LogModel();
        $list = $lm->getUsersHistory($this->user->id, $filtr, $sestupne, $od, $datum_od,
                $datum_do);
        $this->template->list = $list;
    }

    public function createComponentForm()
    {
        $form = new Form();
        $items = [LogModel::DOK_NOVY => 'Vytvoření dokumentu',
            LogModel::DOK_PREDAN => 'Předání dokumentu',
            LogModel::DOK_VYRIZEN => 'Vyřízení dokumentu'];
        $control = $form->addSelect('filter', 'Filtr:', $items);
        $control->getControlPrototype()->onchange("$(this).parent('form').submit()")
                ->class = 'blue';
        $selected = $this->getParameter('filtr');
        if (array_key_exists($selected, $items))
            $control->setDefaultValue($selected);

        $items = ['asc' => 'Vzestupně',
            'desc' => 'Sestupně'];
        $control = $form->addSelect('ordering', 'Řazení:', $items);
        $control->getControlPrototype()->onchange("$(this).parent('form').submit()")
                ->class = 'blue';
        $control->setDefaultValue($this->getParameter('sestupne') ? 'desc' : 'asc');

        $form->onSuccess[] = array($this, 'filterSubmitted');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function filterSubmitted($form, $data)
    {
        $this->redirect('this', $data->filter, $data->ordering == 'desc');
    }

    public function createComponentSearch()
    {
        $form = new Form();

        $form->addGroup();
        $from = $this->getParameter('datum_od');
        $form->addDatePicker('from', 'Od:')
                ->setDefaultValue($from);
        $to = $this->getParameter('datum_do');
        $form->addDatePicker('to', 'Do:')
                ->setDefaultValue($to);

        $form->addSubmit('search', 'Hledat');
        if ($from || $to) {
            $form->addGroup();
            $form->addSubmit('reset', 'Zrušit hledání');
        }
        
        $form->onSuccess[] = array($this, 'searchSubmitted');

        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = 'div id=search';
        $renderer->wrappers['group']['container'] = 'div';
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;

        return $form;
    }

    public function searchSubmitted(Form $form, $data)
    {
        $from = $data->from;
        $to = $data->to;
        if (!$form['search']->isSubmittedBy())
            $from = $to = null; // použit submit "Zrušit hledání"
        $this->redirect('this', ['datum_od' => $from, 'datum_do' => $to, 'od' => null]);
    }

}
