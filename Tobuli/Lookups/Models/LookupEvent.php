<?php

namespace Tobuli\Lookups\Models;

use Formatter;
use Tobuli\Entities\Event;
use Tobuli\Lookups\LookupModel;

class LookupEvent extends LookupModel
{
    protected function modelClass()
    {
        return Event::class;
    }

    protected function listColumns() {
        $this->addColumn('id');
        $this->addColumn('time');
        $this->addColumn([
            'data'       => 'device',
            'name'       => 'device.name',
            'title'      => trans('validation.attributes.device_id'),
            'orderable'  => true,
            'searchable' => true,
            'relations'  => ['device']
        ]);
        $this->addColumn([
            'data'       => 'alert',
            'name'       => 'alert.name',
            'title'      => trans('global.alert'),
            'orderable'  => true,
            'searchable' => true,
            'relations'  => ['alert']
        ]);
        $this->addColumn('type');
        $this->addColumn('message');
        $this->addColumn([
            'data'       => 'address',
            'name'       => 'address',
            'title'      => trans('front.address'),
            'orderable'  => false,
            'searchable' => false,
        ]);

        $this->addColumn([
            'data'       => 'position',
            'name'       => 'position',
            'title'      => trans('front.position'),
            'orderable'  => false,
            'searchable' => false,
        ]);

        $this->addColumn([
            'data'       => 'speed',
            'name'       => 'speed',
            'title'      => trans('front.speed'),
            'orderable'  => false,
            'searchable' => false,
        ]);
    }

    public function renderTime($event) {
        return Formatter::time()->human($event->time);
    }

    public function renderAddress($event) {
        return $event->getAddress();
    }

    public function renderPosition($event)
    {
        if ( ! $event->latitude && ! $event->longitude)
            return null;

        return "{$event->latitude}&deg;, {$event->longitude}&deg;";
    }

    public function renderHtmlPosition($event)
    {
        if ( ! $event->latitude && ! $event->longitude)
            return null;

        return googleMapLink($event->latitude, $event->longitude);
    }
}