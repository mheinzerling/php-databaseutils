<?php

namespace mheinzerling\commons\database\structure\index;


class Unique extends Index
{
    public function __construct(array $fields, string $name = null)
    {
        parent::__construct($fields, $name);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getGeneratedName()
    {
        return "uni_" . $this->fields[0]->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields);
    }
}