<?php

namespace Spisovka;

use Nette;

class DocumentWorkflow extends DocumentStates
{

    /**
     * Prevezme dokument k vyrizeni
     * @param int $dokument_id
     * @return boolean
     * @throws \Exception
     */
    public function markForProcessing()
    {
        $user = self::getUser();

        if (!$this->canUserModify())
            return false;

        dibi::begin();
        try {
            $spis = $this->getSpis();
            if ($spis) {
                $this->spisovy_znak_id = $spis->spisovy_znak_id;
                $this->skartacni_znak = $spis->skartacni_znak;
                $this->skartacni_lhuta = $spis->skartacni_lhuta;
                $this->spousteci_udalost_id = $spis->spousteci_udalost_id;
            }

            // převezmi dokument
            $this->owner_user_id = self::getUser()->id;
            $this->save();

            $this->_changeState(self::STAV_VYRIZUJE_SE);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_KVYRIZENI,
                    'Zaměstnanec ' . $user->displayName . ' převzal dokument k vyřízení.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * Oznaci dokument za vyrizeny
     * @return boolean|string
     * @throws \Exception
     */
    public function close()
    {
//        Je kontrolovano v _changeState()
//        if (!$this->canUserModify())
//            return false;

        if ($result = $this->checkComplete())
            return $result;

        dibi::begin();
        try {
            $event = new StartEvent($this->spousteci_udalost_id);
            $automatic_start = $event->isAutomatic();

            $stav = $automatic_start ? self::STAV_VYRIZEN_SPUSTENA : self::STAV_VYRIZEN_NESPUSTENA;
            $this->_changeState($stav);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_VYRIZEN, 'Dokument označen za vyřízený.');

            if ($automatic_start) {
                $this->datum_spousteci_udalosti = date('Y-m-d');
                $this->save();
                $Log->logDocument($this->id, LogModel::DOK_SPUSTEN,
                        'Začíná plynout skartační lhůta.');
            }

            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }

        return $automatic_start ? true : 'udalost';
    }

    /**
     * Nastaví rozhodný okamžik pro začátek plynutí skartační lhůty.
     * @param string $start_date
     * @throws \Exception 
     */
    public function setStartDate($start_date)
    {
        if ($this->stav != self::STAV_VYRIZEN_NESPUSTENA)
            throw new \LogicException('Neplatný stav dokumentu.');

        dibi::begin();
        try {
            $this->datum_spousteci_udalosti = $start_date;
            $this->save();
            $this->_changeState(self::STAV_VYRIZEN_SPUSTENA);

            $today = new \DateTime();
            $today->setTime(0, 0);
            $given_date = new \DateTime($start_date);
            if ($given_date == $today)
                $msg = 'Začíná plynout skartační lhůta.';
            else
                $msg = 'Skartační lhůta začne plynout od ' . $given_date->format('j.n.Y') . '.';

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_SPUSTEN, $msg);

            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * Může uživatel znovu otevřít již uzavřený dokument?
     */
    public function canUserReopen()
    {
        $user_allowed = $this->getUser()->isAllowed('Dokument', 'znovu_otevrit');
        $modify_ok = $this->canUserModify();
        $state_ok = in_array($this->stav,
                [self::STAV_VYRIZEN_NESPUSTENA, self::STAV_VYRIZEN_SPUSTENA]);
        return $user_allowed && $state_ok && $modify_ok;
    }

    public function reopen()
    {
        if (!$this->canUserReopen())
            throw new \Exception('Otevření dokumentu není možné.');

        dibi::begin();
        try {
            $this->_changeState(self::STAV_VYRIZUJE_SE);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_ZNOVU_OTEVREN);
            dibi::commit();
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    /**
     * @param boolean $called_from_spis
     * @return boolean
     * @throws \Exception
     */
    public function transferToSpisovna($called_from_spis)
    {
        if ($this->getSpis() && !$called_from_spis)
            return 'Dokument ' . $this->cislo_jednaci . ' nelze přenést do spisovny samostatně, je součástí spisu.';

        $err_msg = "Dokument $this->cislo_jednaci nelze přenést do spisovny!";
        if ($this->checkComplete())
            return $err_msg . ' Nejsou vyřízeny všechny potřebné údaje.';

        if ($this->stav < self::STAV_VYRIZEN_NESPUSTENA)
            return $err_msg . ' Není označen jako vyřízený.';
        if ($this->stav < self::STAV_VYRIZEN_SPUSTENA)
            return $err_msg . ' Není spuštěna skartační lhůta.';

        if (!$called_from_spis)
            dibi::begin();
        try {
            $this->_changeState(self::STAV_PREDAN_DO_SPISOVNY);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_SPISOVNA_PREDAN);
            if (!$called_from_spis)
                dibi::commit();
        } catch (Exception $e) {
            if (!$called_from_spis)
                $this->_rollback();
            throw $e;
        }

        return true;
    }

    /**
     * @param boolean $independent  false = voláno při přezetí celého spisu
     *                              true = voláno při přezetí jednoho dokumentu
     * @return boolean|string
     */
    public function receiveIntoSpisovna($independent)
    {
        $error_msg = "Dokument $this->jid nelze přijmout do spisovny!";

        if ($independent && $this->getSpis())
            return "$error_msg Dokument je součástí spisu.";

        if ($kontrola = $this->checkComplete())
            return "$error_msg Nejsou vyřízeny všechny potřebné údaje.";

        // Kontrola stavu - vyrizen a spusten
        if ($this->stav < self::STAV_VYRIZEN_NESPUSTENA)
            return "$error_msg Není označen jako vyřízený.";
        if ($this->stav < self::STAV_VYRIZEN_SPUSTENA)
            return "$error_msg Není spuštěna událost.";

        $transaction = $independent;
        if ($transaction)
            dibi::begin();
        try {
            $this->_changeState(self::STAV_VE_SPISOVNE);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_SPISOVNA_PRIPOJEN,
                    'Dokument přijat do spisovny.');

            if ($transaction)
                dibi::commit();
            return true;
        } catch (Exception $e) {
            if ($transaction)
                $this->_rollback();
            return "Při převzetí dokumentu $this->cislo_jednaci došlo k výjimce: " . $e->getMessage();
        }
    }

    /**
     * @param boolean $use_transaction
     * @throws \Exception
     * @throws Nette\InvalidStateException
     */
    public function returnFromSpisovna($use_transaction = true)
    {
        if (!in_array($this->stav, [self::STAV_PREDAN_DO_SPISOVNY, self::STAV_VE_SPISOVNE]))
            throw new Nette\InvalidStateException();

        if ($use_transaction)
            dibi::begin();
        try {
            $this->_changeState(self::STAV_VYRIZEN_SPUSTENA);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_SPISOVNA_VRACEN);

            if ($use_transaction)
                dibi::commit();
        } catch (Exception $e) {
            if ($use_transaction)
                $this->_rollback();
            throw $e;
        }
    }

    /**
     * Zahrnutí dokumentu do skartačního řízení
     * @return boolean
     * @throws \Exception
     */
    public function shredProcessing()
    {
        dibi::begin();
        try {
            $this->_changeState(self::STAV_SKARTACNI_RIZENI);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_KESKARTACI,
                    'Dokument přidán do skartačního řízení.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function archive()
    {
        dibi::begin();
        try {
            $this->_changeState(self::STAV_ARCHIVOVAN);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_ARCHIVOVAN,
                    'Dokument uložen do archivu.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function shred()
    {
        dibi::begin();
        try {
            $this->_changeState(self::STAV_SKARTOVAN);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::DOK_SKARTOVAN, 'Dokument byl skartován.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function lend($user_id)
    {
        try {
            dibi::begin();

            $this->owner_user_id = $user_id;
            $account = new UserAccount($user_id);
            $ou = $account->getOrgUnit();
            $this->owner_orgunit_id = $ou ? $ou->id : null;
            $this->save();

            $this->_changeState(self::STAV_ZAPUJCEN);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::ZAPUJCKA_PRIDELENA, 'Dokument byl zapůjčen.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

    public function returnToSpisovna()
    {
        try {
            dibi::begin();

            $this->_changeState(self::STAV_VE_SPISOVNE);

            $Log = new LogModel();
            $Log->logDocument($this->id, LogModel::ZAPUJCKA_VRACENA,
                    'Dokument byl navrácen do spisovny.');

            dibi::commit();
            return true;
        } catch (Exception $e) {
            $this->_rollback();
            throw $e;
        }
    }

}
