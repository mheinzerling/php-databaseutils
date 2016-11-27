<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Table;

class LazyPrimary extends Primary
{
    private $fieldNames;

    public function __construct(array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
        parent::__construct(null);
    }

    /**
     * @param Table $table
     * @return Primary
     */
    public function toPrimary(Table $table): Primary
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[$fieldName] = $table->getFields()[$fieldName];
        }
        $this->setTable($table);
        $primary = new Primary($result);
        $primary->setTable($table);
        return $primary;

    }

}