<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\index;


use mheinzerling\commons\database\structure\Field;
use mheinzerling\commons\database\structure\SqlSetting;

class Primary extends Index
{
    const PRIMARY = 'PRIMARY';

    public function __construct(array $fields = null)
    {
        parent::__construct($fields, Primary::PRIMARY);
    }

    public function append(Field $field): void
    {
        $this->fields[$field->getName()] = $field;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param SqlSetting $setting
     * @return string
     */
    public function toSql(SqlSetting $setting): string
    {
        $sql = "PRIMARY KEY ";
        $sql .= "(`" . implode("`, `", array_keys($this->fields)) . "`)";
        return $sql;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param SqlSetting $setting
     * @return string
     */
    public function toAlterDropSql(SqlSetting $setting): string
    {
        return "DROP PRIMARY KEY";
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param Index $before
     * @param SqlSetting $setting
     * @return null|string
     */
    public function modifySql(Index $before, SqlSetting $setting):?string
    {
        if (!$before instanceof Primary) return "TODO: change index type to primary " . $this->getName();
        if ($this->same($before)) return null;
        return "TODO: PRIMARY changed to (" . implode(", ", array_keys($this->fields)) . ") from (" . implode(", ", array_keys($before->fields)) . ") ";
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function toBuilderCode(): string
    {
        return '->primary(["' . implode('", "', array_keys($this->fields)) . '"])';
    }

}