<?php
namespace mheinzerling\commons\database\structure\type;


use Eloquent\Enumeration\AbstractEnumeration;


abstract class Type
{
    public abstract function toSql(): string;

    public static function int(int $length = null):IntType
    {
        return new IntType($length);
    }

    public static function bool():IntType
    {
        return new BoolType();
    }

    public static function varchar(int $length, string $collation = null):VarcharType
    {
        return new VarcharType($length, $collation);
    }

    public static function datetime():DatetimeType
    {
        return new DatetimeType();
    }

    /**
     * @param AbstractEnumeration[] $values
     * @return EnumType
     */
    public static function enum(array $values)
    {
        return new EnumType($values);
    }

    /**
     * @param string $type
     * @param string $collation
     * @param bool $isBoolean
     * @return Type|null
     * @throws \Exception
     */
    public static function fromSql(string $type, string $collation = null, bool $isBoolean = false)
    {
        //TODO register parser
        $result = BoolType::parseBool($type);
        if ($result != null) return $result;
        $result = ($isBoolean) ? BoolType::parseBool($type) : TinyIntType::parseTinyInt($type);
        if ($result != null) return $result;
        $result = IntType::parseInt($type);
        if ($result != null) return $result;
        $result = VarcharType::parseVarchar($type, $collation);
        if ($result != null) return $result;
        $result = DatetimeType::parseDatetime($type);
        if ($result != null) return $result;
        $result = EnumType::parseEnum($type);
        if ($result != null) return $result;
        throw new \Exception("Unknown sql type: " . $type);
    }
}
