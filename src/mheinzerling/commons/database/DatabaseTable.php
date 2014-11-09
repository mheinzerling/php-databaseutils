<?php

namespace mheinzerling\commons\database;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\StringUtils;
use Symfony\Component\Console\Helper\Table;

class DatabaseTable
{

    private $name;
    /**
     * @var DatabaseField[]
     */
    private $fields;
    private $engine;
    private $charset;
    private $currentAutoincrement;

    function __construct($name, array $fields, $engine, $charset, $currentAutoincrement)
    {
        $this->charset = $charset;
        $this->currentAutoincrement = $currentAutoincrement;
        $this->engine = $engine;
        $this->fields = $fields;
        $this->name = $name;
    }

    /**
     * @return DatabaseTable
     */
    public static function fromDatabase(\PDO $connection, $tableName)
    {
        $fields = array();
        $stmt = $connection->query("SHOW COLUMNS FROM " . $tableName);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $fields[$row['Field']] = new DatabaseField($row['Field'], $row['Type'], $row['Null'] != 'NO',
                $row['Key'] == 'PRI', $row['Key'] == 'UNI', $row['Default'], $row['Extra'] == 'auto_increment');
        }

        return new DatabaseTable($tableName, $fields, null, null, null);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function hasField($field)
    {
        return isset($this->fields[$field]);
    }

    /**
     * @return DatabaseField
     */
    public function getField($field)
    {
        return $this->fields[$field];
    }

    public function buildDropQuery()
    {
        return 'DROP TABLE `' . $this->name . '`;';
    }

    public function buildCreateQuery()
    {
        return 'CREATE TABLE `' . $this->name . '` (...);'; //TODO
    }


    public static function parseSqlCreate($sql)
    {
        //CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL)

        $fields = array();
        list($tableName, $remaining) = explode('(', $sql, 2);
        $tableName = trim(str_replace(array("CREATE TABLE", "IF NOT EXISTS"), "", $tableName));


        $b = 0;
        for ($c = 0, $s = strlen($remaining); $c < $s; $c++) {
            if ($remaining[$c] == '(') $b++;
            if ($remaining[$c] == ')') {
                $b--;
                if ($b < 0) break;
            }
        }
        $allFields = substr($remaining, 0, $c);
        $remaining = trim(trim(substr($remaining, $c), ")"));
        if (!empty($remaining)) throw new \Exception("TODO: rem >" . $remaining . "<");

        $carry = "";
        foreach (StringUtils::trimExplode(",", $allFields) as $field) {
            if (!empty($carry)) $field = $carry . "," . $field;
            if (substr_count($field, "(") != substr_count($field, ")")) //comma in enum
            {
                $carry = $field;
                continue;
            }

            $parts = explode(" ", $field, 3);
            $fieldName = $parts[0];
            $type = $parts[1];
            $other = isset($parts[2]) ? $parts[2] : "";
            $fieldName = trim($fieldName, '`');
            $type = strtolower($type);
            if ($type == "int") $type .= "(11)";
            $fields[$fieldName] =
                new DatabaseField($fieldName, $type,
                    !StringUtils::contains($other, 'NOT NULL'),
                    StringUtils::contains($other, 'PRIMARY KEY'),
                    StringUtils::contains($other, 'UNIQUE'),
                    null /*TODO*/,
                    StringUtils::contains($other, 'AUTO_INCREMENT')
                );
            $other = trim(str_replace(array('NOT NULL', 'NULL', 'PRIMARY KEY', 'UNIQUE', 'AUTO_INCREMENT'), "", $other));
            if (!empty($other)) throw new \Exception("TODO: other >" . $other . "<");


            $carry = "";
        }

        $engine = null; /*TODO*/
        $charset = null; /*TODO*/
        $currentAutoincrement = null; /*TODO*/
        return new DatabaseTable($tableName, $fields, $engine, $charset, $currentAutoincrement);
    }

    /**
     * @param $other DatabaseTable
     * @return String[]
     */
    public function compare(DatabaseTable $other)
    {
        $fields = ArrayUtils::mergeArrayKeys($this->getFields(), $other->getFields());
        $results = array();
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