<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Database;

class LazyForeignKey extends ForeignKey
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string[]
     */
    private $fieldNames;

    /**
     * @var string
     */
    private $referenceTableName;
    /**
     * @var string[]
     */
    private $referenceFields;


    /**
     * @param string $tableName
     * @param string[] $fieldNames
     * @param string|null $name
     * @param string $referenceTableName
     * @param string[] $referenceFields
     * @param ReferenceOption $onUpdate
     * @param ReferenceOption $onDelete
     */
    public function __construct(string $tableName, array $fieldNames, string $name = null, string $referenceTableName, array $referenceFields, ReferenceOption $onUpdate, ReferenceOption $onDelete)
    {
        $this->tableName = $tableName;
        $this->fieldNames = $fieldNames;
        $this->referenceTableName = $referenceTableName;
        $this->referenceFields = $referenceFields;
        $table = null;
        parent::__construct(null, $name, $table, [], $onUpdate, $onDelete);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getGeneratedName()
    {
        return "fk_" . $this->tableName . "_" . implode("_", $this->fieldNames) . "__" . $this->referenceTableName . "_" . implode("_", $this->referenceFields);
    }

    public function append(LazyForeignKey $other)
    {
        if ($this->getName() != $other->getName() || $this->tableName != $other->tableName || $this->onUpdate != $other->onUpdate || $this->onDelete != $other->onUpdate)
            throw new \Exception("Tried to merge unrelated lazy foreign keys");
        $this->fieldNames = array_merge($this->fieldNames, $other->fieldNames);
        $this->referenceFields = array_merge($this->referenceFields, $other->referenceFields);
    }

    public function toForeignKey(Database $database): ForeignKey
    {
        $table = $database->getTables()[$this->tableName];
        $fields = [];
        foreach ($this->fieldNames as $f) {
            $fields[$f] = $table->getFields()[$f];
        }
        $referenceTable = $database->getTables()[$this->referenceTableName];
        $rfields = [];
        foreach ($this->referenceFields as $rf) {
            $rfields[$rf] = $referenceTable->getFields()[$rf];
        }

        $this->setTable($table);
        $foreignKey = new ForeignKey($fields, $this->getName(), $referenceTable, $rfields, $this->onUpdate, $this->onDelete);
        $foreignKey->setTable($table);
        return $foreignKey;
    }
}