<?php
/** 
 * AdminModule/presenters/SpecialPresenter.php
 *
 * Stub Trida pro provadeni specialnich jednorazovych pozadavku
 */

class Admin_SpecialPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return Environment::getUser()->isInRole('admin');
    }

    protected function error($msg)
    {
        echo "Došlo k následující chybě: $msg.";
        die;
    }

/* Priklad pro import spisu

    public function renderDefault()
    {
        if (KLIENT != 'kod_klienta')
            $this->error('Funkce je platná pouze pro specifického klienta.');
        
        $data = $this->importData();
        
        try {
            dibi::begin();
            
            $parent_id = 4;
            $parent_sequence = "SPISY.1#Slozka.$parent_id";
            $counter = 0;
            
            foreach ($data as $spis) {
                dibi::query("INSERT INTO :PREFIX:spis (parent_id, nazev, popis, date_created, user_created) VALUES (%i, %s, LEFT(%s, 199), NOW(), %i)",
                    $parent_id, $spis[0], $spis[1], 
                    Environment::getUser()->getIdentity()->id);
                    
                $id = dibi::getConnection()->getInsertId();
                
                dibi::query("UPDATE :PREFIX:spis SET sekvence = %s, sekvence_string = %s WHERE [id] = %i",
                    "1.$parent_id.$id", "$parent_sequence#{$spis[0]}.$id", $id);
                    
                // if (++$counter >= 10)
                    // break;
            }
            
            dibi::commit();
        }
        catch(Exception $e) {
            dibi::rollback();
            throw $e;
        }
        
        echo "Úspěšně dokončeno.";
        die;
    }
    
    protected function importData()
    {
        $result = array();
        
        $a = file(CLIENT_DIR . "/soubor.csv");
        if (!$a)
            $this->error('Nepodařilo se přečíst soubor s daty pro import.');
            
        foreach($a as $line) {
            $pos = strpos($line, ';');
            if ($pos === false)
                continue;
            $nazev_spisu = trim(substr($line, 0, $pos));
            $s = trim(substr($line, $pos + 1));
            if ($s{0} == '"') {
                $s = trim($s, '"');
                $s = str_replace('""', '"', $s);
            }
            $result[] = array($nazev_spisu, $s);
        }
        
        return $result;
    }
*/

}
