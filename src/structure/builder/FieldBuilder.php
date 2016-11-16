<?php

namespace mheinzerling\commons\database\structure\builder;


use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\index\Index;
use mheinzerling\commons\database\structure\index\LazyForeignKey;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\index\Unique;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\StringUtils;

class FieldBuilder
{
    /**
     * @var Field
     */
    private $field;
    /**
     * @var TableBuilder
     */
    private $tb;
    /**
     * @var Type
     */
    private $type;
    /**
     * @var bool
     */
    private $null = false;
    /**
     * @var string|null
     */
    private $default = null;
    /**
     * @var bool
     */
    private $autoincrement = false;

    /**
     * @var bool
     */
    private $primary = false;
    /**
     * @var bool
     */
    private $unique = false;
    /**
     * @var bool
     */
    private $index = false;
    /**
     * @var array
     */
    private $foreign = null;


    public function __construct(TableBuilder $tb, string $name)
    {
        $this->tb = $tb;
        $this->field = new Field($name);
        $tb->addField($this->field);
    }

    public function build():Database
    {
        return $this->complete()->build();
    }

    public function table(string $name):TableBuilder
    {
        return $this->complete()->table($name);
    }

    public function field(string $name):FieldBuilder
    {
        return $this->complete()->field($name);
    }

    private function complete(): TableBuilder
    {
        $this->field->init($this->type, $this->null, $this->default, $this->autoincrement);
        if ($this->primary) $this->tb->appendPrimary($this->field);
        if ($this->unique) $this->tb->addIndex(new Unique([$this->field]));
        if ($this->index) $this->tb->addIndex(new Index([$this->field]));
        if ($this->foreign != null) $this->tb->addIndex(new LazyForeignKey($this->field->getTable()->getName(), [$this->field->getName()], $this->foreign[0], $this->foreign[1], [$this->foreign[2]], $this->foreign[3], $this->foreign[4]));
        return $this->tb;
    }

    public function type(Type $type):FieldBuilder
    {
        $this->type = $type;
        return $this;
    }

    public function null(bool $null = true):FieldBuilder
    {
        $this->null = $null;
        return $this;
    }

    public function default(string $default = null):FieldBuilder
    {
        $this->default = $default;
        return $this;
    }

    public function autoincrement(bool $autoincrement = true):FieldBuilder
    {
        $this->autoincrement = $autoincrement;
        return $this;
    }

    public function primary(bool $primary = true):FieldBuilder
    {
        $this->primary = $primary;
        return $this;
    }

    public function unique(bool $unique = true):FieldBuilder
    {
        $this->unique = $unique;
        return $this;
    }

    public function index(bool $index = true):FieldBuilder
    {
        $this->index = $index;
        return $this;
    }

    public function foreign(string $name = null, string $table, string $reference, ReferenceOption $onUpdate, ReferenceOption $onDelete):FieldBuilder
    {

        $this->foreign = [$name, $table, $reference, $onUpdate, $onDelete];
        return $this;
    }

    /**
     * @param TableBuilder $tb
     * @param array $fieldRow
     * @param array $fkRows
     * @param string[] $booleanFields
     */
    public static function fromDatabase(TableBuilder $tb, array $fieldRow, array $fkRows, array $booleanFields = [])
    {
        $fb = $tb->field($fieldRow['Field'])
            ->typeFromSql($fieldRow['Type'], $fieldRow['Collation'], $booleanFields)
            ->null($fieldRow['Null'] != 'NO')
            ->default($fieldRow['Default'])
            ->autoincrement($fieldRow['Extra'] == 'auto_increment')
            ->primary($fieldRow['Key'] == 'PRI');
        foreach ($fkRows as $fkRow) {
            if ($fkRow['COLUMN_NAME'] == $fieldRow['Field']) {
                $fb->foreign($fkRow['CONSTRAINT_NAME'], $fkRow['REFERENCED_TABLE_NAME'], $fkRow['REFERENCED_COLUMN_NAME'],
                    ReferenceOption::memberByValue($fkRow['UPDATE_RULE']), ReferenceOption::memberByValue($fkRow['DELETE_RULE']));
            }
        }
        $fb->complete();
    }

    private function typeFromSql($sqlTypeString, $collation, array $booleanFields = [])
    {
        return $this->type(Type::fromSql($sqlTypeString, $collation, in_array($this->field->getFullName(), $booleanFields)));
    }

    public static function fromSql(TableBuilder $tb, string $field)
    {
        if (
            StringUtils::startsWith($field, "PRIMARY") ||
            StringUtils::startsWith($field, "UNIQUE") ||
            StringUtils::startsWith($field, "KEY") ||
            StringUtils::startsWith($field, "FOREIGN") ||
            StringUtils::startsWith($field, "CONSTRAINT")
        ) {
            Index::fromSql($tb, $field);
            return;
        }

        $name = explode(" ", $field, 2)[0];
        $remaining = str_replace($name, "", $field);
        $fb = $tb->field(trim($name, '`'))
            ->primary(StringUtils::findAndRemove($remaining, '@(PRIMARY KEY)@i') != null)
            ->null(StringUtils::findAndRemove($remaining, '@(NOT NULL)@i') == null)
            ->autoincrement(StringUtils::findAndRemove($remaining, '@(AUTO_INCREMENT)@i') != null);
        $default = StringUtils::findAndRemove($remaining, "@DEFAULT '([^']*)'@i");
        if ($default == null) $default = StringUtils::findAndRemove($remaining, "@DEFAULT (\d+)@i"); //TODO double etc
        $fb->default($default);
        $remaining = trim(str_replace(["DEFAULT", "NULL"], "", $remaining));
        $fb->type(Type::fromSql($remaining)); //$remaining should be only the type,everything else will fail
        $fb->complete();
    }


}