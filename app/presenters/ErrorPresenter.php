<?php

class ErrorPresenter extends BasePresenter
{

    public function startup()
       {
           // P.L. Preskoc startup kod v BasePresenteru
           Presenter::startup();
       }

    public function beforeRender()
    {
        // P.L. Pouzij login sablonu pro vypis chyby, prestoze je uzivatel jiz prihlasen
        // Standardni sablona "layout" zpusobuje dvojite chyby, pokud je nejaky problem
        // s autentizaci/autorizaci

        BasePresenter::beforeRender();
        $this->setLayout('login');
    }

	/**
	 * @return void
	 */
	public function renderDefault($exception)
	{

		if ($this->isAjax()) {
			$this->getAjaxDriver()->events[] = array('error', $exception->getMessage());
			$this->terminate();

		} else {
			$this->template->robots = 'noindex,noarchive';

			if ($exception instanceof BadRequestException) {
				Environment::getHttpResponse()->setCode($exception->getCode());
				// $this->template->title = '404 Not Found';
                $this->template->message = $exception->getMessage();
				$this->setView('404');
			} else {
				Environment::getHttpResponse()->setCode(500);
				// $this->template->title = '500 Internal Server Error';
                $this->template->message = $exception->getMessage();
				$this->setView('500');

                // vytvor log pro vyjimku
				Debug::processException($exception);
			}
		}

	}


}
