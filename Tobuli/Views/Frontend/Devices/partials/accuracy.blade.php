<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('valid_by_avg_speed', 0) !!}
        {!! Form::checkbox('valid_by_avg_speed', 1, $item->valid_by_avg_speed) !!}
        {!! Form::label('valid_by_avg_speed', trans('front.valid_by_avg_speed')) !!}
    </div>
</div>

@if(!empty($detectionFields))
    <div class="form-group">
        {!!Form::label('engine_hours', trans('validation.attributes.ignition_detection').':')!!}
        {!!Form::select('engine_hours', $engine_hours, $item->engine_hours, ['class' => 'form-control'])!!}
    </div>

    <div class="form-group ignition_detection_engine">
        {!!Form::label('detect_engine', trans('validation.attributes.detect_engine').':')!!}
        {!!Form::select('detect_engine', $detect_engine, $item->detect_engine, ['class' => 'form-control'])!!}
    </div>

    <div class="form-group">
        {!!Form::label('detect_speed', trans('validation.attributes.detect_speed').':')!!}
        {!!Form::select('detect_speed', $detect_speed, $item->detect_speed, ['class' => 'form-control'])!!}
    </div>
@endif

@if (Auth::user()->can('view', $item, 'max_speed'))
    <div class="form-group">
        @php $disabled = !Auth::user()->can('edit', $item, 'max_speed') @endphp

        {!! Form::label('max_speed', trans('validation.attributes.max_speed') . ':') !!}
        <div class="input-group">
            <div class="checkbox input-group-btn">
                {!! Form::hidden('max_speed') !!}
                {!! Form::checkbox('enable_max_speed', 1, (bool)$item->max_speed, ['data-disabler' => '#max_speed_input;disable'] + ($disabled ? ['disabled' => 'disabled'] : [])) !!}
                {!! Form::label(null) !!}
            </div>
            {!! Form::text('max_speed', $item->max_speed, ['class' => 'form-control', 'id' => 'max_speed_input'] + ($disabled ? ['disabled' => 'disabled'] : [])) !!}
        </div>
    </div>
@endif

<div class="form-group">
    {!!Form::label('min_moving_speed', trans('validation.attributes.min_moving_speed').' ('.trans('front.affects_stops_track',['default'=>6]).'):')!!}
    {!!Form::text('min_moving_speed', $item->min_moving_speed, ['class' => 'form-control'])!!}
</div>
<div class="form-group">
    {!!Form::label('min_fuel_fillings', trans('validation.attributes.min_fuel_fillings').' ('.trans('front.default_value',['default'=>10]).'):')!!}
    {!!Form::text('min_fuel_fillings', $item->min_fuel_fillings, ['class' => 'form-control'])!!}
</div>
<div class="form-group">
    {!!Form::label('min_fuel_thefts', trans('validation.attributes.min_fuel_thefts').' ('.trans('front.default_value',['default'=>10]).'):')!!}
    {!!Form::text('min_fuel_thefts', $item->min_fuel_thefts, ['class' => 'form-control'])!!}
</div>
<div class="form-group">
    {!! Form::label('fuel_detect_sec_after_stop', trans('validation.attributes.fuel_detect_sec_after_stop').':') !!}
    <div class="input-group">
        <div class="checkbox input-group-btn">
            {!! Form::hidden('fuel_detect_sec_after_stop') !!}
            {!! Form::checkbox('enable_fuel_detect_sec_after_stop', 1, (bool)$item->fuel_detect_sec_after_stop, ['data-disabler' => 'select[name="fuel_detect_sec_after_stop"];disable']) !!}
            {!! Form::label(null) !!}
        </div>
        {!! Form::select('fuel_detect_sec_after_stop', $fuel_detect_sec_after_stop_options, $item->fuel_detect_sec_after_stop, ['class' => 'form-control']) !!}
    </div>
</div>

@if(Auth::user()->can('view', $item, 'lbs'))
    <div class="form-group">
        @if(Auth::user()->can('edit', $item, 'lbs'))
            <div class="checkbox">
                {!! Form::hidden('lbs', 0) !!}
                {!! Form::checkbox('lbs', 1, (bool)$item->lbs) !!}
                {!! Form::label(null, trans('validation.attributes.lbs')) !!}
            </div>
        @else
            <div class="checkbox">
                {!! Form::checkbox('lbs', 1, (bool)$item->lbs, ['disabled' => 'disabled']) !!}
                {!! Form::label(null, trans('validation.attributes.lbs')) !!}
            </div>
        @endif
        <small>{!!trans('front.lbs_explanation')!!}</small>
    </div>
@endif