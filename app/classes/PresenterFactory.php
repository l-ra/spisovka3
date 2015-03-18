<?php

class PresenterFactory extends Nette\Application\PresenterFactory
{

    /**
     * Formats presenter class name from its name.
     * @param  string
     * @return string
     * @internal
     */
    public function formatPresenterClass($presenter)
    {
        $s = str_replace(':', '_', $presenter);
        return "{$s}Presenter";
    }

    /**
     * Formats presenter name from class name.
     * @param  string
     * @return string
     * @internal
     */
    public function unformatPresenterClass($class)
    {
        $s = str_replace('_', ':', $class);
        $s = str_replace('Presenter', '', $s);
        return $s;
    }
}
