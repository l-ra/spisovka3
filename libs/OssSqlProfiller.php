<?php

class OssSqlProfiller extends DibiProfiler
{
        
    public function after($ticket, $res = NULL)
    {
        parent::after($ticket, $res);
        
        /*list($connection, $event, $sql) = $this->tickets[$ticket];
        if ($event & self::QUERY) {
            
            try {
                $count = $res instanceof DibiResult ? count($res) : '-';
            } catch (Exception $e) {
                $count = '-';
            }            
            
            echo "<pre>";
            echo "SQL: ". $sql ."\n";
            echo "Pocet: ". $count ."\n";
            echo "Cas: ". sprintf('%0.3f', dibi::$elapsedTime * 1000) ." ms\n";
            echo "Spojeni: ". $connection->getConfig('driver') . '/' . $connection->getConfig('name') ."\n";
            echo "</pre>";
            
        }*/
        
    }
    
    public function exception(DibiDriverException $exception)
    {
        
        parent::exception($exception);

        /*if ((self::EXCEPTION & $this->filter) === 0) return;

        echo "<pre>";
        echo "SQL: ". dibi::$sql ."\n";
        echo "Number: ". $exception->getCode() ."\n";
        echo "Message: ". $exception->getMessage() ."\n";
        echo "</pre>";*/
        
    }
    
    
}