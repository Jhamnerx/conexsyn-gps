<div class="action-block">
    <a href="javascript:" class="btn btn-action" data-url="{!!route('sensors.create', $item->id)!!}"
       data-modal="sensors_create" type="button">
        <i class="icon add"></i> {{ trans('front.add_sensor') }}
    </a>
</div>

<div data-table>
    @include('Frontend.Sensors.index')
</div>
