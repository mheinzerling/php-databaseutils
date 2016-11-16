<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;

class LazyPrimary extends Primary
{
    private $fieldNames;

    public function __construct(array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
        parent::__construct(null);
    }

    /**
     * @param Field[] $fields
     * @return Primary
     */
    public function toPrimary(array $fields):Primary
    {
        $result = [];
        foreach ($this->fieldNames as $fieldName) {
            $result[$fieldName] = $fields[$fieldName];
        }
        return new Primary($result);

    }

}