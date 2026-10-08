<?php

/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 18.3.15
 * Time: 16.20
 */

namespace Tobuli\Validation;

use Illuminate\Validation\Rule;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;

class TasksFormValidator extends Validator
{
    public $rules = [
        'create' => [
            'title' => 'required',
            'device_id' => 'required|exists:devices,id',
            'task_set_id' => 'nullable|exists:task_sets,id',
            'priority' => 'required',
            'pickup_address' => 'required',
            'pickup_address_lat' => 'required|lat',
            'pickup_address_lng' => 'required|lng',
            'pickup_time_from' => 'required|date',
            'pickup_time_to' => 'required|date|after:pickup_time_from',
            'pickup_ac' => 'sometimes|required|boolean',
            'pickup_ac_radius' => 'sometimes|required|numeric|gt:0',
            'pickup_ac_duration' => 'sometimes|required|numeric|gte:0',
            'delivery_address' => 'required',
            'delivery_address_lat' => 'required|lat',
            'delivery_address_lng' => 'required|lng',
            'delivery_time_from' => 'required|date',
            'delivery_time_to' => 'required|date|after:delivery_time_from',
            'delivery_ac' => 'sometimes|required|boolean',
            'delivery_ac_radius' => 'sometimes|required|numeric|gt:0',
            'delivery_ac_duration' => 'sometimes|required|numeric|gte:0',
        ],
        'update' => [
            'title' => 'required',
            'device_id' => 'required|exists:devices,id',
            'task_set_id' => 'nullable|exists:task_sets,id',
            'priority' => 'required',
            'pickup_address' => 'required',
            'pickup_address_lat' => 'required|lat',
            'pickup_address_lng' => 'required|lng',
            'pickup_time_from' => 'required|date',
            'pickup_time_to'   => 'required|date|after:pickup_time_from',
            'pickup_ac' => 'sometimes|required|boolean',
            'pickup_ac_radius' => 'sometimes|required|numeric|gt:0',
            'pickup_ac_duration' => 'sometimes|required|numeric|gte:0',
            'delivery_address' => 'required',
            'delivery_address_lat' => 'required|lat',
            'delivery_address_lng' => 'required|lng',
            'delivery_time_from' => 'required|date',
            'delivery_time_to' => 'required|date|after:delivery_time_from',
            'delivery_ac' => 'sometimes|required|boolean',
            'delivery_ac_radius' => 'sometimes|required|numeric|gt:0',
            'delivery_ac_duration' => 'sometimes|required|numeric|gte:0',
        ],
        'assign' => [
            'device_id' => 'required',
            'tasks' => 'required|array'
        ]
    ];

    public function validate(string $name, array $data, $id = null): void
    {
        $this->rules[$name]['priority'] = 'required|in:'.implode(',', array_keys(Task::$priorities));
        $this->rules[$name]['pickup_ac_status'] = 'sometimes|required|' . Rule::in(array_flip(TaskStatus::$statuses));
        $this->rules[$name]['delivery_ac_status'] = $this->rules[$name]['pickup_ac_status'];

        parent::validate($name, $data, $id);
    }
}
