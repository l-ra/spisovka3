<?php

namespace Spisovka;

use Nette;

class ErrorPresenter extends BasePresenter
{

    public function startup()
    {
        // P.L. Preskoc startup kod v BasePresenteru
        Nette\Application\UI\Presenter::startup();
    }

    public function beforeRender()
    {
        // P.L. Pouzij login sablonu pro vypis chyby, prestoze je uzivatel jiz prihlasen
        // Standardni sablona "layout" zpusobuje dvojite chyby, pokud je nejaky problem
        // s autentizaci/autorizaci

        parent::beforeRender();
        $this->setLayout('login');
    }

    /**
     * @return void
     */
    public function renderDefault($exception)
    {

        if ($this->isAjax()) {
            echo $exception->getMessage();
            $this->terminate();
        } else {
            $this->template->robots = 'noindex,noarchive';
            $httpResponse = $this->getHttpResponse();

            if ($exception instanceof Nette\Application\BadRequestException) {
                if (!$httpResponse->isSent())
                    $httpResponse->setCode($exception->getCode());
                // $this->template->title = '404 Not Found';
                $this->template->message = $exception->getMessage();
                $this->setView('404');
            } else {
                if (!$httpResponse->isSent())
                    $httpResponse->setCode(500);
                // $this->template->title = '500 Internal Server Error';
                $this->template->message = $exception->getMessage();
                $this->setView('500');

                // vytvor log pro vyjimku
                // Nette\Diagnostics\Debugger::processException($exception);
            }
        }
    }

}
