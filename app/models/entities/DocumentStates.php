<?php

class DocumentStates extends Document
{
    /*
     * 0 - rozepsany dokument (jeste nevytvoreny)
     * 2 - nepouzit, neplatny stav
     */

    const STAV_NOVY = 1;
    const STAV_VYRIZUJE_SE = 3;
    const STAV_VYRIZEN_NESPUSTENA = 4;
    const STAV_VYRIZEN_SPUSTENA = 5;
    const STAV_PREDAN_DO_SPISOVNY = 6;
    const STAV_VE_SPISOVNE = 7;
    const STAV_SKARTACNI_RIZENI = 8;
    const STAV_ARCHIVOVAN = 9;
    const STAV_SKARTOVAN = 10;
    const STAV_ZAPUJCEN = 11;

    private static $transition_table = [
        self::STAV_NOVY => [self::STAV_VYRIZUJE_SE],
        self::STAV_VYRIZUJE_SE => [self::STAV_VYRIZEN_NESPUSTENA, self::STAV_VYRIZEN_SPUSTENA],
        self::STAV_VYRIZEN_NESPUSTENA => [self::STAV_VYRIZEN_SPUSTENA, self::STAV_VYRIZUJE_SE],
        self::STAV_VYRIZEN_SPUSTENA => [self::STAV_PREDAN_DO_SPISOVNY, self::STAV_VYRIZUJE_SE],
        self::STAV_PREDAN_DO_SPISOVNY => [self::STAV_VYRIZEN_SPUSTENA, self::STAV_VE_SPISOVNE],
        self::STAV_VE_SPISOVNE => [self::STAV_SKARTACNI_RIZENI, self::STAV_ZAPUJCEN],
        self::STAV_SKARTACNI_RIZENI => [self::STAV_ARCHIVOVAN, self::STAV_SKARTOVAN],
        self::STAV_ARCHIVOVAN => [],
        self::STAV_SKARTOVAN => [],
        self::STAV_ZAPUJCEN => [self::STAV_VE_SPISOVNE],
    ];
    private static $transition_conditions = [
        self::STAV_VYRIZEN_NESPUSTENA => 'condClosed',
        self::STAV_VYRIZEN_SPUSTENA => 'condClosed',
        self::STAV_PREDAN_DO_SPISOVNY => 'condSpisovnaReceive',
        self::STAV_VE_SPISOVNE => 'condInSpisovna',
        self::STAV_SKARTACNI_RIZENI => 'condSpisovnaShredProcessing',
        self::STAV_ZAPUJCEN => 'condReturnToSpisovna'
    ];

    /**
     * @param int $new_state
     * @throws Exception
     */
    protected function _changeState($new_state)
    {
        if ($this->is_forwarded)
            throw new Exception('Není možné změnit stav dokumentu, pokud je ve stavu předávání.'
            . ' Nejprve převezměte dokument, případně předání zrušte, pak operaci s dokumentem zopakujte.');

        if (!in_array($new_state, $this->getAvailableTransitions()))
            throw new Exception("Změna stavu dokumentu ze stavu $this->stav do $new_state není povolena.");

        if ($this->_data_changed)
            throw new Exception(__METHOD__ . '() - objekt má neuložené změny, není možné provést přechod do jiného stavu.');

        if (isset(self::$transition_conditions[$this->stav])) {
            $method = self::$transition_conditions[$this->stav];
            $allowed = $this->$method($new_state);
        } else
            $allowed = $this->canUserModify();

        if (!$allowed)
            throw new Exception('Nejste oprávněn měnit stav dokumentu.');

        dibi::query('UPDATE %n SET [stav] = %i WHERE [id] = ' . $this->id,
                ':PREFIX:' . $this::TBL_NAME, $new_state);

        $this->_invalidate();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAvailableTransitions()
    {
        $state = $this->stav;
        if (!in_array($state, array_keys(self::$transition_table)))
            throw new Exception(__METHOD__ . '() - invalid state.');

        return self::$transition_table[$state];
    }

    public static function getSourceStates($destination_state)
    {
        $result = [];
        foreach (self::$transition_table as $source => $dest_array) {
            foreach ($dest_array as $dest) {
                if ($destination_state == $dest)
                    $result[] = $source;
            }
        }
        return $result;
    }
    
    /**
     * Protection against reckless developer
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if ($name == 'stav')
            throw new Exception(__METHOD__ . '() - stav není možné nastavit přímo.');

        return parent::__set($name, $value);
    }

    private function condClosed($new_state)
    {
        // Pri predavani spisu do spisovny je nutne zmenit stav i dokumentu, ktere
        // uzivatel neni opravnen menit
        if ($new_state == self::STAV_PREDAN_DO_SPISOVNY && $this->getSpis())
            return true;
        
        return $this->canUserModify();
    }
    
    private function condSpisovnaReceive()
    {
        return self::getUser()->isAllowed("Spisovna", "prijem_dokumentu");
    }

    private function condSpisovnaShredProcessing()
    {
        return self::getUser()->isAllowed("Spisovna", "skartacni_rizeni") && !$this->_isBorrowed();
    }

    private function condInSpisovna($new_state)
    {
        $user = self::getUser();
        if ($new_state == self::STAV_SKARTACNI_RIZENI) {
            $ok = $user->isAllowed('Spisovna', 'skartacni_navrh') && !$this->_isBorrowed();
            return $ok;
        } else if ($new_state == self::STAV_ZAPUJCEN)
            return $user->isAllowed('Zapujcka', 'schvalit');
    }

    private function condReturnToSpisovna()
    {
        $user = self::getUser();
        return $user->id == $this->owner_user_id || $user->isAllowed('Zapujcka', 'schvalit');
    }

    /**
     * @return boolean  Vraci true, i kdyz existuje neschvalena zapujcka.
     */
    protected function _isBorrowed()
    {
        $z = new Zapujcka();
        $stav = $z->stavZapujcky($this->id);
        return in_array($stav, [Zapujcka::STAV_NESCHVALENA, Zapujcka::STAV_SCHVALENA]);
    }

}
