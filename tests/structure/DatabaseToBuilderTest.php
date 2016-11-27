<?php

namespace mheinzerling\commons\database\structure;

use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\database\structure\type\Type;

class DatabaseSqlTest extends \PHPUnit_Framework_TestCase
{

    public function testToBuilder()
    {
        $expected = '(new DatabaseBuilder(""))->defaultEngine("InnoDB")->defaultCharset("latin1")->defaultCollation("latin1_swedish_ci")
    ->table("credential")->unique(["provider", "user"])->index(["provider", "uid", "user"])->primary(["provider", "uid"])->foreign(["user"], "user", ["id"], ReferenceOption::CASCADE(), ReferenceOption::CASCADE())
    ->charset("latin1")->collation("latin1_swedish_ci")
    ->field("provider")->type(Type::varchar(255, "latin1_swedish_ci"))
    ->field("uid")->type(Type::varchar(255, "latin1_swedish_ci"))
    ->field("user")->type(Type::int(11))->null()
    ->table("user")->primary(["id"])->unique(["nick"])->index(["gender"])
    ->autoincrement(2)->charset("utf8")->collation("utf8_general_ci")
    ->field("id")->type(Type::int(11))->autoincrement()
    ->field("nick")->type(Type::varchar(100, "utf8_general_ci"))
    ->field("birthday")->type(Type::datetime())->null()
    ->field("active")->type(Type::bool())->default("0")
    ->field("gender")->type(Type::enum(["m", "f"]))->null()
    ->table("payload")->foreign(["cprovider", "cuid"], "credential", ["provider", "uid"], ReferenceOption::NO_ACTION(), ReferenceOption::NO_ACTION())
    ->charset("latin1")->collation("latin1_swedish_ci")
    ->field("payload")->type(Type::int(11))->null()
    ->field("cprovider")->type(Type::varchar(255, "latin1_swedish_ci"))
    ->field("cuid")->type(Type::varchar(255, "latin1_swedish_ci"))
    ->build()';
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
            ->build()->toBuilderCode("InnoDB", "latin1", "latin1_swedish_ci");
        static::assertEquals(str_replace("\r", "", $expected), $actual);

    }

}