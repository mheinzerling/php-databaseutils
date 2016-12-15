<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;


class TinyIntType extends IntType
{
    public function __construct(int $zerofillLength = null)
    {
        parent::__construct($zerofillLength);
    }

    public static function parseTinyInt(string $type):?TinyIntType
    {
        if (preg_match("@^tinyint\((\d+)\)$@i", $type, $match)) return new TinyIntType($match[1]);
        return null;
    }

    public function toSql(): string
    {
        return "TINY" . parent::toSql();
    }
}