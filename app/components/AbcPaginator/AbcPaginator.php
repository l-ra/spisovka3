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
 */
class AbcPaginator extends Nette\Application\UI\Control
{

    /**
     * Renders paginator.
     * @return void
     */
    public function render()
    {
        $request = Nette\Environment::getHttpRequest();
        $url = $request->getUri()->getPath();
        $query_string = $request->getUri()->getQuery();
        $query_params = "";
        parse_str($query_string, $query);
        unset($query['abc-abc'],$query['vp-page'],$query['do']);
        if ( count($query)>0 ) {
            foreach ( $query as $key=>$value ) {
                if ( empty($key) ) continue;
                    $query_params .= "&". $key ."=". @urlencode($value);
                }
            }
        
        $this->template->current_letter = $this->getParam('abc');
        $this->template->url = $url;
        $this->template->query = $query_params;
        $this->template->js_function = false;
        if ($request->isAjax())
            $this->template->js_function = 'reloadDialog';

        $this->template->setFile(dirname(__FILE__) . '/template.phtml');
        $this->template->render();
    }

}