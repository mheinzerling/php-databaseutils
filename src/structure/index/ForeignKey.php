<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;
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
    protected function getGeneratedName()
    {
        return "fk_" . $this->fields[0]->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields) . "__" . $this->referenceTable->getName() . "_" . $this->getImplodedFieldNames($this->referenceFields);
    }
}