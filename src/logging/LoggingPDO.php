<?php

namespace mheinzerling\commons\database\logging;

/**
 * Based on http://www.coderholic.com/php-database-query-logging-with-pdo/
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 */
class LoggingPDO extends \PDO
{
    /**
     * @var string[]
     */
    private $log = [];

    public function __construct(string $dsn, string $username = null, $passwd = null, $options = null)
    {
        parent::__construct($dsn, $username, $passwd, $options);
    }


    /**
     * @param string $query
     * @return \PDOStatement|bool
     */
    public function query(string $query)
    {
        $start = microtime(true);
        $result = parent::query($query);
        $time = microtime(true) - $start;
        $this->log("Q", $query, $time, $result !== false);
        return $result;
    }

    /**
     * @param string $query
     * @return int
     */
    public function exec($query)
    {
        $start = microtime(true);
        $result = parent::exec($query);
        $time = microtime(true) - $start;
        $this->log("E", $query, $time, $result);
        return $result;
    }

    /**
     * @param string $statement
     * @param array $options
     * @return LoggingPDOStatement
     */

    public function prepare($statement, $options = null)
    {
        return new LoggingPDOStatement($this, parent::prepare($statement)); //signature broken?
    }

    /**
     * @param string $type
     * @param string $query
     * @param int $timeInMillies
     * @param int|bool $result
     */
    public function log(string $type, string $query, int $timeInMillies, $result)
    {
        $this->log[] = ['query' => "[" . $type . "] " . $query, 'time' => round($timeInMillies, 6), 'result' => $result];
    }

    public function getLog() :string
    {
        $totalTime = 0;
        $result = '';
        foreach ($this->log as $entry) {
            $totalTime += $entry['time'];
            $result .= str_pad($entry['time'], 10, " ", STR_PAD_LEFT) . ' - [' . $entry['result'] . '] - ' . $entry['query'] . "\n";
        }
        $result .= str_pad($totalTime, 10, " ", STR_PAD_LEFT) . ' - ' . count($this->log) . "\n";
        return $result;
    }

    /**
     * @return void
     */
    public function clearLog() //:void
    {
        $this->log = [];
    }

    public function numberOfQueries() :int
    {
        return count($this->log);
    }

}