<?php

namespace Spisovka;

use Nette;

/**
 * AdminModule/presenters/ExportPresenter.php
 *
 * Presenter pro export dat z aplikace
 * Vytvořeno na míru pro jednoho zákazníka
 */
class Admin_ExportPresenter extends BasePresenter
{

    private $file;
    protected $users;

    protected function isUserAllowed()
    {
        $user = $this->user;
        return $user->isInRole('admin') || $user->isInRole('superadmin');
    }

    protected function _error($msg)
    {
        echo "Došlo k následující chybě: $msg.";
        die;
    }

    protected function warning($msg)
    {
        echo "Varování: $msg.<br/>";
    }

    protected function openExportFile()
    {
        $this->file = fopen(TEMP_DIR . "/export_dat.csv", "w");
        if (!$this->file)
            $this->_error("Nepodařilo se otevřít soubor pro export.");
    }

    protected function closeExportFile()
    {
        if ($this->file)
            fclose($this->file);
    }

    protected function selectDocuments()
    {
        $a = array();
        $result = dibi::query("SELECT id FROM [:PREFIX:dokument] WHERE stav > 0 ORDER BY id");

        foreach ($result as $row)
            $a[] = (int) $row->id;

        return $a;
    }

    protected function writeHeader()
    {
        $header = "Věc\tStav dokumentu\tTyp dokumentu\tJID\tČíslo jednací\tNázev spisu\tSpisový znak\tSkartační znak\tSkartační lhůta\tRok skartace\tISDS ID odesilatele\tOdesilatel\tAdresát\tUživatel\tPřihlašovací jméno\tDatum doručení/vzniku\tDatum vyřízení\tOdesláno\tSoubory\n";

        fwrite($this->file, $header);
    }

    protected function exportDocument($d)
    {
        $SEP = '&tab%';

        $idsd_odesilatel = '';  // TODO      
        if ($d->epod_is_isds) { // datova zprava
            // $idsd_odesilatel = ...
        }

        // subjekty
        $odesilatel = '';
        $adresat = '';
        if (isset($d->subjekty))
            foreach ($d->subjekty as $key => $subjekt) {
                if ($subjekt->rezim_subjektu == 'O' || $subjekt->rezim_subjektu == 'AO')
                    $odesilatel .= "$key,";
                if ($subjekt->rezim_subjektu == 'A' || $subjekt->rezim_subjektu == 'AO')
                    $adresat .= "$key,";
            }
        $odesilatel = rtrim($odesilatel, ',');
        $adresat = rtrim($adresat, ',');

        $soubory = '';
        $storage = $this->getStorage();
        if (!empty($d->prilohy))
            foreach ($d->prilohy as $soubor) {
                $path = $storage->getFilePath($soubor);
                $soubory .= "$path,";
            }
        $soubory = rtrim($soubory, ',');

        $odeslani = '';
        if (!empty($d->odeslani))
            foreach ($d->odeslani as $o) {
                $odeslani .= "{$o->zpusob_odeslani_nazev},";
            }
            $odeslani = rtrim($odeslani, ',');

        $nazev_spisu = isset($d->spis) ? $d->spis->nazev : '';

        $uzivatel = Person::fromUserId($d->owner_user_id)->displayName();
        $user_id = $d->owner_user_id;
        $login = $user_id ? $this->users[$user_id]->username : '';

        $skartacni_rok = $d->skartacni_rok ? $d->skartacni_rok : '';

        $line = "{$d->nazev}$SEP{$d->stav_dokumentu}$SEP{$d->typ_dokumentu->nazev}$SEP{$d->jid}$SEP{$d->cislo_jednaci}$SEP$nazev_spisu$SEP{$d->spisovy_znak}$SEP{$d->skartacni_znak}$SEP{$d->skartacni_lhuta}$SEP{$skartacni_rok}$SEP$idsd_odesilatel$SEP$odesilatel$SEP$adresat$SEP$uzivatel$SEP$login$SEP{$d->datum_vzniku}$SEP{$d->datum_vyrizeni}$SEP$odeslani$SEP$soubory";

        if (strpos($line, "\t") !== false)
            $line = str_replace("\t", "  ", $line);

        $line = str_replace($SEP, "\t", $line);

        fwrite($this->file, $line . "\n");
    }

    protected function htmlHeader()
    {
        echo <<<EOJ
            <head>
            <meta charset="utf-8">
            <script type='text/javascript'>
            stop_scrolling = false;
            
            function scrollTimer() {
                window.scrollBy(0, 500);
                if (stop_scrolling != true)
                    setTimeout(scrollTimer, 2000);
            }
            
            setTimeout(scrollTimer, 2000);
            </script>
            </head>
            <body onload="stop_scrolling = true">
            <pre>
EOJ;
    }

    public function renderDefault()
    {
        ob_end_clean();
        $this->htmlHeader();

        if (!in_array(KLIENT, array('klient', 'techagcr3')))
            $this->_error('Funkce je platná pouze pro specifického klienta');

        $doc_ids = $this->selectDocuments();
        if (!$doc_ids)
            $this->_error('Nepodařilo se vybrat dokumenty k exportu');

        $this->openExportFile();

        $this->writeHeader();

        // $model = new UserModel;
        // $this->users = $model->select()->fetchAssoc('id');
        $this->users = dibi::query('SELECT * FROM :PREFIX:user')->fetchAssoc('id');
        echo "Exportuji dokumenty...\n\n";
        flush();
        set_time_limit(0);

        $model = new Dokument;

        $i = 0;
        $time1 = microtime(true);
        $doc_count = count($doc_ids);

        foreach ($doc_ids as $doc_id) {

            $i++;
            $time2 = microtime(true);
            if ($time2 - $time1 > 1.0 || $i == $doc_count) {
                $time1 = $time2;
                printf("%3u%% [%u/%u]\n", floor($i / $doc_count * 100), $i, $doc_count);
                // printf("%u\n", memory_get_usage(true));
                flush();
            }

            // usleep(5000);

            $doc = $model->getInfo($doc_id, 'subjekty,soubory,odeslani');

            if (!$doc) {
                $this->warning("Nepodařilo se načíst dokument ID $doc_id");
                continue;
            }

            $this->exportDocument($doc);
            unset($doc); // uvolni pamet (objekt by byl ale stejne uvolnen pri pristim pruchodu cyklem ;-)
        }

        $this->closeExportFile();

        echo "\n\nÚspěšně dokončeno.";
        die;
    }

}
