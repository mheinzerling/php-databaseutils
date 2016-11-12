<?php

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\StringUtils;

class Table
{

    /**
     * @var string
     */
    private $name;
    /**
     * @var Field[]
     */
    private $fields;
    /**
     * @var string
     */
    private $engine;
    /**
     * @var string
     */
    private $charset;
    /**
     * @var int
     */
    private $currentAutoincrement;

    /**
     * DatabaseTable constructor.
     * @param $name
     * @param Field[] $fields
     * @param string|null $engine
     * @param string|null $charset
     * @param int|null $currentAutoincrement
     */
    function __construct($name, array $fields, string $engine = null, string $charset = null, int $currentAutoincrement = null)
    {
        $this->charset = $charset;
        $this->currentAutoincrement = $currentAutoincrement;
        $this->engine = $engine;
        $this->fields = $fields;
        $this->name = $name;
    }


    public static function fromDatabase(\PDO $connection, string $tableName):Table
    {
        $fields = [];
        $stmt = $connection->query("SHOW COLUMNS FROM " . $tableName);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $fields[$row['Field']] = new Field($row['Field'], $row['Type'], $row['Null'] != 'NO',
                $row['Key'] == 'PRI', $row['Key'] == 'UNI', $row['Default'], $row['Extra'] == 'auto_increment');
        }

        return new Table($tableName, $fields, null, null, null);
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getField(string $field):Field
    {
        return $this->fields[$field];
    }

    public function buildDropQuery(): string
    {
        return 'DROP TABLE `' . $this->name . '`;';
    }

    public function buildCreateQuery():string
    {
        return 'CREATE TABLE `' . $this->name . '` (...);'; //TODO
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