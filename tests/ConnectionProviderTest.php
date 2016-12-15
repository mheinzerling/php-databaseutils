<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database;


class ConnectionProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetReset()
    {
        try {
            ConnectionProvider::get();
        } catch (DatabaseException $e) {
            static::assertEquals("No default connection defined. Call ConnectionProvider::set", $e->getMessage());
        }

        $pdo = new TestDatabaseConnection();
        ConnectionProvider::set($pdo);
        static::assertEquals($pdo, ConnectionProvider::get());

        ConnectionProvider::reset();
        try {
            ConnectionProvider::get();
        } catch (DatabaseException $e) {
            static::assertEquals("No default connection defined. Call ConnectionProvider::set", $e->getMessage());
        }
    }
}
