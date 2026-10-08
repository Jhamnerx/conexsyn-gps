<?php namespace Tobuli\Entities;


use Tobuli\Sensors\SensorsManager;
use Tobuli\Traits\ChangeLogs;

class SensorGroupSensor extends AbstractEntity
{
    use ChangeLogs;

	protected $table = 'sensor_group_sensors';

    protected $fillable = array(
        'group_id',
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
        //'odometer_value_unit',
        //'value',
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
        'ascii',
    );

    protected $casts = [
        'bitcut' => 'array',
        'mappings' => 'array'
    ];

    protected $hidden = ['value'];

    public $timestamps = false;

    public static string $displayField = 'name';

    public function setCalibrationsAttribute($value)
    {
        $this->attributes['calibrations'] = serialize($value);
    }

    public function getCalibrationsAttribute($value)
    {
        return unserialize($value);
    }

    public function setAddToHistoryAttribute($value)
    {
        $value = empty($value) ? 0 : $value;

        $this->attributes['add_to_history'] = $value;
    }

    public function getCounter()
    {
        return $this->value ?? 0;
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
}
