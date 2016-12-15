<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\database\structure\index\ForeignKey;
use mheinzerling\commons\database\structure\index\Index;
use mheinzerling\commons\database\structure\index\LazyForeignKey;
use mheinzerling\commons\database\structure\index\LazyIndex;
use mheinzerling\commons\database\structure\index\LazyUnique;
use mheinzerling\commons\StringUtils;


class Table
{
    /**
     * @var Database
     */
    private $database;
    /**
     * @var string
     */
    private $name;
    /**
     * @var Field[]
     */
    private $fields = [];
    /**
     * @var Index[]
     */
    private $indexes = [];
    /**
     * @var string
     */
    private $engine;
    /**
     * @var string
     */
    private $charset;
    /**
     * @var string
     */
    private $collation;
    /**
     * @var int
     */
    private $currentAutoincrement;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function init(string $engine = null, string $charset = null, string $collation = null, int $currentAutoincrement = null)
    {
        $this->engine = $engine;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->currentAutoincrement = $currentAutoincrement;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function addField(Field $field): void
    {
        $this->fields[$field->getName()] = $field;
        $field->setTable($this);
    }

    public function addIndex(Index $index): void
    {
        $this->indexes[$index->getName()] = $index;
        $index->setTable($this);
    }

    public function resolveLazyIndexes(): void
    {
        foreach ($this->indexes as &$index) {
            if (StringUtils::contains(get_class($index), "lazy")) {
                if ($index instanceof LazyIndex || $index instanceof LazyUnique) {
                    throw new \Exception("Found lazy index/unique that should have been resolved already");
                } else if ($index instanceof LazyForeignKey) {
                    $index = $index->toForeignKey($this->database);
                } else {
                    throw new \Exception("Found unknown lazy index that should have been resolved already or need to be added here");
                }
            }
        }
    }

    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }


    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasField(string $field): bool
    {
        return isset($this->fields[$field]);
    }

    /**
     * @param Table[] $tables
     * @return bool
     */
    public function hasForeignKeysOnlyOn(array $tables): bool
    {
        if (empty($this->indexes)) return true;
        $names = array_keys($tables);
        foreach ($this->indexes as $index) {
            if ($index instanceof ForeignKey) {
                if (!in_array($index->getReferenceTable()->getName(), $names)) return false;
            }
        }
        return true;
    }

    public function toCreateSql(SqlSetting $setting): string
    {
        $delimiter = $setting->singleLine ? " " : "\n";
        $sql = 'CREATE TABLE ';
        if ($setting->createTableIfNotExists) $sql .= 'IF NOT EXISTS ';
        $sql .= '`' . $this->name . "` (" . $delimiter;
        foreach ($this->fields as $field) {
            $sql .= ($setting->singleLine ? "" : "  ") . $field->toSql($setting) . "," . $delimiter;
        }
        if (empty($this->indexes)) $sql = substr($sql, 0, -2);
        foreach ($this->indexes as $index) {
            $sql .= ($setting->singleLine ? "" : "  ") . $index->toSql($setting) . "," . $delimiter;
        }
        if (!empty($this->indexes)) $sql = substr($sql, 0, -2);
        $delimiter = $setting->singleLine ? " " : "\n  ";
        $sql .= $delimiter . ")";
        if ($this->engine != null) $sql .= $delimiter . "ENGINE = " . $this->engine;
        if ($this->charset != null) $sql .= $delimiter . "DEFAULT CHARSET = " . $this->charset;
        if ($this->currentAutoincrement != null) $sql .= $delimiter . "AUTO_INCREMENT = " . $this->currentAutoincrement;
        $sql .= ";";
        return $sql;
    }

    public function toDropQuery(SqlSetting $setting): string
    {
        $sql = "DROP TABLE ";
        if ($setting->dropTableIfExists) $sql .= "IF EXISTS ";
        $sql .= "`" . $this->name . "`;";
        return $sql;
    }

    public function migrate(Migration $migration, Table $before, SqlSetting $setting): void
    {
        //TODO rename
        $meta = [];
        if ($this->engine != $before->engine) $meta[] = "ENGINE=" . $this->engine;
        if ($this->charset != $before->charset) $meta[] = "CHARACTER SET " . $this->charset;
        if ($this->collation != $before->collation) $meta[] = "COLLATE " . $this->collation;
        if ($this->currentAutoincrement != $before->currentAutoincrement) $meta[] = "AUTO_INCREMENT = " . $this->currentAutoincrement;
        if (count($meta) > 0) {
            $migration->tableMeta('ALTER TABLE `' . $this->name . '` ' . implode(", ", $meta));
        }

        $self = [];
        $fieldNames = ArrayUtils::mergeAndSortArrayKeys($this->fields, $before->fields);
        foreach ($fieldNames as $name) {
            if (!$this->hasField($name)) {
                $self[] = $before->fields[$name]->toAlterDropSql($setting);
                continue;
            }
            if (!$before->hasField($name)) {
                $self[] = $this->fields[$name]->roAlterAddSql($setting);
                continue;
            }
            $modify = $this->fields[$name]->modifySql($before->fields[$name], $setting);

            if ($modify != null) $self[] = $modify;
        }


        $foreign = [];
        $indexNames = ArrayUtils::mergeAndSortArrayKeys($this->indexes, $before->indexes);
        foreach ($indexNames as $name) {
            if (!isset($this->indexes[$name])) {
                if ($before->indexes[$name] instanceof ForeignKey) $foreign[] = $before->indexes[$name]->toAlterDropSql($setting);
                else $self[] = $before->indexes[$name]->toAlterDropSql($setting);
                continue;
            }
            if (!isset($before->indexes[$name])) {
                if ($this->indexes[$name] instanceof ForeignKey) $foreign[] = $this->indexes[$name]->toAlterAddSql($setting);
                else $self[] = $this->indexes[$name]->toAlterAddSql($setting);
                continue;
            }
            $modify = $this->indexes[$name]->modifySql($before->indexes[$name], $setting);
            if ($modify != null) {
                if ($this->indexes[$name] instanceof ForeignKey) $foreign[] = $modify;
                else $self[] = $modify;
            }
        }

        if (count($self) > 0) {
            $migration->tableStructure('ALTER TABLE `' . $this->name . '` ' . implode(", ", $self));
        }

        if (count($foreign) > 0) {
            $migration->tableKeys('ALTER TABLE `' . $this->name . '` ' . implode(", ", $foreign));
        }
    }

    public function same(Table $other): bool
    {
        return $this->database->same($other->database) && $this->name == $other->name;
    }

    public function toBuilderCode(): string
    {
        $result = "\n    ->table(\"" . $this->name . "\")";
        foreach ($this->indexes as $index) {
            $result .= $index->toBuilderCode();
        }
        if (count($this->indexes) > 0) $result .= "\n    ";
        if ($this->currentAutoincrement != null) $result .= '->autoincrement(' . $this->currentAutoincrement . ')';
        if ($this->charset != null) $result .= '->charset("' . $this->charset . '")';
        if ($this->collation != null) $result .= '->collation("' . $this->collation . '")';
        foreach ($this->fields as $field) {
            $result .= $field->toBuilderCode();
        }
        return $result;
    }
}