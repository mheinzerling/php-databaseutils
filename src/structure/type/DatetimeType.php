<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;

class DatetimeType extends Type
{
    public static function parseDatetime(string $type):? DatetimeType
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