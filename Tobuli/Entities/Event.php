<?php

namespace Tobuli\Entities;


use Formatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\ChangeLogs;
use Tobuli\Traits\Searchable;

class Event extends AbstractEntity implements DisplayInterface
{
    use Filterable, Searchable;

    const TYPE_FUEL_FILL  = 'fuel_fill';
    const TYPE_FUEL_THEFT = 'fuel_theft';
    const TYPE_EXPIRED_DEVICE = 'expired_device';
    const TYPE_EXPIRING_DEVICE = 'expiring_device';
    const TYPE_EXPIRING_USER = 'expiring_user';
    const TYPE_EXPIRED_USER = 'expired_user';
    const TYPE_EXPIRING_SIM = 'expiring_sim';
    const TYPE_EXPIRED_SIM = 'expired_sim';
    const TYPE_ZONE_IN = 'zone_in';
    const TYPE_ZONE_OUT = 'zone_out';
    const TYPE_DRIVER = 'driver';
    const TYPE_DRIVER_UNAUTHORIZED = 'driver_unauthorized';
    const TYPE_DRIVER_AUTHORIZED = 'driver_authorized';
    const TYPE_MOVE_START = 'move_start';
    const TYPE_MOVE_START_FILTERED = 'move_start_filtered';
    const TYPE_OVERSPEED = 'overspeed';
    const TYPE_TIME_DURATION = 'time_duration';
    const TYPE_OFFLINE_DURATION = 'offline_duration';
    const TYPE_MOVE_DURATION = 'move_duration';
    const TYPE_STOP_DURATION = 'stop_duration';
    const TYPE_IDLE_DURATION = 'idle_duration';
    const TYPE_IGNITION_DURATION = 'ignition_duration';
    const TYPE_IGNITION_ON = 'ignition_on';
    const TYPE_IGNITION_OFF = 'ignition_off';
    const TYPE_SOS = 'sos';
    const TYPE_CUSTOM = 'custom';
    const TYPE_TASK_NEW = 'task_new';
    const TYPE_TASK_COMPLETE = 'task_complete';
    const TYPE_TASK_FAILED = 'task_failed';
    const TYPE_TASK_IN_PROGRESS = 'task_in_progress';
    const TYPE_DISTANCE = 'distance';
    const TYPE_POI_STOP_DURATION = 'poi_stop_duration';
    const TYPE_POI_IDLE_DURATION = 'poi_idle_duration';
    const TYPE_DEVICE_SUBSCRIPTION_EXPIRED = 'device_subscription_expired';
    const TYPE_UNPLUGGED = 'unplugged';
    const TYPE_PLUGGED_IN = 'plugged_in';

    use ChangeLogs;

    public static array $recordEvents = ['deleted'];
    public static array $displayField = ['device_id'];

    private static Collection $typeTitles;

    protected $table = 'events';

    protected $fillable = [
        'user_id',
        'geofence_id',
        'position_id',
        'alert_id',
        'device_id',
        'poi_id',
        'type',
        'message',
        'latitude',
        'longitude',
        'time',
        'speed',
        'altitude',
        'course',
        'power',
        'address',
        'deleted',
        'additional',
        'silent',
    ];

    protected $casts = [
        'additional' => 'array'
    ];

    protected $appends = [
        'name',
        'detail'
    ];

    protected $filterables = [
        'user_id',
        'device_id',
        'alert_id',
        'group_id',
        'type'
    ];

    protected $searchable = [
        'message',
        'device.name'
    ];

    public function getDisplayName(): string
    {
        return $this->device->name . ' ' . $this->name;
    }

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function geofence() {
        return $this->hasOne('Tobuli\Entities\Geofence', 'id', 'geofence_id');
    }

    public function poi() {
        return $this->hasOne('Tobuli\Entities\Poi', 'id', 'poi_id');
    }

    public function alert() {
        return $this->hasOne('Tobuli\Entities\Alert', 'id', 'alert_id');
    }

    public function device() {
        return $this->hasOne('Tobuli\Entities\Device', 'id', 'device_id');
    }

    public function position()
    {
        $instance = new \Tobuli\Entities\TraccarPosition();
        $instance->setTable($this->device->positions()->getRelated()->getTable());
        $instance->setConnection($this->device->positions()->getRelated()->getConnectionName());

        return new HasOne($instance->newQuery(), $this, 'id', 'position_id');
    }

    public function scopeUserOwned(Builder $query, User $user)
    {
        return $query->where('events.user_id', $user->id);
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        //optimize
        $alerts = DB::query()
            ->select("alert_user.alert_id")
            ->from('alert_user')
            ->where('alert_user.user_id', $user->id)
            ->pluck('alert_id');

        return $query->where(function (Builder $query) use ($user, $alerts) {
            $query->userOwned($user);

            if ($alerts) {
                $query->orWhereIn('events.alert_id', $alerts);
            }
        });


        return $query->where(function (Builder $query) use ($user) {
            $query->userOwned($user);

            $query->orWhere(function(Builder $q) use ($user) {
                $q->whereExists(function ($query) use ($user) {
                    $query->select("alert_user.user_id")
                        ->from('alert_user')
                        ->whereRaw('alert_user.alert_id = events.alert_id')
                        ->where('alert_user.user_id', $user->id);
                });
            });
        });
    }

    public function scopeUserControllable(Builder $query, User $user): Builder
    {
        return $query->userOwned($user);
    }

    public function scopeHigherTime(Builder $query, $time, $device_id)
    {
        if ($device_id) {
            if (is_array($device_id))
                $query->whereIn('events.device_id', $device_id);
            else
                $query->where('events.device_id', $device_id);
        }

        $query->where('events.created_at', '>', date('Y-m-d H:i:s', $time));
        $query->whereNull('events.silent');
        $query->with('geofence', 'device', 'alert');
    }

    public function scopeFilterGroupId($query, $group_id)
    {
        $query->join("user_device_pivot", 'events.device_id', '=', 'user_device_pivot.device_id');

        if (is_array($group_id)) {
            $query->whereIn('user_device_pivot.group_id', $group_id);
        } else {
            $query->where('user_device_pivot.group_id', $group_id ?: 0);
        }
    }

    public function setAdditional($key, $value)
    {
        $additional = array_merge($this->additional ? $this->additional : [], [
            $key => $value
        ]);

        $this->attributes['additional'] = json_encode($additional);
    }

    public function getAdditional($key)
    {
        return $this->additional[$key] ?? null;
    }

    public function getAddress()
    {
        if (is_null($this->latitude))
            return null;

        if (is_null($this->longitude))
            return null;

        $address = getGeoAddress($this->latitude, $this->longitude);

        if (in_array($this->type, [self::TYPE_POI_STOP_DURATION, self::TYPE_POI_IDLE_DURATION]))
            $address .= ' - ' . $this->detail;

        return $address;
    }

    public function getTitleAttribute()
    {
        if (settings('plugins.event_section_alert.status'))
            return $this->alert->name ?? $this->message;

        return $this->message;
    }

    public function getMessageAttribute($value)
    {
        return $this->formatMessage();
    }

    public function getDetailAttribute() {
        $detail = null;

        switch($this->type) {
            case Event::TYPE_ZONE_IN:
            case Event::TYPE_ZONE_OUT:
                $detail = Arr::get($this->additional, 'geofence');
                break;
            case Event::TYPE_DRIVER:
            case Event::TYPE_DRIVER_UNAUTHORIZED:
            case Event::TYPE_DRIVER_AUTHORIZED:
                $detail = Arr::get($this->additional, 'driver_name');
                break;
            case Event::TYPE_OVERSPEED:
                $speed  = Arr::get($this->additional, 'overspeed_speed', 0);
                $detail = Formatter::speed()->human($speed);
                break;
            case Event::TYPE_STOP_DURATION:
                $duration = Arr::get($this->additional, 'stop_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_TIME_DURATION:
                $duration = Arr::get($this->additional, 'time_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_OFFLINE_DURATION:
                $duration = Arr::get($this->additional, 'offline_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_MOVE_DURATION:
                $duration = Arr::get($this->additional, 'move_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_IDLE_DURATION:
                $duration = Arr::get($this->additional, 'idle_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_IGNITION_DURATION:
                $duration = Arr::get($this->additional, 'ignition_duration', 0);
                $detail   = $duration.' '. trans('front.minutes');
                break;
            case Event::TYPE_FUEL_FILL:
            case Event::TYPE_FUEL_THEFT:
                $difference = Arr::get($this->additional, 'difference', 0);
                $sensor = runCacheEntity(DeviceSensor::class, Arr::get($this->additional, 'sensor_id'))->first();

                $detail = $sensor ? "{$sensor->name}, $difference" : $difference;
                break;
            case Event::TYPE_TASK_NEW:
            case Event::TYPE_TASK_COMPLETE:
            case Event::TYPE_TASK_FAILED:
            case Event::TYPE_TASK_IN_PROGRESS:
                $detail = Arr::get($this->additional, 'task');
                break;
            case Event::TYPE_DISTANCE:
                $distance = Arr::get($this->additional, 'distance', 0);
                $limit = Arr::get($this->additional, 'limit', 0);
                $detail = Formatter::distance()->human($distance) . ' / ' . Formatter::distance()->human($limit);
                break;
            case Event::TYPE_POI_STOP_DURATION:
            case Event::TYPE_POI_IDLE_DURATION:
                $poi = Arr::get($this->additional, 'poi', null);
                $distance = Arr::get($this->additional, 'distance', 0);

                $detail = $poi ? Formatter::distance()->human($distance)  . ' ' . $poi  : null;
                break;
        }

        return $detail;
    }

    public function getNameAttribute()
    {
        switch($this->type) {
            case Event::TYPE_ZONE_IN:
            case Event::TYPE_ZONE_OUT:
                $name = trans('front.'.$this->type);
                break;
            case Event::TYPE_DRIVER:
                $name = trans('front.driver');
                break;
            case Event::TYPE_DRIVER_UNAUTHORIZED:
                $name = trans('front.driver_change_unauthorized');
                break;
            case Event::TYPE_DRIVER_AUTHORIZED:
                $name = trans('front.driver_change_authorized');
                break;
            case Event::TYPE_OVERSPEED:
                $name = trans('front.overspeed');
                break;
            case Event::TYPE_STOP_DURATION:
                $name = trans('validation.attributes.stop_duration_longer_than');
                break;
            case Event::TYPE_TIME_DURATION:
                $name = trans('validation.attributes.time_duration');
                break;
            case Event::TYPE_OFFLINE_DURATION:
                $name = trans('validation.attributes.offline_duration_longer_than');
                break;
            case Event::TYPE_MOVE_DURATION:
                $name = trans('validation.attributes.move_duration_longer_than');
                break;
            case Event::TYPE_IDLE_DURATION:
                $name = trans('validation.attributes.idle_duration_longer_than');
                break;
            case Event::TYPE_IGNITION_DURATION:
                $name = trans('validation.attributes.ignition_duration_longer_than');
                break;
            case Event::TYPE_IGNITION_ON:
                $name = trans('front.ignition_on');
                break;
            case Event::TYPE_IGNITION_OFF:
                $name = trans('front.ignition_off');
                break;
            case Event::TYPE_FUEL_FILL:
                $name = trans('front.fuel_fillings');
                break;
            case Event::TYPE_FUEL_THEFT:
                $name = trans('front.fuel_thefts');
                break;
            case Event::TYPE_TASK_NEW:
                $name = trans('front.task_new');
                break;
            case Event::TYPE_TASK_COMPLETE:
                $name = trans('front.task_complete');
                break;
            case Event::TYPE_TASK_FAILED:
                $name = trans('front.task_failed');
                break;
            case Event::TYPE_TASK_IN_PROGRESS:
                $name = trans('front.task_in_progress');
                break;
            case Event::TYPE_DISTANCE:
                $name = trans('front.distance_limit');
                break;
            case Event::TYPE_POI_STOP_DURATION:
                $name = trans('front.poi_stop_duration');
                break;
            case Event::TYPE_POI_IDLE_DURATION:
                $name = trans('front.poi_idle_duration');
                break;
            case Event::TYPE_SOS:
                $name = 'SOS';
                break;
            case Event::TYPE_PLUGGED_IN:
                $name = trans('front.plugged_in');
                break;
            case Event::TYPE_UNPLUGGED:
                $name = trans('front.unplugged');
                break;
            case Event::TYPE_MOVE_START:
                $name = trans('front.start_of_movement');
                break;
            case Event::TYPE_MOVE_START_FILTERED:
                $name = trans('front.start_of_movement') . ' (' . trans('validation.attributes.filter') . ')';
                break;
            default:
                $name = empty($this->attributes['message']) ? null : $this->attributes['message'];
        }

        return $name;
    }

    public function getTypeTitleAttribute()
    {
        return Arr::get(self::getTypeTitle($this->type), 'title', '-');
    }

    public function getTimeWithMessageAttribute()
    {
        return Formatter::time()->human($this->time) . ' - ' . $this->message;
    }

    public static function getTypeTitle($type)
    {
        return self::getTypeTitles()
            ->where('type', $type)
            ->first();
    }

    public static function getTypeTitles(): Collection
    {
        if (isset(self::$typeTitles)) {
            return self::$typeTitles;
        }

        return self::$typeTitles = collect([
            [
                'type' => Event::TYPE_ZONE_IN,
                'title' => trans('front.'.Event::TYPE_ZONE_IN),
            ],
            [
                'type' => Event::TYPE_ZONE_OUT,
                'title' => trans('front.'.Event::TYPE_ZONE_OUT),
            ],
            [
                'type' => Event::TYPE_DRIVER,
                'title' => trans('front.driver'),
            ],
            [
                'type' => Event::TYPE_DRIVER_UNAUTHORIZED,
                'title' => trans('front.driver_change_unauthorized'),
            ],
            [
                'type' => Event::TYPE_DRIVER_AUTHORIZED,
                'title' => trans('front.driver_change_authorized'),
            ],
            [
                'type' => Event::TYPE_OVERSPEED,
                'title' => trans('front.overspeed'),
            ],
            [
                'type' => Event::TYPE_STOP_DURATION,
                'title' => trans('front.stop_duration'),
            ],
            [
                'type' => Event::TYPE_TIME_DURATION,
                'title' => trans('front.time_duration'),
            ],
            [
                'type' => Event::TYPE_OFFLINE_DURATION,
                'title' => trans('front.offline_duration'),
            ],
            [
                'type' => Event::TYPE_MOVE_DURATION,
                'title' => trans('front.move_duration'),
            ],
            [
                'type' => Event::TYPE_IDLE_DURATION,
                'title' => trans('front.idle_duration'),
            ],
            [
                'type' => Event::TYPE_IGNITION_DURATION,
                'title' => trans('front.ignition_duration'),
            ],
            [
                'type' => Event::TYPE_IGNITION_ON,
                'title' => trans('front.ignition_on'),
            ],
            [
                'type' => Event::TYPE_IGNITION_OFF,
                'title' => trans('front.ignition_on'),
            ],
            [
                'type' => Event::TYPE_MOVE_START,
                'title' => trans('front.start_of_movement'),
            ],
            [
                'type' => Event::TYPE_MOVE_START_FILTERED,
                'title' => trans('front.start_of_movement') . ' (' . trans('validation.attributes.filter') . ')',
            ],
            [
                'type' => Event::TYPE_FUEL_FILL,
                'title' => trans('front.fuel_fillings'),
            ],
            [
                'type' => Event::TYPE_FUEL_THEFT,
                'title' => trans('front.fuel_thefts'),
            ],
            [
                'type' => Event::TYPE_TASK_NEW,
                'title' => trans('front.task_new'),
            ],
            [
                'type' => Event::TYPE_TASK_COMPLETE,
                'title' => trans('front.task_complete'),
            ],
            [
                'type' => Event::TYPE_TASK_FAILED,
                'title' => trans('front.task_failed'),
            ],
            [
                'type' => Event::TYPE_TASK_IN_PROGRESS,
                'title' => trans('front.task_in_progress'),
            ],
            [
                'type' => Event::TYPE_SOS,
                'title' => 'SOS',
            ],
            [
                'type' => Event::TYPE_CUSTOM,
                'title' => trans('front.custom_events'),
            ],
            [
                'type' => Event::TYPE_DISTANCE,
                'title' => trans('global.distance'),
            ],
        ]);
    }

    public function formatMessage()
    {
        $detail = $this->detail;

        return $this->name . ($detail ? " ($detail)" : "");
    }

    public function toArrayMassInsert()
    {
        return array_intersect_key(
            $this->getAttributes(),
            array_flip(array_merge($this->getFillable(), $this->getDates()))
        );
    }

    public function getTimePassedAttribute()
    {
        return Formatter::duration()
            ->human((time() - strtotime($this->time)));
    }

    public function toPosition(): TraccarPosition
    {
        return new TraccarPosition($this->toArray());
    }

    public function getIconAsset()
    {
        return self::getTypeIconAsset($this->type);
    }

    public static function getTypeIconAsset($type)
    {
        switch ($type) {
            case Event::TYPE_TIME_DURATION:
            case Event::TYPE_MOVE_DURATION:
            case Event::TYPE_STOP_DURATION:
            case Event::TYPE_IDLE_DURATION:
            case Event::TYPE_POI_IDLE_DURATION:
            case Event::TYPE_POI_STOP_DURATION:
                return asset("assets/icons/events_clock_l.svg");

            case self::TYPE_DEVICE_SUBSCRIPTION_EXPIRED:
            case self::TYPE_EXPIRED_USER:
                return asset("assets/icons/events_event.svg");

            default:
                return asset("assets/icons/events_{$type}_l.svg");
        }
    }
}
