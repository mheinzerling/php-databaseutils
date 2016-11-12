<?php

namespace mheinzerling\commons\database;


class TestDatabaseConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $pdo = new TestDatabaseConnection();
        static::assertStringStartsWith("test_", $pdo->query('select database()')->fetchColumn());

        $pdo->deleteDatabase();
        static::assertEquals(null, $pdo->query('select database()')->fetchColumn());
    }

    public function testConnectionInvalidDSNViaEnvironment()
    {
        try {
            putenv(TestDatabaseConnection::DSN . "=invalid");
            new TestDatabaseConnection();
        } catch (\PDOException $e) {
            static::assertEquals("invalid data source name", $e->getMessage());
        } finally {
            putenv(TestDatabaseConnection::DSN . "=mysql:host=127.0.0.1");
        }
    }

    public function testTableStructure()
    {
        $pdo = new TestDatabaseConnection();
        $pdo->query("CREATE TABLE `credential`(`provider` VARCHAR(255),`uid`  VARCHAR(255),`user` INT(11));");
        static::assertEquals([
            ['provider', 'varchar(255)', 'YES', '', null, ''],
            ['uid', 'varchar(255)', 'YES', '', null, ''],
            ['user', 'int(11)', 'YES', '', null, '']], $pdo->tableStructure("credential"));

    }

}
