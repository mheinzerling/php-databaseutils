<?php

namespace mheinzerling\commons\database\structure;


class SqlSetting
{
    public $createTableIfNotExists = true;
    public $dropTableIfExists = true;
    public $dropDatabaseIfExists = true;
    public $singleLine = false;

    //TODO train setters
    public function singleLine(): SqlSetting
    {
        $this->singleLine = true;
        return $this;
    }
}