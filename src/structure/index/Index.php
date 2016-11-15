<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\StringUtils;

class Index
{
    /**
     * @var Field[]
     */
    protected $fields;

    /**
     * @var string
     */
    protected $name;

    public function __construct(array $fields = null, string $name = null)
    {
        $this->fields = $fields;
        $this->name = $name == null ? $this->getGeneratedName() : $name;
    }

    public function getName():string
    {
        return $this->name;
    }

    protected function getGeneratedName()
    {
        return "idx_" . $this->fields[0]->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields);
    }

    protected function getImplodedFieldNames(array $fields):string
    {
        return StringUtils::implode("_", $fields, function ($_, $field) {
            return $field->getName();
        });
    }
}