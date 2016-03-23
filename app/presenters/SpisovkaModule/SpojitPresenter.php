<?php

class Spisovka_SpojitPresenter extends BasePresenter
{

    private $typ_evidence;

    public function startup()
    {
        $client_config = GlobalVariables::get('client_config');
        $this->typ_evidence = $client_config->cislo_jednaci->typ_evidence;

        parent::startup();
    }

    public function renderVyber()
    {
        $this->template->dokument_id = $this->getParameter('id');
    }

    public function actionHledat()
    {
        $query = $this->getParameter('q', null);

        $Dokument = new Dokument();
        $args = $Dokument->hledat($query);
        $args['order'] = array('d.podaci_denik_rok', 'd.podaci_denik_poradi', 'd.poradi');

        // nehledej mezi dokumenty ve spisovně
        $args = $Dokument->filtrSpisovka($args);
        $seznam = $Dokument->seznam($args);

        if (count($seznam) == 0) {
            // prazdna odpoved
            $this->terminate();
        }
        
        if (count($seznam) > 200) {
            echo "prilis_mnoho";
            $this->terminate();
        }

        $tmp = array();
        foreach ($seznam as $dokument_id) {

            if (is_object($dokument_id))
                $dokument_id = $dokument_id->id;

            $dok = $Dokument->getBasicInfo($dokument_id);

            // Tomášovina - kód je použit též u sběrného archu pro zařazení dokumentu do spisu
            if ($this->typ_evidence == "sberny_arch") {
                if ($dok->poradi != 1 || empty($dok->cislo_jednaci))
                    continue;
            }

            $tmp[$dok->id]['dokument_id'] = $dok->id;
            $tmp[$dok->id]['cislo_jednaci'] = $dok->cislo_jednaci;
            $tmp[$dok->id]['jid'] = $dok->jid;
            $tmp[$dok->id]['nazev'] = $dok->nazev;
        }

        $this->sendJson($tmp);
    }

    public function actionVybrano()
    {
        $dokument_id = $this->getParameter('id', null);
        $dokument_spojit = $this->getParameter('spojit_s', null);

        $Dokument = new Dokument();

        $dok_in = $Dokument->getBasicInfo($dokument_id);
        $dok_out = $Dokument->getBasicInfo($dokument_spojit);
        if (!$dok_in || !$dok_out)
            throw new Exception('Neplatný parametr');

        // spojit s dokumentem
        $SouvisejiciDokument = new SouvisejiciDokument();
        $SouvisejiciDokument->spojit($dokument_id, $dokument_spojit);

        $this->terminate();
    }

    public function actionOdebrat()
    {
        $dokument1_id = $this->getParameter('id', null);
        $dokument2_id = $this->getParameter('spojeny', null);

        $Souvisejici = new SouvisejiciDokument();
        $Souvisejici->odebrat($dokument1_id, $dokument2_id);
        
        $this->terminate();
    }

}
