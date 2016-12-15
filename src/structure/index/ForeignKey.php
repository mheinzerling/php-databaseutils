<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\SqlSetting;
use mheinzerling\commons\database\structure\Table;

class ForeignKey extends Index
{
    /**
     * @var Table
     */
    private $referenceTable;
    /**
     * @var Field[]
     */
    private $referenceFields;
    /**
     * @var ReferenceOption
     */
    protected $onUpdate;
    /**
     * @var ReferenceOption
     */
    protected $onDelete;

    /**
     * ForeignKey constructor.
     * @param Field[]|null $fields
     * @param string $name
     * @param Table|null $referenceTable
     * @param Field[] $referenceFields
     * @param ReferenceOption $onUpdate
     * @param ReferenceOption $onDelete
     */
    public function __construct(array $fields = null, string $name = null, Table &$referenceTable = null, array $referenceFields, ReferenceOption $onUpdate, ReferenceOption $onDelete)
    {
        $this->referenceTable = $referenceTable;
        $this->referenceFields = $referenceFields;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
        parent::__construct($fields, $name);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getGeneratedName(): string
    {
        return "fk_" . $this->fields[0]->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields) . "__" . $this->referenceTable->getName() . "_" . $this->getImplodedFieldNames($this->referenceFields);
    }

    /**
     * @return Table
     */
    public function getReferenceTable(): Table
    {
        return $this->referenceTable;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param SqlSetting $setting
     * @return string
     */
    public function toSql(SqlSetting $setting): string
    {
        $sql = "";
        if ($this->getName() != null) $sql .= "CONSTRAINT `" . $this->getName() . "` ";
        $sql .= "FOREIGN KEY ";
        $sql .= "(`" . implode("`, `", array_keys($this->fields)) . "`) ";
        $sql .= "REFERENCES `" . $this->referenceTable->getName() . "` ";
        $sql .= "(`" . implode("`, `", array_keys($this->referenceFields)) . "`)";
        $delimiter = $setting->singleLine ? " " : "\n    ";
        if ($this->onUpdate != null) $sql .= $delimiter . "ON UPDATE " . $this->onUpdate->value();
        if ($this->onDelete != null) $sql .= $delimiter . "ON DELETE " . $this->onDelete->value();
        return $sql;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toBuilderCode(): string
    {
        return '->foreign(["' . implode('", "', array_keys($this->fields)) . '"], "' . $this->referenceTable->getName() . '", ' .
            '["' . implode('", "', array_keys($this->referenceFields)) . '"], ReferenceOption::' . $this->onUpdate->key() . '(), ReferenceOption::' . $this->onDelete->key() . '())';
    }

}