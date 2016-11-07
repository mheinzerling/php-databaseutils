<?php

namespace mheinzerling\commons\database;

class DatabaseSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromDatabaseEmpty()
    {
        $conn = new TestDatabaseConnection();
        $schema = DatabaseSchema::fromDatabase($conn);
        static::assertEquals([], $schema->getTables());
    }

    public function testLoadFromDatabase()
    {
        $conn = new TestDatabaseConnection();
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $conn->exec($sql);


        $schema = DatabaseSchema::fromDatabase($conn);
        $expected = ["revision" => new DatabaseTable("revision", [
            'id' => new DatabaseField('id', 'int(11)', false, true, false, null, true),
            'class' => new DatabaseField('class', 'varchar(255)', false, false, false, null, false),
            'lastExecution' => new DatabaseField('lastExecution', 'datetime', true, false, false, null, false)
        ],
            null, null, null)];
        static::assertEquals($expected, $schema->getTables());

    }

    public function testLoadFromSql()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseSchema::fromSql($sql);
        $expected = ["revision" => new DatabaseTable("revision", [
            'id' => new DatabaseField('id', 'int(11)', false, true, false, null, true),
            'class' => new DatabaseField('class', 'varchar(255)', false, false, false, null, false),
            'lastExecution' => new DatabaseField('lastExecution', 'datetime', true, false, false, null, false)
        ],
            null, null, null)];
        static::assertEquals($expected, $schema->getTables());
    }

    public function testCompareSchemaEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseSchema::fromSql($sql);
        static::assertEquals([], $schema->compare($schema));
    }

    public function testCompareSchemaTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql . $sql2);
        static::assertEquals(['foo' => ['CREATE TABLE `foo` (...);']], $after->compare($before)); //TODO
        static::assertEquals(['foo' => ['DROP TABLE `foo`;']], $before->compare($after));
    }

    public function testCompareSchemaColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` DROP COLUMN `class`;']], $after->compare($before));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` ADD `class` ...;']], $before->compare($after));
    }

    public function testCompareSchemaParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql2);
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(15) NULL, DROP PRIMARY KEY']], $after->compare($before));
        static::assertEquals(['revision' => ['ALTER TABLE `revision` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`id`)']], $before->compare($after));
    }

    public function testIndexFile()
    {

        $before = DatabaseSchema::fromSql($this->res("minimal.sql"));
        $after = DatabaseSchema::fromSql($this->res("full.sql"));
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

    private function res($name)
    {
        $root = realpath(__DIR__ . "/../../../..");
        return file_get_contents($root . "/resources/" . $name);
    }
}