<?php

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;

class Migration
{

    private $statements = [100 => [], 200 => [], 300 => [], 400 => [], 500 => [], 800 => [], 999 => []];

    public function getStatements()
    {
        ksort($this->statements);
        return ArrayUtils::flatten($this->statements);
    }

    public function run(\PDO $pdo)
    {
        $pdo->beginTransaction();
        try {
            foreach ($this->getStatements() as $stmt) {
                $pdo->exec($stmt);
            }
            $pdo->commit();
        } catch (\Throwable $t) {
            if (isset($stmt)) var_dump($stmt);
            $pdo->rollBack();
            throw $t;
        }
    }

    public function dropTable($tableName, $statement)
    {
        $this->statements[100][] = $statement;
    }

    public function tableMeta($tableName, $statement)
    {
        $this->statements[200][] = $statement;
    }

    public function tableStructure($tableName, $statement)
    {
        $this->statements[400][] = $statement;
    }

    public function tableKeys($tableName, $statement)
    {
        $this->statements[800][] = $statement;
    }

    public function addTable($tableName, $statement)
    {
        $this->statements[500][] = $statement;
    }

    public function todo($statement)
    {
        $this->statements[999][] = $statement;
    }
}