<?php

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\database\structure\index\Index;
use mheinzerling\commons\database\structure\index\LazyForeignKey;
use mheinzerling\commons\database\structure\index\LazyIndex;
use mheinzerling\commons\database\structure\index\LazyUnique;
use mheinzerling\commons\StringUtils;
use Symfony\Component\Config\Definition\Exception\Exception;


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
                    throw new \Exception("Found lazy index/unqiue that should have been resolved already");
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

    public function buildDropQuery(): string
    {
        return 'DROP TABLE `' . $this->name . '`;';
    }

    public function buildCreateQuery():string
    {
        return 'CREATE TABLE `' . $this->name . '` (...);'; //TODO
    }


    /**
     * @param $other Table
     * @return string[]
     */
    public function compare(Table $other)
    {
        $fields = ArrayUtils::mergeAndSortArrayKeys($this->getFields(), $other->getFields());
        $results = [];
        foreach ($fields as $field) {
            if (!$this->hasField($field)) {
                $results[] = $other->getField($field)->buildDropQuery($this->name);
                continue;
            }
            if (!$other->hasField($field)) {
                $results[] = $this->getField($field)->buildAddQuery($this->name);
                continue;
            }
            $results = array_merge($results, $this->getField($field)->compare($other->getField($field), $this->name));

        }
        return $results;
    }

}