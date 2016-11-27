<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Table;

class LazyIndex extends Index
{
    /**
     * @var string
     */
    private $tableName;
    /**
     * @var string[]
     */
    private $fieldNames;

    public function __construct(string $tableName, array $fieldNames, string $name = null)
    {
        $this->tableName = $tableName;
        $this->fieldNames = $fieldNames;
        parent::__construct([], $name);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getGeneratedName()
    {
        return "idx_" . $this->tableName . "_" . implode("_", $this->fieldNames);
    }

    /**
     * @param Table $table
     * @return Index
     */
    public function toIndex(Table $table): Index
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[$fieldName] = $table->getFields()[$fieldName];
        }
        $this->setTable($table);
        $index = new Index($result, $this->getName());
        $index->setTable($table);
        return $index;

    }

}