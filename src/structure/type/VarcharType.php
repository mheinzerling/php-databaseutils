<?php
namespace mheinzerling\commons\database\structure\type;


class VarcharType extends Type
{
    /**
     * @var int
     */
    private $length;
    /**
     * @var string
     */
    private $collation;

    public function __construct(int $length, string $collation)
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
}