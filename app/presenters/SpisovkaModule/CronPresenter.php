<?php

// Trida NESMI dedit z BasePresenteru (kvuli autentizaci)

class Spisovka_CronPresenter extends Nette\Application\UI\Presenter
{

    public function renderDefault()
    {
        
    }

    public function actionSpustit()
    {

        /* Kontrola novych zprav z webu */
        UpdateAgent::update(UpdateAgent::CHECK_NOTICES);

        /* Kontrola nove verze */
        UpdateAgent::update(UpdateAgent::CHECK_NEW_VERSION);

        // Zjisti, kdy naposledy byly odeslany informace o uzivateli a po uplynuti urciteho intervalu je odesli znovu
        $send = true;
        $params = $this->context->parameters;
        if (isset($params['send_survey']))
            $send = $params['send_survey'];
        
        if ($send) {
            $last_run = Settings::get('survey_agent_last_run', 0);
            $now = time();
            if ($now - $last_run > 15 * (24 * 60 * 60)) {
                Settings::set('survey_agent_last_run', $now);
                SurveyAgent::send();            
            }
        }
        
        exit;
    }

}
