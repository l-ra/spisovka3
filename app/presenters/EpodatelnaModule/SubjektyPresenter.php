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

    protected function createComponentNovyForm()
    {
        $form1 = parent::createComponentNovyForm();

        $form1['novy']->onClick[] = array($this, 'vytvoritClicked');
        $form1['novy']->controlPrototype->onclick("return epodSubjektNovySubmit(this);");
        $form1['storno']->controlPrototype->onclick("return epodSubjektNovyStorno();");

        return $form1;
    }
}
