<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

abstract class AbstractDeviceUsersRelationSync extends AbstractRelationSync
{
    protected function _attach($only): void
    {
        $itemsQuery = DB::table($this->entitiesTable)
            ->select(['id', 'ud.device_id'])
            ->join('user_device_pivot AS ud', fn (JoinClause $join) => $join
                ->on('ud.user_id', "$this->entitiesTable.user_id")
                ->where('ud.device_id', $this->parent->getKey())
            )
            ->where('for_all_user_devices', 1);

        if ($only !== null) {
            $itemsQuery->whereIn("$this->entitiesTable.user_id", $only);
        }

        if ($this->insertIgnores) {
            DB::statement("INSERT IGNORE INTO $this->pivotTable ($this->pivotKey, device_id) {$itemsQuery->toRaw()}");

            return;
        }

        $itemsQuery
            ->leftJoin("$this->pivotTable AS pivot", fn (JoinClause $join) => $join
                ->on("pivot.$this->pivotKey", "$this->entitiesTable.id")
                ->on('pivot.device_id', 'ud.device_id')
            )
            ->whereNull('pivot.device_id');

        DB::statement("INSERT INTO $this->pivotTable ($this->pivotKey, device_id) {$itemsQuery->toRaw()}");
    }

    protected function _detach($only): void
    {
        $itemsQuery = DB::table($this->entitiesTable)
            ->select(['id', DB::raw($this->parent->id)])
            ->leftJoin('user_device_pivot AS ud', fn (JoinClause $join) => $join
                ->on('ud.user_id', "$this->entitiesTable.user_id")
                ->where('ud.device_id', $this->parent->id)
            )
            ->whereNull('ud.device_id');

        if ($only !== null) {
            $itemsQuery->whereIn("$this->entitiesTable.user_id", $only);
        }

        DB::table($this->pivotTable)
            ->whereRaw("($this->pivotKey, device_id) IN ({$itemsQuery->toRaw()})")
            ->delete();
    }
}