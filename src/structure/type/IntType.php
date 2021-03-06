<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure\type;

class IntType extends Type
{
    /**
     * @var int|null
     */
    private $zerofillLength;

    public function __construct(int $zerofillLength = null)
    {
        $this->zerofillLength = $zerofillLength;
    }

    public static function parseInt(string $type):?IntType
    {
        if (preg_match("@^int\((\d+)\)$@i", $type, $match)) return new IntType(intval($match[1]));
        if (preg_match("@^int$@i", $type, $match)) return new IntType(null);
        return null;
    }

    public function toSql(): string
    {
        return "INT" . ($this->zerofillLength != null ? "(" . $this->zerofillLength . ")" : "");
    }

    public function toBuilderCode(): string
    {
        return '->type(Type::int(' . $this->zerofillLength . '))';
    }

    public function getZerofillLength():?int
    {
        return $this->zerofillLength;
    }
}