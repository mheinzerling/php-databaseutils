<?php

namespace mheinzerling\commons\database\structure;

use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\index\ReferenceOption;

use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\database\TestDatabaseConnection;
use Symfony\Component\Config\Definition\Exception\Exception;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromDatabaseEmpty()
    {
        $pdo = new TestDatabaseConnection();
        $database = DatabaseBuilder::fromDatabase($pdo, []);
        static::assertEquals([], $database->getTables());
    }

    public function testLoadFromDatabase_simple()
    {
        $pdo = new TestDatabaseConnection();
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $pdo->exec($sql);

        $expected = (new DatabaseBuilder($pdo->getDatabaseName()))->defaultEngine("MyISAM")->defaultCharset("latin1")->defaultCollation("latin1_swedish_ci")
            ->table("revision")->autoincrement(1)
            ->field("id")->type(Type::int(11))->primary()->autoincrement()
            ->field("class")->type(Type::varchar(255, "latin1_swedish_ci"))
            ->field("lastExecution")->type(Type::datetime())->null()
            ->build();
        static::assertEquals($expected, DatabaseBuilder::fromDatabase($pdo));
    }

    public function testLoadFromDatabase_full()
    {
        $pdo = new TestDatabaseConnection();
        DatabaseUtils::importDump($pdo, realpath(__DIR__ . "/../../resources/full.sql"));
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

    /*
        public function testLoadFromSql()
        {
            $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
            $schema = Database::fromSql($sql);
            $expected = ["revision" => new Table("revision", [
                'id' => new Field('id', 'int(11)', false, true, false, null, true),
                'class' => new Field('class', 'varchar(255)', false, false, false, null, false),
                'lastExecution' => new Field('lastExecution', 'datetime', true, false, false, null, false)
            ],
                null, null, null)];
            static::assertEquals($expected, $schema->getTables());
        }

    public function testCompareSchemaEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = Database::fromSql($sql);
        static::assertEquals([], $schema->compare($schema));
    }

    public function testCompareSchemaTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = Database::fromSql($sql);
        $after = Database::fromSql($sql . $sql2);
        static::assertEquals(['foo' => ['CREATE TABLE `foo` (...);']], $after->compare($before)); //TODO
        static::assertEquals(['foo' => ['DROP TABLE `foo`;']], $before->compare($after));
    }

    public function testCompareSchemaColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = Database::fromSql($sql);
        $after = Database::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` DROP COLUMN `class`;']], $after->compare($before));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` ADD `class` ...;']], $before->compare($after));
    }

    public function testCompareSchemaParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = Database::fromSql($sql);
        $after = Database::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(15) NULL, DROP PRIMARY KEY']], $after->compare($before));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`id`)']], $before->compare($after));
    }

    public function testIndexFile()
    {

        $before = Database::fromSql($this->res("minimal.sql"));
        $after = Database::fromSql($this->res("full.sql"));
        $expected = [
            'credential' => [
                "ALTER TABLE `credential` MODIFY `provider` VARCHAR(255) NOT NULL",
                "ALTER TABLE `credential` MODIFY `uid` VARCHAR(255) NOT NULL",
                "ALTER TABLE `credential` MODIFY `user` INT(11) NULL"
            ],
            'user' => [
                "ALTER TABLE `user` MODIFY `active` INT(1) NOT NULL",
                "ALTER TABLE `user` MODIFY `birthday` DATETIME NULL",
                "ALTER TABLE `user` MODIFY `gender` ENUM('M','F') NULL",
                "ALTER TABLE `user` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT",
                "ALTER TABLE `user` MODIFY `nick` VARCHAR(100) NOT NULL"
            ]
        ];
        static::assertEquals($expected, $after->compare($before));
    }
    */

    private function res($name)
    {
        $root = realpath(__DIR__ . "/../..");
        return file_get_contents($root . "/resources/" . $name);
    }
}