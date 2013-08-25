<?php

namespace mheinzerling\PostalCode;


use mheinzerling\commons\database\DatabaseUtils;

class DatabaseUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testQueryCreation()
    {
        $data = array();
        $data[] = array("a" => 1, "b" => "3", "c" => 3.756);
        $data[] = array("b" => "3", "a" => 7, "c" => null);
        $data[] = array("c" => 5.756, "a" => 4, "b" => "asdf");
        $queries = DatabaseUtils::toInsertQueries("tablename", $data);
        $expected = array("INSERT IGNORE INTO `tablename`(`a`,`b`,`c`) VALUES " .
            "(1,'3','3.756')," .
            "(7,'3',NULL)," .
            "(4,'asdf','5.756')");
        $this->assertEquals($expected, $queries);
    }

    /*public function testLoadDump()
    {
        $connection = new TestDatabaseConnection();
        DatabaseUtils::executeFile($connection, __DIR__ . "/../../../resources/test/geodbsql/postalcodes_DE_dump.sql");
        $rows = $connection->query("SELECT COUNT(*) FROM postalcodes")->fetch(\PDO::FETCH_NUM)[0];
        $this->assertEquals(59227, $rows);
    }*/
}
