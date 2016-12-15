<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;


class BoolType extends TinyIntType
{

    public function __construct()
    {
        parent::__construct(1);
    }

    public static function parseBool(string $type):?BoolType
    {
        if (preg_match("@^tinyint\((\d+)\)$@i", $type, $match)) return new BoolType();
        if (preg_match("@^bool$@i", $type, $match)) return new BoolType();
        return null;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toSql(): string
    {
        return "BOOL";
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toBuilderCode(): string
    {
        return '->type(Type::bool())';
    }

}