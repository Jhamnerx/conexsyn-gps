<?php

namespace Tobuli\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Event;
use Tobuli\Entities\SendQueue;

class EventWriteService
{
    /**
     * @param $events array
     */
    public function write($events)
    {
        if (empty($events))
            return;

        $this->writeEvents($events);
        $this->writeSendQueue($events);
        $this->writeEventFireAt($events);
    }

    protected function writeEvents($events)
    {
        $events = Arr::where($events, function($event, $key) {
            return !empty($event);
        });

        if (empty($events))
            return;

        $now = \Carbon::now();
        
        /** @var Event $event */
        foreach ($events as $event) {
            if (!$event->created_at) {
                $event->setCreatedAt($now);
            }

            if (!$event->updated_at) {
                $event->setUpdatedAt($now);
            }
        }

        $data = array_map(fn (Event $event) => $event->toArrayMassInsert(), $events);
        Event::insert($data);
    }

    protected function writeEventFireAt($events)
    {
        $events = Arr::where($events, function($event) {
            return $event->device_id && $event->alert_id;
        });

        if (empty($events))
            return;

        foreach ($events as $event) {
            DB::table('alert_device')
                ->where('alert_id',  $event->alert_id)
                ->where('device_id',  $event->device_id)
                ->update([
                    'fired_at' => $event->time
                ]);
        }
    }

    protected function writeSendQueue($events)
    {
        if (empty($events))
            return;

        $sendQueues = [];

        foreach ($events as $event) {
            if (!$event->channels)
                continue;

            foreach ($event->channels as $channel => $channel_data) {
                if ($channel_data === null)
                    continue;

                $sendQueues[] = (new SendQueue([
                    'user_id'      => $event->user_id,
                    'type'         => $event->type,
                    'data'         => $event,
                    'channel'      => $channel,
                    'channel_data' => $channel_data,
                    'sender'       => $event->sender ?? null,
                ]))->toArrayMassInsert();
            }
        }

        if ($sendQueues)
            SendQueue::insert($sendQueues);
    }
}
