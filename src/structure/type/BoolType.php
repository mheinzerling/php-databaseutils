<?php
namespace mheinzerling\commons\database\structure\type;


class BoolType extends TinyIntType
{

    public function __construct()
    {
        parent::__construct(1);
    }

    /**
     * @param string $type
     * @return BoolType|null
     */
    public static function parseBool(string $type)
    {
        if (preg_match("@^tinyint\((\d+)\)$@i", $type, $match)) return new BoolType();
        if (preg_match("@^bool$@i", $type, $match)) return new BoolType();
        return null;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toSql():string
    {
        return "BOOL";
    }


}