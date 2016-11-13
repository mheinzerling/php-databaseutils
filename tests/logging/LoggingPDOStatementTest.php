<?php

namespace mheinzerling\commons\database\logging;

use mheinzerling\commons\database\TestDatabaseConnection;

class LoggingPDOStatementTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $pdo = new TestDatabaseConnection(); //extends LoggingPDO
        $pdo->clearLog();
        $pdo->exec("CREATE TABLE `foo` (`bar` INT(11))");
        $pdo->exec("INSERT INTO `foo` VALUE (3)");

        $stmt = $pdo->prepare("SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max");
        static::assertInstanceOf(LoggingPDOStatement::class, $stmt);
        $param = 1;
        $stmt->bindParam(":min", $param);
        $stmt->bindValue(":max", 5);
        $stmt->execute();
        static::assertEquals([3], $stmt->fetchAll(\PDO::FETCH_COLUMN, 0));
        static::assertEquals(['00000', null, null], $stmt->errorInfo());
        static::assertEquals('00000', $stmt->errorCode());
        static::assertEquals(1, $stmt->columnCount());
        static::assertEquals(['native_type' => 'LONG', 'pdo_type' => 2, 'flags' => [], 'table' => 'foo', 'name' => 'bar', 'len' => 11, 'precision' => 0], $stmt->getColumnMeta(0));
        $stmt->execute();
        static::assertEquals(['bar' => '3'], $stmt->fetch(\PDO::FETCH_ASSOC));
        $stmt->execute();
        static::assertEquals('3', $stmt->fetchColumn(0));
        $stmt->execute();
        static::assertEquals([0 => [3, 'bar' => 3]], $stmt->fetchAll());
        $stmt->execute();
        static::assertEquals([0 => ['bar' => 3]], $stmt->fetchAll(\PDO::FETCH_ASSOC));


        $stmt = $pdo->prepare("SELECT boom");
        try {
            $stmt->execute();
            static::fail("Exception expected");
        } catch (\PDOException $e) {
            static::assertEquals("SQLSTATE[42S22]: Column not found: 1054 Unknown column 'boom' in 'field list'", $e->getMessage());
        }

        $expected = "X - [0] - [E] CREATE TABLE `foo` (`bar` INT(11))
X - [1] - [E] INSERT INTO `foo` VALUE (3)
X - [1] - [P] SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max
X - [1] - [P] SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max
X - [1] - [P] SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max
X - [1] - [P] SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max
X - [1] - [P] SELECT `bar` FROM `foo` WHERE `bar` BETWEEN :min AND :max
X - [0] - [X] SQL: [11] SELECT boom
Params:  0
X - 8
";
        LoggingPDOTest::assertIgnoreTimeDatabaseLinebreak($expected, $pdo->getLog());

    }

}
