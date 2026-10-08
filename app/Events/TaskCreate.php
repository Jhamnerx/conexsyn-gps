<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Tobuli\Entities\Task;

class TaskCreate extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $task;

    public function __construct(Task $task) {
        $this->task = $task;
    }

    public function broadcastOn() {
        return [
            $this->task->device->getSocketChannel()
        ];
    }

    public function broadcastAs()
    {
        return 'task';
    }
}
