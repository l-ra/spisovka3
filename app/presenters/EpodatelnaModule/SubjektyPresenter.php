<?php

class Epodatelna_SubjektyPresenter extends SubjektyPresenter
{

    // Volano pouze pres Ajax
    public function renderNacti()
    {
        $subjekt_id = $this->getParameter('id');
        $Subjekt = new Subjekt();
        $this->template->subjekt = $Subjekt->getInfo($subjekt_id);
    }

}
