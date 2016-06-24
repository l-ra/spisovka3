<?php

/**
 * Description of Document
 *
 * @author Pavel Laštovička
 */
class Document extends DBEntity
{

    const TBL_NAME = 'dokument';
    const TBL_WORKFLOW = ':PREFIX:workflow';

    protected $_workflow;

    /**
     * @param name    string
     */
    public function __get($name)
    {
        if ($name == 'workflow')
            return $this->getWorkflow();

        return parent::__get($name);
    }

    /**
     * Vrátí odpovídající záznam z workflow tabulky. 
     * Pracuje správně pouze není-li dokument ve stavu předávání.
     * @return type
     * @throws Exception
     */
    protected function getWorkflow()
    {
        if (!$this->_workflow) {
            $result = dibi::query("SELECT * FROM %n WHERE dokument_id = $this->id AND aktivni = 1",
                            self::TBL_WORKFLOW);
            if (!count($result))
                throw new Exception(__METHOD__ . "() - workflow záznam k dokumentu ID $this->id neexistuje");

            $this->_workflow = $result->fetch();
        }

        return $this->_workflow;
    }

    /**
     * Vrátí číselný stav dokumentu.
     */
    public function getState()
    {
        return $this->workflow->stav_dokumentu;
    }

    public function canUserModify()
    {
        $access = false;
        return $access;
    }

    /**
     * Může uživatel znovu otevřít již uzavřený dokument?
     */
    public function canUserReopen()
    {
        $user_allowed = $this->getUser()->isAllowed('Dokument', 'znovu_otevrit');
        return $user_allowed && in_array($this->getState(),
                        [Workflow::STAV_VYRIZEN_NESPUSTENA, Workflow::STAV_VYRIZEN_SPUSTENA]);
    }

    public function reopen()
    {
        if (!$this->canUserReopen())
            throw new Exception('Otevření dokumentu není možné.');
        
        $this->changeState(Workflow::STAV_VYRIZUJE_SE);
        $Log = new LogModel();
        $Log->logDokument($this->id, LogModel::DOK_ZNOVU_OTEVREN);
    }

    protected function changeState($new_state)
    {
        dibi::begin();
        try {
            $wf = $this->getWorkflow();
            $wf_table = self::TBL_WORKFLOW;
            dibi::query("UPDATE [$wf_table] SET [aktivni] = 0 WHERE [dokument_id] = $this->id");
            
            unset($wf->id);
            $wf->stav_dokumentu = $new_state;
            $wf->user_id = self::getUser()->id;
            $wf->date = new DateTime();
            dibi::insert($wf_table, $wf)->execute();
            
            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

}
