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
    public static function getConnection() :\PDO
    {
        if (self::$connection == null) throw new \Exception("No default connection defined. Call PersistenceManager::setConnection"); //TODO
        return self::$connection;
    }

    /**
     * @param \PDO $connection
     * @return void
     */
    public static function setConnection(\PDO $connection)//:void
    {
        self::$connection = $connection;
        //TODO listener
    }

    /**
     * @return void
     */
    public static function resetConnection() //:void
    {
        self::$connection = null;
        //TODO listener
    }
}