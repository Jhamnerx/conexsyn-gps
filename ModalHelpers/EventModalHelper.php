<?php namespace ModalHelpers;

use App\Transformers\Event\EventLookupTransformer;
use Illuminate\Support\Arr;
use Tobuli\Entities\Event;
use Formatter;
use FractalTransformer;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;

class EventModalHelper extends ModalHelper {

    public function lookup($data)
    {
        $this->checkException('events', 'view');

        $query = Event::with('device', 'geofence', 'alert')
            ->select('events.*')
            ->orderBy('events.id', 'desc');

        if ( ! empty($data['user']))
            $query->userAccessible($data['user']);

        if ( ! empty($data['alert_id']))
            $query->where('events.alert_id', $data['alert_id']);

        if ( ! empty($data['device_id']))
            $query->where('events.device_id', $data['device_id']);

        if ( ! empty($data['type']))
            $query->where('events.type', $data['type']);

        if ( ! empty($data['date_from']))
            $query->where('events.time', '>=', Formatter::time()->reverse($data['date_from']));

        if ( ! empty($data['date_to']))
            $query->where('events.time', '<=', Formatter::time()->reverse($data['date_to']));

        if ( ! empty($data['created_from']))
            $query->where('events.created_at', '>=', Formatter::time()->reverse($data['created_from']));

        if ( ! empty($data['created_to']))
            $query->where('events.created_at', '<=', Formatter::time()->reverse($data['created_to']));

        if ( ! empty($data['search'])) {
            $query->search($data['search']);
        }

        $limit = Arr::get($data, 'limit', 30);
        $limit = min($limit, 1000);

        if (Arr::has($data, 'page')) {
            $events = $query->paginate($limit);
        } else {
            //optizime first page load
            if (!Arr::get($data, 'cursor')) {
                $query->where('events.id', '>', 0);
            }
            $events = $query->cursorPaginate($limit);
        }

        if ($this->api) {
            $events->getCollection()->transform(function (Event $event)
            {
                return FractalTransformer::setSerializer(WithoutDataArraySerializer::class)
                    ->item($event, EventLookupTransformer::class)->toArray();
            });

            return $events;
        }

        return $events;
    }

    public function destroy($id = null)
    {
        $this->checkException('events', 'clean');

        Event::userControllable($this->user)
            ->when($id, function($query) use ($id) {
                $query->where('id', $id);
            })
            ->delete();
    }
}