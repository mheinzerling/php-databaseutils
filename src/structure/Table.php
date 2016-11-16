<?php

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

    /**
     * @param string|null $engine
     * @param string|null $charset
     * @param string|null $collation
     * @param int|null $currentAutoincrement
     */
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

    public function addField(Field $field)
    {
        $this->fields[$field->getName()] = $field;
        $field->setTable($this);
    }

    public function addIndex(Index $index)
    {
        $this->indexes[$index->getName()] = $index;
    }

    public function resolveLazyIndexes()
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

    public function hasField(string $field):bool
    {
        return isset($this->fields[$field]);
    }

    /**
     * @param Table[] $tables
     * @return bool
     */
    public function hasForeignKeysOnlyOn(array $tables):bool
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

    public function toCreateSql(SqlSetting $setting):string
    {
        $sql = 'CREATE TABLE ';
        if ($setting->createTableIfNotExists) $sql .= 'IF NOT EXISTS ';
        $sql .= '`' . $this->name . "` (\n";
        foreach ($this->fields as $field) {
            $sql .= "  " . $field->toSql($setting) . ",\n";
        }
        if (empty($this->indexes)) $sql = substr($sql, 0, -2);
        foreach ($this->indexes as $index) {
            $sql .= "  " . $index->toSql($setting) . ",\n";
        }
        if (!empty($this->indexes)) $sql = substr($sql, 0, -2);
        $sql .= "\n  )";
        if ($this->engine != null) $sql .= "\n  ENGINE = " . $this->engine;
        if ($this->charset != null) $sql .= "\n  DEFAULT CHARSET = " . $this->charset;
        if ($this->currentAutoincrement != null) $sql .= "\n  AUTO_INCREMENT = " . $this->currentAutoincrement;
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


    /**
     * @param Table $other
     * @param SqlSetting $setting
     * @return \string[]
     */
    public function compare(Table $other, SqlSetting $setting)
    {
        $fields = ArrayUtils::mergeAndSortArrayKeys($this->getFields(), $other->getFields());
        $results = [];
        foreach ($fields as $field) {
            if (!$this->hasField($field)) {
                $results[] = $other->getFields()[$field]->toDropSql($setting);
                continue;
            }
            if (!$other->hasField($field)) {
                $results[] = $this->getFields()[$field]->toCreateSql($setting);
                continue;
            }
            $results = array_merge($results, $this->getFields()[$field]->compare($other->getFields()[$field], $this->name));
        }

        /*        if (!$this->primary && $other->primary) {
                    $key .= 'DROP PRIMARY KEY';
                } elseif ($this->primary && !$other->primary) {
                    $key .= 'ADD PRIMARY KEY(`' . $this->name . '`)';
                }

                if ($this->unique != $other->unique) {
                    $results[] = 'Alter column index: ' . $tableName . '.' . $this->name . ' (' . $other->unique . '=>' . $this->unique . ') ';
                }*/


        return $results;
    }

}