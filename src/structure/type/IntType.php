<?php
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

    /**
     * @param string $type
     * @return IntType|null
     */
    public static function parseInt(string $type)
    {
        if (preg_match("@^int\((\d+)\)$@i", $type, $match)) return new IntType($match[1]);
        if (preg_match("@^int$@i", $type, $match)) return new IntType(null);
        return null;
    }

}