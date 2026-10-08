<?php

namespace App\Listeners;

use App\Events\TaskCreate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tobuli\Services\FcmService;

class TaskCreateListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TaskCreate  $event
     * @return void
     */
    public function handle(TaskCreate $event)
    {
        $fcmService = new FcmService();

        $fcmService->send(
            $event->task->device,
            trans('front.new_task'),
            $event->task->title
        );

    }
}
