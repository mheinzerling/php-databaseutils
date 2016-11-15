<?php
namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\database\structure\type\Type;

class Field
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var string
     */
    private $name;
    /**
     * @var Type
     */
    private $type;
    /**
     * @var bool
     */
    private $null;
    /**
     * @var string
     */
    private $default;
    /**
     * @var bool
     */
    private $autoincrement;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function init(Type $type, bool $null, string $default = null, bool $autoincrement)
    {
        $this->autoincrement = $autoincrement;
        $this->default = $default;
        $this->null = $null;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    public function getFullName()
    {
        return $this->table->getName() . "." . $this->name;
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