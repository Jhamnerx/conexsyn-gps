<?php

namespace Tobuli\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait DatabaseRunChangesTrait
{
    private function dropIndexIfExists(string $table, $columns)
    {
        $this->runTableIndexChanges($table, $columns, function (Blueprint $table, array $columns) {
            $table->dropIndex($columns);
        });
    }

    private function dropForeignIfExists(string $table, $columns)
    {
        $this->runTableIndexChanges($table, $columns, function (Blueprint $table, array $columns) {
            $table->dropForeign($columns);
        });
    }

    private function dropColumnIfExists(string $table, $columns)
    {
        $this->runTableIndexChanges($table, $columns, function (Blueprint $table, array $columns) {
            $table->dropColumn($columns);
        });
    }

    private function addIndexIfNotExists(string $table, $columns)
    {
        $this->runTableIndexChanges($table, $columns, function (Blueprint $table, array $columns) {
            $table->index($columns);
        });
    }

    private function runTableIndexChanges(string $table, $columns, \Closure $callback)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($callback, $columns) {
                if (is_string($columns)) {
                    $columns = [$columns];
                }

                $callback($table, $columns);
            });
        } catch (QueryException $e) {
        }
    }
}