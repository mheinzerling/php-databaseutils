<?php
declare(strict_types = 1);
namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\database\structure\type\IntType;
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

    public function init(Type $type, bool $null, string $default = null, bool $autoincrement): void
    {
        $this->autoincrement = $autoincrement;
        $this->default = $default;
        $this->null = $null;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getFullName(): string
    {
        return $this->table->getName() . "." . $this->name;
    }

    public function toSql(SqlSetting $setting): string
    {
        $sql = '`' . $this->name . '` ';
        $sql .= $this->type->toSql() . ' ';
        if (!$this->null) $sql .= "NOT NULL ";
        if ($this->autoincrement) $sql .= "AUTO_INCREMENT ";
        if ($this->default != null) $sql .= "DEFAULT '" . $this->default . "' "; //todo check int/double/time etc
        else if ($this->default == null && $this->null) $sql .= "DEFAULT NULL ";
        return trim($sql);
    }

    public function roAlterAddSql(SqlSetting $setting): string
    {
        return 'ADD ' . $this->toSql($setting) . ";";
    }

    public function toAlterDropSql(SqlSetting $setting): string
    {
        return 'DROP COLUMN `' . $this->name . '`;';
    }


    public function modifySql(Field $before, SqlSetting $setting): ?string
    {
        //TODO rename
        $ta = $this->type;
        $tb = $before->type;
        $typeChange = $ta != $tb;
        if ($typeChange && $ta instanceof IntType && $tb instanceof IntType) {
            //TODO default
            if ($ta->getZerofillLength() == null && $tb->getZerofillLength() == 11) $typeChange = false;
            else if ($ta->getZerofillLength() == 11 && $tb->getZerofillLength() == null) $typeChange = false;
        }
        $nullChange = $this->null != $before->null;
        $defaultChange = $this->default != $before->default;
        $autoincrementChange = $this->autoincrement != $before->autoincrement;
        $change = $typeChange || $nullChange || $defaultChange || $autoincrementChange;

        if (!$change) return null;
        return 'MODIFY ' . $this->toSql($setting);
    }

    public function same(Field $other): bool
    {
        return $this->table->same($other->table) && $this->name == $other->name;
    }

    public function toBuilderCode(): string
    {
        $result = "\n    ->field(\"" . $this->name . "\")";
        $result .= $this->type->toBuilderCode();
        if ($this->autoincrement != null) $result .= '->autoincrement()';
        if ($this->default != null) $result .= '->default("' . $this->default . '")';
        if ($this->null) $result .= '->null()';
        return $result;
    }

    public function isAutoincrement(): bool
    {
        return $this->autoincrement;
    }

    public function optional(): bool
    {
        return $this->null;
    }

    public function default(): ?string
    {
        return $this->default;
    }


}