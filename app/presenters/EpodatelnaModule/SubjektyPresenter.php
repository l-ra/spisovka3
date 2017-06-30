<?php

namespace Spisovka;

class Epodatelna_SubjektyPresenter extends SubjektyPresenter
{

    // Volano pouze pres Ajax
    public function renderNacti($id)
    {
        $this->template->subjekt = new Subject($id);
    }

}
