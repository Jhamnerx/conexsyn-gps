<?php

namespace Tobuli\Lookups\Models;

use CustomFacades\Repositories\UserRepo;
use Illuminate\Support\Arr;
use Tobuli\Entities\Device;
use Tobuli\Lookups\LookupModel;
use Formatter;

class LookupObjectList extends LookupDevice
{
    protected $settings = null;

    protected function listColumns() {
        $columns = Arr::get($this->getSettings(),'columns', []);

        foreach ($columns as $column) {

            if (!$this->user->can('view', $this->model(), $column['field']))
                continue;

            switch ($column['field']) {
                case 'name':
                case 'imei':
                case 'sim_number':
                case 'device_model':
                case 'plate_number':
                case 'vin':
                case 'registration_number':
                case 'object_owner':
                case 'additional_notes':
                    $this->addColumn([
                        'data'       => $column['field'],
                        'name'       => $column['field'],
                        'title'      => $column['title'],
                        'orderable'  => true,
                        'searchable' => true,
                        'datatype'   => $column['class'],
                    ]);
                    break;
                case 'sim_activation_date':
                case 'sim_expiration_date':
                case 'installation_date':
                    if (settings('plugins.additional_installation_fields.status'))
                        $this->addColumn([
                            'data'       => $column['field'],
                            'name'       => $column['field'],
                            'title'      => $column['title'],
                            'orderable'  => true,
                            'searchable' => true,
                            'datatype'   => $column['class'],
                        ]);
                    break;
                case 'fuel':
                    $this->addColumn([
                        'data'       => 'fuel_percentage',
                        'name'       => 'fuel_percentage',
                        'title'      => trans('global.percentage'),
                        'orderable'  => false,
                        'searchable' => false,
                        'datatype'   => 'device',
                    ]);

                    $this->addColumn([
                        'data'       => 'fuel_quantity',
                        'name'       => 'fuel_quantity',
                        'title'      => trans('global.quantity'),
                        'orderable'  => false,
                        'searchable' => false,
                        'datatype'   => 'device',
                    ]);

                    $this->addColumn([
                        'data'       => 'fuel_price',
                        'name'       => 'fuel_price',
                        'title'      => trans('global.price'),
                        'orderable'  => false,
                        'searchable' => false,
                        'datatype'   => 'device',
                    ]);
                    break;
                case 'active':
                case 'expiration_date':
                    $this->addColumn([
                        'data'       => $column['field'],
                        'name'       => $column['field'],
                        'title'      => $column['title'],
                        'orderable'  => true,
                        'searchable' => false,
                        'datatype'   => $column['class'],
                    ]);
                    break;
                case 'device_type_id':
                    $this->addColumn([
                        'data'       => 'device_type_id',
                        'name'       => 'deviceType.title',
                        'title'      => trans("validation.attributes.device_type_id"),
                        'orderable'  => true,
                        'searchable' => false,
                        'datatype'   => 'device',
                    ]);
                    break;
                default:
                    $this->addColumn([
                        'data'       => $column['field'],
                        'name'       => $column['field'],
                        'title'      => $column['title'],
                        'orderable'  => false,
                        'searchable' => false,
                        'datatype'   => $column['class'],
                        'datacolor'  => empty($column['color']) ? null : $column['color'],
                    ]);
                    break;
            }
        }
    }

    public function getSettings()
    {
        if ( ! is_null($this->settings))
            return $this->settings;

        $settings = UserRepo::getListViewSettings($this->user->id);
        $fields = config('tobuli.listview_fields');

        listviewTrans($this->user->id, $settings, $fields);

        return $this->settings = $settings;
    }

    public function renderDistance($device)
    {
        return $device->getTotalDistance();
    }

    public function renderHtmlDistance($device)
    {
        return $this->renderDistance($device) . " " . Formatter::distance()->unit();
    }

    public function renderFuelPercentage($device)
    {
        $sensor = $device->getFuelTankSensor();

        if ( ! $sensor) {
            return '-';
        }

        $value = $sensor->getValueCurrent($device)->getValue();
        $fullTank = $sensor->getMaxTankValue();

        $percentage = ($fullTank && $fullTank > 0) ? floatval($value) * 100 / $fullTank : 0;

        if ( $percentage < 0 )
            $percentage = 0;

        if ( $percentage > 100 )
            $percentage = 100;

        return round($percentage) . '%';
    }

    public function renderFuelQuantity($device)
    {
        $sensor = $device->getFuelTankSensor();

        if ( ! $sensor) {
            return '-';
        }

        return $sensor->getValueCurrent($device)->getFormatted();
    }

    public function renderFuelPrice($device) {
        $sensor = $device->getFuelTankSensor();

        if ( ! $sensor)
            return '-';

        return floatval($sensor->getValueCurrent($device)->getValue()) * $device->fuel_price;
    }

    public function renderSensor($device, $data)
    {
        $column = $this->getColumn($data);

        if ( ! $device->sensors )
            return '-';

        foreach ($device->sensors as $sensor) {
            if ($sensor->hash !== $column['data'])
                continue;

            return $sensor->getValueCurrent($device)->getFormatted();
        }
    }

    public function renderHtmlSensor($device, $data)
    {
        $column = $this->getColumn($data);

        if ( ! $device->sensors )
            return '-';

        foreach ($device->sensors as $sensor) {
            if ($sensor->hash !== $column['data'])
                continue;

            $color = 'inherit';

            if (!empty($column['datacolor'])) {
                foreach ($column['datacolor'] as $datacolor) {
                    if ($sensor->value >= $datacolor['from'] && $sensor->value <= $datacolor['to']) {
                        $color = $datacolor['color'];
                    }
                }
            }

            $value = $sensor->getValueCurrent($device)->getFormatted();

            return "<span style='color: {$color};'>{$value}</span>";
        }
    }
}