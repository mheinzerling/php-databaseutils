<?php

namespace mheinzerling\commons\database;


class DatabaseUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testInsertAssocFail()
    {
        $pdo = new TestDatabaseConnection();
        $pdo->exec("CREATE TABLE `foo` (`bar` INT(11) PRIMARY KEY ,`goo` VARCHAR(11))");
        $pdo->clearLog();
        static::assertEquals(1, DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz', 'bar' => 1]));

        $expected = "X - [1] - [P] INSERT INTO `foo`(`goo`,`bar`) VALUES (:goo,:bar)
X - 1
";
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
        try {
            static::assertFalse(DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz2', 'bar' => 1]));
        } catch (\PDOException $e) {
            static::assertEquals("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1' for key 'PRIMARY'", $e->getMessage());
        }

        static::assertEquals([["bar" => "1", "goo" => "xyz"]], $pdo->query("SELECT * FROM `foo`")->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function testInsertAssocUpdate()
    {
        $pdo = new TestDatabaseConnection();
        $pdo->exec("CREATE TABLE `foo` (`bar` INT(11) PRIMARY KEY ,`goo` VARCHAR(11))");
        $pdo->clearLog();
        static::assertEquals(1, DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz', 'bar' => 1], DatabaseUtils::DUPLICATE_UPDATE));
        $expected = "X - [1] - [P] INSERT INTO `foo`(`goo`,`bar`) VALUES (:goo,:bar) ON DUPLICATE KEY UPDATE `goo`=VALUES(`goo`), `bar`=VALUES(`bar`)
X - 1
";
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
        static::assertEquals(2, DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz2', 'bar' => 1], DatabaseUtils::DUPLICATE_UPDATE));
        static::assertEquals([["bar" => "1", "goo" => "xyz2"]], $pdo->query("SELECT * FROM `foo`")->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function testInsertAssocIgnore()
    {
        $pdo = new TestDatabaseConnection();
        $pdo->exec("CREATE TABLE `foo` (`bar` INT(11) PRIMARY KEY ,`goo` VARCHAR(11))");
        $pdo->clearLog();
        static::assertEquals(1, DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz', 'bar' => 1], DatabaseUtils::DUPLICATE_IGNORE));
        $expected = "X - [1] - [P] INSERT IGNORE INTO `foo`(`goo`,`bar`) VALUES (:goo,:bar)
X - 1
";
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
        static::assertEquals(0, DatabaseUtils::insertAssoc($pdo, "foo", ['goo' => 'xyz2', 'bar' => 1], DatabaseUtils::DUPLICATE_IGNORE));
        static::assertEquals([["bar" => "1", "goo" => "xyz"]], $pdo->query("SELECT * FROM `foo`")->fetchAll(\PDO::FETCH_ASSOC));
    }


    public function testInsertMultiple()
    {
        $pdo = new TestDatabaseConnection();
        $pdo->exec("CREATE TABLE `foo` (`a` INT(11) PRIMARY KEY ,`b` VARCHAR(30),`c` VARCHAR(30))");
        $pdo->clearLog();
        $data = [];
        $data[] = ["a" => 1, "b" => "3", "c" => 3.756];
        $data[] = ["b" => "3", "a" => 7, "c" => null];
        $data[] = ["a" => 5, "c" => new \DateTime("2016-01-17"), "b" => "asdf"];

        static::assertEquals(3, DatabaseUtils::insertMultiple($pdo, "foo", ["a", "b", "c"], $data, DatabaseUtils::DUPLICATE_IGNORE));
        $expected = "X - [3] - [P] INSERT IGNORE INTO `foo`(`a`,`b`,`c`) VALUES (?, ?, ?), (?, ?, ?), (?, ?, ?)
X - 1
";
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
        static::assertEquals([["a" => "1", "b" => "3", "c" => "3.756"],
            ["a" => "5", "b" => "asdf", "c" => "2016-01-17 00:00:00"],
            ["a" => "7", "b" => "3", "c" => null]], $pdo->query("SELECT * FROM `foo` ORDER BY `a`")->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function testImportDump()
    {
        $pdo = new TestDatabaseConnection();
        static::assertEquals(true, DatabaseUtils::importDump($pdo, __DIR__ . "/../resources/employees.sql"));
        static::assertEquals([['dept_no', 'char(4)', 'NO', 'PRI', null, ''], ['dept_name', 'varchar(40)', 'NO', 'UNI', null, '']], $pdo->tableStructure("departments"));
    }
}
