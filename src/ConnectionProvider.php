<?php
declare(strict_types = 1);

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
     * @throws DatabaseException
     */
    public static function get(): \PDO
    {
        if (self::$connection == null) throw new DatabaseException("No default connection defined. Call ConnectionProvider::set");
        return self::$connection;
    }


    public static function set(\PDO $connection): void
    {
        self::$connection = $connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}