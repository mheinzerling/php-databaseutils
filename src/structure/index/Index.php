<?php

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\builder\TableBuilder;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\SqlSetting;
use mheinzerling\commons\database\structure\Table;
use mheinzerling\commons\StringUtils;

class Index
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var Field[]
     */
    protected $fields;

    /**
     * @var string
     */
    private $name;

    public function __construct(array $fields = null, string $name = null)
    {
        $this->fields = $fields;
        $this->name = $name;
    }


    /**
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return array_map(function (Field $f) {
            $f->getName();
        }, $this->fields);
    }


    /**
     * @param TableBuilder $tb
     * @param string $constraint
     * @return void
     */
    public static function fromSql(TableBuilder $tb, string $constraint)
    {
        //TODO more robust
        if (StringUtils::contains($constraint, "FOREIGN KEY", true)) {
            $onUpdate = ReferenceOption::memberByValueWithDefault(StringUtils::findAndRemove($constraint, "@ON UPDATE (RESTRICT|CASCADE|SET NULL|NO ACTION)@i"), null);
            $onDelete = ReferenceOption::memberByValueWithDefault(StringUtils::findAndRemove($constraint, "@ON DELETE (RESTRICT|CASCADE|SET NULL|NO ACTION)@i"), null);
            $parts = explode("FOREIGN KEY", $constraint);
            $name = trim(str_replace("CONSTRAINT ", "", $parts[0]), "`\t\r\n ");

            $parts = explode("REFERENCES", $parts[1]);
            $fieldNames = StringUtils::trimExplode(",", $parts[0], StringUtils::TRIM_DEFAULT . "`()");
            $parts = explode("(", $parts[1]);
            $referenceTableName = trim($parts[0], "`\t\r\n ");
            $referenceFieldNames = StringUtils::trimExplode(",", $parts[1], StringUtils::TRIM_DEFAULT . "`()");
            $tb->foreign($fieldNames, $referenceTableName, $referenceFieldNames, $onUpdate, $onDelete, $name);
        } else {
            $primary = StringUtils::findAndRemove($constraint, "@(primary key)@i") != null;
            $unique = StringUtils::findAndRemove($constraint, "@(unique key)@i") != null;
            $details = StringUtils::trimExplode("(", $constraint);
            $name = trim($details[0], "`\t\r\n ");
            if (StringUtils::startsWith($constraint, "KEY ", true)) $name = trim(substr($name, 4), "`");
            $values = StringUtils::trimExplode(",", $details[1], StringUtils::TRIM_DEFAULT . "`)");

            if ($primary) $tb->primary($values);
            else if ($unique) $tb->unique($values, $name);
            else  $tb->index($values, $name);

        }

    }

    public function getName(): string
    {
        if ($this->name == null) $this->name = $this->getGeneratedName();
        return $this->name;
    }

    protected function getGeneratedName()
    {
        return "idx_" . $this->table->getName() . "_" . $this->getImplodedFieldNames($this->fields);
    }

    protected function getImplodedFieldNames(array $fields): string
    {
        return StringUtils::implode("_", $fields, function ($_, $field) {
            /**
             * @var $field Field
             */
            return $field->getName();
        });
    }

    public function toSql(SqlSetting $setting): string
    {
        $sql = "KEY ";
        if ($this->name != null) $sql .= "`" . $this->name . "` ";
        $sql .= "(`" . implode("`, `", array_keys($this->fields)) . "`)";
        return $sql;
    }

    public function toAlterAddSql(SqlSetting $setting): string
    {
        return "ADD " . $this->toSql($setting);
    }

    public function toAlterDropSql(SqlSetting $setting): string
    {
        return "DROP KEY `" . $this->name . "`";
    }

    /**
     * @param Index $before
     * @param SqlSetting $setting
     * @return string|null
     */
    public function modifySql(Index $before, SqlSetting $setting)
    {
        if ($this->same($before)) return null;
        return "TODO: change index " . $this->name;
    }

    public function same(Index $other): bool
    {
        return get_class($this) == get_class($other) && $this->table->same($other->table) && array_keys($this->fields) == array_keys($other->fields);
    }

    public function toBuilderCode(): string
    {
        return '->index(["' . implode('", "', array_keys($this->fields)) . '"])';
    }


}