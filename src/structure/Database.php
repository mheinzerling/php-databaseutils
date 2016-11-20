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

    public function toCreateSql(SqlSetting $setting):string
    {
        //TODO create database (if not exists); setting withDatabase
        $sql = "";
        foreach ($this->topoOrder($this->tables) as $table) {
            $sql .= $table->toCreateSql($setting);
            $sql .= "\n\n";
        }
        return trim($sql);
    }

    /**
     * @param Table[] $tables
     * @return Table[]
     * @throws \Exception
     */
    private function topoOrder(array $tables):array
    {
        $result = [];
        while (count($tables)) {
            $added = false;
            foreach ($tables as $name => $table) {
                if ($table->hasForeignKeysOnlyOn($result)) {
                    $result[$name] = $table;
                    unset($tables[$name]);
                    $added = true;
                }
            }
            if ($added === false && count($tables) > 0) throw new \Exception("Not supported cyclic dependency in " . implode(", ", array_keys($tables)));
        }
        return $result;
    }


    public function toDropSql(SqlSetting $setting):string
    {
        $sql = "DROP DATABASE ";
        if ($setting->dropDatabaseIfExists) $sql .= "IF EXISTS ";
        $sql .= "`" . $this->name . "`;";
        return $sql;
    }

    /**
     * @param Database $before
     * @param SqlSetting $setting
     * @param string[] $renames A rename mapping old=>new with table.field values
     * @return Migration operations to get from other to current schema
     */
    public function migrate(Database $before, SqlSetting $setting, array $renames = null /*TODO*/):Migration
    {
        $migration = new Migration();
        if ($this->name != $before->name) $migration->todo("TODO: rename database to >" . $this->name . "< from >" . $before->name . "<");
        $myTables = $this->getTables();
        $otherTables = $before->getTables();
        $tablesNames = ArrayUtils::mergeAndSortArrayKeys($myTables, $otherTables);
        foreach ($tablesNames as $name) {
            if (!isset($myTables[$name])) {
                $migration->dropTable($name, $otherTables[$name]->toDropQuery($setting));
                continue;
            }

            if (!isset($otherTables[$name])) {
                $migration->addTable($name, $myTables[$name]->toCreateSql($setting));
                continue;
            }

            $myTables[$name]->migrate($migration, $otherTables[$name], $setting);
        }
        return $migration;
    }

    public function same(Database $other):bool
    {
        return $this->name == $other->name;
    }
}