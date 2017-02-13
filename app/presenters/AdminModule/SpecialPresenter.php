<?php

/**
 * AdminModule/presenters/SpecialPresenter.php
 *
 * Stub Trida pro provadeni specialnich jednorazovych pozadavku.
 * Uzivatel musi mit roli administratora.
 */
class Admin_SpecialPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return $this->user->isInRole('admin');
    }

    protected function myError($msg)
    {
        echo "Došlo k následující chybě: $msg.";
        die;
    }

    /**
     * Import spisovych znaku
     * TODO: Přepsat pro volání třídy spisů namísto přímého přístupu do databáze
     */
    public function actionImportSpisy()
    {
        // Ochrana před spuštěním nesprávným zákazníkem na sdíleném hostingu.
        // Nicméně v tom případě by aplikace oznámila, že chybí soubor s daty k importu.
        if (KLIENT != 'kod_klienta')
            $this->myError('Funkce je platná pouze pro specifického klienta.');

        $data = $this->parseSpisy();

        try {
            dibi::begin();

            /* Takto už import nikdy neřešit! Příště přepsat a volat metody modelu
             * 
            $parent_id = 3911;
            $parent_sequence_string = dibi::query("SELECT sekvence_string FROM :PREFIX:spis WHERE id = $parent_id")->fetchSingle();
//            $counter = 0;

            foreach ($data as $spis) {
                dibi::query("INSERT INTO :PREFIX:spis (parent_id, nazev, popis, date_created, user_created) VALUES (%i, %s, LEFT(%s, 199), NOW(), %i)",
                        $parent_id, $spis[0], $spis[1], $this->user->id);

                $id = dibi::getConnection()->getInsertId();

                dibi::query("UPDATE :PREFIX:spis SET sekvence = %s, sekvence_string = %s WHERE [id] = %i",
                        "1.$parent_id.$id", "$parent_sequence_string#{$spis[0]}.$id", $id);

                // if (++$counter >= 10)
                // break;
            }
            */
            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }

        echo "Úspěšně dokončeno.";
        die;
    }

    /** Tuto metodu je potřeba vždy přepsat podle formátu dat zákazníka
     * 
     * @return array
     */
    protected function parseSpisy()
    {
        $result = array();

        /* Vygeneruj spisy pro testování
        for ($i = 1; $i <= 1000; $i++)
            $result[] = ["Spis H-$i", 'popis'];
        
        return $result; */
        
        $a = file(CLIENT_DIR . "/spisy.csv");
        if (!$a)
            $this->myError('Nepodařilo se přečíst soubor s daty pro import.');

        foreach ($a as $line) {
            $pos = mb_strpos($line, ';');
            if ($pos === false)
                continue;
            $nazev_spisu = trim(mb_substr($line, 0, $pos));
            $s = trim(mb_substr($line, $pos + 1));
            if ($s{0} == '"') {
                $s = trim($s, '"');
                $s = str_replace('""', '"', $s);
            }
            $result[] = array($nazev_spisu, $s);
        }

        return $result;
    }

    /**
     * Import spisovych znaku
     */
    public function actionImportSpisoveZnaky()
    {
        $data = $this->parseSpisoveZnaky();

        try {
            // Nelze použít transakce! Transakce už je použita pro vytvoření jednotlivého znaku.

            // Pouze pro testování:
            // $counter = 0;
            $model = new SpisovyZnak;

            foreach ($data as $sz) {
                $sz2 = ['nazev' => $sz['spis_znak'], 'popis' => $sz['title'],
                    'skartacni_znak' => $sz['skart_znak'], 'skartacni_lhuta' => $sz['skart_lhuta'],
                    'parent_id' => null];
                $model->vytvorit($sz2);

                // if (++$counter >= 5)
                //    break;
            }
        } catch (Exception $e) {
            throw $e;
        }

        echo "Úspěšně dokončeno.";
        die;
    }

    /** Tuto metodu je potřeba vždy přepsat podle formátu dat zákazníka
     * 
     * @return array
     */
    protected function parseSpisoveZnaky()
    {
        $result = array();

        $path_name = CLIENT_DIR . "/spisznak.csv";
        $a = file($path_name);
        if (!$a)
            $this->myError("Nepodařilo se přečíst soubor s daty pro import: $path_name");

        foreach ($a as $line) {
            $line = trim($line, "\n\r");
            $fields = explode("\t", $line);
            $spisznak = $fields[0];
            $title = $fields[1];

            $code = $fields[2];
            $skart_znak = null;
            $skart_lhuta = null;
            if ($code) {
                $skart_znak = $code{0};
                if (!in_array($skart_znak, ['A', 'S', 'V']))
                    $this->myError("Neplatný skartační znak v záznamu: $spisznak  $title");
                $skart_lhuta = substr($code, ($code{1} == ' ') ? 2 : 1);
                if (!is_numeric($skart_lhuta))
                    $this->myError("Neplatná skartační lhůta v záznamu: $spisznak  $title");
            }

            $result[] = ['spis_znak' => $spisznak, 'title' => $title,
                'skart_znak' => $skart_znak, 'skart_lhuta' => $skart_lhuta];
        }

        return $result;
    }

}
