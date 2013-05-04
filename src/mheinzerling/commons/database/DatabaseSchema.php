<?php

namespace mheinzerling\commons\database;


use mheinzerling\commons\StringUtils;

class DatabaseSchema
{
    private $tables;

    public function getTables()
    {
        return $this->tables;
    }

    public static function fromDatabase(\PDO $connection)
    {
        $schema = new DatabaseSchema();
        $schema->tables = array();

        $stmt = $connection->query("SHOW TABLES");
        while ($table = $stmt->fetch(\PDO::FETCH_NUM)) {

            $schema->tables[$table[0]] = array();
        }

        foreach ($schema->tables as $table_name => $fields) {
            $stmt = $connection->query("SHOW COLUMNS FROM " . $table_name);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $schema->tables[$table_name][$row['Field']] = $row;
            }
        }
        return $schema;
    }

    public static function fromSql($sql)
    {
        $schema = new DatabaseSchema();
        $schema->tables = array();
        $queries = StringUtils::trimExplode(';', $sql);
        foreach ($queries as $query) {
            if (StringUtils::startsWith($query, "CREATE")) {
                list ($name, $fields) = DatabaseSchema::parseSqlCreate($query);
                $schema->tables[$name] = $fields;
            }
        }
        return $schema;
    }

    private static function parseSqlCreate($sql)
    {
        //CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL)

        $fields = array();
        list($tableName, $allFields) = explode('(', $sql, 2);
        $tableName = trim(str_replace("CREATE TABLE", "", $tableName));
        foreach (StringUtils::trimExplode(",", $allFields) as $field) {

            list($fieldName, $type, $other) = explode(" ", $field, 3);
            $fieldName = trim($fieldName, '`');
            $type = strtolower($type);
            if ($type == "int") $type .= "(11)";
            $fields[$fieldName] = array();
            $fields[$fieldName]['Field'] = $fieldName;
            $fields[$fieldName]['Type'] = $type;
            $fields[$fieldName]['Null'] = StringUtils::contains($other, 'NOT NULL') ? 'NO' : 'YES';
            $fields[$fieldName]['Key'] = StringUtils::contains($other, 'PRIMARY KEY') ? 'PRI' : (StringUtils::contains($other, 'UNIQUE') ? 'UNI' : null); //TODO MUL
            $fields[$fieldName]['Default'] = null; //TODO default
            $fields[$fieldName]['Extra'] = StringUtils::contains($other, 'AUTO_INCREMENT') ? 'auto_increment' : null;
        }
        return array($tableName, $fields);
    }


    private function mergeArrayKeys(array $me, array $other)
    {
        $myTableNames = array_keys($me);
        $otherTableNames = array_keys($other);
        $tables = array_unique(array_merge($myTableNames, $otherTableNames));
        sort($tables);
        return $tables;
    }


    /**
     * @param DatabaseSchema $otherSchema
     *
     * @return String[] operations to get from other to current schema
     */

    public function compare(DatabaseSchema $otherSchema)
    {
        $tables = $this->mergeArrayKeys($this->getTables(), $otherSchema->getTables());
        $myTables = $this->getTables();
        $otherTable = $otherSchema->getTables();
        $results = array();

        foreach ($tables as $table) {
            if (!isset($myTables[$table])) {
                $results[$table][] = 'DROP TABLE `' . $table . '`;';
                continue;
            }

            if (!isset($otherTable[$table])) {
                $results[$table][] = 'CREATE TABLE `' . $table . '` (...);'; //TODO
                continue;
            }

            $myTable = $myTables[$table];
            $otherTable = $otherTable[$table];
            $fields = $this->mergeArrayKeys($myTable, $otherTable);

            foreach ($fields as $field) {
                if (!isset($myTable[$field])) {
                    $results[$table][] = 'ALTER TABLE `' . $table . '` DROP COLUMN `' . $field . '`;';
                    continue;
                }

                if (!isset($otherTable[$field])) {
                    $results[$table][] = 'ALTER TABLE `' . $table . '` ADD `' . $field . '` ...;'; //TODO
                    continue;
                }


                $myParams = $myTable[$field];
                $othersParams = $otherTable[$field];
                $key = '';

                if ($myParams['Key'] == $othersParams['Key']) {
                    //do nothing
                } elseif ($myParams['Key'] != "PRI" && $othersParams['Key'] == "PRI") {
                    $key .= 'DROP PRIMARY KEY';
                } elseif ($myParams['Key'] == "PRI" && $othersParams['Key'] != "PRI") {
                    $key .= 'ADD PRIMARY KEY(`' . $field . '`)';
                } else {
                    $results[$table][] = 'Alter column index: ' . $table . '.' . $field . ' (' . $othersParams['Key'] . '=>' . $myParams['Key'] . ') ';
                }

                $diff = false;
                foreach ($myParams as $name => $details) {
                    if ($name == 'Key') continue;
                    if ($myParams[$name] != $othersParams[$name]) {
                        $diff = true;
                        break;
                    }
                }
                if ($diff) {
                    $mod = trim('MODIFY `' . $field . '` ' . strtoupper($myParams['Type']) . ' ' .
                        ($myParams['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' ' . strtoupper($myParams['Extra']));
                } else $mod = '';
                if ($key != '' && $diff) $key = ', ' . $key;

                if ($key != '' || $diff) {
                    $results[$table][] = 'ALTER TABLE `' . $table . '` ' . $mod . '' . $key;
                }

            }
        }
        return $results;
    }
}