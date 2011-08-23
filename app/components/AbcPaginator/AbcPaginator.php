<?php

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 * @version    $Id: VisualPaginator.php 4 2009-07-14 15:22:02Z david@grudl.com $
 */

/**
 * ABC paginator control.
 *
 * @author     Tomas Vancura
 * @copyright  Copyright (c) 2011 Tomas Vancura
 * @package    Nette Extras
 */
class AbcPaginator extends Control
{

    /**
     * Renders paginator.
     * @return void
     */
    public function render()
    {

        $this->template->url = Environment::getHttpRequest()->getUri()->getPath();
        $this->template->abc = $this->presenter->getParam('abc');

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}