<div class="widget widget-camera" id="widget-camera">
@if (!empty($images) || !empty($deviceImage))
    <div class="widget-heading">
        <div class="widget-title">
            <i class="icon camera"></i> {{ trans('front.cameras') }}
        </div>
    </div>
    <div class="widget-body">
        @if (!empty($deviceImage))
            <a class="camera-image" href="{{ route('device_media.display_image', [$device->id, $deviceImage->name]) }}" target="_blank">
                <img class="image" src="{{ route('device_media.display_image', [$device->id, $deviceImage->name]) }}">
            </a>
        @endif

        @foreach ($images as $camera_id => $image)
            <a class="camera-image" href="{{ route('device_media.display_camera_image', [$camera_id, $image['image']->name]) }}" target="_blank">
                <span class="name">{{ $image['camera_name'] }}</span>
                <img class="image" src="{{ route('device_media.display_camera_image', [$camera_id, $image['image']->name]) }}">
            </a>
        @endforeach
    </div>
@endif
</div>
