<?php

namespace mheinzerling\commons\database;


//TODO add listener for connection changes
class ConnectionProvider
{
    /**
     * @var \PDO
     */
    private static $connection;

    /**
     * @return \PDO
     * @throws \Exception
     */
    public static function get() :\PDO
    {
        if (self::$connection == null) throw new DatabaseException("No default connection defined. Call ConnectionProvider::set");
        return self::$connection;
    }

    /**
     * @param \PDO $connection
     * @return void
     */
    public static function set(\PDO $connection)//:void
    {
        self::$connection = $connection;
    }

    /**
     * @return void
     */
    public static function reset() //:void
    {
        self::$connection = null;
    }
}