<?php

namespace mheinzerling\commons\database\structure\builder;

use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\database\TestDatabaseConnection;

class DatabaseBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromDatabaseEmpty()
    {
        $pdo = new TestDatabaseConnection();
        $database = DatabaseBuilder::fromDatabase($pdo, []);
        static::assertEquals([], $database->getTables());
    }

    public function testLoadFromDatabaseSimple()
    {
        $pdo = new TestDatabaseConnection();
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $pdo->exec($sql);

        $table = DatabaseUtils::exec($pdo, 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA=?', [$pdo->getDatabaseName()])->fetch(\PDO::FETCH_ASSOC);
        $expected = (new DatabaseBuilder($pdo->getDatabaseName()))->defaultEngine($table['ENGINE'])->defaultCharset(explode("_", $table['TABLE_COLLATION'])[0])->defaultCollation($table['TABLE_COLLATION'])
            ->table("revision")->autoincrement(1)
            ->field("id")->type(Type::int(11))->primary()->autoincrement()
            ->field("class")->type(Type::varchar(255, $table['TABLE_COLLATION']))
            ->field("lastExecution")->type(Type::datetime())->null()
            ->build();
        static::assertEquals($expected, DatabaseBuilder::fromDatabase($pdo));
    }

    public function testLoadFromDatabase_full()
    {
        $pdo = new TestDatabaseConnection();
        DatabaseUtils::importDump($pdo, realpath(__DIR__ . "/../../../resources/full.sql"));
        $expected = (new DatabaseBuilder($pdo->getDatabaseName()))->defaultEngine("InnoDB")->defaultCharset("latin1")->defaultCollation("latin1_swedish_ci")
            ->table("credential")->unique(["provider", "user"])->index(["provider", "uid", "user"])
            ->field("provider")->type(Type::varchar(255, "latin1_swedish_ci"))->primary()
            ->field("uid")->type(Type::varchar(255, "latin1_swedish_ci"))->primary()
            ->field("user")->type(Type::int(11))->null()->foreign(null, "user", "id", ReferenceOption::CASCADE(), ReferenceOption::CASCADE())
            ->table("user")->autoincrement(2)->charset("utf8")->collation("utf8_general_ci")
            ->field("id")->type(Type::int(11))->primary()->autoincrement()
            ->field("nick")->type(Type::varchar(100, "utf8_general_ci"))->unique()
            ->field("birthday")->type(Type::datetime())->null()
            ->field("active")->type(Type::bool())->default('0')
            ->field("gender")->type(Type::enum(['m', 'f']))->null()->index()
            ->table("payload")->foreign(["cprovider", "cuid"], "credential", ["provider", "uid"], ReferenceOption::NO_ACTION(), ReferenceOption::NO_ACTION())
            ->field("payload")->type(Type::int(11))->null()
            ->field("cprovider")->type(Type::varchar(255, "latin1_swedish_ci"))
            ->field("cuid")->type(Type::varchar(255, "latin1_swedish_ci"))
            ->build();
        $actual = DatabaseBuilder::fromDatabase($pdo, ["user.active"]);
        static::assertEquals($expected, $actual);
    }

    public function testLoadFromSqlEmpty()
    {
        $sql = "";
        $expected = (new DatabaseBuilder(""))->build();
        static::assertEquals($expected, DatabaseBuilder::fromSql($sql));
    }

    public function testLoadFromSqlSimple()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $expected = (new DatabaseBuilder(""))
            ->table("revision")
            ->field("id")->type(Type::int())->primary()->autoincrement()
            ->field("class")->type(Type::varchar(255))
            ->field("lastExecution")->type(Type::datetime())->null()
            ->build();
        static::assertEquals($expected, DatabaseBuilder::fromSql($sql));

    }

    public function testLoadFromSqlFull()
    {
        $sql = file_get_contents(__DIR__ . "/../../../resources/full.sql");
        $expected = (new DatabaseBuilder(""))->defaultEngine("InnoDB")->defaultCharset("latin1")
            ->table("credential")->unique(["provider", "user"])->index(["provider", "uid", "user"])
            ->field("provider")->type(Type::varchar(255))->primary()
            ->field("uid")->type(Type::varchar(255))->primary()
            ->field("user")->type(Type::int(11))->null()->foreign(null, "user", "id", ReferenceOption::CASCADE(), ReferenceOption::CASCADE())
            ->table("user")->autoincrement(2)->charset("utf8")
            ->field("id")->type(Type::int(11))->primary()->autoincrement()
            ->field("nick")->type(Type::varchar(100))->unique()
            ->field("birthday")->type(Type::datetime())->null()
            ->field("active")->type(Type::bool())->default('0')
            ->field("gender")->type(Type::enum(['m', 'f']))->null()->index()
            ->table("payload")->foreign(["cprovider", "cuid"], "credential", ["provider", "uid"], ReferenceOption::NO_ACTION(), ReferenceOption::NO_ACTION())
            ->field("payload")->type(Type::int(11))->null()
            ->field("cprovider")->type(Type::varchar(255))
            ->field("cuid")->type(Type::varchar(255))
            ->build();
        static::assertEquals($expected, DatabaseBuilder::fromSql($sql));

    }
}