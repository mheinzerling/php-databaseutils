<?php

namespace mheinzerling\commons\database;


class LoggingPDOStatement
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function execute(array $input_parameters = null):bool
    {
        $start = microtime(true);
        try {
            $result = $this->statement->execute($input_parameters);
        } catch (\PDOException $e) {
            echo $this->statement->queryString;
            throw $e;
        }
        $time = microtime(true) - $start;
        LoggingPDO::$log[] = ['query' => '[P] ' . $this->statement->queryString, 'time' => round($time, 6)];
        return $result;
    }

    public function __call(string $function_name, array $parameters = null)
    {
        return call_user_func_array([$this->statement, $function_name], $parameters);
    }

}