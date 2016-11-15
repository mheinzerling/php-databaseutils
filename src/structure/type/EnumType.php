<?php

namespace mheinzerling\commons\database\structure\type;


use mheinzerling\commons\database\structure\Gender;
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
        $values = explode("','", substr($type, 6, -2)); //todo
        return new EnumType($values);

    }

}