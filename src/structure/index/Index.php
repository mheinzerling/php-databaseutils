<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\builder\TableBuilder;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\StringUtils;

class Index
{
    /**
     * @var Field[]
     */
    protected $fields;

    /**
     * @var string
     */
    protected $name;

    public function __construct(array $fields = null, string $name = null)
    {
        $this->fields = $fields;
        $this->name = $name == null ? $this->getGeneratedName() : $name;
    }

    /**
     * @param TableBuilder $tb
     * @param string $constraint
     * @return void
     */
    public static function fromSql(TableBuilder $tb, string $constraint)
    {
        //TODO more robust
        if (StringUtils::contains($constraint, "FOREIGN KEY")) {
            $onUpdate = ReferenceOption::memberByValueWithDefault(StringUtils::findAndRemove($constraint, "@ON UPDATE (RESTRICT|CASCADE|SET NULL|NO ACTION)@i"), null);
            $onDelete = ReferenceOption::memberByValueWithDefault(StringUtils::findAndRemove($constraint, "@ON DELETE (RESTRICT|CASCADE|SET NULL|NO ACTION)@i"), null);
            $parts = explode("FOREIGN KEY", $constraint);
            $name = trim(str_replace("CONSTRAINT ", "", $parts[0]), "`\t\r\n ");

            $parts = explode("REFERENCES", $parts[1]);
            $fieldNames = StringUtils::trimExplode(",", $parts[0]);
            foreach ($fieldNames as &$fieldName) $fieldName = trim($fieldName, "`()\t\r\n ");

            $parts = explode("(", $parts[1]);
            $referenceTableName = trim($parts[0], "`\t\r\n ");
            $referenceFieldNames = StringUtils::trimExplode(",", $parts[1]);
            foreach ($referenceFieldNames as &$referenceFieldName) $referenceFieldName = trim($referenceFieldName, "`()\t\r\n ");
            $tb->foreign($fieldNames, $referenceTableName, $referenceFieldNames, $onUpdate, $onDelete, $name);
        } else {
            $primary = StringUtils::findAndRemove($constraint, "@(primary key)@i") != null;
            $unique = StringUtils::findAndRemove($constraint, "@(unique key)@i") != null;
            $details = StringUtils::trimExplode("(", $constraint);
            $name = trim($details[0], "`\t\r\n ");
            if (StringUtils::startsWith(strtoupper($constraint), "KEY ")) $name = trim(substr($name, 4), "`");
            $values = StringUtils::trimExplode(",", $details[1]);
            foreach ($values as &$value) $value = trim($value, "`)\t\r\n ");

            if ($primary) $tb->primary($values);
            else if ($unique) $tb->unique($values, $name);
            else  $tb->index($values, $name);

        }

    }

    public function getName():string
    {
        return $this->name;
    }

    protected function getGeneratedName()
    {
        return "idx_" . $this->fields[0]->getTable()->getName() . "_" . $this->getImplodedFieldNames($this->fields);
    }

    protected function getImplodedFieldNames(array $fields):string
    {
        return StringUtils::implode("_", $fields, function ($_, $field) {
            /**
             * @var $field Field
             */
            return $field->getName();
        });
    }
}