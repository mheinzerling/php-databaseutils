<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;

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
     * @param Field[] $fields
     * @return Index
     */
    public function toIndex(array $fields):Index
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[] = $fields[$fieldName];
        }
        return new Index($result, $this->name);

    }

}