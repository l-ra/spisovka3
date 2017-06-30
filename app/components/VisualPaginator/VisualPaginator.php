<?php

namespace Spisovka\Components;

use Nette;


/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 */


/**
 * Visual paginator control.
 *
 * @copyright  Copyright (c) 2009 David Grudl
 */
class VisualPaginator extends Nette\Application\UI\Control
{
    /** @var Nette\Utils\Paginator */
    private $paginator;

    /** @persistent */
    public $page = 1;


    /**
     *
     * @var Nette\Http\Request 
     */
    protected $httpRequest;
    
    public function __construct($parent, $name, Nette\Http\Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        parent::__construct($parent, $name);
    }

    /**
     * @return Nette\Paginator
     */
    public function getPaginator()
    {
        if (!$this->paginator) {
            $this->paginator = new Nette\Utils\Paginator;
        }
        return $this->paginator;
    }



    /**
     * Renders paginator.
     * @return void
     */
    public function render()
    {
        $paginator = $this->getPaginator();
        $page = $paginator->page;
        if ($paginator->pageCount < 2) {
            $steps = array($page);

        } else {
            $arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
            $count = 4;
            $quotient = ($paginator->pageCount - 1) / $count;
            for ($i = 0; $i <= $count; $i++) {
                $arr[] = round($quotient * $i) + $paginator->firstPage;
            }
            sort($arr);
            $steps = array_values(array_unique($arr));
        }
                
        $this->template->steps = $steps;
        $this->template->paginator = $paginator;
        
        // [P.L.] Pridano
        $this->template->onclick = '';        
        if ($this->httpRequest->isAjax())
            $this->template->onclick = 'onclick="reloadDialog(this); return false;"';

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }



    /**
     * Loads state informations.
     * @param  array
     * @return void
     */
    public function loadState(array $params)
    {
        parent::loadState($params);
        $this->getPaginator()->page = $this->page;
    }

}