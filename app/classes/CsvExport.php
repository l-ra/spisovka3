<?php

namespace Spisovka;

/**
 * Knihovna pro export dat
 *
 * @author blue.point Solutions
 * @author Tomas Vancura
 */
class CsvExport
{

    public static function csv($data, $ignore_cols = null, $code = 'UTF-8', $break = '\r\n', $separator
    = ',', $quote = '"')
    {
        if (!$data)
            return null;
        if (count($data) == 0)
            return null;

        $export_data = "";

        // sloupce
        $sloupce = array();
        foreach ($data[0] as $key => $value) {
            if (in_array($key, $ignore_cols))
                continue; // ignorovat sloupce
            $sloupce[] = $key;
        }

        $export_data .= $quote . implode($quote . $separator . $quote, $sloupce) . $quote .
                ( ($break == "\\n") ? "\n" : ($break == "\\r") ? "\r" : ($break == "\\r\\n") ? "\r\n"
                                            : $break);

        foreach ($data as $d) {
            $tmp = array();
            foreach ($sloupce as $sloupec) {

                if (is_object($d)) {
                    $value = $d->$sloupec;
                } else {
                    $value = $d[$sloupec];
                }

                if ($code != "UTF-8") {
                    $tmp[] = $quote . iconv("UTF-8", $code . "//TRANSLIT//IGNORE", $value) . $quote;
                } else {
                    $tmp[] = $quote . $value . $quote;
                }
            }
            $export_data .= implode($separator, $tmp) . ( ($break == "\\n") ? "\n" : ($break == "\\r")
                                        ? "\r" : ($break == "\\r\\n") ? "\r\n" : $break);
            unset($tmp);
        }

        return $export_data;
    }

}
