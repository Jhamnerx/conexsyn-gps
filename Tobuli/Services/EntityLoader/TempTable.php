<?php


namespace Tobuli\Services\EntityLoader;


use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TempTable
{
    protected $tableName;

    protected $id = 'id';

    public function __construct()
    {
        $this->tableName = 'tmp_' . uniqid() . '_' . uniqid();
        $this->createTable();
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function append($query)
    {
        DB::insert(
            "INSERT IGNORE INTO `{$this->tableName}` (`{$this->id}`) " . $query->toSql(),
            $query->getBindings());
    }

    public function remove($query)
    {
        DB::table($this->tableName)
            ->whereRaw("`{$this->id}` IN (". $query->toSql().")", $query->getBindings())
            ->delete();
    }

    public function toQuery() : Builder
    {
        return DB::table($this->tableName)->select($this->id);
    }

    protected function createTable()
    {
        DB::statement("CREATE TEMPORARY TABLE {$this->tableName} ({$this->id} INT, UNIQUE INDEX {$this->id}_index ({$this->id}))");
    }

}