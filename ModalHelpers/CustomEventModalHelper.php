<?php

namespace ModalHelpers;

use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\EventCustomRepo;
use CustomFacades\Repositories\TrackerPortRepo;
use CustomFacades\Validators\EventCustomFormValidator;
use Illuminate\Support\Facades\DB;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\ConditionService;

class CustomEventModalHelper extends ModalHelper
{
    public function get()
    {
        $this->checkException('custom_events', 'view');

        $this->data['filter']['user_id'] = $this->user->id;
        $events = EventCustomRepo::searchAndPaginate($this->data, 'id', 'desc', 10);

        if ($this->api) {
            $events = $events->toArray();
            $events['url'] = route('api.get_custom_events');
        }

        return compact('events');
    }

    public function createData()
    {
        $this->checkException('custom_events', 'create');

        $protocols = TrackerPortRepo::getProtocolList();
        $types = ConditionService::getList();

        if ($this->api) {
            $protocols = apiArray($protocols);
            $types = apiArray($types);
        }

        return compact('protocols', 'types');
    }

    public function create()
    {
        $this->checkException('custom_events', 'store');

        EventCustomFormValidator::validate('create', $this->data);

        $this->validateTags();

        $item = EventCustomRepo::create($this->data + ['user_id' => $this->user->id, 'always' => isset($this->data['alawys'])]);

        return ['status' => 1, 'item' => $item];
    }

    public function editData()
    {
        $id = array_key_exists('custom_event_id', $this->data) ? $this->data['custom_event_id'] : request()->route('custom_events');

        $item = EventCustomRepo::find($id);

        $this->checkException('custom_events', 'edit', $item);

        $protocols = TrackerPortRepo::getProtocolList();
        $types = ConditionService::getList();

        if ($this->api) {
            $protocols = apiArray($protocols);
            $types = apiArray($types);
        }

        return compact('item', 'protocols', 'types');
    }

    public function edit()
    {
        $item = EventCustomRepo::find($this->data['id']);

        $this->checkException('custom_events', 'update', $item);

        EventCustomFormValidator::validate('update', $this->data);

        $this->validateTags();

        EventCustomRepo::update($item->id, $this->data + ['always' => isset($this->data['alawys'])]);

        return ['status' => 1];
    }

    public function doDestroy($id)
    {
        $item = EventCustomRepo::find($id);

        $this->checkException('custom_events', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('custom_event_id', $this->data) ? $this->data['custom_event_id'] : $this->data['id'];

        $item = EventCustomRepo::find($id);

        $this->checkException('custom_events', 'remove', $item);

        EventCustomRepo::delete($id);

        return ['status' => 1];
    }

    public function getGroupedEvents($devices)
    {
        $devicesProtocols = $devices ? array_unique($devices->groupProtocols()->get()->pluck('protocol')->all()) : [];

        $groups = [];

        $items = EventCustomRepo::getWhereInWhere($devicesProtocols, 'protocol', ['user_id' => null])->pluck('message_with_protocol', 'id')->all();
        $groups[] = [
            'key'   => 'system',
            'name'  => trans('front.system_events'),
            'items' => $items
        ];

        $items = EventCustomRepo::getWhereInWhere($devicesProtocols, 'protocol', ['user_id' => $this->user->id])->pluck('message_with_protocol', 'id')->all();
        $groups[] = [
            'key'   => 'custom',
            'name'  => trans('front.custom_events'),
            'items' => $items
        ];

        return $groups;
    }

    public function getProtocols()
    {
        if (!$this->api) {
            $devices = isset($this->data['devices']) ? $this->data['devices'] : [];
            $protocols = DeviceRepo::getProtocols($devices)->pluck('protocol', 'protocol')->all();
            $protocols = [
                '-' => '- ' . trans('validation.attributes.protocol') . ' -'
            ] + EventCustomRepo::getProtocols($this->data['type'] == '1' ? $this->user->id : NULL, $protocols)->pluck('protocol', 'protocol')->all();
        } else {
            $protocols = [
                [
                    'type' => 1,
                    'items' => apiArray(EventCustomRepo::getProtocols($this->user->id)->pluck('protocol', 'protocol')->all())
                ],
                [
                    'type' => 2,
                    'items' => apiArray(EventCustomRepo::getProtocols(NULL)->pluck('protocol', 'protocol')->all())
                ],
            ];
        }

        return $protocols;
    }

    public function getEvents()
    {
        $protocol = $this->data['protocol'];
        $where = [];
        $where['user_id'] = ($this->data['type'] == '1' ? $this->user->id : NULL);
        if (!empty($protocol) || $protocol != '-')
            $where['protocol'] = $protocol;

        $items = EventCustomRepo::getWhere($where)->pluck('message', 'id')->all();
        if ($this->api)
            $items = apiArray($items);

        return $items;
    }

    protected function validateTags()
    {
        if ($this->api && isset($this->data['conditions'])) {
            $conditions = json_decode($this->data['conditions'], true);

            $this->data['tag'] = [];
            $this->data['type'] = [];
            $this->data['tag_value'] = [];

            foreach ($conditions as $key => $condition) {
                $this->data['tag'][$key] = $condition['tag'] ?? '';
                $this->data['type'][$key] = $condition['type'] ?? '';
                $this->data['tag_value'][$key] = $condition['tag_value'] ?? '';
            }
        }

        $this->data['conditions'] = [];

        foreach ($this->data['tag'] as $key => $tag) {
            $tag = strtolower($tag);
            $type = $this->data['type'][$key] ?? '';
            $tag_value = $this->data['tag_value'][$key] ?? '';

            if ($tag == '' && $tag_value == '')
                continue;

            if ($tag == '' || $type === '')
                throw new ValidationException(['conditions' => trans('front.fill_all_fields')]);

            if (!ConditionService::validate($type, $tag_value))
                throw new ValidationException(['conditions' => trans('front.fill_all_fields')]);

            $this->data['conditions'][] = [
                'tag' => $tag,
                'type' => $type,
                'tag_value' => $tag_value
            ];
        }

        if (empty($this->data['conditions']))
            throw new ValidationException(['conditions' => trans('front.fill_all_fields')]);
    }
}
