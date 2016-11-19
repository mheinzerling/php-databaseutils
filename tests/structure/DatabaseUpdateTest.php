<?php

namespace mheinzerling\commons\database\structure;

use mheinzerling\commons\database\structure\builder\DatabaseBuilder;

class DatabaseUpdateTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseBuilder::fromSql($sql);
        static::assertEquals([], $schema->update($schema, new SqlSetting()));
    }

    public function testTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql . $sql2);
        static::assertEquals(['foo' => ["CREATE TABLE IF NOT EXISTS `foo` (\n  `id` INT NOT NULL AUTO_INCREMENT,\n  PRIMARY KEY (`id`)\n  );"]],
            $after->update($before, new SqlSetting()));
        static::assertEquals(['foo' => ["DROP TABLE IF EXISTS `foo`;"]], $before->update($after, new SqlSetting()));
    }

    public function testColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(['revision' => ["ALTER TABLE `revision` DROP COLUMN `class`;"]], $after->update($before, new SqlSetting()));
        static::assertEquals(['revision' => ["ALTER TABLE `revision` ADD `class` VARCHAR(255) NOT NULL;"]], $before->update($after, new SqlSetting()));
    }

    public function testParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(15) DEFAULT NULL, DROP PRIMARY KEY']], $after->update($before, new SqlSetting()));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)']], $before->update($after, new SqlSetting()));
    }

    public function testIndexFile()
    {

        $before = DatabaseBuilder::fromSql($this->res("minimal.sql"));
        $after = DatabaseBuilder::fromSql($this->res("full.sql"));
        $expected = [
            'credential' => [
                "TODO: change table engine to >InnoDB< from ><",
                "TODO: change table charset to >latin1< from ><",
                "ALTER TABLE `credential` MODIFY `provider` VARCHAR(255) NOT NULL, MODIFY `uid` VARCHAR(255) NOT NULL, ADD PRIMARY KEY (`provider`, `uid`), " .
                "ADD CONSTRAINT `fk_credential_user__user_id` FOREIGN KEY (`user`) REFERENCES `user` (`id`)\n    ON UPDATE CASCADE\n    ON DELETE CASCADE, " .
                "ADD KEY `idx_credential_provider_uid_user` (`provider`, `uid`, `user`), ADD UNIQUE KEY `uni_credential_provider_user` (`provider`, `user`)"
            ],
            'user' => [
                "TODO: change table engine to >InnoDB< from ><",
                "TODO: change table charset to >utf8< from ><",
                "TODO: change table currentAutoincrement to >2< from ><",
                "ALTER TABLE `user` MODIFY `active` BOOL NOT NULL DEFAULT '0', MODIFY `gender` ENUM('m', 'f') DEFAULT NULL, MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, MODIFY `nick` VARCHAR(100) NOT NULL, " .
                "ADD PRIMARY KEY (`id`), ADD KEY `idx_user_gender` (`gender`), ADD UNIQUE KEY `uni_user_nick` (`nick`)"
            ],
            'payload' => [
                "CREATE TABLE IF NOT EXISTS `payload` (\n  `payload` INT(11) DEFAULT NULL,\n  `cprovider` VARCHAR(255) NOT NULL,\n  `cuid` VARCHAR(255) NOT NULL," .
                "\n  CONSTRAINT `fk_payload_cprovider_cuid__credential_provider_uid` FOREIGN KEY (`cprovider`, `cuid`) REFERENCES `credential` (`provider`, `uid`)\n" .
                "    ON UPDATE NO ACTION\n    ON DELETE NO ACTION\n  )\n  ENGINE = InnoDB\n  DEFAULT CHARSET = latin1;"
            ]
        ];
        static::assertEquals($expected, $after->update($before, new SqlSetting()));
        //TODO indexes
    }


    private function res($name)
    {
        $root = realpath(__DIR__ . "/../..");
        return file_get_contents($root . "/resources/" . $name);
    }
}