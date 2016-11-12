<?php

namespace mheinzerling\commons\database;

use mheinzerling\commons\database\logging\LoggingPDO;

class TestDatabaseConnection extends LoggingPDO
{
    const DSN = "MHEINZERLING_TESTDATASECONNECTION_DSN";
    const USER = "MHEINZERLING_TESTDATASECONNECTION_USER";
    const PASSWORD = "MHEINZERLING_TESTDATASECONNECTION_PWASSWORD";
    /**
     * @var string
     */
    private $dbName;

    public function __construct(bool $dropDatabaseAtShutdown = true)
    {
        parent::__construct(
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::DSN, 'mysql:host=127.0.0.1'),
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::USER, 'travis'),
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::PASSWORD, '')
        );
        $this->query("SET NAMES 'utf8'");
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbName = "test_" . microtime(true);
        if (!$this->query("CREATE DATABASE `" . $this->dbName . "`")) throw new DatabaseException('Could not create database >' . $this->dbName . '<');
        if (!$this->query("USE `" . $this->dbName . "`")) throw new DatabaseException('Could not use database >' . $this->dbName . '<');
        if ($dropDatabaseAtShutdown) register_shutdown_function([$this, "deleteDatabase"]);
    }

    private static function envWithFallback(string $key, $default):string
    {
        $value = getenv($key);
        if ($value === false) return $default;
        return $value;
    }

    /**
     * @return void
     */
    public function deleteDatabase()
    {
        $this->exec("DROP DATABASE IF EXISTS `" . $this->dbName . "`");
    }

    public function tableStructure(string $tableName, $fetchType = \PDO::FETCH_NUM):array
    {
        return $this->query("DESCRIBE `" . $tableName . "`")->fetchAll($fetchType);
    }

}