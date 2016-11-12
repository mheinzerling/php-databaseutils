<?php

namespace mheinzerling\commons\database;

use mheinzerling\commons\database\logging\LoggingPDO;

class TestDatabaseConnection extends LoggingPDO
{
    /**
     * @var string
     */
    private $dbName;

    public function __construct(bool $dropDatabaseAtShutdown = true)
    {
        parent::__construct('mysql:host=127.0.0.1', 'travis', ''); //TODO get from ENV
        $this->query("SET NAMES 'utf8'");
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbName = "test_" . microtime(true);
        if (!$this->query("CREATE DATABASE `" . $this->dbName . "`")) die('Could not create database >' . $this->dbName . '<');
        if (!$this->query("USE `" . $this->dbName . "`")) die('Could not use database >' . $this->dbName . '<');
        if ($dropDatabaseAtShutdown) register_shutdown_function([$this, "deleteDatabase"]);
    }

    /**
     * @return void
     */
    public function deleteDatabase()
    {
        $this->exec("DROP DATABASE `" . $this->dbName . "`");
    }

    public function tableStructure(string $tableName, $fetchType = \PDO::FETCH_NUM):array
    {
        return $this->query("DESCRIBE `" . $tableName . "`")->fetchAll($fetchType);
    }

}