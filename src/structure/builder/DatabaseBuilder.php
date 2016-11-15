<?php

namespace mheinzerling\commons\database\structure\builder;


use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\Table;

class DatabaseBuilder
{
    /**
     * @var Database
     */
    private $database;
    /**
     * @var string|null
     */
    private $defaultEngine;
    /**
     * @var string|null
     */
    private $defaultCharset;
    /**
     * @var string|null
     */
    private $defaultCollation;

    public function __construct(string $name)
    {
        $this->database = new Database($name);
    }

    public function build():Database
    {
        $this->database->resolveLazyIndexes();
        return $this->database;
    }

    public function table(string $name):TableBuilder
    {
        return new TableBuilder($this, $name);
    }

    public function defaultEngine(string $defaultEngine)
    {
        $this->defaultEngine = $defaultEngine;
        return $this;
    }

    public function defaultCharset(string $defaultCharset)
    {
        $this->defaultCharset = $defaultCharset;
        return $this;
    }

    public function defaultCollation(string $defaultCollation)
    {
        $this->defaultCollation = $defaultCollation;
        return $this;
    }


    public function getName():string
    {
        return $this->database->getName();
    }

    /**
     * @return string|null
     */
    public function getDefaultEngine()
    {
        return $this->defaultEngine;
    }

    /**
     * @return string|null
     */
    public function getDefaultCharset()
    {
        return $this->defaultCharset;
    }

    /**
     * @return string|null
     */
    public function getDefaultCollation()
    {
        return $this->defaultCollation;
    }

    /**
     * @param Table $table
     * @return void
     */
    public function addTable(Table $table)
    {
        $this->database->addTable($table);
    }


    /**
     * @param \PDO $pdo
     * @param string[] $booleanFields
     * @return Database
     */
    public static function fromDatabase(\PDO $pdo, array $booleanFields = []):Database
    {
        $dbName = $pdo->query("SELECT DATABASE();")->fetchColumn();
        $db = new DatabaseBuilder($dbName);
        $tables = DatabaseUtils::exec($pdo, 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA=?', [$dbName]);
        while ($table = $tables->fetch(\PDO::FETCH_ASSOC)) {
            TableBuilder::fromDatabase($pdo, $db, $table['TABLE_NAME'], $table['ENGINE'], $table['TABLE_COLLATION'], $table['AUTO_INCREMENT'], $booleanFields);
        }
        return $db->build();
    }

    public static function fromSqlCreateStatements(string $sql):Database
    {
        $database = new Database([]);
        $queries = StringUtils::trimExplode(';', $sql);
        foreach ($queries as $query) {
            if (StringUtils::startsWith($query, "CREATE")) {
                $table = Table::parseSqlCreate($query);
                $database->getTables()[$table->getName()] = $table;
            }
            //TODO Indexes etc.
        }
        return $database;
    }


}