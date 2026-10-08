<div class="action-block">
    <a href="javascript:" class="btn btn-action" data-url="{!!route('device_camera.create', $item->id)!!}"
       data-modal="device_camera_create" type="button">
        <i class="icon add"></i> {{ trans('front.add_camera') }}
    </a>
</div>

<div data-table>
    @include('Frontend.DeviceMedia.partials.cameras.index')
</div>