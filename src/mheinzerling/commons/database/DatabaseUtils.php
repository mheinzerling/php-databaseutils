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
}