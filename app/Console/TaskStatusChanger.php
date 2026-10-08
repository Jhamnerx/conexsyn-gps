<?php

namespace App\Console;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Tobuli\Entities\Device;
use Tobuli\Entities\Task;
use Tobuli\Entities\TaskStatus;

class TaskStatusChanger
{
    private bool $enabled;
    private Device $device;
    private bool $debug;
    private Collection $tasksAc;

    public function __construct(Device $device, bool $debug = false)
    {
        $this->device = $device;
        $this->debug = $debug;
        $this->enabled = (bool)settings('plugins.task_status_auto_change_by_device_position.status');
    }

    public function process($position): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->tasksAc)) {
            $this->tasksAc = Task::where('device_id', $this->device->id)
                ->where(fn (Builder $query) => $query
                    ->where('delivery_ac', 1)
                    ->orWhere('pickup_ac', 1)
                )
                ->whereColumn('status', '!=', 'delivery_ac_status')
                ->whereIn('status', [TaskStatus::STATUS_NEW, TaskStatus::STATUS_IN_PROGRESS])
                ->get();
        }

        foreach ($this->tasksAc as $idx => $task) {
            $this->line("Checking: $task->id $task->title");

            if ($this->checkTaskRadiusStay($task, 'delivery', $position)) {
                $this->tasksAc->forget($idx);
                continue;
            }

            $this->checkTaskRadiusStay($task, 'pickup', $position);
        }
    }

    private function checkTaskRadiusStay(Task $task, string $prefix, $position): bool
    {
        $this->line($prefix);

        if (!$task->{$prefix . '_ac'}) {
            $this->line('Not enabled');

            return false;
        }

        $placeStatus = $task->{$prefix . '_ac_status'};

        if ($task->status === $placeStatus) {
            $this->line('Already with status');

            return false;
        }

        if ($position->time <= $task->{$prefix . '_ac_started_at'}) {
            $this->line('History position');

            return false;
        }

        $duration = $task->{$prefix . '_ac_duration'};
        $inRadius = 1000 * getDistance(
                $task->{$prefix . '_address_lat'},
                $task->{$prefix . '_address_lng'},
                $position->latitude,
                $position->longitude
            ) <= $task->{$prefix . '_ac_radius'};

        if ($inRadius && !$duration) {
            $this->line('Success - no duration');

            return $task->update(['status' => $placeStatus]);
        }

        if (!$inRadius) {
            $this->line('Not in radius - reset time measure');

            $task->update(["{$prefix}_ac_started_at" => null]);
            return false;
        }

        if (!$task->{$prefix . '_ac_started_at'}) {
            $this->line('Start time measure');

            $task->update(["{$prefix}_ac_started_at" => $position->time]);
            return false;
        }

        if (strtotime($position->time) - strtotime($task->{$prefix . '_ac_started_at'}) >= $duration) {
            $this->line('Success');

            return $task->update([
                'status' => $placeStatus,
                "{$prefix}_ac_finished_at" => $position->time,
            ]);
        };

        return false;
    }

    private function line(string $text): void
    {
        if ($this->debug) {
            echo $text . PHP_EOL;
        }
    }
}