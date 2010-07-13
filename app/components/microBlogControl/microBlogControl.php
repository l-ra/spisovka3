<?php

class microBlogControl extends Control {


    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {

        $mblog = array();
        for( $i=0 ; $i < 10 ; $i++ ) {
            $tmp = new stdClass();
            $tmp->id = $i;
            $tmp->author = 'Tomík';
            $tmp->comment = 'Lorem ipsum dolor sit amet consectetuer convallis augue leo Curabitur quis. Pede eget odio tellus tellus tempus lacinia nunc enim id Curabitur. Id orci cursus vitae ut et Donec et Vestibulum Curabitur Curabitur. Et auctor id risus at sed hendrerit sem feugiat nunc vitae. Sed urna Nullam Aenean tristique Maecenas tristique elit elit sapien ligula. Cursus justo dapibus consequat Donec at leo nunc a dui a. Nec vel orci.';
            $tmp->date = date('Y-m-d H:i:s');
            $mblog[$i] = $tmp;
            unset($tmp);
        }
        $this->template->mblog = $mblog;

        $this->template->setFile(dirname(__FILE__) . '/microBlogControl.phtml');
	//$this->template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
	$this->template->render();
    }



	/**
	 * Loads params
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		if (isset($params['order'])) {
                    $params['order'] = implode('.', $params['order']);
		}

		parent::loadState($params);
	}



	/**
	 * Save params
	 * @param  array
	 * @return void
	 */
	public function saveState(array & $params)
	{
		parent::saveState($params);
		if (isset($params['order'])) {
			$params['order'] = implode('.', $params['order']);
		}
	}






}