<?php

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

    /**
     * @param string $type
     * @return EnumType|null
     */
    public static function parseEnum(string $type)
    {
        if (!StringUtils::startsWith(strtolower($type), "enum")) return null;
        preg_match_all("@'([^']*)'@", $type, $matches);
        return new EnumType($matches[1]);

    }

    public function toSql():string
    {
        return "ENUM('" . implode("', '", $this->values) . "')";
    }

}