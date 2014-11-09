<?php

namespace mheinzerling\commons\database;

class DatabaseSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromDatabaseEmpty()
    {
        $conn = new TestDatabaseConnection();
        $schema = DatabaseSchema::fromDatabase($conn);
        $this->assertEquals(array(), $schema->getTables());
    }

    public function testLoadFromDatabase()
    {
        $conn = new TestDatabaseConnection();
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $conn->exec($sql);


        $schema = DatabaseSchema::fromDatabase($conn);
        $expected = array("revision" => new DatabaseTable("revision", array(
            'id' => new DatabaseField('id', 'int(11)', false, true, false, null, true),
            'class' => new DatabaseField('class', 'varchar(255)', false, false, false, null, false),
            'lastExecution' => new DatabaseField('lastExecution', 'datetime', true, false, false, null, false)
        ),
            null, null, null));
        $this->assertEquals($expected, $schema->getTables());

    }

    public function testLoadFromSql()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseSchema::fromSql($sql);
        $expected = array("revision" => new DatabaseTable("revision", array(
            'id' => new DatabaseField('id', 'int(11)', false, true, false, null, true),
            'class' => new DatabaseField('class', 'varchar(255)', false, false, false, null, false),
            'lastExecution' => new DatabaseField('lastExecution', 'datetime', true, false, false, null, false)
        ),
            null, null, null));
        $this->assertEquals($expected, $schema->getTables());
    }

    public function testCompareSchemaEquals()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $schema = DatabaseSchema::fromSql($sql);
        $this->assertEquals(array(), $schema->compare($schema));
    }

    public function testCompareSchemaTable()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE foo (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql . $sql2);
        $this->assertEquals(array('foo' => array('CREATE TABLE `foo` (...);')), $after->compare($before)); //TODO
        $this->assertEquals(array('foo' => array('DROP TABLE `foo`;')), $before->compare($after));
    }

    public function testCompareSchemaColumn()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`lastExecution` DATETIME NULL);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql2);
        $this->assertEquals(array('revision' => array('ALTER TABLE `revision` DROP COLUMN `class`;')), $after->compare($before));
        $this->assertEquals(array('revision' => array('ALTER TABLE `revision` ADD `class` ...;')), $before->compare($after));
    }

    public function testCompareSchemaParams()
    {
        $sql = "CREATE TABLE revision (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,`class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $sql2 = "CREATE TABLE revision (`id` INT(15) NULL, `class` VARCHAR(255) NOT NULL,`lastExecution` DATETIME NULL);";
        $before = DatabaseSchema::fromSql($sql);
        $after = DatabaseSchema::fromSql($sql2);
        $this->assertEquals(array('revision' => array('ALTER TABLE `revision` MODIFY `id` INT(15) NULL, DROP PRIMARY KEY')), $after->compare($before));
        $this->assertEquals(array('revision' => array('ALTER TABLE `revision` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`id`)')), $before->compare($after));
    }

}