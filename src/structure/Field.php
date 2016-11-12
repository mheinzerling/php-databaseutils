<?php
namespace mheinzerling\commons\database\structure;


class Field
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $type;
    /**
     * @var bool
     */
    private $null;
    /**
     * @var bool
     */
    private $primary;
    /**
     * @var bool
     */
    private $unique;
    /**
     * @var string
     */
    private $default;
    /**
     * @var bool
     */
    private $autoincrement;

    function __construct(string $name, string $type, bool $null, bool $primary, bool $unique, string $default = null, bool $autoincrement)
    {
        $this->autoincrement = $autoincrement;
        $this->default = $default;
        $this->name = $name;
        $this->null = $null;
        $this->primary = $primary;
        $this->unique = $unique;
        $this->type = $type;
    }

    public function buildDropQuery(string $tableName):string
    {
        return 'ALTER TABLE `' . $tableName . '` DROP COLUMN `' . $this->name . '`;';
    }

    public function buildAddQuery(string $tableName):string
    {
        return 'ALTER TABLE `' . $tableName . '` ADD `' . $this->name . '` ...;'; //TODO
    }


    /**
     * @param Field $other
     * @param string $tableName
     * @return string[]
     */
    public function compare(Field $other, string $tableName) : array
    {
        $results = [];
        $key = '';

        if (!$this->primary && $other->primary) {
            $key .= 'DROP PRIMARY KEY';
        } elseif ($this->primary && !$other->primary) {
            $key .= 'ADD PRIMARY KEY(`' . $this->name . '`)';
        }

        if ($this->unique != $other->unique) {
            $results[] = 'Alter column index: ' . $tableName . '.' . $this->name . ' (' . $other->unique . '=>' . $this->unique . ') ';
        }

        $diff = $this->type != $other->type || $this->null != $other->null || $this->default != $other->default || $this->autoincrement != $other->autoincrement;

        if ($diff) {
            $mod = trim('MODIFY `' . $this->name . '` ' . strtoupper($this->type) . ' ' .
                ($this->null ? 'NULL' : 'NOT NULL') . ' ' . ($this->autoincrement ? 'AUTO_INCREMENT' : ''));
        } else $mod = '';

        if ($key != '' && $diff) $key = ', ' . $key;

        if ($key != '' || $diff) {
            $results[] = 'ALTER TABLE `' . $tableName . '` ' . $mod . '' . $key;
        }
        return $results;
    }
}