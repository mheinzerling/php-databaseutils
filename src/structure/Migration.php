<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure;


use mheinzerling\commons\ArrayUtils;

class Migration
{

    /**
     * @var array
     */
    private $statements = [100 => [], 200 => [], 300 => [], 400 => [], 500 => [], 800 => [], 999 => []];

    public function getStatements(): array
    {
        ksort($this->statements);
        return ArrayUtils::flatten($this->statements);
    }

    public function run(\PDO $pdo): void
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

    public function dropTable(string $statement): void
    {
        $this->statements[100][] = $statement;
    }

    public function tableMeta(string $statement): void
    {
        $this->statements[200][] = $statement;
    }

    public function tableStructure(string $statement): void
    {
        $this->statements[400][] = $statement;
    }

    public function tableKeys(string $statement): void
    {
        $this->statements[800][] = $statement;
    }

    public function addTable(string $statement): void
    {
        $this->statements[500][] = $statement;
    }

    public function todo($statement): void
    {
        $this->statements[999][] = $statement;
    }
}