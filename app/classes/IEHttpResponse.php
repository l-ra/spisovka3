<?php

namespace Spisovka;

/**
 * Fix pro Internet Explorer a jeho "kompatibilní zobrazení".
 * Přidá speciální HTTP hlavičku, která kompatibilní zobrazení vypne.
 *
 * @author Pavel Laštovička
 */
class IEHttpResponse extends \Nette\Http\Response
{

    public function __construct()
    {
        parent::__construct();

        $this->setHeader('X-UA-Compatible', 'IE=edge');
    }

}
