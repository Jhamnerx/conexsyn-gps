<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Tobuli\Traits\ChangeLogs;
use Tobuli\Traits\DisplayTrait;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Searchable;

class Route extends AbstractEntity implements DisplayInterface
{
    use ChangeLogs;
    use DisplayTrait;
    use Searchable;
    use Filterable;
    use HasFactory;

    public static string $displayField = 'name';

    protected $table = 'routes';

    protected $fillable = ['user_id', 'group_id', 'name', 'active', 'color'];

    protected $hidden = ['polyline'];

    protected $casts = [
        'coordinates' => 'array',
    ];

    protected $searchable = [
        'name',
    ];

    protected $filterables = [
        'group_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function (Route $item) {
            if (!$item->wasChanged(['coordinates']) && !$item->wasRecentlyCreated) {
                return;
            }

            $linestring = null;

            foreach($item->coordinates as $coordinate) {
                $linestring .= $coordinate['lat'] . ' ' . $coordinate['lng'] . ',';
            }

            $linestring = substr($linestring, 0, -1);

            DB::unprepared("UPDATE routes SET polyline = GeomFromText('LINESTRING($linestring)') WHERE id = '$item->id'");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(RouteGroup::class, 'group_id', 'id');
    }

    public function scopeVisible($query)
    {
        $query->where('active', 1);
    }

    public function scopeUserOwned(Builder $query, User $user): Builder
    {
        return $query->where(['user_id' => $user->id]);
    }
}
