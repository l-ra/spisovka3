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
        'DMdeliveryTime' => 'oprava data doručení příchozích datových zpráv v seznamu'
    ];

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

        try {
            Settings::reload();
            $done = Settings::get(self::SETTINGS_TASKS);
            $done = $done ? explode(',', $done) : [];

            foreach (array_keys(self::$tasks) as $name) {
                if (in_array($name, $done))
                    continue;

                $function = 'upgrade' . $name;
                $this->$function();

                $done[] = $name;
                Settings::set(self::SETTINGS_TASKS, implode(',', $done));
            }

            Settings::set(self::SETTINGS_NEEDED, false);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function upgradeEmailMailbox()
    {
        $storage = Nette\Environment::getService('storage');
        $file_model = new FileModel();

        $res = dibi::query("SELECT [file_id] FROM [:PREFIX:epodatelna] WHERE [typ] = 'E' AND [odchozi] = 0");
        $processed = 0;
        foreach ($res as $row)
            if ($row->file_id) {
                $file = $file_model->getInfo($row->file_id);
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

    public function upgradeDMenvelopes()
    {
        $storage = Nette\Environment::getService('storage');
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
                $storage->remove($file_id);
        }
    }

    public function upgradeDMdeliveryTime()
    {
        $messages = IsdsMessage::getAll(['where' => "[typ] = 'I' AND NOT [odchozi]"]);

        foreach ($messages as $message) {
            $envelope = unserialize($message->isds_envelope);
            $date = new \DateTime($envelope->dmDeliveryTime);
            $message->doruceno_dne = $date;
            $message->save();            
        }
    }
}
