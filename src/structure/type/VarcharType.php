<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;


class VarcharType extends Type
{
    /**
     * @var int|null
     */
    private $length;
    /**
     * @var string|null
     */
    private $collation;

    public function __construct(?int $length, string $collation = null)
    {
        $this->length = $length;
        $this->collation = $collation;
    }

    public static function parseVarchar(string $type, string $collation = null): ?VarcharType
    {
        if (preg_match("@^varchar\((\d+)\)$@i", $type, $match)) return new VarcharType(intval($match[1]), $collation);
        if (preg_match("@^text$@i", $type, $match)) return new VarcharType(null, $collation);
        return null;
    }

    public function toSql(): string
    {
        if ($this->length == null) $sql = "TEXT";
        else $sql = "VARCHAR(" . $this->length . ")";
        if ($this->collation != null) $sql .= " COLLATE " . $this->collation;
        return $sql;
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::varchar(' . ($this->length == null ? 'null' : $this->length) . ', "' . $this->collation . '"))';
    }
}