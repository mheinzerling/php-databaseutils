<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure;


class SqlSetting
{
    /**
     * @var bool
     */
    public $createTableIfNotExists = true;
    /**
     * @var bool
     */
    public $dropTableIfExists = true;
    /**
     * @var bool
     */
    public $dropDatabaseIfExists = true;
    /**
     * @var bool
     */
    public $singleLine = false;
    /**
     * @var bool
     */
    public $skipForeinKeys = false;

    //TODO train setters
    public function singleLine(): SqlSetting
    {
        $this->singleLine = true;
        return $this;
    }
}