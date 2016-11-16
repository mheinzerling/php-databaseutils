<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;

class Primary extends Index
{
    const PRIMARY = 'PRIMARY';

    public function __construct(array $fields = null)
    {
        parent::__construct($fields, Primary::PRIMARY);
    }

    public function append(Field $field)
    {
        $this->fields[] = $field;
    }
}