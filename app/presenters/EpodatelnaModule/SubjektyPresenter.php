<?php //netteloader=Epodatelna_SubjektyPresenter

class Epodatelna_SubjektyPresenter extends SubjektyPresenter
{
    // Volano pouze pres Ajax
    public function renderNacti()
    {
        $subjekt_id = $this->getParameter('id',null);
        $Subjekt = new Subjekt();
        $this->template->subjekt = $Subjekt->getInfo($subjekt_id);
    }

}
