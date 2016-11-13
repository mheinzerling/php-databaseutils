<?php

namespace mheinzerling\commons\database\logging;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 */
class LoggingPDOStatement extends \PDOStatement
{
    /**
     * @var LoggingPDO
     */
    private $pdo;
    /**
     * @var \PDOStatement
     */
    private $statement;

    public function __construct(LoggingPDO $pdo, \PDOStatement $statement)
    {
        $this->pdo = $pdo;
        $this->statement = $statement;
    }

    public function execute($input_parameters = null):bool
    {
        $start = microtime(true);
        try {
            $result = $this->statement->execute($input_parameters);
        } catch (\PDOException $e) {
            $time = microtime(true) - $start;
            ob_start();
            $this->debugDumpParams();
            $this->pdo->log("X", ob_get_clean(), $time, 0);
            throw $e;
        }
        $time = microtime(true) - $start;
        $this->pdo->log("P", $this->statement->queryString, $time, $this->rowCount());

        return $result;
    }

    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        return $this->statement->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        return $this->statement->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        return $this->statement->bindValue($parameter, $value, $data_type);
    }

    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    public function columnCount()
    {
        return $this->statement->columnCount();
    }

    public function debugDumpParams()
    {
        $this->statement->debugDumpParams();
    }

    public function errorCode():string
    {
        return $this->statement->errorCode();
    }

    public function errorInfo():array
    {
        return $this->statement->errorInfo();
    }

    public function fetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return $this->statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }

    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = null):array
    {
        //no idea why I neet this hack
        if ($fetch_style == null) return $this->statement->fetchAll();
        if ($fetch_argument == null) return $this->statement->fetchAll($fetch_style);
        if ($ctor_args == null) return $this->statement->fetchAll($fetch_style, $fetch_argument);
        return $this->statement->fetchAll($fetch_style, $fetch_argument, $ctor_args);
    }

    public function fetchColumn($column_number = 0)
    {
        return $this->statement->fetchColumn($column_number);
    }

    public function fetchObject($class_name = null, $ctor_args = null)
    {
        return $this->statement->fetchObject($class_name, $ctor_args);
    }

    public function getAttribute($attribute)
    {
        return $this->statement->getAttribute($attribute);
    }

    public function getColumnMeta($column)
    {
        return $this->statement->getColumnMeta($column);
    }

    public function nextRowset():bool
    {
        return $this->statement->nextRowset();
    }

    public function rowCount():int
    {
        return $this->statement->rowCount();
    }

    public function setAttribute($attribute, $value):bool
    {
        return $this->statement->setAttribute($attribute, $value);
    }

    public function setFetchMode($mode, $param = null):bool
    {
        return $this->statement->setFetchMode($mode, $param);
    }
}