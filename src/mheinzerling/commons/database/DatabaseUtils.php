<?php

namespace mheinzerling\commons\database;


class DatabaseUtils
{

    public static function insertAssoc(\PDO $conn, $table, array $data)
    {
        $data = DatabaseUtils::prepareObjects($data);
        $bind = ':' . implode(',:', array_keys($data));
        $sql = 'INSERT INTO `' . $table . '`(`' . implode('`,`', array_keys($data)) . '`) ' . 'VALUES (' . $bind . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_combine(explode(',', $bind), array_values($data)));
    }

    public static function insertOrUpdateAssoc(\PDO $conn, $table, array $data)
    {
        $data = DatabaseUtils::prepareObjects($data);
        $insertValues = ':' . implode(',:', array_keys($data));
        $updateAssignment = '';
        foreach ($data as $key => $value) {
            $updateAssignment .= "`" . $key . "`=:u" . $key . ", ";
        }
        $updateAssignment = substr($updateAssignment, 0, -2);
        $sql = 'INSERT INTO `' . $table . '`(`' . implode('`,`', array_keys($data)) . '`) ' . 'VALUES (' . $insertValues . ') ' .
            ' ON DUPLICATE KEY UPDATE ' . $updateAssignment;
        $stmt = $conn->prepare($sql);

        foreach ($data as $k => $v) {
            $data['u' . $k] = $v;
        }
        $stmt->execute($data);
    }

    public static function prepareObjects(array $data)
    {
        $result = array();
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format("Y-m-d H:i:s");
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return \PDOStatement
     */
    public static function exec(\PDO $connection, $query, $values)
    {
        $stmt = $connection->prepare($query);
        $stmt->execute(self::prepareObjects($values));
        return $stmt;
    }

    public static function executeFile(\PDO $connection, $sqlFileName)
    {
        $connection->exec(file_get_contents($sqlFileName));
    }

    public static function insertMultiple(\PDO $connection, $table, array $data)
    {
        $queries = self::toInsertQueries($table, $data);

        $connection->beginTransaction();
        foreach ($queries as $query) {
            $connection->exec($query);
        }
        $connection->commit();
    }

    /**
     * Private.
     *
     * @param $table
     * @param $data
     * @return Array
     */
    public static function toInsertQueries($table, array $data, $chunkSize = 1000, $ignoreDuplicate = true)
    {
        $keys = array_keys(reset($data));

        $chunks = array_chunk($data, $chunkSize);
        $queries = array();
        foreach ($chunks as $chunk) {
            $values = array();
            foreach ($chunk as $row) {
                $keySortedData = array();
                foreach ($keys as $key) {
                    $content = $row[$key];
                    if (is_null($content)) $content = "NULL";
                    else if (!is_int($content)) $content = "'" . $content . "'";
                    $keySortedData[] = $content;
                }
                $values[] = "(" . implode(",", $keySortedData) . ")";
            }
            $query = "INSERT " . ($ignoreDuplicate ? "IGNORE " : "") . "INTO `$table`(`" . implode("`,`", $keys) . "`) VALUES " . implode(",", $values);
            $queries[] = $query;
        }
        return $queries;
    }

    public static function importDump(\PDO $connection, $sqlFile)
    {
        $content = file_get_contents($sqlFile);
        $queries = explode(";\n", $content);
        $connection->beginTransaction();
        foreach ($queries as $query) {
            try {
                $connection->exec($query);
            } catch (\PDOException $e) {
                if ($e->getMessage() == "SQLSTATE[HY000]: General error: trying to execute an empty query") continue;
                throw $e;
            }
        }
        $connection->commit();
    }
}