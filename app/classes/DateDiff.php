<?php

class DateDiff
{

    public $y;
    public $m;
    public $d;
    public $h;
    public $i;
    public $s;
    public $invert;

    public static function diff(DateTime $date1, DateTime $date2 = null, $absolute = false)
    {

        if (is_null($date2)) {
            $date2 = new DateTime();
        }

        if (function_exists('date_diff')) {
            $interval = date_diff($date1, $date2, $absolute);
        } else {
            $DateDiff = new DateDiff();
            $inteval = $DateDiff->date_diff($date1, $date2);
        }

        if (empty($interval)) {
            return 0;
        } else if ($interval->invert) {
            return (-1) * (($interval->d * 86400) + ($interval->h * 3600) + ($interval->m * 60) + $interval->s);
        } else {
            return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->m * 60) + $interval->s;
        }
    }

    public static function add($date1, $interval)
    {

        if (!($date1 instanceof DateTime)) {
            $date1 = new DateTime($date1);
        }

        $y = $date1->format('Y') + $interval;
        $m = $date1->format('n');
        $d = $date1->format('j');
        $h = $date1->format('G');
        $i = $date1->format('i');
        $s = $date1->format('s');

        return mktime($h, $i, $s, $m, $d, $y);
    }

    private function format($format)
    {
        $format = str_replace('%R%y', ($this->invert ? '-' : '+') . $this->y, $format);
        $format = str_replace('%R%m', ($this->invert ? '-' : '+') . $this->m, $format);
        $format = str_replace('%R%d', ($this->invert ? '-' : '+') . $this->d, $format);
        $format = str_replace('%R%h', ($this->invert ? '-' : '+') . $this->h, $format);
        $format = str_replace('%R%i', ($this->invert ? '-' : '+') . $this->i, $format);
        $format = str_replace('%R%s', ($this->invert ? '-' : '+') . $this->s, $format);

        $format = str_replace('%y', $this->y, $format);
        $format = str_replace('%m', $this->m, $format);
        $format = str_replace('%d', $this->d, $format);
        $format = str_replace('%h', $this->h, $format);
        $format = str_replace('%i', $this->i, $format);
        $format = str_replace('%s', $this->s, $format);

        return $format;
    }

    public function date_diff(DateTime $date1, DateTime $date2)
    {

        if ($date1 > $date2) {
            $tmp = $date1;
            $date1 = $date2;
            $date2 = $tmp;
            $this->invert = true;
        }

        $this->y = ((int) $date2->format('Y')) - ((int) $date1->format('Y'));
        $this->m = ((int) $date2->format('n')) - ((int) $date1->format('n'));
        if ($this->m < 0) {
            $this->y -= 1;
            $this->m = $this->m + 12;
        }
        $this->d = ((int) $date2->format('j')) - ((int) $date1->format('j'));
        if ($this->d < 0) {
            $this->m -= 1;
            $this->d = $this->d + ((int) $date1->format('t'));
        }
        $this->h = ((int) $date2->format('G')) - ((int) $date1->format('G'));
        if ($this->h < 0) {
            $this->d -= 1;
            $this->h = $this->h + 24;
        }
        $this->i = ((int) $date2->format('i')) - ((int) $date1->format('i'));
        if ($this->i < 0) {
            $this->h -= 1;
            $this->i = $this->i + 60;
        }
        $this->s = ((int) $date2->format('s')) - ((int) $date1->format('s'));
        if ($this->s < 0) {
            $this->i -= 1;
            $this->s = $this->s + 60;
        }

        return $this;
    }

}
