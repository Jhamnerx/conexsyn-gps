<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceService;

class MaintenanceController extends Controller
{
    public function index($imei = null)
    {
        if ($imei) {
            $this->checkException('checklist_qr_code', 'view');
            $device = Device::firstWhere('imei', $imei);
            $this->checkException('devices', 'show', $device);
        }

        $services = $this->user->perm('maintenance', 'view')
            ? $this->getServices($device ?? null)
            : [];

        $data = [
            'services' => $services,
            'sorting'  => [
                'sort_by' => '',
                'sort'    => ''
            ],
        ];

        if (request()->ajax())
            return view('front::Maintenance.modal')->with($data);
        else
            return view('front::Maintenance.index')->with($data);
    }

    public function table()
    {
        $services = $this->user->perm('maintenance', 'view')
            ? $this->getServices()
            : [];

        $sortBy = request()->input('sorting.sort_by', 'name');
        $sort   = request()->input('sorting.sort', 'desc') == 'asc' ? 'asc' : 'desc';

        $sortFunction = $sort == 'asc' ? 'sortBy' : 'sortByDesc';

        $services = collect($services)->{$sortFunction}(function ($service, $key) use($sortBy) {
            if ($sortBy == 'device.name')
                return $service->device->name;

            if ($sortBy == 'name')
                return $service->name;

            if ($service->expiration_by == 'odometer' && $sortBy != 'odometer_percentage' && $sortBy != 'odometer_left')
                return -1;

            if ($service->expiration_by == 'engine_hours' && $sortBy != 'engine_hours_percentage' && $sortBy != 'engine_hours_left')
                return -1;

            if ($service->expiration_by == 'days' && $sortBy != 'days_percentage' && $sortBy != 'days_left')
                return -1;

            switch ($sortBy) {
                case 'odometer_percentage':
                case 'engine_hours_percentage':
                case 'days_percentage':
                    return $service->percentage;

                case 'odometer_left':
                case 'engine_hours_left':
                case 'days_left':
                    return $service->left;
            }
        });

        $data = [
            'sorting'  => [
                'sort_by' => $sortBy,
                'sort'    => $sort
            ],
            'services' => $services
        ];

        return view('front::Maintenance.table')->with($data);
    }

    private function getServices(?Device $device = null): Collection
    {
        return DeviceService::with('device.sensors')
            ->userAccessible($this->user)
            ->when($device, fn (Builder $query) => $query->where('device_id', $device->id))
            ->orderBy('device_id')
            ->get()
            ->each(function ($service) {
                $service->setDevice($service->device)
                    ->setSensors($service->device->sensors);
            });
    }
}
