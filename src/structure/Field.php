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

    public function toSql(SqlSetting $setting):string
    {
        $sql = '`' . $this->name . '` ';
        $sql .= $this->type->toSql() . ' ';
        if (!$this->null) $sql .= "NOT NULL ";
        if ($this->autoincrement) $sql .= "AUTO_INCREMENT ";
        if ($this->default != null) $sql .= "DEFAULT '" . $this->default . "' "; //todo check int/double/time etc
        else if ($this->default == null && $this->null) $sql .= "DEFAULT NULL ";
        return trim($sql);
    }

    public function toCreateSql(SqlSetting $setting):string
    {
        return 'ALTER TABLE `' . $this->table->getName() . '` ADD ' . $this->toSql($setting) . ";";
    }

    public function toDropSql(SqlSetting $setting):string
    {
        return 'ALTER TABLE `' . $this->table->getName() . '` DROP COLUMN `' . $this->name . '`;';
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


        $diff = $this->type != $other->type || $this->null != $other->null || $this->default != $other->default || $this->autoincrement != $other->autoincrement;

        if ($diff) {
            $mod = trim('MODIFY `' . $this->name . '` ' . strtoupper($this->type->toSql()) . ' ' .
                ($this->null ? 'NULL' : 'NOT NULL') . ' ' . ($this->autoincrement ? 'AUTO_INCREMENT' : ''));
        } else $mod = '';

        if ($key != '' && $diff) $key = ', ' . $key;

        if ($key != '' || $diff) {
            $results[] = 'ALTER TABLE `' . $tableName . '` ' . $mod . '' . $key;
        }
        return $results;
    }


}