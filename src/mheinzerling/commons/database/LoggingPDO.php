<?php

namespace mheinzerling\commons\database;

/**
 * Based on http://www.coderholic.com/php-database-query-logging-with-pdo/
 * @package mheinzerling\commons\database
 */
class LoggingPDO extends \PDO
{
    public static $log = array();

    public function __construct($dsn, $username = null, $passwd = null, $options = null)
    {
        parent::__construct($dsn, $username, $passwd, $options);
    }


    public function query($query)
    {
        $start = microtime(true);
        $result = parent::query($query);
        $time = microtime(true) - $start;
        LoggingPDO::$log[] = array('query' => "[Q] " . $query, 'time' => round($time, 6));
        return $result;
    }

    public function exec($query)
    {
        $start = microtime(true);
        $result = parent::exec($query);
        $time = microtime(true) - $start;
        LoggingPDO::$log[] = array('query' => "[E] " . $query, 'time' => round($time, 6));
        return $result;
    }

    /**
     * @inheritdoc
     * @return LoggingPDOStatement
     */
    public function prepare($statement, $options = NULL)
    {
        return new LoggingPDOStatement(parent::prepare($statement));
    }

    public static function getLog()
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

    public static function clearLog()
    {
        self::$log = array();
    }

    public static function numberOfQueries()
    {
        return count(self::$log);
    }

}