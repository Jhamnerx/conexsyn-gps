<?php
namespace App\Transformers\Event;

use Illuminate\Support\Arr;
use Tobuli\Entities\Event;
use Formatter;
use Tobuli\Services\AlertSoundService;

class EventLatestTransformer extends EventTransformer {

    /**
     * @param Event $entity
     * @return array|null
     */
    public function transform(Event $entity)
    {
        $data = $entity->attributesToArray();
        $data['time'] = Formatter::time()->convert($entity->time);
        $data['speed'] = Formatter::speed()->format($entity->speed);
        $data['altitude'] = Formatter::altitude()->format($entity->altitude);
        $data['message'] = $entity->title;

        $notifications = Arr::get($entity, 'alert.notifications', []);
        $data['sound'] = Arr::get($notifications, 'sound.active', false)
            ? AlertSoundService::getAsset(Arr::get($notifications, 'sound.input'))
            : null;
        $data['color'] = Arr::get($notifications, 'color.active', false)
            ? Arr::get($notifications, 'color.input')
            : null;
        $data['delay'] = Arr::get($notifications, 'popup.input', Arr::get($notifications, 'auto_hide.active', true) ? 10 : 0);

        //$data['device_name'] = $entity->device->name ?? null;
        $data['device'] = $entity->device ? [
            'id'   => $entity->device->id,
            'name' => htmlentities($entity->device->name)
        ] : null;

        if ($entity->geofence) {
            $data['geofence'] = [
                'id' => $entity->geofence->id,
                'name' => htmlentities($entity->geofence->name)
            ];
        }

        return $data;
    }
}
