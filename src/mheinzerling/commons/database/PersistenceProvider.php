<?php

namespace mheinzerling\commons\database;


class PersistenceProvider
{
    /**
     * @var \PDO
     */
    private static $connection;

    /**
     * @return \PDO
     * @throws \Exception
     */
    public static function getConnection()
    {
        if (self::$connection == null) throw new \Exception("No default connection defined. Call PersistenceManager::setConnection"); //TODO
        return self::$connection;
    }

    public static function setConnection(\PDO $connection)
    {
        self::$connection = $connection;
        //TODO listener
    }

    public static function resetConnection()
    {
        self::$connection = null;
        //TODO listener
    }
}