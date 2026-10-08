@php /** @var \Tobuli\Entities\Geofence $item */ @endphp

{!! Form::hidden('polygon') !!}
{!! Form::open(['route' => ['geofences.update', $item->id], 'method' => 'PUT', 'id' => 'geofence_update']) !!}

<div class="tab-pane-body">

    <div class="form-group">
        {!! Form::label('name', trans('validation.attributes.name').':') !!}
        {!! Form::text('name', $item->name, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group">
        {!! Form::label('type', trans('validation.attributes.type').':') !!}
        {!! Form::select('type', $geofenceTypes, $item->type, ['class' => 'form-control', 'onChange' => "app.geofences.changeType(this);"]) !!}
    </div>
    @if (settings('plugins.moving_geofence.status'))
        <div class="form-group">
            {!! Form::label('device_id', trans('validation.attributes.geofence_device').':') !!}
            {!! Form::select('device_id', ['' => trans('front.none')] + $devices->pluck('name', 'id')->all(), $item->device_id, ['class' => 'form-control devices_list', 'data-live-search' => 'true']) !!}
        </div>
    @endif

    <div class="form-group">
        {!! Form::label('group_id', trans('validation.attributes.group_id').':') !!}
        <div class="input-group">
            <div class="geofence_groups_select_ajax">
                {!! Form::select('group_id', $geofenceGroups, $item->group_id, ['class' => 'form-control geofence_groups_select']) !!}
            </div>

            <span class="input-group-btn">
                <a href="javascript:" class="btn btn-primary" data-url="{{ route('geofences_groups_subform.index') }}" data-modal="geofence_groups" title="{{ trans('front.add_group') }}">
                    <i class="icon add"></i>
                </a>
            </span>
        </div>
    </div>

    @if (settings('plugins.geofences_speed_limit.status'))
        <div class="form-group">
            {!! Form::label('speed_limit', trans('validation.attributes.speed_limit') . ':') !!}
            {!! Form::text('speed_limit', $item->speed_limit, ['class' => 'form-control']) !!}
        </div>
    @endif

    @if (config('addon.reports_geofleet_in'))
        @foreach([0, 1] as $i)
            @php $interval = $item->additional['intervals'][$i] ?? null @endphp

            <div class="form-group">
                <h5>{!! trans('validation.attributes.interval') . ' ' . ($i + 1) !!}</h5>

                <div class="col-sm-4">
                    {!! Form::label("additional[intervals][$i][from]", trans('front.from') . ':') !!}
                    {!! Form::select(
                        "additional[intervals][$i][from]",
                        getSelectTimeRange(),
                        isset($interval['from']) ? Formatter::dtime()->convert($interval['from'], 'H:i') : null,
                        ['class' => 'form-control']
                    ) !!}
                </div>

                <div class="col-sm-4">
                    {!! Form::label("additional[intervals][$i][to]", trans('front.to') . ':') !!}
                    {!! Form::select(
                        "additional[intervals][$i][to]",
                        getSelectTimeRange(),
                        isset($interval['to']) ? Formatter::dtime()->convert($interval['to'], 'H:i') : null,
                        ['class' => 'form-control']
                    ) !!}
                </div>

                <div class="col-sm-4">
                    {!! Form::label("additional[intervals][$i][speed_limit]", trans('validation.attributes.speed_limit') . ':') !!}
                    {!! Form::text("additional[intervals][$i][speed_limit]", $interval['speed_limit'] ?? null, ['class' => 'form-control']) !!}
                </div>
            </div>
        @endforeach
    @endif

    <div class="form-group">
        {!! Form::label('polygon_color', trans('validation.attributes.polygon_color').':') !!}
        {!! Form::text('polygon_color', $item->polygon_color, ['class' => 'form-control colorpicker']) !!}
    </div>

    <div class="buttons text-center">
        <a type="button" class="btn btn-action" href="javascript:" onClick="app.geofences.update();">{!!trans('global.save')!!}</a>
        <a type="button" class="btn btn-default" href="javascript:" onClick="app.openTab('geofencing_tab');">{!!trans('global.cancel')!!}</a>
    </div>
</div>

{!!  Form::close() !!}