<div class="form-group">
    {!!Form::label('group_id', trans('validation.attributes.group_id').':')!!}
    {!!Form::select('group_id', $device_groups, $group_id ?? null, ['class' => 'form-control', 'data-live-search' => 'true'])!!}
</div>

@if(Auth::user()->can('view', $item, 'device_type_id'))
    <div class="form-group">
        {!!Form::label('device_type_id', trans('validation.attributes.device_type_id').':')!!}
        {!!Form::select('device_type_id', $device_types, $item->device_type_id, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'device_type_id') ? ['disabled' => 'disabled'] : []))!!}
    </div>
@endif

@if(Auth::user()->can('view', $item, 'authentication'))
    <div class="form-group">
        {!! Form::label('authentication', trans('validation.attributes.authentication').':') !!}
        {!! Form::text('authentication',
            $item->authentication,
            ['class' => 'form-control'] + (Auth::user()->can('edit', $item, 'authentication') ? [] : ['disabled']))
        !!}
    </div>
@endif

@php $additional_fields_on = settings('plugins.additional_installation_fields.status') @endphp

<div class="row">
    <div class="col-sm-6">
        @if(Auth::user()->can('view', $item, 'sim_number') && empty($multiItems))
            <div class="form-group">
                {!!Form::label('sim_number', trans('validation.attributes.sim_number').':')!!}
                {!!Form::text('sim_number', $item->sim_number, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'sim_number') ? ['disabled' => 'disabled'] : []))!!}
            </div>
        @endif

        @if(settings('plugins.sim_blocking.status') && Auth::user()->can('view', $item, 'msisdn'))
            <div class="form-group">
                {!! Form::label('msisdn', trans('validation.attributes.msisdn').':') !!}
                {!! Form::text('msisdn', $item->msisdn, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'msisdn') ? ['disabled' => 'disabled'] : [])) !!}
            </div>
        @endif

        @if($additional_fields_on && Auth::user()->can('view', $item, 'sim_activation_date'))
            <div class="form-group">
                {!!Form::label('sim_activation_date', trans('validation.attributes.sim_activation_date').':')!!}
                {!!Form::text('sim_activation_date', $item->sim_activation_date == '0000-00-00' ? null : $item->sim_activation_date, ['class' => 'form-control datepicker'] + ( ! Auth::user()->can('edit', $item, 'sim_activation_date') ? ['disabled' => 'disabled'] : []))!!}
            </div>
        @endif

        @if($additional_fields_on && Auth::user()->can('view', $item, 'sim_expiration_date'))
            <div class="form-group">
                {!!Form::label('sim_expiration_date', trans('validation.attributes.sim_expiration_date').':')!!}
                {!!Form::text('sim_expiration_date', $item->sim_expiration_date == '0000-00-00' ? null : $item->sim_expiration_date, ['class' => 'form-control datepicker'] + ( ! Auth::user()->can('edit', $item, 'sim_expiration_date') ? ['disabled' => 'disabled'] : []))!!}
            </div>
        @endif
        @if(Auth::user()->can('view', $item, 'vin'))
        <div class="form-group">
            {!!Form::label('vin', trans('validation.attributes.vin').':')!!}
            {!!Form::text('vin', $item->vin, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'vin') ? ['disabled' => 'disabled'] : []))!!}
        </div>
        @endif
        @if(Auth::user()->can('view', $item, 'device_model'))
        <div class="form-group">
            {!!Form::label('device_model', trans('validation.attributes.device_model').':')!!}
            {!!Form::text('device_model', $item->device_model, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'device_model') ? ['disabled' => 'disabled'] : []))!!}
        </div>
        @endif
    </div>
    <div class="col-sm-6">
        @if($additional_fields_on && Auth::user()->can('view', $item, 'installation_date'))
            <div class="form-group">
                {!!Form::label('installation_date', trans('validation.attributes.installation_date').':')!!}
                {!!Form::text('installation_date', $item->installation_date == '0000-00-00' ? NULL : $item->installation_date, ['class' => 'form-control datepicker'] + ( ! Auth::user()->can('edit', $item, 'installation_date') ? ['disabled' => 'disabled'] : []))!!}
            </div>
        @endif
        @if(Auth::user()->can('view', $item, 'plate_number'))
        <div class="form-group">
            {!!Form::label('plate_number', trans('validation.attributes.plate_number').':')!!}
            {!!Form::text('plate_number', $item->plate_number, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'plate_number') ? ['disabled' => 'disabled'] : []))!!}
        </div>
        @endif
        @if(Auth::user()->can('view', $item, 'registration_number'))
        <div class="form-group">
            {!!Form::label('registration_number', trans('validation.attributes.registration_number').':')!!}
            {!!Form::text('registration_number', $item->registration_number, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'registration_number') ? ['disabled' => 'disabled'] : []))!!}
        </div>
        @endif
        @if(Auth::user()->can('view', $item, 'object_owner'))
        <div class="form-group">
            {!!Form::label('object_owner', trans('validation.attributes.object_owner').':')!!}
            {!!Form::text('object_owner', $item->object_owner, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'object_owner') ? ['disabled' => 'disabled'] : []))!!}
        </div>
        @endif
    </div>
</div>
@if(Auth::user()->can('view', $item, 'additional_notes'))
<div class="form-group">
    {!!Form::label('additional_notes', trans('validation.attributes.additional_notes').':')!!}
    {!!Form::text('additional_notes', $item->additional_notes, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'additional_notes') ? ['disabled' => 'disabled'] : []))!!}
</div>
@endif
@if(Auth::user()->can('view', $item, 'comment'))
<div class="form-group">
    {!!Form::label('comment', trans('validation.attributes.comment').':')!!}
    {!!Form::text('comment', $item->comment, ['class' => 'form-control'] + ( ! Auth::user()->can('edit', $item, 'comment') ? ['disabled' => 'disabled'] : []))!!}
</div>
@endif
@if (config('addon.device_tracker_app_login'))
    <div class="form-group">
        <div class="checkbox-inline">
            {!! Form::hidden('app_tracker_login', 0) !!}
            {!! Form::checkbox('app_tracker_login', 1, $item->app_tracker_login) !!}
            {!! Form::label(null, trans('validation.attributes.app_tracker_login')) !!}
        </div>
    </div>
@endif
<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('gprs_templates_only', 0) !!}
        {!! Form::checkbox('gprs_templates_only', 1, $item->gprs_templates_only) !!}
        {!! Form::label('gprs_templates_only', trans('validation.attributes.gprs_templates_only') ) !!}
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!!Form::label('fuel_measurement_id', trans('validation.attributes.fuel_measurement_type').':')!!}
            {!!Form::select('fuel_measurement_id', $device_fuel_measurements_select, $item->fuel_measurement_id, ['class' => 'form-control'])!!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="fuel_quantity">
                <span class="fuel_title"></span> {!!trans('front.per')!!} <span class="distance_title"></span>:
            </label>
            {!!Form::text('fuel_quantity', $item->fuel_quantity, ['class' => 'form-control', 'placeholder' => '0.00', 'id' => 'fuel_quantity'])!!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="fuel_price">
                {!! trans('front.cost_for') !!} <span class="cost_title"></span>:
            </label>
            {!!Form::text('fuel_price', $item->fuel_price, ['class' => 'form-control', 'placeholder' => '0.00', 'id' => 'fuel_price'])!!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!!Form::label('fuel_type', trans('validation.attributes.fuel_type').':')!!}
            {!!Form::select('fuel_type', $fuel_types, $item->fuel_type, ['class' => 'form-control'])!!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!!Form::label('fuel_emissions', trans('validation.attributes.fuel_emissions').':')!!}
            {!!Form::text('fuel_emissions', $item->fuel_emissions, ['class' => 'form-control', 'placeholder' => '0.00'])!!}
        </div>
    </div>
</div>

@if(Auth::user()->can('view', $item, 'forward'))
    <div class="form-group">
        {!! Form::label(null, trans('validation.attributes.forward').':') !!}
        <div class="input-group">
            <div class="checkbox input-group-btn">
                {!! Form::hidden('forward[active]', 0) !!}
                {!! Form::checkbox('forward[active]', 1, \Illuminate\Support\Arr::get($item->forward, 'active')) !!}
                {!! Form::label(null) !!}
            </div>
            {!! Form::text('forward[ip]', \Illuminate\Support\Arr::get($item->forward, 'ip'), array_merge(['class' => 'form-control', 'placeholder' => '10.0.0.0:6000'], (Auth::user()->can('edit', $item, 'forward')) ? [] : ['readonly' => true])) !!}
            <div class="input-group-addon">
                <div class="checkbox-inline">
                    {!! Form::radio('forward[protocol]', 'TCP', \Illuminate\Support\Arr::get($item->forward, 'protocol') != 'UDP') !!}
                    {!! Form::label(null, 'TCP') !!}
                </div>
                <div class="checkbox-inline">
                    {!! Form::radio('forward[protocol]', 'UDP', \Illuminate\Support\Arr::get($item->forward, 'protocol') == 'UDP') !!}
                    {!! Form::label(null, 'UDP') !!}
                </div>
            </div>
        </div>
        <small>{!!trans('front.forward_semicolon')!!}</small>
    </div>
@endif

<div class="form-group">
    {!!Form::label('timezone_id', trans('validation.attributes.time_adjustment').':')!!}
    {!!Form::select('timezone_id', $timezones, empty($timezone_id) ? 0 : $timezone_id, ['class' => 'form-control'])!!}
    <small>{!!trans('front.by_default_time')!!}</small>
</div>