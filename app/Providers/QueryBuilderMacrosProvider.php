<?php

namespace App\Providers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\JoinClause;
use Tobuli\Services\EntityLoader\EnityLoader;
use Tobuli\Services\EntityLoader\TempTable;

class QueryBuilderMacrosProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        QueryBuilder::macro('clearOrdersBy', function () {
            $this->{$this->unions ? 'unionOrders' : 'orders'} = null;

            return $this;
        });

        EloquentBuilder::macro("clearOrdersBy", function () {
            $query = $this->getQuery();

            $query->{$query->unions ? 'unionOrders' : 'orders'} = null;

            return $this;
        });

        QueryBuilder::macro("toRaw", function () {

            $sql = vsprintf(str_replace('?', '%s', $this->toSql()), collect($this->getBindings())->map(function ($binding) {
                return is_numeric($binding) ? $binding : "'{$binding}'";
            })->toArray());

            return $sql;
        });

        EloquentBuilder::macro("toRaw", function () {
            return $this->getQuery()->toRaw();
        });

        EloquentBuilder::macro('isJoined', function ($table) {
            $query = $this->getQuery();

            if ($query->joins == null) {
                return false;
            }

            foreach ($query->joins as $join) {
                if ($join->table == $table) {
                    return true;
                }
            }

            return false;
        });

        EloquentBuilder::macro('toPaginator', function (int $limit, string $sortCol, string $sortDir): LengthAwarePaginator {
            if (!empty($sortCol)) {
                $this->orderBy($sortCol, strtolower($sortDir) == 'asc' ? 'asc' : 'desc');
            }

            $items = $this->paginate($limit);

            if ($items->currentPage() > $items->lastPage()) {
                $items = $this->paginate($limit, ['*'], 'page', 1);
            }

            $items->sorting = ['sort_by' => $sortCol, 'sort' => $sortDir];

            return $items;
        });

        EloquentBuilder::macro('reversePaginate', function (int $limit = null) {
            $items = $this->paginate($limit);

            return $items->setCollection($items->getCollection()->reverse()->values());
        });

        EloquentBuilder::macro('whereManagerOwn', function (string $column, $manager) {
            return $this->whereIn($column, function ($query) use ($manager) {
                $query
                    ->select('users.id')
                    ->from('users')
                    ->where('users.id', $manager->id)
                    ->orWhere('users.manager_id', $manager->id)
                ;
            });
        });

        BelongsToMany::macro('attachQuery', function ($query) {
            /** @var BelongsToMany $this */

            switch (true) {
                case $query instanceof EloquentBuilder:
                    $model = $query->getModel();
                    break;
                case is_subclass_of($query, Relation::class):
                    $model = $query->getQuery()->getModel();
                    break;
                default:
                    throw new \RuntimeException("Class must implement EloquentBuilder or Relation");
            }

            $queryClass = get_class($model);
            $macroClass = get_class($this->getRelated());
            if ($macroClass !== $queryClass) {
                throw new \RuntimeException("The related object classes do not match: $macroClass and $queryClass");
            }

            $tablePivot = $this->getTable();
            $foreignPivotKey = $this->getForeignPivotKeyName();
            $relatedPivotKey = $this->getRelatedPivotKeyName();

            $relatedKey = $this->getRelated()->getKeyName();
            $relatedTable = $this->getRelated()->getTable();
            $relatedQualifiedKey = "$relatedTable.$relatedKey";

            $select = $query
                ->clearOrdersBy()
                ->select(\DB::raw("{$this->getParent()->id} AS `$foreignPivotKey`, $relatedQualifiedKey"))
                ->leftJoin("$tablePivot AS tmp_table_pivot", function ($join) use ($relatedPivotKey, $foreignPivotKey, $relatedQualifiedKey){
                    $join->on("tmp_table_pivot.$relatedPivotKey", '=', $relatedQualifiedKey)
                        ->where("tmp_table_pivot.$foreignPivotKey", '=', $this->getParent()->id);
                })
                ->whereNull("tmp_table_pivot.$relatedPivotKey")
            ;

            $insert = "INSERT INTO `$tablePivot` (`$foreignPivotKey`, `$relatedPivotKey`) ";

            $bindings = $select->getBindings();
            $insertQuery = str_finish($insert, ' ') . $select->toSql();

            \DB::insert($insertQuery, $bindings);
        });

        BelongsToMany::macro('detachQuery', function ($query) {

            $tablePivot = $this->getTable();
            $foreignPivotKey = $this->getForeignPivotKeyName();
            $relatedPivotKey = $this->getRelatedPivotKeyName();

            $relatedKey = $this->getRelated()->getKeyName();
            $relatedTable = $this->getRelated()->getTable();
            $relatedQualifiedKey = "$relatedTable.$relatedKey";

            $sql = $query->select($relatedQualifiedKey)->toRaw();

            \DB::table($tablePivot)
                ->where("$tablePivot.$foreignPivotKey", $this->getParent()->id)
                ->join(\DB::raw('(' . $sql. ') AS tmp_table_related'), function($join) use ($relatedKey, $tablePivot, $relatedPivotKey){
                    $join->on("tmp_table_related.$relatedKey", '=', "$tablePivot.$relatedPivotKey");
                })
                ->whereNotNull("tmp_table_related.$relatedKey")
                ->delete();
        });

        EloquentBuilder::macro('throughModelDetached', /** @var null|string|Relation $checkRelation */ function (string $relationName, string $throughModel, $checkRelation = null) {
            /** @var BelongsToMany $mainRelation */
            $mainRelation = $this->getRelation($relationName);

            $model = $this->getModel();
            $modelThrough = $model->getRelationByClass($throughModel)->getRelated();

            if ($checkRelation === null) {
                $checkRelation = $relationName;
            }

            $throughRelation = (is_string($checkRelation) ? (new $throughModel())->$checkRelation() : $checkRelation)
                ->getModel()
                ->getRelationByClass($throughModel);

            if (get_class($throughRelation->getRelated()) !== get_class($modelThrough)) {
                throw new \InvalidArgumentException(sprintf(
                    'Query relation class (%s) does not match main relation class (%s)',
                    get_class($throughRelation),
                    get_class($modelThrough),
                ));
            }

            $query = \DB::query()
                ->from($mainRelation->getTable())
                ->leftJoin(
                    $this->from,
                    $mainRelation->getQualifiedParentKeyName(),
                    $mainRelation->getQualifiedForeignPivotKeyName()
                )
                ->leftJoin($throughRelation->getTable(), function (JoinClause $join) use ($mainRelation, $throughRelation, $modelThrough) {
                    $join->on(
                        $throughRelation->getQualifiedForeignPivotKeyName(),
                        $mainRelation->getQualifiedRelatedPivotKeyName()
                    )->on(
                        $throughRelation->getQualifiedRelatedPivotKeyName(),
                        $this->from . '.' . $modelThrough->getForeignKey()
                    );
                })
                ->whereNull($throughRelation->getQualifiedForeignPivotKeyName());

            if ($checkRelation instanceof Relation && $checkRelation->getParent()->exists) {
                $subQuery = $checkRelation->getQuery();
                $subQuery->clearOrdersBy()
                    ->select($subQuery->from . '.' . $checkRelation->getModel()->getKeyName())
                    ->distinct(false);

                $query->whereRaw($mainRelation->getQualifiedRelatedPivotKeyName() . ' IN (' . $subQuery->toRaw() . ')');
            }

            return $query;
        });

        BelongsToMany::macro('syncLoader', function (EnityLoader $loader) {
            if (!$loader->hasSelect())
                return;

            if ($loader->hasDetach()) {
                if (!$loader->getQueryStored()) {
                    $loader->setQueryStored($this->getQuery());
                }

                $this->detachQuery($loader->getQueryDetach());
            }

            if ($loader->hasAttach()) {
                $this->attachQuery($loader->getQueryAttach());
            }
        });

        EloquentBuilder::macro('whereMorphDisplayField', function (string $relation, string $type, string $operator, string $value) {
            if (class_exists($type)) {
                $class = $type;
            } else {
                $class = Relation::morphMap()[$type] ?? false;
            }

            if (!$class) {
                return $this;
            }

            return $this->whereHasMorph($relation, $class, function (EloquentBuilder $query) use ($operator, $value, $class) {
                $field = $class::$displayField ?? false;

                if (is_string($field)) {
                    $query->where($field, $operator, $value);
                }

                if (is_array($field)) {
                    $query->where(function (EloquentBuilder $query) use ($field, $operator, $value) {
                        foreach ($field as $f) {
                            $query->orWhere($f, $operator, $value);
                        }
                    });
                }
            });
        });
    }
}
