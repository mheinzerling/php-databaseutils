<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure;

use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\database\TestDatabaseConnection;

class MigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseBuilder::fromSql($sql);
        static::assertEquals([], $schema->migrate($schema, new SqlSetting())->getStatements());
    }

    public function testTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql . $sql2);
        static::assertEquals(["CREATE TABLE IF NOT EXISTS `foo` (\n  `id` INT NOT NULL AUTO_INCREMENT,\n  PRIMARY KEY (`id`)\n  );"],
            array_values($after->migrate($before, new SqlSetting())->getStatements()));
        static::assertEquals(["DROP TABLE IF EXISTS `foo`;"], array_values($before->migrate($after, new SqlSetting())->getStatements()));
    }

    public function testColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(["ALTER TABLE `revision` DROP COLUMN `class`;"], array_values($after->migrate($before, new SqlSetting())->getStatements()));
        static::assertEquals(["ALTER TABLE `revision` ADD `class` VARCHAR(255) NOT NULL;"], array_values($before->migrate($after, new SqlSetting())->getStatements()));
    }

    public function testParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(['ALTER TABLE `revision` MODIFY `id` INT(15) DEFAULT NULL, DROP PRIMARY KEY'], array_values($after->migrate($before, new SqlSetting())->getStatements()));
        static::assertEquals(['ALTER TABLE `revision` MODIFY `id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)'], array_values($before->migrate($after, new SqlSetting())->getStatements()));
    }

    public function testIndexFile()
    {
        $before = DatabaseBuilder::fromSql($this->res("minimal.sql"));
        $after = DatabaseBuilder::fromSql($this->res("full.sql"));
        $expected = [
            "ALTER TABLE `user` ENGINE=InnoDB, CHARACTER SET utf8, AUTO_INCREMENT = 2",
            "ALTER TABLE `credential` ENGINE=InnoDB, CHARACTER SET latin1",

            "ALTER TABLE `user` MODIFY `active` BOOL NOT NULL DEFAULT '0', MODIFY `gender` ENUM('m', 'f') DEFAULT NULL, MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, MODIFY `nick` VARCHAR(100) NOT NULL, ADD PRIMARY KEY (`id`), ADD KEY `idx_user_gender` (`gender`), ADD UNIQUE KEY `uni_user_nick` (`nick`)",
            "ALTER TABLE `credential` MODIFY `provider` VARCHAR(255) NOT NULL, MODIFY `uid` VARCHAR(255) NOT NULL, ADD PRIMARY KEY (`provider`, `uid`), ADD KEY `idx_credential_provider_uid_user` (`provider`, `uid`, `user`), ADD UNIQUE KEY `uni_credential_provider_user` (`provider`, `user`)",

            "CREATE TABLE IF NOT EXISTS `payload` ( `payload` INT(11) DEFAULT NULL, `cprovider` VARCHAR(255) NOT NULL, `cuid` VARCHAR(255) NOT NULL," .
            " CONSTRAINT `fk_payload_cprovider_cuid__credential_provider_uid` FOREIGN KEY (`cprovider`, `cuid`) REFERENCES `credential` (`provider`, `uid`)" .
            " ON UPDATE NO ACTION ON DELETE NO ACTION ) ENGINE = InnoDB DEFAULT CHARSET = latin1;",


            "ALTER TABLE `credential` ADD CONSTRAINT `fk_credential_user__user_id` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE"
        ];
        $migration = $after->migrate($before, (new SqlSetting())->singleLine());
        static::assertEquals($expected, array_values($migration->getStatements()));

        $pdo = new TestDatabaseConnection();
        DatabaseUtils::importDump($pdo, __DIR__ . "/../../resources/minimal.sql");
        $migration->run($pdo);

        //only collation changes etc. TODO better assert
        //static::assertEquals($after, DatabaseBuilder::fromDatabase($pdo, ["user.active"]));
    }

    private function res($name)
    {
        $root = realpath(__DIR__ . "/../..");
        return file_get_contents($root . "/resources/" . $name);
    }

    public function testFull()
    {
        $pdo = new TestDatabaseConnection();
        DatabaseUtils::importDump($pdo, __DIR__ . "/../../resources/full_generated.sql"); //no exception
        $expected = file_get_contents(__DIR__ . "/../../resources/full_generated.sql"); //only whitespace an order changes + collation
        $actual = (new DatabaseBuilder(""))->defaultEngine("InnoDB")->defaultCharset("latin1")->defaultCollation("latin1_swedish_ci")
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
            ->build()->migrate(new Database(""), new SqlSetting())->getStatements();
        static::assertEquals($expected, implode("\n\n", $actual));

    }


}