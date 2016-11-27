<?php
namespace mheinzerling\commons\database\structure\type;


class TinyIntType extends IntType
{
    public function __construct(int $zerofillLength = null)
    {
        parent::__construct($zerofillLength);
    }

    /**
     * @param string $type
     * @return TinyIntType|null
     */
    public static function parseTinyInt(string $type)
    {
        if (preg_match("@^tinyint\((\d+)\)$@i", $type, $match)) return new TinyIntType($match[1]);
        return null;
    }

    public function toSql(): string
    {
        return "TINY" . parent::toSql();
    }
}