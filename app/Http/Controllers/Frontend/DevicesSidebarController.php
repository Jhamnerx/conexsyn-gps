<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AbstractSidebarItemsController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Sensors\Types\Blocked;

/**
 * @property Device $itemModel
 */
class DevicesSidebarController extends AbstractSidebarItemsController
{
    protected string $repo = 'devices';
    protected string $viewDir = 'front::Objects';
    protected string $nextRoute = 'objects.sidebar';
    protected string $groupClass = DeviceGroup::class;

    private bool $filterAll = false;

    public function items()
    {
        $this->filterAll = true;

        return parent::items();
    }

    protected function getGroupItemsQuery($groupId, $search, $filters)
    {
        $query = $this->user
            ->devices()
            ->with(['traccar', 'sensors' => function (HasMany $query) {
                $types = ['speed'];

                if (Blocked::isEnabled()) {
                    $types[] = Device::STATUS_BLOCKED;
                }

                $query->whereIn('type', $types);
            }]);

        if ($search) {
            $query->search($search);
        }

        if ($filters) {
            $query->filter($filters);
        }

        //optimization form db index
        if (config('tobuli.device_sidebar_query')) {
            $query->whereIn('devices.id', function ($q) use ($groupId) {
                $q->select('device_id')->from('user_device_pivot')->where('user_id', $this->user->id);

                if (!$this->filterAll)
                    $q->where('group_id', $groupId);
            });
        }

        return $this->filterAll
            ? $query->filter(request()->all())
            : $query->filterGroupId($groupId);
    }

    public function showFilters(Request $request)
    {
        $input = $request->input();

        $statuses = [
            Device::STATUS_MOVE        => trans('front.move'),
            Device::STATUS_STOP        => trans('front.stop'),
            Device::STATUS_ENGINE_ON   => trans('front.engine_on'),
            Device::STATUS_ENGINE_OFF  => trans('front.engine_off'),
            Device::STATUS_ONLINE      => trans('global.online'),
            Device::STATUS_OFFLINE     => trans('global.offline'),
            Device::STATUS_PARK        => trans('front.park'),
            Device::STATUS_IDLE        => trans('front.idle'),
            Device::STATUS_EXPIRED     => trans('front.expired'),
            Device::STATUS_INACTIVE    => trans('front.inactive'),
        ];

        if (empty($input['filter_sidebar']) && empty($input['filter_map'])) {
            $input['filter_sidebar'] = true;
            $input['filter_map'] = true;
        }

        if (Blocked::isEnabled()) {
            $statuses[Device::STATUS_BLOCKED] = trans('front.blocked');
        }

        return view('front::Objects.filters')->with(
            compact('input', 'statuses')
        );
    }
}
