<?php
namespace mheinzerling\commons\database;


class DatabaseField
{
    private $name;
    private $type;
    /**
     * @var Boolean
     */
    private $null;
    /**
     * @var Boolean
     */
    private $primary;
    /**
     * @var Boolean
     */
    private $unique;
    private $default;
    /**
     * @var Boolean
     */
    private $autoincrement;

    function __construct($name, $type, $null, $primary, $unique, $default, $autoincrement)
    {
        $this->autoincrement = $autoincrement;
        $this->default = $default;
        $this->name = $name;
        $this->null = $null;
        $this->primary = $primary;
        $this->unique = $unique;
        $this->type = $type;
    }

    public function buildDropQuery($tableName)
    {
        return 'ALTER TABLE `' . $tableName . '` DROP COLUMN `' . $this->name . '`;';
    }

    public function buildAddQuery($tableName)
    {
        return 'ALTER TABLE `' . $tableName . '` ADD `' . $this->name . '` ...;'; //TODO
    }


    /**
     * @param DatabaseField $other
     * @return String[]
     */
    public function compare(DatabaseField $other, $tableName)
    {
        $results = array();
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