<?php namespace Tobuli\Entities;

use App\Events\DeviceSensorDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tobuli\Sensors\Extractions\Calibration;
use Tobuli\Sensors\SensorsManager;
use Tobuli\Sensors\Value;
use Tobuli\Traits\ChangeLogs;

class DeviceSensor extends AbstractEntity
{
    use HasFactory, ChangeLogs;

    protected $table = 'device_sensors';

    protected $fillable = array(
        'user_id',
        'device_id',
        'name',
        'type',
        'tag_name',
        'add_to_history',
        'add_to_graph',
        'shown_value_by',
        'full_tank',
        'full_tank_value',
        'fuel_tank_name',
        'min_value',
        'max_value',
        'formula',
        'odometer_value',
        'odometer_value_unit',
        'value',
        //'value_formula',
        //'show_in_popup',
        'unit_of_measurement',
        'unit_type',
        'on_tag_value',
        'off_tag_value',
        'on_type',
        'off_type',
        'calibrations',
        'skip_calibration',
        'skip_empty',
        'reset_blank',
        'decbin',
        'hexbin',
        'bitcut',
        'mappings',
        'data',
        'ascii',
        'value_set_at',
        'value_changed_at',
    );

    private $cacheSensor;

    private $cacheCalibrations;

    protected $casts = [
        'bitcut' => 'array',
        'mappings' => 'array',
        'data' => AsArrayObject::class
    ];

    public static string $displayField = 'name';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->type == 'odometer' && empty($model->value)) {
                $model->value = 0;
            }
        });

        static::saving(function ($model) {
            if ($model->type == 'odometer' && empty($model->value) && $model->isDirty('value')) {
                $model->value = 0;
            }
        });

        static::deleting(function ($sensor) {
            if ($device = $sensor->device) {
                event(new DeviceSensorDeleted($device, $sensor));
            }
        });
    }

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function device() {
        return $this->hasOne('Tobuli\Entities\Device', 'id', 'device_id');
    }

    /**
     * @return \Tobuli\Sensors\Sensor|null
     */
    public function getTypeObject()
    {
        if ( ! $this->type)
            return null;

        return (new SensorsManager())->resolveType($this->type);
    }

    public function getTypeTitleAttribute($value)
    {
        if ($typeObject = $this->getTypeObject())
            return $typeObject::getTypeTitle();

        return null;
    }

    /**
     * @return mixed|\Tobuli\Sensors\Sensor
     * @throws \Exception
     */
    protected function getSensor()
    {
        return $this->cacheSensor ?? $this->cacheSensor = (new SensorsManager())->build($this);
    }

    public function setOdometerValueAttribute($value)
    {
        if ($this->type != 'odometer')
            return;

        if ($this->odometer_value_unit == 'mi')
            $value = milesToKilometers($value);

        $this->attributes['value'] = $value;
    }

    public function getOdometerValueAttribute()
    {
        if ($this->type != 'odometer')
            return null;

        $value = floatval($this->value);

        if ($this->odometer_value_unit == 'mi')
            return kilometersToMiles($value);

        return $value;
    }

    public function setAddToHistoryAttribute($value)
    {
        $value = empty($value) ? 0 : $value;

        $this->attributes['add_to_history'] = $value;
    }

    public function setCalibrationsAttribute($value)
    {
        $this->attributes['calibrations'] = serialize($value);
    }

    public function getCalibrationsAttribute($value)
    {
        return unserialize($value);
    }

    public function getHashAttribute($value)
    {
        return md5($this->type . $this->name);
    }

    public function getUnit()
    {
        return $this->getSensor()->getUnit() ?? '';
    }

    public function formatName()
    {
        $description = '';

        if (!empty($this->fuel_tank_name))
            $description = ' ('.$this->fuel_tank_name.')';

        return htmlspecialchars($this->name . $description);
    }

    public function resetValue()
    {
        $this->value = null;
    }

    //public for positionWrite and rfid listner
    public function setValue($value, $position = null)
    {
        if ($this->isCounter())
            return false;

        if ($this->type == 'anonymizer' && !$value) {
            if (is_null($this->data))
                $this->data = [];

            $this->data['anon_latitude'] = $position->latitude;
            $this->data['anon_longitude'] = $position->longitude;
        }

        if (is_null($value) && (!$this->reset_blank))
            return false;

        if (is_array($value))
            return false;

        if (is_object($value))
            return false;

        if ($this->skip_empty && empty($value))
            return false;

        $time = $position->time ?? date('Y-m-d H:i:s');

        if ($time < $this->value_set_at)
            return false;

        $this->value_set_at = $time;

        if ($value !== $this->value)
            $this->value_changed_at = $this->value_set_at;

        $this->value = $value;

        if ($this->type == 'odometer' && $this->shown_value_by == 'connected_odometer')
            $this->value_formula = $value;

        return true;
    }

    //public for positionWrite
    public function setCounter($value)
    {
        if (empty($this->value))
            $this->value = 0;

        $this->value = intval($this->value) + intval($value);
    }

    //public for positionWrite sensor edit form
    public function getCounter()
    {
        return $this->value ?? 0;
    }

    //public for positionWrite
    public function isCounter()
    {
        return $this->type == 'counter';
    }

    //public for positionWrite
    public function isPositionValue()
    {
        return $this->getSensor()::isPositionValue();
    }

    public function isPersistent()
    {
        return $this->getSensor()::isPersistent();
    }

    //public for positionWrite and history action
    public function isUpdatable()
    {
        if ($this->type == 'odometer' && $this->shown_value_by == 'virtual_odometer')
            return false;

        return $this->getSensor()::isUpdatable();
/*
        return $this->isBooleanValue() || in_array($this->type, [
                'numerical',
                'textual',
                'odometer',
                'engine_hours',
                'fuel_tank',
                'fuel_consumption',
                'temperature',
                'tachometer',
                'battery',
                'speed_ecm',
        ]);
*/
    }

    public function getValueScale($value)
    {
        if ($this->type == 'gsm' || $this->type == 'battery') {
            return ceil(($value ? floatval($value) : 0) / 20);
        }

        return null;
    }

    public function getCurrent()
    {
        if ($this->type == 'odometer' && $this->shown_value_by == 'virtual_odometer')
            return round($this->odometer_value, 3);

        if (!$this->getSensor()::isUpdatable() && $this->getSensor()::getTimeout())
            return null;

        return $this->value;
    }

    /**
     * @param $position
     * @return Value
     */
    public function getValueCurrent($position)
    {
        $value = $this->getValue($position->other, false);

        if (is_null($value)) {
            $value = $this->getCurrent();
        }

        if ($value === '-') {
            $value = null;
        }

        return $this->getSensor()->toValue($value);
    }

    public function getValuePosition($position)
    {
        return $this->getSensor()->getPositionValue($position);
    }

    //public for positionWrite
    public function getValueParameters($position)
    {
        return $this->getSensor()->getPositionParameterValue($position);
    }

    public function getValue($other, $newest = true)
    {
        if ($this->type == 'odometer' && $this->shown_value_by == 'virtual_odometer')
            return $this->getCurrent();

        if ($this->isCounter())
            return $this->getCurrent();

        $value = $this->getSensor()->getDataValue($other);

        if (is_null($value) && $newest) {
            return $this->value && $this->value != '-' ? $this->value : null;
        }

        return $value;
    }

    public function getValueFormated($position, $newest = true) {
        $value = $this->getValue($position->other, $newest);

        if (is_null($value))
            return '-';

        return $this->getSensor()->getValueFormatted($value);
    }

    protected function getCalibrations()
    {
        if (!isset($this->cacheCalibrations))
        {
            $this->cacheCalibrations = $this->calibrations
                ? new Calibration($this->calibrations, $this->skip_calibration)
                : false;
        }

        return $this->cacheCalibrations;
    }

    public function getMaxTankValue()
    {
        if ($this->full_tank)
            return $this->full_tank;

        if ($calibrations = $this->getCalibrations())
            return $calibrations->getMaxValue();

        return null;
    }

    public function getIconAsset($value = null)
    {
        $type = $this->type;

        switch ($type) {
            case "battery_external":
                $name = 'battery';
                break;
            case "route_color_2":
            case "route_color_3":
                $name = 'route_color';
                break;
            default:
                $name = $type;
        }

        switch ($type) {
            case 'battery':
            case 'gsm':
                $name .= '-' . ceil(($value ? floatval($value) : 0) / 20);
                break;
        }

        return asset("assets/icons/sensors_{$name}_l.svg");
    }

    protected function performUpdate(Builder $query)
    {
        if (!array_diff(array_keys($this->getDirty()), ['value', 'value_formula', 'value_set_at', 'value_changed_at'])) {
            $this->timestamps = false;
        }

        $result = parent::performUpdate($query);

        $this->timestamps = true;

        return $result;
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        return $query->whereIn('device_id', $user->devices()->select('devices.id'));
    }
}
