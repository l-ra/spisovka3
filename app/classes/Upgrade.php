<?php

namespace Spisovka;

use Nette;

final class Upgrade
{

    const SETTINGS_TASKS = 'upgrade_finished_tasks';
    const SETTINGS_NEEDED = 'upgrade_needed';

    static $tasks = [
        'EmailMailbox' => 'změna souborů s e-maily v e-podatelně do mailbox formátu',
        'DMenvelopes' => 'přenos obálek datových zpráv ze souborů do databáze',
        'DMdeliveryTime' => 'oprava data doručení příchozích datových zpráv v seznamu',
        'MimeType' => 'nové vygenerování informací o typu souborů u dokumentů',
    ];
    private $storage;

    public function __construct(Storage_Basic $storage)
    {
        $this->storage = $storage;
    }

    public function check()
    {
        $need = Settings::get(self::SETTINGS_NEEDED, false);
        if ($need)
            $this->perform();
    }

    public function perform()
    {
        $lock = new Lock('upgrade');
        $lock->delete_file = true;

        ob_end_clean();
        echo "<pre>Provádím aktualizaci aplikace\n";
        try {
            Settings::reload();
            $done = Settings::get(self::SETTINGS_TASKS);
            $done = $done ? explode(',', $done) : [];

            foreach (self::$tasks as $name => $desc) {
                echo "\nKrok $name - $desc\n";
                flush();
                if (in_array($name, $done)) {
                    echo "již proveden\n";
                    continue;
                }

                echo "provádím: ";
                flush();
                $function = 'upgrade' . $name;
                $this->$function();

                $done[] = $name;
                Settings::set(self::SETTINGS_TASKS, implode(',', $done));
                echo "OK\n";
            }

            Settings::set(self::SETTINGS_NEEDED, false);
        } catch (\Exception $e) {
            echo "Chyba\n";
            throw $e;
        }

        echo "\n" . 'Hotovo. <a href="#" onclick="window.location.reload(true);">Klikněte</a> pro spuštění aplikace.';
        die;
    }

    private function upgradeEmailMailbox()
    {
        $storage = $this->storage;

        $res = dibi::query("SELECT [file_id] FROM [epodatelna] WHERE [typ] = 'E' AND [odchozi] = 0");
        $processed = 0;
        foreach ($res as $row)
            if ($row->file_id) {
                $file = new FileRecord($row->file_id);
                $filename = $storage->getFilePath($file);

                $handle = fopen($filename, 'r+b');
                if (!$handle)
                    continue;
                $start = fread($handle, 5);
                if ($start == 'From ') {
                    // already converted
                    fclose($handle);
                    continue;
                }

                rewind($handle);
                $data = fread($handle, filesize($filename));
                rewind($handle);
                fwrite($handle, "From unknown  Sat Jan  1 00:00:00 2000\r\n");
                fwrite($handle, $data);
                fclose($handle);
                $processed++;
            }

        dump(__METHOD__ . "() - $processed e-mails converted");
    }

    private function upgradeDMenvelopes()
    {
        $storage = $this->storage;
        /* @var $storage Storage_Basic */
        $messages = IsdsMessage::getAll();

        foreach ($messages as $message) {
            $filename = $message->getIsdsFile($storage);
            $contents = file_get_contents($filename);
            if (!$contents)
                continue;
            $data = unserialize($contents);
            if ($data === false)
                continue;

            // sjednoť formát dat a odstraň přiložené písemnosti
            if ($message->odchozi) {
                unset($data->dmOrdinal);
                $file_id = $message->file_id;
                $message->file_id = null; // příprava pro smazání
            } else {
                $dm = $data->dmDm;
                unset($dm->dmFiles);
                $dm->dmMessageStatus = $data->dmMessageStatus;
                $dm->dmAttachmentSize = $data->dmAttachmentSize;
                $dm->dmDeliveryTime = $data->dmDeliveryTime;
                $dm->dmAcceptanceTime = $data->dmAcceptanceTime;
                $data = $dm;
            }

            $message->isds_envelope = serialize($data);
            $message->save();

            // smaž nyní zbytečný .bsr soubor
            if ($message->odchozi)
                $storage->remove(new FileRecord($file_id));
        }
    }

    /**
     * Závisí na aktualizačním kroku DMenvelopes, který musí být proveden než se zavolá
     * tento krok. Proto není možné tento kód přesunout do app/aktualizace/scripts.php.
     */
    private function upgradeDMdeliveryTime()
    {
        $messages = IsdsMessage::getAll(['where' => "[typ] = 'I' AND NOT [odchozi]"]);

        foreach ($messages as $message) {
            $envelope = unserialize($message->isds_envelope);
            $date = new \DateTime($envelope->dmDeliveryTime);
            $message->doruceno_dne = $date;
            $message->save();
        }
    }

    private function upgradeMimeType()
    {
        $files = FileRecord::getAll();
        foreach ($files as $file) {
            $path = $this->storage->getFilePath($file);
            $type = FileModel::getMimeType($path);
            $file->mime_type = $type;
            $file->save();
        }
    }

}
