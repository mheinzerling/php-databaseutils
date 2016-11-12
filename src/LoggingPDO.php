<?php

namespace mheinzerling\commons\database;

/**
 * Based on http://www.coderholic.com/php-database-query-logging-with-pdo/
 */
class LoggingPDO extends \PDO
{
    /**
     * @var string[]
     */
    public static $log = [];

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
        LoggingPDO::$log[] = ['query' => "[Q] " . $query, 'time' => round($time, 6)];
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
        LoggingPDO::$log[] = ['query' => "[E] " . $query, 'time' => round($time, 6)];
        return $result;
    }

    /**
     * @param string $statement
     * @param array $options
     * @return LoggingPDOStatement
     */

    public function prepare($statement, $options = null)
    {
        return new LoggingPDOStatement(parent::prepare($statement)); //signature broken?
    }

    public static function getLog() :string
    {
        $totalTime = 0;
        $result = '';
        foreach (self::$log as $entry) {
            $totalTime += $entry['time'];
            $result .= str_pad($entry['time'], 10, " ", STR_PAD_LEFT) . ' - ' . $entry['query'] . "\n";
        }
        $result .= str_pad($totalTime, 10, " ", STR_PAD_LEFT) . ' - ' . count(self::$log) . "\n";
        return $result;
    }

    /**
     * @return void
     */
    public static function clearLog() //:void
    {
        self::$log = [];
    }

    public static function numberOfQueries() :int
    {
        return count(self::$log);
    }

}