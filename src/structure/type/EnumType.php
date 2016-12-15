<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\type;

use mheinzerling\commons\StringUtils;


class EnumType extends Type
{
    /**
     * @var string[] $values
     */
    private $values;

    /**
     * @param string[] $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function parseEnum(string $type): ?EnumType
    {
        if (!StringUtils::startsWith(strtolower($type), "enum")) return null;
        preg_match_all("@'([^']*)'@", $type, $matches);
        return new EnumType($matches[1]);

    }

    public function toSql(): string
    {
        return "ENUM('" . implode("', '", $this->values) . "')";
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::enum(["' . implode('", "', $this->values) . '"]))';
    }

}