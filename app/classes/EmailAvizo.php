<?php

class EmailAvizo {
 
/*    public static function epodatelna_prijeti($komu, $data)
    {        
        try {            
            // Urad
            $user_config = Nette\Environment::getVariable('user_config');
            $urad = $user_config->urad;
            
            $mail = new ESSMail;
            $mail->signed(1);
        
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
    }    */
    
    public static function epodatelna_zaevidovana($komu, $data)
    {
        try {
            // Urad
            $user_config = Nette\Environment::getVariable('user_config');
            $urad = $user_config->urad;

            $mail = new ESSMail;
            $mail->signed(1);

            $mail->addTo($komu);

            if (empty($data['nazev'])) {
                $mail->setSubject("Re: [Zpráva zaevidována]");
            } else {
                $mail->setSubject("Re: " . $data['nazev'] . " [Zpráva zaevidována]");
            }

            $zprava = "Vaše emailová zpráva byla zaevidována dne " . date("j.n.Y G:i:s") . " pod číslem " . $data['jid'] . " a bude co nejdříve vyřízena.";

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

}
