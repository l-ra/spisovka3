<?php

namespace Spisovka\Components;

class SearchControl extends \Spisovka\Form
{

    const PARAM = 'hledat';

    public function __construct(\Spisovka\BasePresenter $presenter)
    {
        parent::__construct();

        $this->addText('query', 'Hledat:', 20, 100)
                        ->setDefaultValue($presenter->getParameter(self::PARAM));
                      //->getControlPrototype()->title = "Hledat lze dle ...";

        $this->addSubmit('button', 'Hledat');
        $this->onSuccess[] = array($this, 'submitted');

        $renderer = $this->getRenderer();
        // $renderer->wrappers['form']['container'] = 'div id=search';
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = null;
        $renderer->wrappers['label']['container'] = null;
        $renderer->wrappers['control']['container'] = null;
    }

    public function render(...$args)
    {
        echo '<div id="search">';
        parent::render();
        $presenter = $this->getPresenter();
        if ($presenter->getParameter(self::PARAM)) {
            $anchor = \Nette\Utils\Html::el('a', 'Zrušit vyhledávání');
            $anchor->style = 'color: red';
            // Prechod na prvni stranku pri strankovani bohuzel nelze vyresit lepe/ciste
            // $anchor->href = $presenter->link('this', [self::PARAM => null, 'vp-page' => null]);
            $anchor->href = $presenter->link($presenter->view);
            echo $anchor;
        }
        echo '</div>';
    }

    public function submitted($form, $values)
    {
        $this->getPresenter()->redirect('this', [self::PARAM => $values['query']]);
    }

}
