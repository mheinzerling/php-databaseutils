<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\builder;


use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\index\Index;
use mheinzerling\commons\database\structure\index\LazyForeignKey;
use mheinzerling\commons\database\structure\index\LazyIndex;
use mheinzerling\commons\database\structure\index\LazyPrimary;
use mheinzerling\commons\database\structure\index\LazyUnique;
use mheinzerling\commons\database\structure\index\Primary;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\Table;
use mheinzerling\commons\StringUtils;

class TableBuilder
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var DatabaseBuilder
     */
    private $db;

    /**
     * @var Index[];
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
     * @var int|null
     */
    private $currentAutoincrement = null;

    public function __construct(DatabaseBuilder $db, string $name)
    {
        $this->db = $db;
        $this->table = new Table($name);
        $this->db->addTable($this->table);
    }


    public function build(): Database
    {
        return $this->complete()->build();
    }

    public function table(string $name): TableBuilder
    {
        return $this->complete()->table($name);
    }

    public function engine(string $engine = null): TableBuilder
    {
        $this->engine = $engine;
        return $this;
    }

    public function charset(string $charset = null): TableBuilder
    {
        $this->charset = $charset;
        return $this;
    }

    public function collation(string $collation): TableBuilder
    {
        $this->collation = $collation;
        return $this;
    }

    public function autoincrement(int $currentAutoincrement = null): TableBuilder
    {
        $this->currentAutoincrement = $currentAutoincrement;
        return $this;
    }

    public function complete(): DatabaseBuilder
    {
        $this->table->init($this->engine != null ? $this->engine : $this->db->getDefaultEngine(),
            $this->charset != null ? $this->charset : $this->db->getDefaultCharset(),
            $this->collation != null ? $this->collation : $this->db->getDefaultCollation(),
            $this->currentAutoincrement);

        foreach ($this->indexes as $name => &$index) {
            if ($index instanceof LazyUnique) {
                $index = $index->toUnique($this->table);
            } else if ($index instanceof LazyIndex) {
                $index = $index->toIndex($this->table);
            } else if ($index instanceof LazyPrimary) {
                $index = $index->toPrimary($this->table);
            }

            $this->table->addIndex($index);
        }


        return $this->db;
    }

    public function field(string $name): FieldBuilder
    {
        return new FieldBuilder($this, $name);
    }

    public function addField(Field $field): void
    {
        $this->table->addField($field);
    }

    /**
     * @param string[] $fields
     * @param null $name
     * @return TableBuilder
     */
    public function unique(array $fields, $name = null): TableBuilder
    {
        return $this->addIndex(new LazyUnique($this->table->getName(), $fields, $name));
    }

    /**
     * @param string[] $fields
     * @param null $name
     * @return TableBuilder
     */
    public function index(array $fields, $name = null): TableBuilder
    {
        return $this->addIndex(new LazyIndex($this->table->getName(), $fields, $name));
    }

    /**
     * @param string[] $fields
     * @param string $referenceTable
     * @param string[] $referenceFields
     * @param ReferenceOption $onUpdate
     * @param ReferenceOption $onDelete
     * @param string|null $name
     * @return TableBuilder
     */
    public function foreign(array $fields, string $referenceTable, array $referenceFields, ReferenceOption $onUpdate, ReferenceOption $onDelete, string $name = null): TableBuilder
    {
        return $this->addIndex(new LazyForeignKey($this->table->getName(), $fields, $name, $referenceTable, $referenceFields, $onUpdate, $onDelete));
    }


    /**
     * @param string[] $fields
     * @return TableBuilder
     */
    public function primary(array $fields): TableBuilder
    {
        return $this->addIndex(new LazyPrimary($fields));
    }

    public function appendPrimary(Field $field): void
    {
        if (isset($this->indexes[Primary::PRIMARY])) {
            /**
             * @var $primary Primary
             */
            $primary = $this->indexes[Primary::PRIMARY];
            if ($primary instanceof LazyPrimary) throw new \Exception("Unsupported");
            $primary->append($field);
        } else $this->indexes[Primary::PRIMARY] = new Primary([$field->getName() => $field]);
    }

    public function addIndex(Index $index): TableBuilder
    {
        $index->setTable($this->table);
        if (isset($this->indexes[$index->getName()]) && $index instanceof LazyForeignKey) {
            /**
             * @var $lazyFk LazyForeignKey
             */
            $lazyFk = $this->indexes[$index->getName()];
            $lazyFk->append($index);
        } else {
            $this->indexes[$index->getName()] = $index;
        }
        return $this;
    }

    /**
     * @param \PDO $pdo
     * @param DatabaseBuilder $db
     * @param string $tableName
     * @param string $engine
     * @param string $collation
     * @param int $currentAutoincrement
     * @param string[] $booleanFields
     */
    public static function fromDatabase(\PDO $pdo, DatabaseBuilder $db, string $tableName, string $engine, string $collation, string $currentAutoincrement = null, array $booleanFields = []): void
    {
        $tb = $db->table($tableName)->engine($engine)->charset(explode("_", $collation, 2)[0])->collation($collation)->autoincrement(intval($currentAutoincrement));


        $fks = $pdo->query('SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS k, INFORMATION_SCHEMA.referential_constraints AS r
            WHERE k.CONSTRAINT_NAME=r.CONSTRAINT_NAME AND
                  k.TABLE_SCHEMA = "' . $db->getName() . '" AND r.constraint_schema = "' . $db->getName() . '" AND 
                  k.TABLE_NAME = "' . $tableName . '" AND r.TABLE_NAME = "' . $tableName . '" AND  
                  k.REFERENCED_COLUMN_NAME IS NOT NULL
        ')->fetchAll(\PDO::FETCH_ASSOC);
        $ignore = [Primary::PRIMARY];
        foreach ($fks as $fk) $ignore[] = $fk['CONSTRAINT_NAME'];

        $stmt = $pdo->query('SHOW INDEXES FROM `' . $tableName . '` WHERE `Key_name` NOT IN ("' . implode('","', $ignore) . '")');
        $indexes = [];
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($indexRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $name = $indexRow['Key_name'];
            if (!isset($indexes[$name])) $indexes[$name] = ['unique' => $indexRow['Non_unique'] == 0, 'fieldNames' => []];
            $indexes[$name]['fieldNames'][] = $indexRow['Column_name'];

        }
        foreach ($indexes as $name => $index) {
            if ($index['unique'] === true) $tb->unique($index['fieldNames'], $name);
            else  $tb->index($index['fieldNames'], $name);
        }

        $stmt = $pdo->query("SHOW FULL COLUMNS FROM `" . $tableName . "`");
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($fieldRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            FieldBuilder::fromDatabase($tb, $fieldRow, $fks, $booleanFields);
        }

        $tb->complete();
    }

    /**
     * @param DatabaseBuilder $db
     * @param string $sql
     * @return void
     * @throws \Exception
     */
    public static function fromSqlCreate(DatabaseBuilder $db, string $sql): void
    {
        list($tableName, $remaining) = explode('(', $sql, 2);
        $tableName = trim(str_replace(["CREATE TABLE", "IF NOT EXISTS", "`"], "", $tableName));
        $tb = $db->table($tableName);

        list($allFields, $remaining) = self::splitFieldsAndConfiguration($remaining);

        $tb->engine(StringUtils::findAndRemove($remaining, "@ENGINE\s*=\s*(\w+)@i"))
            ->charset(StringUtils::findAndRemove($remaining, "@DEFAULT CHARSET\s*=\s*(\w+)@i"))
            ->autoincrement(intval(StringUtils::findAndRemove($remaining, "@AUTO_INCREMENT\s*=\s*(\d+)@i")));

        $remaining = trim($remaining);
        if (!empty($remaining)) throw new \Exception("TODO: unhandled SQL CREATE TABLE statement configuration parts >" . $remaining . "<");

        $carry = "";
        foreach (StringUtils::trimExplode(",", $allFields) as $field) {
            if (!empty($carry)) $field = $carry . "," . $field;
            if (substr_count($field, "(") != substr_count($field, ")")) //comma in enum
            {
                $carry = $field;
                continue;
            }
            $field = preg_replace('/\s\s+/', ' ', $field);
            FieldBuilder::fromSql($tb, $field);
            $carry = "";
        }
        $tb->complete();
    }

    /**
     * @param $remaining
     * @return string[]
     */
    protected static function splitFieldsAndConfiguration(string $remaining): array
    {
        $pos = StringUtils::findMatchingBracketPos("(" . $remaining) - 1;
        $allFields = substr($remaining, 0, $pos);
        $remaining = trim(substr($remaining, $pos), ")");
        return [$allFields, $remaining];
    }

}