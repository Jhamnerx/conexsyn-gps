<?php

namespace Tobuli\Entities;

use App\Events\Device\DeviceDisabled;
use App\Events\Device\DeviceEnabled;
use App\Events\Device\DeviceSubscriptionActivate;
use App\Events\Device\DeviceSubscriptionRenew;
use App\Events\DeviceCreated;
use App\Events\DeviceDeleted;
use App\Events\DeviceImeiChanged;
use App\Jobs\DeviceCameraDirChange;
use App\Jobs\TrackerConfigWithRestart;
use Carbon\Carbon;
use Formatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Sensors\Types\Blocked;
use Tobuli\Services\DeviceImageService;
use Tobuli\Services\DeviceModelCache;
use Tobuli\Services\EventWriteService;
use Tobuli\Traits\AttributesRelationsGetter;
use Tobuli\Traits\ChangeLogs;
use Tobuli\Traits\Chattable;
use Tobuli\Traits\Customizable;
use Tobuli\Traits\DisplayTrait;
use Tobuli\Traits\EventLoggable;
use Tobuli\Traits\FcmTokensTrait;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Includable;
use Tobuli\Traits\Nameable;
use Tobuli\Traits\Searchable;
use Tobuli\Utils\NetworkUtil;

class Device extends AbstractEntity implements DisplayInterface, FcmTokenableInterface, ChattableInterface
{
    use AttributesRelationsGetter;
    use ChangeLogs;
    use Chattable;
    use Customizable;
    use DisplayTrait;
    use EventLoggable;
    use FcmTokensTrait;
    use Filterable;
    use HasFactory;
    use HasApiTokens;
    use Includable;
    use Nameable;
    use Notifiable;
    use Searchable;

    const STATUS_ACK     = 'ack';
    const STATUS_ENGINE  = 'engine';
    const STATUS_EXPIRED = 'expired';
    const STATUS_OFFLINE = 'offline';
    const STATUS_ONLINE  = 'online';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MOVE = 'move';
    const STATUS_STOP = 'stop';
    const STATUS_PARK = 'park';
    const STATUS_IDLE = 'idle';
    const STATUS_ENGINE_ON = 'engine_on';
    const STATUS_ENGINE_OFF = 'engine_off';
    const STATUS_BLOCKED = 'blocked';

    const SCOPE_STATUSES = [
        self::STATUS_EXPIRED,
        self::STATUS_OFFLINE,
        self::STATUS_ONLINE,
        self::STATUS_INACTIVE,
        self::STATUS_MOVE,
        self::STATUS_STOP,
        self::STATUS_PARK,
        self::STATUS_IDLE,
        self::STATUS_ENGINE_ON,
        self::STATUS_ENGINE_OFF,
        self::STATUS_BLOCKED,
    ];

    const KIND_GENERAL = 0;
    const KIND_BEACON = 1;

    const STOP_DURATION_OFFSET = 10;

    /** Tipo de unidad para la retransmisión a MININTER (define endpoint e identificadores). */
    const MININTER_TYPE_SERENAZGO = 'serenazgo';
    const MININTER_TYPE_POLICIAL  = 'policial';

    public static array $displayField = ['imei', 'name'];

    protected $table = 'devices';

    protected $fillable = array(
        'deleted',
        'traccar_device_id',
        'timezone_id',
        'name',
        'imei',
        'icon_id',
        'model_id',
        'fuel_measurement_id',
        'fuel_quantity',
        'fuel_price',
        'fuel_per_km',
        'fuel_type',
        'fuel_emissions',
        'sim_number',
        'device_model',
        'plate_number',
        'vin',
        'registration_number',
        'object_owner',
        'additional_notes',
        'comment',
        'expiration_date',
        'tail_color',
        'tail_length',
        'engine_hours',
        'detect_engine',
        'detect_distance',
        'detect_speed',
        'min_moving_speed',
        'min_fuel_fillings',
        'min_fuel_thefts',
        'snap_to_road',
        'gprs_templates_only',
        'valid_by_avg_speed',
        'max_speed',
        'icon_colors',
        'parameters',
        'currents',
        'active',
        'forward',
        'sim_activation_date',
        'sim_expiration_date',
        'installation_date',
        'msisdn',
        'device_type_id',
        'app_tracker_login',
        'fuel_detect_sec_after_stop',
        'lbs',
        'custom_data',
        'authentication',
        'mtc',
        'mininter',
        'mininter_type',
    );

    protected $appends = [
        //'stop_duration'
        //'lat',
        //'lng',
        //'speed',
        //'course',
        //'altitude',
        //'protocol',
        //'time'
    ];

    //protected $hidden = ['currents'];

    protected $casts = [
        'currents' => 'array',
        'icon_colors' => 'array',
        'custom_data' => 'array',
        'mtc' => 'boolean',
        'mininter' => 'boolean',
    ];

    protected $searchable = [
        'name',
        'imei',
        'sim_number',
        'vin',
        'plate_number',
        'registration_number',
        'object_owner',
        'device_model',
        'additional_notes'
    ];
    protected $filterables = [
        'id',
        'imei',
        'sim_number',
        'msisdn',
        'vin',
        'plate_number',
        'registration_number',
        'object_owner',
        'device_model',
        'group_id',
        'device_id',
        'status',
        'online',
        'offline'
    ];

    protected $hidden = ['app_uuid'];

    private $attributesRelations = [
        'users_emails' => ['users'],
        'stop_duration' => ['traccar'],
        'speed' => ['traccar'],
        'status' => ['traccar'],
        'status_color' => ['traccar'],
        'protocol' => ['traccar'],
        'time' => ['traccar'],
    ];

    protected $dispatchesEvents = [
        'created' => DeviceCreated::class,
        'deleted' => DeviceDeleted::class,
    ];

    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    public function getIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            $traccarDevice = TraccarDevice::create([
                'name' => $device->name,
                'uniqueId' => $device->imei,
                'database_id' => $device->database_id
            ]);

            unset($device->database_id);

            $device->traccar_device_id = $traccarDevice->id;
        });

        static::updated(function ($device) {
            if ($device->isDirty('name') || $device->isDirty('imei')) {
                $device->traccar()->update([
                    'name' => $device->name,
                    'uniqueId' => $device->imei
                ]);
            }

            if ($device->isDirty('imei')) {
                event(new DeviceImeiChanged($device));
            }

            if ($device->isDirty('timezone_id')) {
                $device->applyPositionsTimezone();
            }
        });

        static::saving(function (Device $device) {
            if ($device->isDirty('forward')) {
                $hosts = empty($device->forward['ip']) ? [] : semicol_explode($device->forward['ip']);

                foreach ($hosts as $host) {
                    if (NetworkUtil::isHostSelfReferencing($host)) {
                        throw new ValidationException($host . ' host is referring to server IP');
                    }
                }
            }

            if ($device->isDirty('fuel_measurement_id') || $device->isDirty('fuel_quantity')) {
                if ($device->fuel_measurement_id == 4) {
                    $device->fuel_per_km = 0;
                    $device->fuel_per_h = convertFuelConsumption($device->fuel_measurement_id, $device->fuel_quantity);
                } else {
                    $device->fuel_per_km = convertFuelConsumption($device->fuel_measurement_id, $device->fuel_quantity);
                    $device->fuel_per_h = 0;
                }
            }
        });

        static::saved(function (Device $device) {
            $isImeiDirty = $device->isDirty('imei');

            if ($isImeiDirty) {
                UnregisteredDevice::where('imei', $device->imei)->delete();

                dispatch((new DeviceCameraDirChange($device, $device->getOriginal('imei'))));
            }

            if ($isImeiDirty && !$device->wasRecentlyCreated) {
                DeviceModelCache::setDevice($device, $device->getOriginal('imei'));
            } elseif ($isImeiDirty || $device->isDirty('model_id')) {
                DeviceModelCache::setDevice($device);
            }

            if ($device->isDirty('forward'))
                dispatch((new TrackerConfigWithRestart()));

            if ($device->isDirty('active')) {
                event($device->active ? new DeviceEnabled($device) : new DeviceDisabled($device));
            }
        });

        static::deleted(function ($device) {
            DeviceModelCache::deleteDevice($device);
        });
    }

    public function positions()
    {
        return $this->traccar->positions();
    }

    public function positionTraccar()
    {
        if ( ! $this->traccar) {
            return null;
        }

        return new \Tobuli\Entities\TraccarPosition([
            'id' => $this->traccar->lastestPosition_id,
            'device_id' => $this->traccar->id,
            'latitude' => $this->traccar->lastValidLatitude,
            'longitude' => $this->traccar->lastValidLongitude,
            'other' => $this->traccar->other,
            'speed' => $this->traccar->speed,
            'altitude' => $this->traccar->altitude,
            'course' => $this->traccar->course,
            'time' => $this->traccar->time,
            'device_time' => $this->traccar->device_time,
            'server_time' => $this->traccar->server_time,
            'protocol' => $this->traccar->protocol,
            'valid' => true
        ]);
    }

    public function createPositionsTable()
    {
        $connection = $this->positions()->getRelated()->getConnectionName();
        $tableName = $this->positions()->getRelated()->getTable();

        if (Schema::connection($connection)->hasTable($tableName))
            throw new \Exception(trans('global.cant_create_device_database'));

        Schema::connection($connection)->create($tableName, function(Blueprint $table)
        {
            $table->bigIncrements('id')->startingValue($this->traccar->latestPosition_id ?? 1);
            $table->double('altitude')->nullable();
            $table->double('course')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->text('other')->nullable();
            $table->double('speed')->nullable()->index();
            $table->datetime('time')->nullable()->index();
            $table->datetime('device_time')->nullable();
            $table->datetime('server_time')->nullable()->index();
            $table->text('sensors_values')->nullable();
            $table->tinyInteger('valid')->nullable();
            $table->double('distance')->nullable();
            $table->string('protocol', 20)->nullable();
        });
    }

    public function icon()
    {
        return $this->hasOne('Tobuli\Entities\DeviceIcon', 'id', 'icon_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function getIconAttribute()
    {
        $icon = $this->getRelationValue('icon');

        return $icon ? $icon->setStatus($this->getStatus()) : null;
    }

    public function traccar()
    {
        return $this->hasOne('Tobuli\Entities\TraccarDevice', 'id', 'traccar_device_id');
    }

    public function alerts()
    {
        return $this->belongsToMany('Tobuli\Entities\Alert', 'alert_device', 'device_id', 'alert_id')
            // escape deattached users devices
            ->when(config('tobuli.alerts_throw_user_device'), function($query) {
                $query->join('user_device_pivot', function ($join) {
                    $join
                        ->on('user_device_pivot.device_id', '=', 'alert_device.device_id')
                        ->on('user_device_pivot.user_id', '=', 'alerts.user_id');
                });
            })
            ->withPivot('started_at', 'fired_at', 'silenced_at', 'active_from', 'active_to');
    }

    public function events()
    {
        return $this->hasMany('Tobuli\Entities\Event', 'device_id');
    }

    public function last_event()
    {
        $query = $this->hasOne('Tobuli\Entities\Event', 'device_id');

        if ($user = getActingUser()) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('id', 'desc');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany('Tobuli\Entities\User', 'user_device_pivot', 'device_id', 'user_id')->withPivot('group_id');
    }

    /** Cola de posiciones pendientes de retransmitir a SUTRAN. */
    public function sutranPositions()
    {
        return $this->hasMany(Sutran::class, 'device_id');
    }

    /** Cola de posiciones pendientes de retransmitir a MININTER. */
    public function mininterPositions()
    {
        return $this->hasMany(Mininter::class, 'device_id');
    }

    /**
     * Usuario del que se toman ubigeo, ID de municipalidad y código de comisaría para MININTER.
     */
    public function mininterUser(): ?User
    {
        return $this->users->first(function (User $user) {
            return (bool) $user->is_municipalidad;
        });
    }

    public static function mininterTypes(): array
    {
        return [
            self::MININTER_TYPE_SERENAZGO => trans('front.mininter_type_serenazgo'),
            self::MININTER_TYPE_POLICIAL  => trans('front.mininter_type_policial'),
        ];
    }

    public function driver() {
        return $this->hasOne('Tobuli\Entities\UserDriver', 'id', 'current_driver_id');
    }

    public function drivers() {
        return $this->hasMany('Tobuli\Entities\UserDriver', 'device_id');
    }

    public function sensors() {
        return $this->hasMany('Tobuli\Entities\DeviceSensor', 'device_id');
    }

    public function services() {
        return $this->hasMany('Tobuli\Entities\DeviceService', 'device_id');
    }

    public function expenses()
    {
        return $this->hasMany('Tobuli\Entities\DeviceExpense', 'device_id');
    }

    public function timezone()
    {
        return $this->hasOne('Tobuli\Entities\Timezone', 'id', 'timezone_id');
    }

    public function deviceCameras() {
        return $this->hasMany('Tobuli\Entities\DeviceCamera', 'device_id');
    }

    public function group()
    {
        return $this->hasOne('Tobuli\Entities\DeviceGroup', 'id', 'group_id');
    }

    public function plans()
    {
        return $this->belongsToMany('Tobuli\Entities\DevicePlan', 'device_device_plan', 'device_id', 'plan_id');
    }

    public function deviceType()
    {
        return $this->hasOne('Tobuli\Entities\DeviceType', 'id', 'device_type_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function subscriptions()
    {
        return $this->hasManyThrough(
            'Tobuli\Entities\Subscription',
            'Tobuli\Entities\Order',
            'entity_id',
            'order_id',
            'id',
            'id'
        )->where('orders.entity_type', 'device')->active();
    }

    public function sentCommands()
    {
        return $this->belongsTo(SentCommand::class, 'imei', 'device_imei');
    }

    public function setDeviceTypeIdAttribute($value)
    {
        $this->attributes['device_type_id'] = empty($value) ? null : $value;
    }

    public function setTimezoneIdAttribute($value)
    {
        $this->attributes['timezone_id'] = empty($value) ? null : $value;
    }

    public function setFuelMeasurementIdAttribute($value)
    {
        $this->attributes['fuel_measurement_id'] = empty($value) ? null : $value;
    }

    public function setForwardAttribute($value)
    {
        if (Arr::get($value, 'active'))
            $this->attributes['forward'] = json_encode($value);
        else
            $this->attributes['forward'] = null;
    }

    public function getForwardAttribute($value)
    {
        return json_decode($value, TRUE);
    }

    public function isSimExpired()
    {
        static $display_sim_expired = null;

        if (is_null($display_sim_expired)) {
            $display_sim_expired = settings('plugins.display_sim_expired.status');
        }

        if (!$display_sim_expired)
            return false;

        if ( ! $this->hasSimExpireDate())
            return false;

        return  strtotime($this->sim_expiration_date) < time();
    }

    public function hasSimExpireDate()
    {
        if ( ! $this->sim_expiration_date)
            return false;

        if ($this->sim_expiration_date == '0000-00-00')
            return false;

        return true;
    }

    public function isExpiredWithoutExtra()
    {
        if ( ! $this->hasExpireDate())
            return false;

        return  strtotime($this->expiration_date) < time();
    }

    public function isExpired(): bool
    {
        if (!$this->isExpiredWithoutExtra()) {
            return false;
        }

        static $extraTime = null;

        if (is_null($extraTime))
            $extraTime = settings('main_settings.extra_expiration_time');

        return !$extraTime
            || \Carbon::parse($this->expiration_date)->addSeconds($extraTime)->getTimestamp() <= time();
    }

    public function hasExpireDate()
    {
        if ( ! $this->expiration_date)
            return false;

        if ($this->expiration_date == '0000-00-00 00:00:00')
            return false;

        if ($this->expiration_date == '0000-00-00')
            return false;

        return true;
    }

    public function isPlanAble()
    {
        static $enable_device_plans = null;

        if (is_null($enable_device_plans))
            $enable_device_plans = settings('main_settings.enable_device_plans');

        if (!$enable_device_plans)
            return false;

        if (!$this->hasExpireDate())
            return false;

        return true;
    }

    public function isConnected()
    {
        return Redis::connection('process')->get('connected.' . $this->imei) ? true : false;
    }

    public function getParameter($key, $default = null)
    {
        $parameters = $this->getParameters();

        return array_key_exists($key, $parameters) ? $parameters[$key] : $default;
    }

    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();

        $parameters[$key] = $value;

        $this->setParameters($parameters);
    }

    public function setParameters($value)
    {
        if ( is_array($value))
        {
            $xml = '<info>';

            foreach ($value as $key => $val)
            {
                if (is_numeric($key)) continue;
                if (is_array($val)) continue;

                $val = is_bool($val) ? ($val ? 'true' : 'false') : $val;
                $val = html_entity_decode($val);
                $xml .= "<{$key}>{$val}</$key>";
            }
            $xml .= '</info>';

            $value = $xml;
        }

        $this->traccar->other = $value;
    }

    public function getParameters()
    {
        if ( ! isset($this->traccar->other))
            return [];

        $parameters = parseXMLToArray($this->traccar->other);

        return $parameters;
    }

    public function getTotalDistance()
    {
        $distance = $this->getParameter('totaldistance') / 1000;

        return Formatter::distance()->format($distance);
    }

    public function getSpeed($position = null) {
        $speed = 0;

        if (is_null($position) && $this->getTimeoutStatus() != self::STATUS_ONLINE)
            return $speed;

        $sensor = $this->getSpeedSensor();

        if ($sensor) {
            $speed = $sensor->getValuePosition(
                is_null($position) ? $this->positionTraccar() : $position
            );
        } else {
            $speed = is_null($position) ? ($this->traccar->speed ?? null) : ($position->speed ?? null);
        }

        return $speed;
    }

    public function getTimeoutStatus()
    {
        static $minutes = null;

        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout') * 60;

        $serverTime = strtotime($this->getServerTime());

        if ($serverTime < time() - $minutes)
            return self::STATUS_OFFLINE;

        $ackTime = strtotime($this->getAckTime());

        return $serverTime > $ackTime ? self::STATUS_ONLINE : self::STATUS_ACK;
    }

    public function getStatusAttribute() {
        return $this->getStatus();
    }

    public function getStatus()
    {
        if (!$this->active)
            return self::STATUS_OFFLINE;

        if ($this->getBlockedStatus()) {
            return self::STATUS_BLOCKED;
        }

        if ($this->isExpired())
            return self::STATUS_OFFLINE;

        $status = $this->getTimeoutStatus();

        if ($status == self::STATUS_OFFLINE)
            return $status;

        $speed = $this->getSpeed();

        if ($speed >= $this->min_moving_speed)
            return self::STATUS_ONLINE;

        $stopDuration = $this->getStopDuration();

        if (!is_null($stopDuration) && $stopDuration < self::STOP_DURATION_OFFSET)
            return self::STATUS_ONLINE;

        if ($engine = $this->getEngineStatus()) {
            return self::STATUS_ENGINE;
        }

        return self::STATUS_ACK;
    }

    public function getStatusColorAttribute() {
        return $this->getStatusColor();
    }

    public function getStatusColor($status = null)
    {
        if (is_null($status)) {
            $status = $this->getStatus();
        }

        switch ($status) {
            case self::STATUS_ONLINE:
                $icon_status = 'moving';
                break;
            case self::STATUS_ACK:
                $icon_status = 'stopped';
                break;
            case self::STATUS_ENGINE:
                $icon_status = 'engine';
                break;
            case self::STATUS_BLOCKED:
                $icon_status = 'blocked';
                break;
            default:
                $icon_status = 'offline';
        }

        return Arr::get($this->icon_colors, $icon_status) ?? settings('device.status_colors.colors.' . $icon_status);
    }

    /**
     * @param array|string $type
     */
    public function getSensorsByType($type)
    {
        if (empty($this->sensors))
            return null;

        if (!is_array($type)) {
            $type = (array)$type;
        }

        return $this->sensors->filter(function ($sensor) use ($type) {
            return in_array($sensor->type, $type);
        });
    }

    public function getSensorByType($type)
    {
        $sensors = $this->sensors;

        if (empty($sensors))
            return null;

        foreach ($sensors as $sensor) {
            if ($sensor['type'] == $type) {
                $type_sensor = $sensor;
                break;
            }
        }

        if (empty($type_sensor))
            return null;

        return $type_sensor;
    }

    public function getRfidSensor()
    {
        return $this->getSensorByType('rfid');
    }

    public function getAnonymizerSensor()
    {
        return config('addon.sensor_type_anonymizer')
            ? $this->getSensorByType('anonymizer')
            : null;
    }

    public function getFuelTankSensor()
    {
        return $this->getSensorByType('fuel_tank');
    }

    public function getLoadSensor()
    {
        return $this->getSensorByType('load');
    }

    public function getOdometerSensor()
    {
        return $this->getSensorByType('odometer');
    }

    public function getSpeedSensor()
    {
        $detect_speed = $this->detect_speed ?? null;

        if ($detect_speed != 'speed_ecm')
            return null;

        return $this->getSensorByType('speed_ecm');
    }

    public function getEngineHoursSensor()
    {
        return $this->getSensorByType('engine_hours');
    }

    public function getEngineSensor()
    {
        $detect_engine = $this->getEngineDetect();

        if (empty($detect_engine))
            return null;

        return $this->getSensorByType($detect_engine);
    }

    public function getEngineStatusAttribute()
    {
        return $this->getEngineStatus();
    }

    public function getEngineStatus($formated = false)
    {
        $value = $this->getEngineStatusByTimestamps();

        if (is_null($value))
            return $formated ? '-' : null;

        if ($value && $this->getTimeoutStatus() == self::STATUS_OFFLINE)
            $value = false;

        if ($formated)
            return $value ? trans('front.on') : trans('front.off');

        return (bool)$value;
    }

    protected function getEngineDetect()
    {
        $detect_engine = $this->engine_hours == 'engine_hours' ? $this->detect_engine : $this->engine_hours;

        if (empty($detect_engine))
            return null;

        if ($detect_engine == 'gps')
            return null;

        return $detect_engine;
    }

    protected function getEngineStatusByTimestamps()
    {
        $detect_engine = $this->getEngineDetect();

        if (empty($detect_engine))
            return null;

        $engineOn  = isset($this->traccar->engine_on_at) ? strtotime($this->traccar->engine_on_at) : 0;
        $engineOff = isset($this->traccar->engine_off_at) ? strtotime($this->traccar->engine_off_at) : 0;

        return $engineOn && $engineOn > $engineOff;
    }

    protected function getEngineStatusBySensor()
    {
        $sensor = $this->getEngineSensor();

        if (empty($sensor))
            return null;

        if ($this->getTimeoutStatus() == self::STATUS_OFFLINE)
            return false;

        $value = $sensor->getValueCurrent($this)->getValue();

        return (bool)$value;
    }

    protected function getBlockedStatus(): bool
    {
        $sensor = $this->getBlockedSensor();

        return $sensor && $sensor->getValueCurrent($this)->getValue();
    }

    protected function getBlockedSensor(): ?DeviceSensor
    {
        return Blocked::isEnabled()
            ? $this->getSensorByType(Blocked::getType())
            : null;
    }

    public function getDistanceBetween($dateFrom, $dateTo)
    {
        $odometer = $this->getOdometerSensor();

        $query = $this->positions()->whereBetween('time', [$dateFrom, $dateTo])->limit(1);

        if ( ! is_null($odometer) && $odometer->shown_value_by != 'virtual_odometer') {
            $query->where('sensors_values', 'like', '%'.$odometer->id.'%');
        }

        $first     = (clone $query)->orderBy('time', 'asc');
        $positions = (clone $query)->orderBy('time', 'desc')->union($first)->get();

        if ($positions->count() < 2)
            return 0;

        if ( ! is_null($odometer) && $odometer->shown_value_by != 'virtual_odometer') {
            $to = (float)$odometer->getValuePosition($positions[0]);
            $from = (float)$odometer->getValuePosition($positions[1]);

            $distance = (empty($from) || empty($to)) ? 0 : $to - $from;
        } else {
            $distance = $positions[0]->getParameter('totaldistance') - $positions[1]->getParameter('totaldistance');

            //from meters to kilometers
            $distance = $distance / 1000;
        }

        return ($distance > 0) ? $distance : 0;
    }

    public function getProtocolAttribute()
    {
        return $this->traccar->protocol ?? null;
    }

    public function getDeviceTime()
    {
        return $this->traccar->device_time ?? null;
    }

    public function getTime()
    {
        return $this->traccar->time ?? null;
    }

    public function getAckTime()
    {
        return $this->traccar->ack_time ?? '';
    }

    public function getServerTime()
    {
        return $this->traccar->server_time ?? '';
    }

    public function getTimeAttribute()
    {
        if (!$this->active)
            return trans('front.disabled');

        if ($this->isSimExpired())
            return trans('front.sim_expired');

        if ($this->isExpired())
            return trans('front.expired');

        $time = max($this->getTime(), $this->getAckTime());

        if (empty($time) || substr($time, 0, 4) == '0000')
            return trans('front.not_connected');

        return Formatter::time()->human($time);
    }

    public function getOnlineAttribute() {
        return $this->getStatus();
    }

    public function getLatAttribute()
    {
        if ($this->isExpired())
            return null;

        return $this->latitude;
    }

    public function getLngAttribute()
    {
        if ($this->isExpired())
            return null;

        return $this->longitude;
    }

    public function getLatitudeAttribute()
    {
        if (($anonymizer = $this->getAnonymizerSensor()) && $anonymizer->getValueCurrent($this)->getValue()) {
            return $anonymizer->data['anon_latitude'] ?? null;
        }

        return isset($this->traccar->lastValidLatitude) ? cord($this->traccar->lastValidLatitude) : null;
    }

    public function getLongitudeAttribute()
    {
        if (($anonymizer = $this->getAnonymizerSensor()) && $anonymizer->getValueCurrent($this)->getValue()) {
            return $anonymizer->data['anon_longitude'] ?? null;
        }

        return isset($this->traccar->lastValidLongitude) ? cord($this->traccar->lastValidLongitude) : null;
    }

    public function getCourseAttribute() {
        $course = 0;

        if (isset($this->traccar->course))
            $course = $this->traccar->course;

        return round($course);
    }

    public function getAltitudeAttribute() {
        $altitude = 0;

        if (isset($this->traccar->altitude))
            $altitude = $this->traccar->altitude;

        return Formatter::altitude()->format($altitude);
    }

    public function getTailAttribute() {
        $length = $this->tail_length;

        if (!$length)
            return [];

        if (empty($this->traccar->latest_positions))
            return [];

        $tail = [];
        $arr = explode(';',  $this->traccar->latest_positions);

        foreach ($arr as $value) {
            if ($length-- < 0)
                break;

            try {
                list($lat, $lng) = explode('/', $value);

                array_unshift($tail, [
                    'lat' => $lat,
                    'lng' => $lng
                ]);
            } catch (\Exception $e) {}
        }

        return $tail;
    }

    public function getLatestPositionsAttribute() {
        return isset($this->traccar->latest_positions) ? $this->traccar->latest_positions : null;
    }

    public function getTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getServerTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getAckTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->ack_time) ? strtotime($this->traccar->ack_time) : 0;
    }

    public function getAckTimeAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->ack_time) ? $this->traccar->ack_time : null;
    }

    public function getServerTimeAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->server_time) ? $this->traccar->server_time : null;
    }

    public function getMovedAtAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->moved_at) ? $this->traccar->moved_at : null;
    }

    public function getMovedTimestampAttribute() {
        return $this->moved_at ? strtotime($this->moved_at) : 0;
    }

    public function getLastConnectTimeAttribute() {
        $lastConnect = $this->getLastConnectTimestampAttribute();

        return $lastConnect ? date('Y-m-d H:i:s', $lastConnect) : null;
    }

    public function getLastConnectTimestampAttribute() {
        return max($this->server_timestamp, $this->ack_timestamp);
    }

    public function getOtherAttribute() {
        return isset($this->traccar->other) ? $this->traccar->other : null;
    }

    public function getSpeedAttribute() {
        return Formatter::speed()->format($this->getSpeed());
    }

    public function getIdleDuration()
    {
        $engine_off_at = isset($this->traccar->engine_off_at) ? strtotime($this->traccar->engine_off_at) : 0;
        $engine_on_at  = isset($this->traccar->engine_on_at) ? strtotime($this->traccar->engine_on_at) : 0;
        $moved_at      = isset($this->traccar->moved_at) ? strtotime($this->traccar->moved_at) : 0;
        $time          = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $server_time   = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
        $engine_changed_at = isset($this->traccar->engine_changed_at) ? strtotime($this->traccar->engine_changed_at) : 0;

        if ( ! $moved_at)
            return 0;

        if ( ! $engine_off_at)
            return 0;

        if ($engine_on_at < $engine_off_at)
            return 0;

        $check_at = max($engine_changed_at, $moved_at);

        //device send incorrcet self timestamp
        if ($server_time > $time)
            return time() - $check_at + ($time - $server_time);

        return time() - $check_at;
    }

    public function getIdleDurationAttribute()
    {
        $duration = $this->getIdleDuration();

        return Formatter::duration()->human($duration);
    }

    public function getIgnitionDuration()
    {
        $engineOn      = isset($this->traccar->engine_on_at) ? strtotime($this->traccar->engine_on_at) : 0;
        $engineOff     = isset($this->traccar->engine_on_off) ? strtotime($this->traccar->engine_on_off) : 0;
        $engineChanged = isset($this->traccar->engine_changed_at) ? strtotime($this->traccar->engine_changed_at) : 0;
        $time          = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $serverTime    = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;

        if (! $engineOn || ! $engineChanged) {
            return 0;
        }

        if ($engineOn < $engineOff)
            return 0;

        if ($engineChanged >= $engineOn) {
            return 0;
        }

        //device sent incorrcet self timestamp
        if ($serverTime > $time) {
            return time() - $engineChanged + ($time - $serverTime);
        }

        return time() - $engineChanged;
    }

    public function getIgnitionDurationAttribute()
    {
        $duration = $this->getIgnitionDuration();

        return Formatter::duration()->human($duration);
    }

    public function getStopDuration()
    {
        $moved_at    = isset($this->traccar->moved_at) ? strtotime($this->traccar->moved_at) : 0;
        $time        = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $server_time = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;

        if ( ! $moved_at)
            return null;

        //device send incorrect self timestamp
        if ($time > $server_time )
            return time() - $moved_at + ($time - $server_time);

        return time() - $moved_at;
    }

    public function getStopDurationAttribute()
    {
        $duration = $this->getStopDuration();

        if (is_null($duration))
            return '-';

        if ($duration < 5)
            $duration = 0;

        return Formatter::duration()->human($duration);
    }

    public function generateTail() {
        $limit = 15;

        $positions = $this->positions()
            ->where('distance', '>', 0.02)
            ->orderliness()
            ->limit($limit)
            ->get();

        $tail_positions = [];

        foreach ($positions as $position) {
            $tail_positions[] = $position->latitude.'/'.$position->longitude;
        }

        $this->traccar->update([
            'latest_positions' => implode(';', $tail_positions)
        ]);
    }

    public function applyPositionsTimezone()
    {
        if ( !$this->timezone_id || $this->timezone_id == 57 ) {
            $value = 'device_time';
        } else {
            list($hours, $minutes) = explode(' ', $this->timezone->time);

            if ($this->timezone->prefix == 'plus')
                $value = "DATE_ADD(device_time, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
            else
                $value = "DATE_SUB(device_time, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
        }

        $this->traccar()->update(['time' => DB::raw($value)]);
        $this->positions()->update(['time' => DB::raw($value)]);
    }

    public function isCorrectUTC()
    {
        $change = 900; //15 mins

        $server_time = strtotime( $this->getServerTime() );
        $device_time = strtotime( $this->getDeviceTime() );

        if ($server_time && (abs($server_time - $device_time) < $change))
            return true;

        return false;
    }

    public function canChat()
    {
        if ($this->app_tracker_login) {
            return true;
        }

        return $this->protocol == 'osmand';
    }

    public function scopeFilterDeviceId($query, $device_id)
    {
        if (is_array($device_id)) {
            $query->whereIn('devices.id', $device_id);
        } else {
            $query->where('devices.id', $device_id);
        }
    }

    public function scopeFilterGroupId($query, $group_id)
    {
        if (is_array($group_id)) {
            $query->whereIn('user_device_pivot.group_id', $group_id);
        } else {
            $query->where('user_device_pivot.group_id', $group_id ?: 0);
        }
    }

    public function scopeVisible($query)
    {
        $query->where('user_device_pivot.active', 1);
    }

    public function scopeNPerGroup($query, $group, $n = 10)
    {
        // queried table
        $table = ($this->getTable());

        // initialize MySQL variables inline
        $query->from( DB::raw("(SELECT @rank:=0, @group:=0) as vars, {$table}") );

        // if no columns already selected, let's select *
        if ( ! $query->getQuery()->columns)
        {
            $query->select("{$table}.*");
        }

        // make sure column aliases are unique
        $groupAlias = 'group_'.md5(time());
        $rankAlias  = 'rank_'.md5(time());

        // apply mysql variables
        $query->addSelect(DB::raw(
            "@rank := IF(@group = {$group}, @rank+1, 1) as {$rankAlias}, @group := {$group} as {$groupAlias}"
        ));

        // make sure first order clause is the group order
        $query->getQuery()->orders = (array) $query->getQuery()->orders;
        array_unshift($query->getQuery()->orders, ['column' => $group, 'direction' => 'asc']);

        // prepare subquery
        $subQuery = $query->toSql();

        // prepare new main base Query\Builder
        $newBase = $this->newQuery()
            ->from(DB::raw("({$subQuery}) as {$table}"))
            ->mergeBindings($query->getQuery())
            ->where($rankAlias, '<=', $n)
            ->getQuery();

        // replace underlying builder to get rid of previous clauses
        $query->setQuery($newBase);
    }

    public function changeDriver($driver, $rfid = null, $time = null, $withoutAlerts = false)
    {
        if (is_null($time))
            $time = date('Y-m-d H:i:s');

        $this->current_driver_id = $driver->id ?? null;
        $this->current_driver_rfid = $driver->rfid ?? $rfid;
        $this->save();

        DB::table('user_driver_position_pivot')->insert([
            'device_id' => $this->id,
            'driver_id' => $driver->id ?? null,
            'rfid'      => $driver->rfid ?? $rfid,
            'date'      => $time
        ]);

        if (!$driver || $withoutAlerts)
            return;

        $position = $this->positionTraccar();

        if (is_null($position))
            return;

        $alerts = $this->alerts->filter(function($item){
            return $item->type == 'driver';
        });

        foreach ($alerts as $alert) {
            $event = $this->events()->make([
                'type'         => 'driver',
                'user_id'      => $alert->user_id,
                'alert_id'     => $alert->id,
                'device_id'    => $this->id,
                'geofence_id'  => null,
                'position_id'  => $position->id,
                'altitude'     => $position->altitude,
                'course'       => $position->course,
                'latitude'     => $position->latitude,
                'longitude'    => $position->longitude,
                'speed'        => $position->speed,
                'time'         => $position->time,
                'message'      => $driver->name,
                'additional'   => [
                    'driver_id'   => $driver->id,
                    'driver_name' => $driver->name
                ]
            ]);

            $event->channels = $alert->channels;

            (new EventWriteService())->write([$event]);
        }
    }

    public function getDriversLog(array $options = []): array
    {
        $baseQuery = DB::table('user_driver_position_pivot AS dp')
            ->select('d.*', 'dp.date')
            ->join('user_drivers AS d', 'dp.driver_id', '=', 'd.id')
            ->where('dp.device_id', $this->id);

        $query = (clone $baseQuery)->groupBy('date');

        if (isset($options['from'])) {
            $query->where('dp.date', '>=', $options['from']);
        }

        if (isset($options['to'])) {
            $query->where('dp.date', '<=', $options['to']);
        }

        if (isset($options['from']) && isset($options['offset_from'])) {
            $query->unionAll((clone $baseQuery)
                ->where('dp.date', '<=', $options['from'])
                ->limit($options['offset_from'])
                ->orderBy('date', 'DESC')
            );
        }

        if (isset($options['to']) && isset($options['offset_to'])) {
            $query->unionAll((clone $baseQuery)
                ->where('dp.date', '>=', $options['to'])
                ->limit($options['offset_to'])
                ->orderBy('date', 'ASC')
            );
        }

        return $query
            ->orderBy('date', $options['order'] ?? 'ASC')
            ->get()
            ->all();
    }

    public function beacons(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_current_beacons_pivot', 'device_id', 'beacon_id');
    }

    public function setExpirationDateAttribute($value)
    {
        $this->attributes['expiration_date'] = is_null($value) ? '0000-00-00 00:00:00' : $value;
    }

    public function getExpirationDateAttribute($value)
    {
        if ($value == '0000-00-00') {
            return null;
        }

        if ($value == '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    public function scopeViewBox($query, $topLat, $topLng, $bottomLat, $bottomLng)
    {
        return $query->traccarJoin()
            ->where('lastValidLatitude', '>', $topLat)
            ->where('lastValidLongitude', '>', $topLng)
            ->where('lastValidLatitude', '<', $bottomLat)
            ->where('lastValidLongitude', '<', $bottomLng);
    }

    public function scopeChatable($query)
    {
        //need join outside where closure
        $query->traccarJoin();

        return $query->where(function($q) {
            $q->protocol('osmand');
            $q->orWhere('app_tracker_login', 1);
        });
    }

    public function scopeHasExpiration($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('expiration_date');
            $q->where('expiration_date', '!=', '0000-00-00');
            $q->where('expiration_date', '!=', '0000-00-00 00:00:00');
        });
    }

    public function scopeHasntExpiration($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expiration_date');
            $q->orWhere('expiration_date', '=', '0000-00-00');
            $q->orWhere('expiration_date', '=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsExpiringAfter($query, int $days, bool $addExtra = true)
    {
        $dateFrom = Carbon::now();
        $dateTo = Carbon::now()->addDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $dateFrom->subSeconds($extraTime);
            $dateTo->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '>=', $dateFrom)
            ->where('expiration_date', '<=', $dateTo);
    }

    public function scopeIsExpiredBefore($query, int $days, bool $addExtra = true)
    {
        $date = Carbon::now()->subDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', $date);
    }

    public function scopeExpired($query, bool $addExtra = true)
    {
        $date = Carbon::now();

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', $date);
    }

    public function scopeExpiredForLastDays($query, int $days = 0, bool $addExtra = true)
    {
        $date = Carbon::now()->subDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query->expired()->where('expiration_date', '>=', $date);
    }

    public function scopeUnexpired($query, bool $addExtra = true)
    {
        $date = Carbon::now();

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->where(function($q) use ($date) {
                $q->hasntExpiration();
                $q->orWhere(function($q2) use ($date) {
                    $q2->hasExpiration()->where('expiration_date', '>', $date);
                });
            });
    }

    public function scopeHasSimExpiration($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('sim_expiration_date');
            $q->where('sim_expiration_date', '!=', '0000-00-00');
            $q->where('sim_expiration_date', '!=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsSimExpiringAfter($query, $days)
    {
        return $query
            ->hasSimExpiration()
            ->where('sim_expiration_date', '>=', Carbon::now())
            ->where('sim_expiration_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeIsSimExpiredBefore($query, $days)
    {
        return $query
            ->hasSimExpiration()
            ->where('sim_expiration_date', '<=', Carbon::now()->subDays($days));
    }

    public function scopeFilterUserAbility($query, User $user, $ability = 'own') {
        return $query->with('users')->get()->filter(function($device) use ($user, $ability) {
            return $user->can($ability, $device);
        });
    }

    public function scopeWhereIdOrImei(Builder $query, $value)
    {
        return $query->where(function (Builder $query) use ($value) {
            $query->where('id', $value);
            $query->orWhere('imei', $value);
        });
    }

    public static function getFields()
    {
        $fields = [
            'id' => trans('validation.attributes.id'),
            'name' => trans('validation.attributes.name'),
            'imei' => trans('validation.attributes.imei'),
            'sim_number' => trans('validation.attributes.sim_number'),
            'vin' => trans('validation.attributes.vin'),
            'device_model' => trans('validation.attributes.device_model'),
            'plate_number' => trans('validation.attributes.plate_number'),
            'registration_number' => trans('validation.attributes.registration_number'),
            'object_owner' => trans('validation.attributes.object_owner'),
            'additional_notes' => trans('validation.attributes.additional_notes'),
            'comment' => trans('validation.attributes.comment'),
            'active' => trans('validation.attributes.active'),

            //'fuel_quantity' => trans('validation.attributes.fuel_quantity'),
            //'fuel_price' => trans('validation.attributes.fuel_price'),

            'users_list' => trans('admin.users'),
            'protocol' => trans('front.protocol'),
            'latitude' => trans('front.latitude'),
            'longitude' => trans('front.longitude'),
            'altitude' => trans('front.altitude'),
            'course' => trans('front.course'),
            'speed' => trans('front.speed'),
            'last_connect_time' => trans('admin.last_connection'),
            'stop_duration' => trans('front.stop_duration'),

            'created_at' => trans('validation.attributes.created_at'),
            'expiration_date' => trans('validation.attributes.expiration_date'),
        ];

        if (settings('plugins.sim_blocking.status')) {
            $fields['msisdn'] = trans('validation.attributes.msisdn');
        }

        if (settings('plugins.additional_installation_fields.status')) {
            $fields['sim_activation_date'] = trans('validation.attributes.sim_activation_date');
            $fields['sim_expiration_date'] = trans('validation.attributes.sim_expiration_date');
            $fields['installation_date']   = trans('validation.attributes.installation_date');
        }

        if (config('addon.device_type')) {
            $fields['deviceType.title'] = trans('validation.attributes.device_type_id');
        }

        return $fields;
    }

    public function getUsersEmailsAttribute()
    {
        return $this
            ->users
            ->filter(function($user){
                return auth()->user()->can('show', $user);
            })
            ->implode('email', ', ');
    }

    public function getImageAttribute()
    {
        return (new DeviceImageService($this))->get();
    }

    public function getPlanAttribute()
    {
        return $this->plans->first() ?? null;
    }

    public function getPlanIdAttribute()
    {

        return $this->plan->id ?? null;
    }

    public function getNameWithSimNumberAttribute()
    {
        return $this->name." ({$this->sim_number})";
    }

    public function isMove() {
        return $this->getStatus() == self::STATUS_ONLINE;
    }

    public function isIdle() {
        return $this->getStatus() == self::STATUS_ENGINE;
    }

    public function isStop() {
        return $this->getStatus() == self::STATUS_ACK;
    }

    public function isOffline() {
        return $this->getTimeoutStatus() === self::STATUS_OFFLINE;
    }

    public function isPark()
    {
        return $this->isStop() && ! $this->isIdle();
    }

    public function isOfflineFrom($date) {
        $time = strtotime( max($this->getServerTime(), $this->getAckTime()) );

        return Carbon::parse($date)->timestamp > $time;
    }

    public function isInactive()
    {
        $time = strtotime( max($this->getServerTime(), $this->getAckTime()) );

        return Carbon::now()->subMinutes(settings('main_settings.default_object_inactive_timeout'))->timestamp > $time;
    }

    public function isNeverConnected() {
        return is_null($this->getServerTime()) && is_null($this->getAckTime());
    }

    public function wasConnected() {
        return ! $this->isNeverConnected();
    }

    public function scopeTraccarJoin($query) {
        if ($query->isJoined("traccar_devices"))
            return $query;

        //prevent traccar.devices id overwrite
        $selects = $query->getQuery()->columns;
        if (!($selects && in_array('devices.*', $selects))) {
            $query->addSelect('devices.*');
        }

        return $query->leftJoin("traccar_devices", 'devices.traccar_device_id', '=', 'traccar_devices.id');
    }

    public function scopeWasConnected($query) {
        return $query
            ->traccarJoin()
            ->whereNotNull('traccar_devices.server_time');
    }

    public function scopeNeverConnected($query) {
        return $query
            ->traccarJoin()
            ->whereNull('traccar_devices.server_time');
    }

    public function scopeConnectedAfter($query, $time) {
        return $query
            ->traccarJoin()
            ->where('traccar_devices.server_time', '>=', $time);
    }

    public function scopeConnectedBefore($query, $time) {
        return $query
            ->traccarJoin()
            ->where('traccar_devices.server_time', '<', $time);
    }

    public function scopeUpdatedAfter($query, $time)
    {
        return $query
            ->traccarJoin()
            ->where('traccar_devices.updated_at', '>=', $time);
    }

    public function scopeUpdatedBefore($query, $time)
    {
        return $query
            ->traccarJoin()
            ->where('traccar_devices.updated_at', '<', $time);
    }

    public function scopeStatus(Builder $query, $statuses): Builder
    {
        if (!is_array($statuses)) {
            $statuses = (array)$statuses;
        }

        $statuses = array_filter($statuses, function ($status) {
            return in_array($status, self::SCOPE_STATUSES);
        });

        if (empty($statuses)) {
            return $query;
        }

        $query->traccarJoin();

        return $query->where(function (Builder $query) use ($statuses) {
            foreach ($statuses as $status) {
                $scope = Str::camel($status);

                $query->orWhere(function (Builder $query) use ($scope) {
                    $query->$scope();
                });
            }
        });
    }

    public function scopeFilterStatus($query, $status)
    {
        $query->status($status);
    }

    public function scopeFilterOnline($query, $value)
    {
        $query->online($value);
    }

    public function scopeFilterOffline($query, $value)
    {
        $query->offline($value);
    }

    public function scopeOnline($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes(intval($minutes));

        return $query->connectedAfter($time);
    }

    public function scopeOffline($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes(intval($minutes));

        return $query->connectedBefore($time);
    }

    public function scopeInactive($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_inactive_timeout');

        return $query->offline($minutes);
    }

    public function scopeBlocked($query)
    {
        return $query->whereHas('sensors', function ($q) {
            $q->where('type', 'blocked')->where('value', '1');
        });
    }

    public function scopeMove($query) {

        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar_devices.moved_at')
            ->whereRaw('traccar_devices.moved_at > COALESCE(traccar_devices.stoped_at, 0)');
    }

    public function scopeStop($query) {
        return $query
            ->traccarJoin()
            ->wasConnected()
            ->where(function($q) {
                $q->whereNull('traccar_devices.moved_at');
                $q->orWhereRaw('COALESCE(traccar_devices.stoped_at, 0) > traccar_devices.moved_at');
            });
    }

    public function scopeStopDuration($query, $minutes)
    {
        $time = Carbon::now()->subMinutes($minutes);

        return $query
            ->stop()
            ->where('traccar_devices.stop_begin_at', '<', $time);
    }

    public function scopePark($query) {
        return $query
            ->engineOff()
            ->stop()
            ->online();
    }

    public function scopeIdle($query) {
        return $query
            ->engineOn()
            ->stopDuration(self::STOP_DURATION_OFFSET)
            ->online();
    }

    public function scopeEngineOn($query) {
        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar_devices.engine_on_at')
            ->whereRaw('traccar_devices.engine_on_at > COALESCE(traccar_devices.engine_off_at, 0)');
    }

    public function scopeEngineOff($query) {
        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar_devices.engine_off_at')
            ->whereRaw('traccar_devices.engine_off_at > COALESCE(traccar_devices.engine_on_at, 0)');
    }

    public function scopeProtocol($query, $protocol)
    {
        $query->traccarJoin();

        if (is_null($protocol))
            $query->whereNull('traccar_devices.protocol');

        if (is_array($protocol))
            $query->whereIn('traccar_devices.protocol', $protocol);

        if (is_string($protocol))
            $query->where('traccar_devices.protocol', $protocol);

        return $query;
    }

    public function scopeGroupProtocols($query)
    {
        $query
            ->traccarJoin()
            ->with('traccar')
            ->groupBy('traccar_devices.protocol')
            ->whereNotNull('traccar_devices.protocol');

        return $query;
    }

    /**
     * @param  Builder  $query
     * @param int|int[] $ids
     * @return Builder
     */
    public function scopeInGeofences(Builder $query, $ids): Builder
    {
        if (!is_array($ids)) {
            $ids = (array)$ids;
        }

        return $query
            ->traccarJoin()
            ->whereNotNull(['traccar_devices.lastValidLatitude', 'traccar_devices.lastValidLongitude'])
            ->whereExists(function ($query) use ($ids) {
                $query = (new Builder($query))
                    ->setModel(new Geofence())
                    ->whereIn('id', $ids)
                    ->containPoint('traccar_devices.lastValidLatitude', 'traccar_devices.lastValidLongitude');
            });
    }

    public function scopeKindGeneral(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_GENERAL);
    }

    public function scopeKindBeacon(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_BEACON);
    }

    public function isGeneral(): bool
    {
        return $this->kind === self::KIND_GENERAL;
    }

    public function isBeacon(): bool
    {
        return $this->kind === self::KIND_BEACON;
    }

    public function activate(DevicePlan $plan, $expirationDate)
    {
        $this->plans()->sync([$plan->id]);

        $this->update([
            'expiration_date' => $expirationDate,
        ]);

        event(new DeviceSubscriptionActivate($this));
    }

    public function renew($expirationDate)
    {
        $this->setExpirationDate($expirationDate);

        event(new DeviceSubscriptionRenew($this));
    }

    public function setExpirationDate($expirationDate)
    {
        $this->update([
            'expiration_date' => $expirationDate,
        ]);
    }

    public function scopeSelectUsersEmails(
        Builder $query,
        ?User $user = null,
        string $alias = 'users_list',
        string $separator = ', '
    ): Builder {
        $selects = $query->getQuery()->columns;
        if (!($selects && in_array('devices.*', $selects))) {
            $query->addSelect('devices.*');
        }

        return $query->selectSub(fn (QueryBuilder $query) => $query
            ->selectRaw("GROUP_CONCAT(tmp_u.email SEPARATOR \"$separator\")")
            ->from('users', 'tmp_u')
            ->join('user_device_pivot AS tmp_p', 'tmp_p.user_id', '=', 'tmp_u.id')
            ->when($user, fn (QueryBuilder $query) => User::queryAccessible($query, $user, 'tmp_u'))
            ->whereColumn('tmp_p.device_id', '=', 'devices.id') // putting it in join, causes error on MySQL 5.5
            , $alias);
    }
}
