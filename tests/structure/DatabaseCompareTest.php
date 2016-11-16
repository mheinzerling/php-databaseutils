<?php

namespace mheinzerling\commons\database\structure;

use mheinzerling\commons\database\structure\builder\DatabaseBuilder;

class DatabaseCompareTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseBuilder::fromSql($sql);
        static::assertEquals([], $schema->compare($schema, new SqlSetting()));
    }

    public function testTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql . $sql2);
        static::assertEquals(['foo' => ["CREATE TABLE IF NOT EXISTS `foo` (\n  `id` INT NOT NULL AUTO_INCREMENT,\n  PRIMARY KEY (`id`)\n  );"]],
            $after->compare($before, new SqlSetting()));
        static::assertEquals(['foo' => ["DROP TABLE IF EXISTS `foo`;"]], $before->compare($after, new SqlSetting()));
    }

    public function testColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(['revision' => ["ALTER TABLE `revision` DROP COLUMN `class`;"]], $after->compare($before, new SqlSetting()));
        static::assertEquals(['revision' => ["ALTER TABLE `revision` ADD `class` VARCHAR(255) NOT NULL;"]], $before->compare($after, new SqlSetting()));
    }

    public function testParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = DatabaseBuilder::fromSql($sql);
        $after = DatabaseBuilder::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(15) NULL, DROP PRIMARY KEY']], $after->compare($before, new SqlSetting()));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`id`)']], $before->compare($after, new SqlSetting()));
        //TODO indexes
    }

    public function testIndexFile()
    {

        $before = DatabaseBuilder::fromSql($this->res("minimal.sql"));
        $after = DatabaseBuilder::fromSql($this->res("full.sql"));
        $expected = [
            'credential' => [
                "ALTER TABLE `credential` MODIFY `provider` VARCHAR(255) NOT NULL",
                "ALTER TABLE `credential` MODIFY `uid` VARCHAR(255) NOT NULL",
                "ALTER TABLE `credential` MODIFY `user` INT(11) NULL"
            ],
            'user' => [
                "ALTER TABLE `user` MODIFY `active` INT(1) NOT NULL",
                "ALTER TABLE `user` MODIFY `gender` ENUM('m','f') NULL",
                "ALTER TABLE `user` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT",
                "ALTER TABLE `user` MODIFY `nick` VARCHAR(100) NOT NULL"
            ],
            'payload' => [
                "CREATE TABLE IF NOT EXISTS `payload` (\n  `payload` INT(11) DEFAULT NULL,\n  `cprovider` VARCHAR(255) NOT NULL,\n  `cuid` VARCHAR(255) NOT NULL," .
                "\n  CONSTRAINT `fk_payload_cprovider_cuid__credential_provider_uid` FOREIGN KEY (`cprovider`, `cuid`) REFERENCES `credential` (`provider`, `uid`)\n" .
                "    ON UPDATE NO ACTION\n    ON DELETE NO ACTION\n  )\n  ENGINE = InnoDB\n  DEFAULT CHARSET = latin1;"
            ]
        ];
        static::assertEquals($expected, $after->compare($before, new SqlSetting()));
        //TODO indexes
    }


    private function res($name)
    {
        $root = realpath(__DIR__ . "/../..");
        return file_get_contents($root . "/resources/" . $name);
    }
}