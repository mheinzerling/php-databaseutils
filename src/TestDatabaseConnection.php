<?php
declare(strict_types = 1);

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
    private $databaseName;

    public function __construct(bool $dropDatabaseAtShutdown = true)
    {
        parent::__construct(
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::DSN, 'mysql:host=127.0.0.1'),
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::USER, 'travis'),
            TestDatabaseConnection::envWithFallback(TestDatabaseConnection::PASSWORD, '')
        );
        $this->query("SET NAMES 'utf8'");
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->databaseName = "test_" . microtime(true);
        if (!$this->query("CREATE DATABASE `" . $this->databaseName . "`")) throw new DatabaseException('Could not create database >' . $this->databaseName . '<');
        if (!$this->query("USE `" . $this->databaseName . "`")) throw new DatabaseException('Could not use database >' . $this->databaseName . '<');
        if ($dropDatabaseAtShutdown) register_shutdown_function([$this, "deleteDatabase"]);
    }

    private static function envWithFallback(string $key, string $default = null): string
    {
        $value = getenv($key);
        if ($value === false) return $default;
        return $value;
    }

    public function deleteDatabase(): void
    {
        $this->exec("DROP DATABASE IF EXISTS `" . $this->databaseName . "`");
    }

    public function tableStructure(string $tableName, $fetchType = \PDO::FETCH_NUM): array
    {
        return $this->query("DESCRIBE `" . $tableName . "`")->fetchAll($fetchType);
    }

    public function getAssertableLog(): string
    {
        $withoutTime = preg_replace("@\d+\.\d+@", "X", $this->getLog());
        $withoutStartingWhitespace = preg_replace("@^\s+@m", "", $withoutTime);
        $fixStartingZeroTime = preg_replace("@^0@m", "X", $withoutStartingWhitespace);
        $withoutWindowsLinebreak = str_replace("\r", "", $fixStartingZeroTime);
        return $withoutWindowsLinebreak;
    }


    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }


}