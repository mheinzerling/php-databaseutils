<?php
namespace mheinzerling\commons\database\structure\type;


class VarcharType extends Type
{
    /**
     * @var int
     */
    private $length;
    /**
     * @var string|null
     */
    private $collation;

    public function __construct(int $length, string $collation = null)
    {
        $this->length = $length;
        $this->collation = $collation;
    }

    /**
     * @param string $type
     * @param string $collation
     * @return VarcharType|null
     */
    public static function parseVarchar(string $type, string $collation = null)
    {
        if (preg_match("@^varchar\((\d+)\)$@i", $type, $match)) return new VarcharType($match[1], $collation);
        return null;
    }

    public function toSql(): string
    {
        $sql = "VARCHAR(" . $this->length . ")";
        if ($this->collation != null) $sql .= " COLLATE " . $this->collation;
        return $sql;
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::varchar(' . $this->length . ', "' . $this->collation . '"))';
    }
}