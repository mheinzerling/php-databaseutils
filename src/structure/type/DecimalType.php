<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;

class DecimalType extends Type
{
    /**
     * @var int
     */
    private $precision;

    /**
     * @var int
     */
    private $scale;

    public function __construct(int $precision, int $scale)
    {
        $this->precision = $precision;
        $this->scale = $scale;
    }


    public static function parseDecimal(string $type):?DecimalType
    {
        if (preg_match("@^decimal\((\d+),(\d+)\)$@i", $type, $match)) return new DecimalType(intval($match[1]), intval($match[2]));
        return null;
    }

    public function toSql(): string
    {
        return "DECIMAL(" . $this->precision . "," . $this->scale . ")";
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::decimal(' . $this->precision . "," . $this->scale . '))';
    }
}