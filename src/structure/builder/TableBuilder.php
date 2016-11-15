<?php

namespace mheinzerling\commons\database\structure\builder;


use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\index\Index;
use mheinzerling\commons\database\structure\index\LazyForeignKey;
use mheinzerling\commons\database\structure\index\LazyIndex;
use mheinzerling\commons\database\structure\index\LazyUnique;
use mheinzerling\commons\database\structure\index\Primary;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\Table;

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

    public function build():Database
    {
        return $this->complete()->build();
    }

    public function table(string $name):TableBuilder
    {
        return $this->complete()->table($name);
    }

    public function engine(string $engine):TableBuilder
    {
        $this->engine = $engine;
        return $this;
    }

    public function charset(string $charset):TableBuilder
    {
        $this->charset = $charset;
        return $this;
    }

    public function collation(string $collation):TableBuilder
    {
        $this->collation = $collation;
        return $this;
    }

    public function autoincrement(int $currentAutoincrement = null):TableBuilder
    {
        $this->currentAutoincrement = $currentAutoincrement;
        return $this;
    }

    private function complete():DatabaseBuilder
    {
        $this->table->init($this->engine != null ? $this->engine : $this->db->getDefaultEngine(),
            $this->charset != null ? $this->charset : $this->db->getDefaultCharset(),
            $this->collation != null ? $this->collation : $this->db->getDefaultCollation(),
            $this->currentAutoincrement);

        foreach ($this->indexes as $name => &$index) {
            if ($index instanceof LazyUnique) {
                $index = $index->toUnique($this->table->getFields());
            } else if ($index instanceof LazyIndex) {
                $index = $index->toIndex($this->table->getFields());
            }
            //TODO cleanup/foreign
            $this->table->addIndex($index);
        }


        return $this->db;
    }

    public function field(string $name):FieldBuilder
    {
        return new FieldBuilder($this, $name);
    }

    public function addField(Field $field)
    {
        $this->table->addField($field);
    }

    /**
     * @param string[] $fields
     * @param null $name
     * @return TableBuilder
     */
    public function unique(array $fields, $name = null):TableBuilder
    {
        return $this->addIndex(new LazyUnique($this->table->getName(), $fields, $name));
    }

    /**
     * @param string[] $fields
     * @param null $name
     * @return TableBuilder
     */
    public function index(array $fields, $name = null):TableBuilder
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
    public function foreign(array $fields, string $referenceTable, array $referenceFields, ReferenceOption $onUpdate, ReferenceOption $onDelete, string $name = null):TableBuilder
    {
        return $this->addIndex(new LazyForeignKey($this->table->getName(), $fields, $name, $referenceTable, $referenceFields, $onUpdate, $onDelete));
    }

    public function appendPrimary($field)
    {
        if (isset($this->indexes[Primary::PRIMARY])) $this->indexes[Primary::PRIMARY]->append($field);
        $this->indexes[Primary::PRIMARY] = new Primary([$field]);
    }

    public function addIndex(Index $index)
    {
        if (isset($this->indexes[$index->getName()]) && $index instanceof LazyForeignKey) {
            $this->indexes[$index->getName()]->append($index);
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
    public static function fromDatabase(\PDO $pdo, DatabaseBuilder $db, string $tableName, string $engine, string $collation, int $currentAutoincrement = null, array $booleanFields = [])
    {
        $tb = $db->table($tableName)->engine($engine)->charset(explode("_", $collation, 2)[0])->collation($collation)->autoincrement($currentAutoincrement);


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
        while ($fieldRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            FieldBuilder::fromDatabase($tb, $fieldRow, $fks, $booleanFields);
        }

        $tb->complete();
    }

    public static function parseSqlCreate(string $sql): Table
    {
        //CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL)

        $fields = [];
        list($tableName, $remaining) = explode('(', $sql, 2);
        $tableName = trim(str_replace(["CREATE TABLE", "IF NOT EXISTS", "`"], "", $tableName));


        $b = 0;
        for ($c = 0, $s = strlen($remaining); $c < $s; $c++) {
            if ($remaining[$c] == '(') $b++;
            if ($remaining[$c] == ')') {
                $b--;
                if ($b < 0) break;
            }
        }
        $allFields = substr($remaining, 0, $c);
        $remaining = trim(substr($remaining, $c), ")");

        $engine = StringUtils::findAndRemove($remaining, "@ENGINE\s*=\s*(\w+)@");
        $charset = StringUtils::findAndRemove($remaining, "@DEFAULT CHARSET\s*=\s*(\w+)@");
        $currentAutoincrement = StringUtils::findAndRemove($remaining, "@AUTO_INCREMENT\s*=\s*(\d+)@");

        $remaining = trim($remaining);

        if (!empty($remaining)) throw new \Exception("TODO: rem >" . $remaining . "<");

        $carry = "";
        foreach (StringUtils::trimExplode(",", $allFields) as $field) {
            if (!empty($carry)) $field = $carry . "," . $field;
            if (substr_count($field, "(") != substr_count($field, ")")) //comma in enum
            {
                $carry = $field;
                continue;
            }

            $field = preg_replace('/\s\s+/', ' ', $field);
            $parts = explode(" ", $field, 3);

            if ($parts[0] == 'PRIMARY') {
                //TODO
            } else if ($parts[0] == 'KEY') {
                //TODO
            } else if ($parts[0] == 'UNIQUE') {
                //TODO
            } else {
                $fieldName = $parts[0];
                $type = $parts[1];
                $other = isset($parts[2]) ? $parts[2] : "";
                $fieldName = trim($fieldName, '`');
                $type = strtolower($type);
                if ($type == "int") $type .= "(11)";
                $fields[$fieldName] =
                    new Field($fieldName, $type,
                        !StringUtils::contains($other, 'NOT NULL'),
                        StringUtils::contains($other, 'PRIMARY KEY'),
                        StringUtils::contains($other, 'UNIQUE'),
                        StringUtils::findAndRemove($other, "@DEFAULT '?(\w+)'?@"),
                        StringUtils::contains($other, 'AUTO_INCREMENT')
                    );
                $other = trim(str_replace(['NOT NULL', 'NULL', 'PRIMARY KEY', 'UNIQUE', 'AUTO_INCREMENT'], "", $other));
                if (!empty($other)) throw new \Exception("TODO: other >" . $other . "<");
            }

            $carry = "";
        }
        return new Table($tableName, $fields, $engine, $charset, $currentAutoincrement);
    }
}