<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use CustomFacades\Repositories\TasksRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\TasksFormValidator;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;
use Tobuli\Services\CustomValuesService;
use Tobuli\Services\TaskUserService;

class TasksController extends Controller
{
    private CustomValuesService $customValuesService;
    private TaskUserService $service;

    public function __construct()
    {
        parent::__construct();

        $this->customValuesService = new CustomValuesService();
    }

    protected function afterAuth($user)
    {
        $this->service = new TaskUserService($user);
    }

    public function index()
    {
        $this->checkException('tasks', 'view');

        $autoStatusChange = settings('plugins.task_status_auto_change_by_device_position');

        $data = [
            'devices'    => UserRepo::getDevices($this->user->id)
                ->pluck('name', 'id')
                ->prepend('-- '.trans('admin.select').' --', '0')
                ->all(),
            'taskSets'   => $this->user->taskSets()->pluck('title', 'id')->prepend('-- ' . trans('admin.select') . ' --', ''),
            'priorities' => array_map(fn ($value) => trans($value), Task::$priorities),
            'statuses'   => TaskStatus::getList(),
            'acEnabled'  => $autoStatusChange['status'],
            'acValues'   => $autoStatusChange['options'],
        ];

        return view('front::Tasks.index')->with($data);
    }

    public function search()
    {
        $this->checkException('tasks', 'view');

        $filter = ['accessible_user_id' => $this->user->id];

        if ( ! empty($this->data['search_device_id']))
            $filter['device_id'] = (int) $this->data['search_device_id'];

        if ( ! empty($this->data['search_task_status']))
            $filter['status'] = (int) $this->data['search_task_status'];

        if ( ! empty($this->data['search_time_from']))
            $filter['delivery_time_from'] = $this->data['search_time_from'];

        if ( ! empty($this->data['search_time_to']))
            $filter['delivery_time_to'] = $this->data['search_time_to'];

        if ( ! empty($this->data['search_invoice_number']))
            $filter['invoice_number'] = $this->data['search_invoice_number'];

        $tasks = TasksRepo::searchAndPaginate(['filter' => $filter, 'sorting' => request('sorting', [])], 'id', 'desc', 10);

        if (!$this->api) {
            $data = [
                'tasks'    => $tasks,
                'devices'  => UserRepo::getDevices($this->user->id)->pluck('name', 'id')->all(),
                'statuses' => TaskStatus::getList(),
            ];

            return view('front::Tasks.list')->with($data);
        }

        if (config('addon.custom_fields_task')
            && !empty($customFields = (new Task())->customFields()->pluck('slug', 'id')->all())
        ) {
            $tasks->through(function (Task $task) use ($customFields) {
                $customValues = $task->customValues()->pluck('value', 'custom_field_id');

                $taskData = [];

                foreach ($customValues as $id => $value) {
                    $taskData[] = [
                        'name'  => $customFields[$id],
                        'value' => $value,
                    ];
                }

                $task['custom_fields'] = $taskData;

                return $task;
            });
        }

        return response()->json([
            'status' => 1,
            'items'  => collect(['url' => route('api.get_tasks')])->merge($tasks)
        ]);
    }

    public function store()
    {
        $task = $this->service->create($this->data);

        $this->customValuesService->saveCustomValues($task, $this->data['custom_fields'] ?? []);

        return response()->json([
            'status' => 1,
            'item'   => $task
        ]);
    }

    public function doDestroy($id = null) {

        $ids = request()->get('id', $id);

        if ( ! is_array($ids))
            $ids = [$ids];

        return view('front::Tasks.destroy')->with(['ids' => $ids]);
    }

    public function destroy() {
        $id = array_key_exists('task_id', $this->data) ? $this->data['task_id'] : $this->data['id'];

        if ( ! is_array($id))
            $ids = [$id];
        else
            $ids = $id;

        $tasks = Task::whereIn('id', $ids)->get();

        foreach ($tasks as $task)
        {
            if ( ! $this->user->can('remove', $task))
                continue;

            $task->delete();
        }

        return ['status' => 1];
    }

    public function show($id) {
        $item = TasksRepo::findWithAttributes($id);

        $this->checkException('tasks', 'show', $item);

        if ($this->api)
            return response()->json([
                'status' => 1,
                'item'   => $item
            ]);

        return view('front::Tasks.show')->with(['item' => $item]);
    }

    public function edit($id = null)
    {
        $item = TasksRepo::find($id);

        $this->checkException('tasks', 'edit', $item);

        $autoStatusChange = settings('plugins.task_status_auto_change_by_device_position');

        $data = [
            'item'       => $item,
            'devices'    => UserRepo::getDevices($this->user->id)->pluck('name', 'id')->all(),
            'taskSets'   => $this->user->taskSets()->pluck('title', 'id')->prepend('-- ' . trans('admin.select') . ' --', ''),
            'statuses'   => TaskStatus::getList(),
            'priorities' => array_map(fn ($value) => trans($value), Task::$priorities),
            'acEnabled'  => $autoStatusChange['status'],
        ];

        return view('front::Tasks.edit')->with($data);
    }

    public function update($id = null)
    {
        $task = Task::find($id ?? $this->data['id']);

        $this->service->edit($task, $this->data);

        $this->customValuesService->saveCustomValues($task, $this->data['custom_fields'] ?? []);

        return response()->json([
            'status' => 1
        ]);
    }

    public function getSignature($taskStatusId)
    {
        $taskStatus = TaskStatus::find($taskStatusId);

        if ( ! $taskStatus)
            throw new ResourseNotFoundException('global.task_status');

        $this->checkException('tasks', 'show', $taskStatus->task);

        if ( ! $taskStatus->signature)
            throw new ResourseNotFoundException('signature');

        return response($taskStatus->signature)
            ->header('Content-Type', 'image/jpeg')
            ->header('Pragma', 'public')
            ->header('Content-Disposition', 'inline; filename="photo.jpeg"')
            ->header('Cache-Control', 'max-age=60, must-revalidate');
    }

    public function getStatuses() {
        return response()->json([
            'status' => 1,
            'items'  => toOptions( TaskStatus::getList() )
        ]);
    }

    public function getPriorities() {
        return response()->json([
            'status' => 1,
            'items'  => toOptions( array_map(function($value) { return trans($value);}, Task::$priorities) )
        ]);
    }

    public function getCustomFields()
    {
        if (!config('addon.custom_fields_task')) {
            abort(404);
        }

        return response()->json([
            'status' => 1,
            'items'  => (new Task())->customFields()->get()
        ]);
    }

    public function import()
    {
        return view('front::Tasks.import');
    }

    public function importSet()
    {
        $file = request()->file('file');

        if (is_null($file)) {
            throw new ResourseNotFoundException(trans('validation.attributes.file'));
        }

        if ( ! $file->isValid()) {
            return;
        }

        $manager = new \Tobuli\Importers\Task\TaskImportManager();
        $manager->setFieldsReadMap(request()->get('fields', []))
            ->import($file->getPathName());

        return response()->json(['status' => 1]);
    }

    public function assignForm()
    {
        $ids = request()->get('id', []);

        return view('front::Tasks.assign')->with([
            'tasks'      => TasksRepo::searchAndPaginate(['filter' => ['user_id' => $this->user->id]], 'id', 'desc', 10),
            'devices'    => UserRepo::getDevices($this->user->id)->pluck('name', 'id')->all(),
            'ids'        => is_array($ids) ? $ids : [$ids],
        ]);

    }

    public function assign()
    {
        TasksFormValidator::validate('assign', $this->data);

        $device = Device::find($this->data['device_id']);

        $this->checkException('devices', 'own', $device);


        $tasks = Task::whereIn('id', $this->data['tasks'])->get();

        foreach ($tasks as $task)
        {
            if ( ! $this->user->can('edit', $task))
                continue;

            $task->device()->associate($device);
            $task->save();
        }

        return response()->json([
            'status' => 1
        ]);
    }
}
