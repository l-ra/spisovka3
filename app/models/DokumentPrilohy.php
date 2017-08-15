<?php

namespace Spisovka;

use Nette;

class DokumentPrilohy extends BaseModel
{

    protected $name = 'dokument_to_file';
    protected $tb_file = 'file';

    // Pouzito v sestavach
    public static function pocet_priloh(array $dokument_ids)
    {
        $result = dibi::query("SELECT dokument_id, COUNT(*) AS pocet FROM [:PREFIX:dokument_to_file] WHERE dokument_id IN %in GROUP BY dokument_id",
                        $dokument_ids)->fetchPairs('dokument_id', 'pocet');

        return count($result) ? $result : array();
    }

    public function pripojit($dokument_id, $file_id)
    {
        $row = array();
        $row['dokument_id'] = $dokument_id;
        $row['file_id'] = $file_id;

        return $this->insert($row);
    }

    public function odebrat($dokument_id, $file_id)
    {
        $param = [['file_id = %i', $file_id], ['dokument_id = %i', $dokument_id]];
        return $this->delete($param);
    }

    public static function maxVelikostUploadu($lidsky_vystup = false)
    {

        function _getSize($str)
        {
            $c = substr($str, -1);
            $n = substr($str, 0, -1);

            switch (strtoupper($c)) {
                case 'K':
                    return $n * pow(2, 10);
                case 'M':
                    return $n * pow(2, 20);
                case 'G':
                    return $n * pow(2, 30);
            }

            return (int) $str;
        }

        if (function_exists("ini_get")) {
            $s1 = ini_get("upload_max_filesize");
            $n1 = _getSize($s1);
            $s2 = ini_get("post_max_size");
            $n2 = _getSize($s2);
            $min = min($n1, $n2);

            if (!$lidsky_vystup)
                return $min;
            if ($min % pow(2, 20) == 0)
                return $min / pow(2, 20) . " MB";
            return $min / 1024 . " kB";
        }

        return $lidsky_vystup ? "nepodařilo se zjistit" : false;
    }

}
