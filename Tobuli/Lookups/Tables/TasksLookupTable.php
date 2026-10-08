<?php

namespace Tobuli\Lookups\Tables;

use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;
use Tobuli\Lookups\LookupTable;
use Tobuli\Lookups\Models\LookupTask;

class TasksLookupTable extends LookupTable
{
    private const FILTER_KEYS = [
        'device_id',
        'status',
        'time_from',
        'time_to',
        'invoice_number',
    ];

    protected function getLookupClass(): string
    {
        return LookupTask::class;
    }

    public function getTitle(): string
    {
        return trans('front.tasks');
    }

    public function getIcon(): string
    {
        return 'icon task';
    }

    public function getDefaultColumns(): array
    {
        return [
            'title',
            'device',
            'status',
            'priority',
            'invoice_number',
            'pickup_time_from',
            'delivery_time_from',
        ];
    }

    public function baseQuery()
    {
        return $this->getUser()->tasks();
    }
    
    protected function extraQuery($query)
    {
        foreach (self::FILTER_KEYS as $key) {
            $this->applyFilter($query, $key);
        }

        return $query;
    }

    private function applyFilter($query, string $key): void
    {
        $value = $this->request()->input($key);

        if (!$value) {
            return;
        }

        switch ($key) {
            case 'time_from';
                $query->where('delivery_time_from', '>=', $value);
                break;
            case 'time_to';
                $query->where('delivery_time_to', '<=', $value);
                break;
            default:
                $query->where($key, $value);
                break;
        }
    }

    /**
     * @param Task $model
     */
    public function getRowActions($model): array
    {
        $user = $this->getUser();

        if (!$user) {
            return [];
        }

        $actions = [];

        if ($model->lastStatus && $model->lastStatus->signature) {
            $actions[] = [
                'title' => trans('validation.attributes.signature'),
                'url' => route('tasks.signature', $model->lastStatus->id),
            ];
        }

        if ($user->can('edit', $model)) {
            $actions[] = [
                'title' => trans('global.edit'),
                'url' => route('tasks.edit', $model->id),
                'modal' => 'tasks_edit',
            ];
        }

        if ($user->can('remove', $model)) {
            $actions[] = [
                'title' => trans('global.delete'),
                'url' => route('tasks.do_destroy', $model->id),
                'modal' => 'tasks_destroy',
            ];
        }

        return $actions;
    }

    protected function getBuilderParameters()
    {
        $params = parent::getBuilderParameters();
        $params['searching'] = false;

        return $params;
    }

    public function getFilters(): array
    {
        return [
            \Field::select('device_id', trans('validation.attributes.device_id'))
                ->setOptions(['' => '-- ' . trans('admin.select') . ' --'])
                ->setOptionsViaQuery($this->user->accessibleDevices(), 'name'),
            \Field::select('status', trans('front.task_status'))
                ->setOptions(array_replace(
                    ['' => '-- '.trans('admin.select').' --'],
                    TaskStatus::getList()
                )),
            \Field::datetime('time_from', trans('global.from')),
            \Field::datetime('time_to', trans('global.to')),
            \Field::string('invoice_number', trans('validation.attributes.invoice_number')),
        ];
    }
}