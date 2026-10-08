<?php namespace Tobuli\Entities;

use Illuminate\Support\Facades\DB;
use Tobuli\Traits\Searchable;

class EventCustom extends AbstractEntity
{
    use Searchable;

	protected $table = 'events_custom';

    protected $fillable = array(
        'user_id',
        'protocol',
        'conditions',
        'message',
        'always'
    );

    protected $searchable = [
        'message',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($eventCustom)
        {
            $eventCustom->tags()->delete();

            if ($eventCustom->conditions) {
                foreach ($eventCustom->conditions as $condition) {
                    $tags[$condition['tag']] = [
                        'event_custom_id' => $eventCustom->id,
                        'tag' => $condition['tag']
                    ];
                }

                DB::table('event_custom_tags')->insert($tags);
            }
        });
    }

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function tags() {
        return $this->hasMany('Tobuli\Entities\EventCustomTag', 'event_custom_id', 'id');
    }

    public function port() {
        return $this->hasOne('Tobuli\Entities\TrackerPort', 'name', 'protocol');
    }

    public function setConditionsAttribute($value)
    {
        $this->attributes['conditions'] = serialize($value);
    }

    public function getConditionsAttribute($value)
    {
        return unserialize($value);
    }

    public function getMessageWithProtocolAttribute($value)
    {
        $message = $this->message;
        $user = auth()->user();

        if ($user && $user->perm('device.protocol', 'view'))
            $message .= ' (' . $this->protocol . ')';

        return $message;
    }
}
