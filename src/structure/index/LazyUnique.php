<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Table;

class LazyUnique extends Unique
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
        return "uni_" . $this->tableName . "_" . implode("_", $this->fieldNames);
    }

    /**
     * @param Table $table
     * @return Unique
     */
    public function toUnique(Table $table):Unique
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[$fieldName] = $table->getFields()[$fieldName];
        }
        $this->setTable($table);
        $unique = new Unique($result, $this->getName());
        $unique->setTable($table);
        return $unique;

    }

}