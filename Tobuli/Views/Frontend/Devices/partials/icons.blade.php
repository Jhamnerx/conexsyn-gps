<div class="form-group">
    {!!Form::label('device_icons_type', trans('validation.attributes.icon_type').':')!!}
    {!!Form::select('device_icons_type', $icons_type, $item->icon->type ?? null, ['class' => 'form-control'])!!}
</div>

{!! Form::hidden('icon_id', 0) !!}
@foreach($device_icons_grouped as $group => $icons)
    <div data-disablable="#device_icons_type;hide-disable;{{ $group }}">
        <div class="form-group">
            {!!Form::label(null, trans('validation.attributes.icon_id').':')!!}
        </div>
        <div class="icon-list">
            @foreach($icons as $icon)
                <div class="checkbox-inline">
                    {!! Form::radio('icon_id', $icon->id, ($item['icon_id'] == $icon['id'])) !!}
                    <label>
                        <img src="{!!asset($icon->path)!!}" alt="ICON"
                             style="width: {!!$icon->width!!}px; height: {!!$icon->height!!}px;"/>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
<div data-disablable="#device_icons_type;hide;arrow">
    <div class="form-group">
        {!!Form::label('icon_moving', trans('front.moving').':')!!}
        {!!Form::select('icon_moving', $device_icon_colors, $item->icon_colors['moving'] ?? settings('device.status_colors.colors.moving'), ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('icon_stopped', trans('front.stopped').':')!!}
        {!!Form::select('icon_stopped', $device_icon_colors, $item->icon_colors['stopped'] ?? settings('device.status_colors.colors.stopped'), ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('icon_offline', trans('front.offline').':')!!}
        {!!Form::select('icon_offline', $device_icon_colors, $item->icon_colors['offline'] ?? settings('device.status_colors.colors.offline'), ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('icon_engine', trans('front.engine_idle').':')!!}
        {!!Form::select('icon_engine', $device_icon_colors, $item->icon_colors['engine'] ?? settings('device.status_colors.colors.engine'), ['class' => 'form-control'])!!}
    </div>

    @if (\Tobuli\Sensors\Types\Blocked::isEnabled())
        <div class="form-group">
            {!! Form::label('icon_blocked', trans('front.blocked').':') !!}
            {!! Form::select('icon_blocked', $device_icon_colors, $item->icon_colors['blocked'] ?? settings('device.status_colors.colors.blocked'), ['class' => 'form-control']) !!}
        </div>
    @endif
</div>