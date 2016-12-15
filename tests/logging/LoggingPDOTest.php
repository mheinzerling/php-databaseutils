<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\logging;


use mheinzerling\commons\database\TestDatabaseConnection;

class LoggingPDOTest extends \PHPUnit_Framework_TestCase
{
    public function testExecQuery()
    {
        $pdo = new TestDatabaseConnection(); //extends LoggingPDO
        $expected = 'X - [1] - [Q] SET NAMES \'utf8\'
X - [1] - [Q] CREATE DATABASE `test_X`
X - [1] - [Q] USE `test_X`
X - 3
';
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
        static::assertEquals(3, $pdo->numberOfQueries());
        $pdo->clearLog();
        static::assertEquals(0, $pdo->exec("CREATE TABLE `foo` (`bar` INT(11))"));
        static::assertEquals(1, $pdo->exec("INSERT INTO `foo` VALUE (3)"));
        static::assertEquals([['3', 'bar' => 3]], $pdo->query("SELECT * FROM `foo`")->fetchAll());
        $expected = 'X - [0] - [E] CREATE TABLE `foo` (`bar` INT(11))
X - [1] - [E] INSERT INTO `foo` VALUE (3)
X - [1] - [Q] SELECT * FROM `foo`
X - 3
';
        static::assertEquals(str_replace("\r", "", $expected), $pdo->getAssertableLog());
    }
}
