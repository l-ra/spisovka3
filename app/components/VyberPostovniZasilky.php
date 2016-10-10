<?php

namespace Spisovka\Controls;

/**
 * Description of VyberPostovniZasilky
 *
 * @author Pavel Laštovička
 */
class VyberPostovniZasilkyControl extends \Nette\Forms\Controls\BaseControl
{

    public function __construct()
    {
        parent::__construct('Druh zásilky:');
        $this->setValue(array());
    }

	public function getLabel($caption = NULL)
	{
		return parent::getLabel($caption)->for(NULL);
	}
    
    /**
     * Generates control's HTML element.
     * @return string
     */
    public function getControl()
    {
        $value = $this->value;
        $input_name = $this->getHtmlName();

        $html = '';
        $last_order = 0;
        foreach (\DruhZasilky::get() as $id => $druh) {
            if (floor($druh->order / 100) > floor($last_order / 100))
                $html .= '<br />';
            $last_order = $druh->order;
            
            $el = \Nette\Utils\Html::el('input type="checkbox"');
            $el->name = $input_name;
            $el->value = $id;
            $el->checked = in_array($id, $value);
            $html .= '<label>' . $el;
            $html .= htmlspecialchars($druh->nazev, ENT_COMPAT, 'UTF-8');
            $html .= '</label>';
        }

        return $html;
    }

    /**
     * Returns HTML name of control.
     * @return string
     */
    public function getHtmlName()
    {
        return parent::getHtmlName() . '[]';
    }

    public function setValue($value)
    {
        if ($value === null)
            $value = [];
        if (is_string($value))
            $value = unserialize($value);
        parent::setValue($value);
    }
    
    /**
     * @return array
     */
    public function getValue()
    {
        $selection = parent::getValue();
        foreach ($selection as &$item)
            $item = (int) $item;

        return $selection;
    }

	/**
	 * Is any item selected?
	 * @return bool
	 */
	public function isFilled()
	{
		return $this->getValue() !== array();
	}
}
