<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database;


use mheinzerling\commons\ArrayUtils;
use mheinzerling\commons\StringUtils;

class DatabaseUtils
{
    const DUPLICATE_FAIL = 0;
    const DUPLICATE_IGNORE = 1;
    const DUPLICATE_UPDATE = 2;

    public static function insertAssoc(\PDO $pdo, string $table, array $data, int $onDuplicate = self::DUPLICATE_FAIL): int
    {
        $keys = array_keys($data);
        $sql = self::startInsert($table, $keys, $onDuplicate);
        $sql .= '(' . ':' . implode(',:', $keys) . ')';
        return self::onDuplicateAndExec($pdo, $onDuplicate, $keys, $sql, $data);
    }

    public static function insertMultiple(\PDO $pdo, string $table, array $keys, array $datas, int $onDuplicate = self::DUPLICATE_FAIL): int
    {
        $sql = self::startInsert($table, $keys, $onDuplicate);
        $row = "(" . substr(str_repeat("?, ", count($keys)), 0, -2) . "), ";
        $sql .= substr(str_repeat($row, count($datas)), 0, -2);
        foreach ($datas as &$data) ArrayUtils::fixOrderByKey($keys, $data);
        return self::onDuplicateAndExec($pdo, $onDuplicate, $keys, $sql, array_values(ArrayUtils::flatten($datas)));
    }

    private static function startInsert(string $table, array $keys, int $onDuplicate): string
    {
        $sql = 'INSERT ';
        if ($onDuplicate == self::DUPLICATE_IGNORE) $sql .= "IGNORE ";
        $sql .= 'INTO `' . $table . '`(`' . implode('`,`', $keys) . '`) ';
        $sql .= 'VALUES ';
        return $sql;
    }

    private static function onDuplicateAndExec(\PDO $pdo, int $onDuplicate, array $keys, string $sql, array $params): int
    {
        if ($onDuplicate == self::DUPLICATE_UPDATE) {
            /** @noinspection PhpUnusedParameterInspection */
            $updateAssignment = StringUtils::implode(", ", $keys, function ($_, $key) {
                return "`" . $key . "`=VALUES(`" . $key . "`)";
            });
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $updateAssignment;
        }
        $stmt = self::exec($pdo, $sql, $params);
        return $stmt->rowCount();
    }

    private static function prepareObjects(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if ($value instanceof \DateTime) {
                $value = $value->format("Y-m-d H:i:s");
            }
        }
    }

    public static function exec(\PDO $pdo, string $query, array $values): \PDOStatement
    {
        $stmt = $pdo->prepare($query);
        self::prepareObjects($values);
        $stmt->execute($values);
        return $stmt;
    }


    public static function importDump(\PDO $pdo, string $sqlFile): bool
    {
        $content = file_get_contents($sqlFile);
        $queries = explode(";\n", str_replace("\r", "", $content));
        $pdo->beginTransaction();
        foreach ($queries as $query) {
            $query = trim($query);
            try {
                if (StringUtils::startsWith($query, "/*!50503 select", true)) continue;
                if (StringUtils::startsWith($query, "SELECT", true)) continue;
                if (StringUtils::startsWith($query, "--", true)) continue;
                $pdo->exec($query);
            } catch (\PDOException $e) {
                if ($e->getMessage() == "SQLSTATE[HY000]: General error: trying to execute an empty query") continue;
                throw $e;
            }
        }
        return $pdo->commit();
    }
}