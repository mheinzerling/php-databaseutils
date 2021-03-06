<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\SqlSetting;

class Unique extends Index
{
    public function __construct(array $fields, string $name = null)
    {
        parent::__construct($fields, $name);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getGeneratedName(): string
    {
        return "uni_" . reset($this->fields)->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields);
    }

    public function toSql(SqlSetting $setting): string
    {
        return "UNIQUE " . parent::toSql($setting);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toBuilderCode(): string
    {
        return '->unique(["' . implode('", "', array_keys($this->fields)) . '"])';
    }
}