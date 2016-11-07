<?php

namespace mheinzerling\commons\database;


class DatabaseUtils
{

    public static function insertAssoc(\PDO $conn, string $table, array $data):bool
    {
        $data = DatabaseUtils::prepareObjects($data);
        $bind = ':' . implode(',:', array_keys($data));
        $sql = 'INSERT INTO `' . $table . '`(`' . implode('`,`', array_keys($data)) . '`) ' . 'VALUES (' . $bind . ')';
        $stmt = $conn->prepare($sql);
        return $stmt->execute(array_combine(explode(',', $bind), array_values($data)));
    }

    public static function insertOrUpdateAssoc(\PDO $conn, string $table, array $data):bool
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
        return $stmt->execute($data);
    }

    public static function prepareObjects(array $data):array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format("Y-m-d H:i:s");
            }
            $result[$key] = $value;
        }
        return $result;
    }

    public static function exec(\PDO $connection, string $query, array $values):\PDOStatement
    {
        $stmt = $connection->prepare($query);
        $stmt->execute(self::prepareObjects($values));
        return $stmt;
    }

    public static function executeFile(\PDO $connection, string $sqlFileName):int
    {
        return $connection->exec(file_get_contents($sqlFileName));
    }

    public static function insertMultiple(\PDO $connection, string $table, array $data):bool
    {
        $queries = self::toInsertQueries($table, $data);

        $connection->beginTransaction();
        foreach ($queries as $query) {
            $connection->exec($query);
        }
        return $connection->commit();
    }

    /**
     * @param string $table
     * @param array $data
     * @param int $chunkSize
     * @param bool $ignoreDuplicate
     * @return array|\string[]
     */
    public static function toInsertQueries(string $table, array $data, $chunkSize = 1000, $ignoreDuplicate = true):array
    {
        $keys = array_keys(reset($data));

        $chunks = array_chunk($data, $chunkSize);
        $queries = [];
        foreach ($chunks as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $keySortedData = [];
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

    public static function importDump(\PDO $connection, string $sqlFile):bool
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
        return $connection->commit();
    }
}