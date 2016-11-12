<?php

namespace mheinzerling\commons\database;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\StringUtils;

class DatabaseSchema
{
    /**
     * @var DatabaseTable[]
     */
    private $tables;

    /**
     * @return DatabaseTable[]
     */
    public function getTables():array
    {
        return $this->tables;
    }

    public static function fromDatabase(\PDO $connection) :DatabaseSchema
    {
        $schema = new DatabaseSchema();
        $schema->tables = [];

        $stmt = $connection->query("SHOW TABLES");
        while ($table = $stmt->fetch(\PDO::FETCH_COLUMN)) {
            $schema->tables[$table] = DatabaseTable::fromDatabase($connection, $table);
        }
        return $schema;
    }

    public static function fromSql(string $sql):DatabaseSchema
    {
        $schema = new DatabaseSchema();
        $schema->tables = [];
        $queries = StringUtils::trimExplode(';', $sql);
        foreach ($queries as $query) {
            if (StringUtils::startsWith($query, "CREATE")) {
                $table = DatabaseTable::parseSqlCreate($query);
                $schema->tables[$table->getName()] = $table;
            }
            //TODO Indeces etc.
        }
        return $schema;
    }

    /**
     * @param DatabaseSchema $otherSchema
     * @return string[] operations to get from other to current schema
     */
    public function compare(DatabaseSchema $otherSchema):array
    {
        $myTables = $this->getTables();
        $otherTables = $otherSchema->getTables();
        $tablesNames = ArrayUtils::mergeAndSortArrayKeys($myTables, $otherTables);
        $results = [];

        foreach ($tablesNames as $name) {
            if (!isset($myTables[$name])) {
                $results[$name][] = $otherTables[$name]->buildDropQuery();
                continue;
            }

            if (!isset($otherTables[$name])) {
                $results[$name][] = $myTables[$name]->buildCreateQuery();
                continue;
            }

            $r = $myTables[$name]->compare($otherTables[$name]);
            if (count($r) > 0) $results[$name] = $r;
        }
        return $results;
    }
}