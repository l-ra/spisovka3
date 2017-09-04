<?php

namespace Spisovka;

/**
 * Description of LatteFilters
 *
 * @author Pavel Laštovička
 */
class LatteFilters
{

    static public function register($template)
    {
        $filters = ['decPoint', 'edate', 'edatetime', 'html2br'];
        foreach ($filters as $filter) {
            $template->addFilter($filter, [__CLASS__, $filter]);
        }
    }

    /** Filtr pro zobrazení čísel s desetinnou čárkou
     */
    static public function decPoint($s)
    {
        return str_replace('.', ',', $s);
    }

    /** Filtr odstraní vybrané značky, zbytek HTML projde neošetřen
      Použito pro zobrazení HTML e-mailu
     */
    static public function html2br($string)
    {
        if (strpos($string, "&lt;") !== false) {
            $string = html_entity_decode($string);
        }

        $string = preg_replace('#<body.*?>#i', "", $string);
        $string = preg_replace('#<\!doctype.*?>#i', "", $string);
        $string = preg_replace('#</body.*?>#i', "", $string);
        $string = preg_replace('#<html.*?>#i', "", $string);
        $string = preg_replace('#<script.*?>.*?</script>#is', "[javascript blokováno!]",
                $string);
        $string = preg_replace('#<head.*?>.*?</head>#is', "", $string);
        $string = preg_replace('#<iframe.*?>#i', "[iframe blokováno!]", $string);
        $string = preg_replace('#</iframe>#i', "", $string);
        $string = preg_replace('#src=".*?"#i', "[externí zdroj blokováno!]", $string);

        return $string;
    }

    /**  Filtr pro vlastní formátování datumu, příp. i času
     */
    static public function edate($string, $format = null)
    {
        if (empty($string))
            return "";
        if ($string == "0000-00-00 00:00:00")
            return "";
        if ($string == "0000-00-00")
            return "";
        if (is_numeric($string)) {
            return date($format == null ? 'j.n.Y' : $format, $string);
        }
        try {
            $datetime = new \DateTime($string);
        } catch (\Exception $e) {
            // datum je neplatné (možná $string vůbec není datum), tak vrať argument
            $e->getMessage();
            return $string;
        }

        return $datetime->format($format == null ? 'j.n.Y' : $format);
    }

    static public function edatetime($string)
    {
        return self::edate($string, 'j.n.Y G:i:s');
    }

}
