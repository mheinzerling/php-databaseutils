<?php

namespace mheinzerling\commons\database;


class TestDatabaseConnection extends LoggingPDO
{
    private $dbName;

    public function __construct($dropDatabaseAtShutdown = true)
    {
        parent::__construct('mysql:host=127.0.0.1', 'travis', '');
        $this->query("SET NAMES 'utf8'");
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbName = "test_" . microtime(true);
        if (!$this->query("CREATE DATABASE `" . $this->dbName . "`")) die('Could not create database >' . $this->dbName . '<');
        if (!$this->query("USE `" . $this->dbName . "`")) die('Could not use database >' . $this->dbName . '<');
        if ($dropDatabaseAtShutdown) register_shutdown_function(array($this, "deleteDatabase"));
    }

    public function deleteDatabase()
    {
        $this->exec("DROP DATABASE `" . $this->dbName . "`");
    }

    public function tableStructure($tableName, $fetchType = \PDO::FETCH_NUM)
    {
        return $this->query("DESCRIBE `" . $tableName . "`")->fetchAll($fetchType);
    }

}