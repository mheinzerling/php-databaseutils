<?php

namespace mheinzerling\commons\database;


class LoggingPDOStatement
{
    private $statement;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function execute(array $input_parameters = null)
    {
        $start = microtime(true);
        try {
            $result = $this->statement->execute($input_parameters);
        } catch (\PDOException $e) {
            echo $this->statement->queryString;
            throw $e;
        }
        $time = microtime(true) - $start;
        LoggingPDO::$log[] = array('query' => '[P] ' . $this->statement->queryString, 'time' => round($time, 6));
        return $result;
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }

}