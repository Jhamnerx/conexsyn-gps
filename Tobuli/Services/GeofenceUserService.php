<?php

namespace Tobuli\Services;

use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Device;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Exporters\Util\ExportTypesUtil;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\Traits\ModelUserServiceTrait;

class GeofenceUserService extends GeofenceService
{
    use ModelUserServiceTrait {
        ModelUserServiceTrait::__construct as private modelUserConstruct;
    }

    public function __construct(User $user)
    {
        parent::__construct();
        $this->modelUserConstruct($user, 'geofences');
    }

    protected function normalize(array $data)
    {
        $this->checkDevice($data);

        if (config('addon.reports_geofleet_in') && isset($data['additional']['intervals'])) {
            foreach ($data['additional']['intervals'] as &$interval) {
                $interval['from'] = Formatter::dtime()->reverse($interval['from']);
                $interval['to'] = Formatter::dtime()->reverse($interval['to']);
            }
        }

        return parent::normalize($data) + ['user_id' => $this->user->id];
    }

    private function checkDevice(array &$data)
    {
        if (!array_key_exists('device_id', $data)) {
            return;
        }

        if (empty($data['device_id'])) {
            $data['device_id'] = null;

            return;
        }

        $device = Device::find($data['device_id']);

        if ($device && !$this->user->can('view', $device)) {
            unset($data['device_id']);
        }
    }

    public function getExportType(string $type): array
    {
        $selected = null;

        $items = $type === ExportTypesUtil::EXPORT_TYPE_GROUPS
            ? GeofenceGroup::where('user_id', $this->user->id)
                ->pluck('title', 'id')
                ->prepend(trans('front.ungrouped'), '0')
                ->all()
            : Geofence::userAccessible($this->user)
                ->pluck('name', 'id')
                ->all();

        if ($type === ExportTypesUtil::EXPORT_TYPE_ACTIVE) {
            $selected = Geofence::userAccessible($this->user)->where(['active' => 1])
                ->pluck('id', 'id')
                ->all();
        } elseif ($type === ExportTypesUtil::EXPORT_TYPE_INACTIVE) {
            $selected = Geofence::userAccessible($this->user)->where(['active' => 0])
                ->pluck('id', 'id')
                ->all();
        }

        return compact('items', 'selected', 'type');
    }
}
