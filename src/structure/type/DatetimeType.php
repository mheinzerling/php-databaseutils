<?php
namespace mheinzerling\commons\database\structure\type;

class DatetimeType extends Type
{

    /**
     * @param string $type
     * @return DatetimeType|null
     */
    public static function parseDatetime(string $type)
    {
        if (strtoupper($type) == "DATETIME") return new DatetimeType();
        return null;
    }

    public function toSql(): string
    {
        return "DATETIME";
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::datetime())';
    }
}