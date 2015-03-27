<?php

class EmailAvizo {
 
    protected static $config;

    public static function epodatelna_prijeti($komu, $data, $config = null)
    {
        
        if ( empty(self::$config) ) {
            self::setConfig($config);
        }
        try {
            
            // Urad
            $user_config = Nette\Environment::getVariable('user_config');
            $urad = $user_config->urad;
            
            $mail = new ESSMail;
            $mail->signed(1);
            $mail->setFromConfig(self::$config);
        
            $mail->addTo($komu);
            
            if ( empty($data->predmet) ) {
                $mail->setSubject("Re: [Zpráva přijata]");
            } else {
                $mail->setSubject("Re: ".$data->predmet." [Zpráva přijata]");
            }
            
            $zprava = "Váše emailová zpráva byla přijata podatelnou dne ". date("j.n.Y G:i:s") ." a bude předána ke zpracování.";
            
            $mail->setBodySign($zprava);
        
            //$mail->send();
            return true;
        } catch (Exception $e) {
            //throw new InvalidStateException('Chyba při odesílání emailu! '. $e->getMessage(),'error_ext');
            return false;
        }        
        
    }    
    
    public static function epodatelna_zaevidovana($komu, $data, $config = null)
    {
        
        if ( empty(self::$config) ) {
            self::setConfig($config);
        }
        try {
            
            // Urad
            $user_config = Nette\Environment::getVariable('user_config');
            $urad = $user_config->urad;
            
            $mail = new ESSMail;
            $mail->signed(1);
            $mail->setFromConfig(self::$config);
        
            $mail->addTo($komu);
            
            if ( empty($data['nazev']) ) {
                $mail->setSubject("Re: [Zpráva zaevidována]");
            } else {
                $mail->setSubject("Re: ".$data['nazev']." [Zpráva zaevidována]");
            }
            
            $zprava = "Vaše emailová zpráva byla zaevidována dne ".date("j.n.Y G:i:s")." pod číslem ". $data['jid'] ." a bude co nejdříve vyřízena.";
            
            //if ( !empty($data['predano']) ) {
            //    $zprava .= "\n\nPředano: ". $data['predano'];
            //}
            
            $mail->setBodySign($zprava);
        
            //$mail->send();
            return true;
        } catch (Exception $e) {
            //throw new InvalidStateException('Chyba při odesílání emailu! '. $e->getMessage(),'error_ext');
            return false;
        }        
        
    }      
    
    public static function test($komu, $config = null)
    {
        
        if ( empty(self::$config) ) {
            self::setFromConfig($config);
        }
        try {
            
            $mail = new ESSMail;
            $mail->signed(1);
            $mail->setFromConfig(self::$config);
        
            $mail->addTo($komu);
            $mail->setSubject("Testovací email");
            $mail->setBody("Testovací email");
        
            //$mail->send();
            return true;
        } catch (Exception $e) {
            //throw new InvalidStateException('Chyba při odesílání emailu! '. $e->getMessage(),'error_ext');
            return false;
        }        
        
    }
    
    
    
    
    /**
     * Nastavi konfiguraci emailu dle uzivatelske nastaveni e-podatelny
     * V pripade prazdneho parametru se pouzije defaultni ucet e-podatelny
     *
     * @param array $config konfigurace dle nastaveni e-podatelny
     * @return bool
     */
    protected static function setConfig($config = null)
    {
        if ( is_null($config) ) {
            $ep = (new Spisovka\ConfigEpodatelna())->get();
            if ( isset($ep['odeslani'][0]) ) {
                if ( $ep['odeslani'][0]['aktivni'] == '1' ) {
                    self::$config = $ep['odeslani'][0];
                    return $ep['odeslani'][0];
                } else {
                    //throw new InvalidStateException('Nebyl zjištěn aktivní účet pro odesílání emailů.');
                    return false;
                }
            } else {
                //throw new InvalidStateException('Nebyl zjištěn účet pro odesílání emailů.');
                return false;
            }
        } else {
            if ( isset($config['email']) ) {
                self::$config = $config;
                return $config;
            } else {
                //throw new InvalidStateException('Konfigurace pro odesílání emailů není platná.');
                return false;
            }
        }
    }
    
}
