<?php

class ShutdownHandler
{

    public static function _handler()
    {
        $error = error_get_last();
        if (strpos($error['message'], 'aximum execution time') !== false) {
            echo 'Zpracování požadavku bylo přerušeno z důvodu překročení povoleného časového limitu.';
        }
    }

}

?>