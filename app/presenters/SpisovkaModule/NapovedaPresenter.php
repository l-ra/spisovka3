<?php

class Spisovka_NapovedaPresenter extends BasePresenter
{

    protected function isUserAllowed()
    {
        return true;
    }

    public function renderDefault($param1, $param2, $param3)
    {
        if ($param1 == "obsah") {
            $this->template->helpFile = dirname(APP_DIR) . "/help/hlavni.phtml";
            $this->template->helpContents = $this->napovedy();
            $help_name = array();
            include dirname(APP_DIR) . "/help/help_name.php";
            $this->template->helpTitles = $help_name;
            $this->setView('obsah');
        } else {
            $this->template->helpFile = dirname(APP_DIR) . "/help/" . ucfirst($param1) . "Module/" . ucfirst($param2) . "/" . $param3 . ".phtml";
            if (!is_file($this->template->helpFile))
                $this->setView('neexistuje');
        }
    }

    private function napovedy()
    {
        $dirs = array();

        foreach (Nette\Utils\Finder::findFiles('*.phtml')->from(dirname(APP_DIR) . "/help/") as $file) {

            $file = str_replace(dirname(APP_DIR) . "/help", "", $file);
            if (strpos($file, "/") !== false) {
                $file_part = explode("/", $file);
            } else {
                $file_part = explode("\\", $file);
            }
            if ($file_part[1] == "@layout.phtml" || $file_part[1] == "hlavni.phtml" || $file_part[1] == "Default") {
                continue;
            }
            unset($file_part[0]);

            $tmp = new stdClass();
            $tmp->path = $file;
            $tmp->name = implode(" - ", $file_part);

            $param1 = strtolower(str_replace("Module", "", $file_part[1]));
            $param2 = strtolower($file_part[2]);
            $param3 = strtolower(str_replace(".phtml", "", $file_part[3]));
            $tmp->url = "napoveda/$param1/$param2/$param3";
            $tmp->code = "$param1/$param2/$param3";

            $dirs[$param1][$param2][$param3] = $tmp;
        }

        return $dirs;

        //print_r($dirs); exit;
    }

}
