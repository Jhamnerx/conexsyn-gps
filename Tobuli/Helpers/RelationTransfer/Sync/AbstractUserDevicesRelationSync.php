<?php

namespace Tobuli\Helpers\RelationTransfer\Sync;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\User;

/**
 * @property User $parent
 */
class AbstractUserDevicesRelationSync extends AbstractRelationSync
{
    protected function _attach($only): void
    {
        $itemsQuery = DB::table($this->entitiesTable)
            ->where("$this->entitiesTable.user_id", $this->parent->id)
            ->select(["$this->entitiesTable.id", 'ud.device_id'])
            ->join('user_device_pivot AS ud', 'ud.user_id', "$this->entitiesTable.user_id")
            ->where('for_all_user_devices', 1);

        if ($only !== null) {
            $itemsQuery->whereIn('ud.device_id', $only);
        }

        if ($this->insertIgnores) {
            DB::statement("INSERT IGNORE INTO $this->pivotTable ($this->pivotKey, device_id) {$itemsQuery->toRaw()}");

            return;
        }

        $itemsQuery
            ->leftJoin("$this->pivotTable AS pivot", fn (JoinClause $join) => $join
                ->on('pivot.device_id', 'ud.device_id')
                ->on("pivot.$this->pivotKey", "$this->entitiesTable.id")
            )
            ->whereNull("$this->pivotTable.device_id");

        DB::statement("INSERT INTO $this->pivotTable ($this->pivotKey, device_id) {$itemsQuery->toRaw()}");
    }

    protected function _detach($only): void
    {
        DB::table($this->pivotTable)
            ->join($this->entitiesTable, "$this->entitiesTable.id", "$this->pivotTable.$this->pivotKey")
            ->leftJoin('user_device_pivot AS ud', fn (JoinClause $join) => $join
                ->on('ud.device_id', "$this->pivotTable.device_id")
                ->where('ud.user_id', $this->parent->id)
            )
            ->where("$this->entitiesTable.user_id", $this->parent->id)
            ->whereNull('ud.user_id')
            ->delete();
    }
}