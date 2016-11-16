<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\SqlSetting;

class Primary extends Index
{
    const PRIMARY = 'PRIMARY';

    public function __construct(array $fields = null)
    {
        parent::__construct($fields, Primary::PRIMARY);
    }

    public function append(Field $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param SqlSetting $setting
     * @return string
     */
    public function toSql(SqlSetting $setting):string
    {
        $sql = "PRIMARY KEY ";
        $sql .= "(`" . implode("`, `", array_keys($this->fields)) . "`)";
        return $sql;
    }
}