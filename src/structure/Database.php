<?php

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;


class Database
{
    /**
     * @var string@null
     */
    private $name;
    /**
     * @var Table[]
     */
    private $tables = [];

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function addTable(Table $table)
    {
        $this->tables[$table->getName()] = $table;
        $table->setDatabase($this);
    }

    /**
     * @return Table[]
     */
    public function getTables():array
    {
        return $this->tables;
    }

    public function getName():string
    {
        return $this->name;

    }

    public function resolveLazyIndexes()
    {
        foreach ($this->tables as $table) $table->resolveLazyIndexes();
    }


    /**
     * @param Database $otherSchema
     * @return string[] operations to get from other to current schema
     */
    public function compare(Database $otherSchema):array
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