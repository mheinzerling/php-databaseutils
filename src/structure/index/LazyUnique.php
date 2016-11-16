<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;

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
     * @param Field[] $fields
     * @return Unique
     */
    public function toUnique(array $fields):Unique
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[$fieldName] = $fields[$fieldName];
        }
        return new Unique($result, $this->name);

    }

}