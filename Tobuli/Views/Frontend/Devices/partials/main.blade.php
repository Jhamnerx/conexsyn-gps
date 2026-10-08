@php /** @var \Tobuli\Entities\Device $item */@endphp

@if(Auth::user()->can('view', $item, 'active'))
    <div class="form-group">
        <div class="checkbox-inline">
            {!! Form::hidden('active', 0) !!}
            {!! Form::checkbox('active', 1, $item->active, Auth::user()->can('edit', $item, 'active') ? [] : ['disabled' => 'disabled']) !!}
            {!! Form::label(null, trans('validation.attributes.active')) !!}
        </div>
    </div>
@endif

<div class="form-group">
    {!!Form::label('name', trans('validation.attributes.name').'*:')!!}
    {!!Form::text('name', $item->name, ['class' => 'form-control'])!!}
</div>

@if(Auth::user()->can('view', $item, 'imei') && !isset($multiItems))
    <div class="form-group">
        <label for="imei">
            {{ trans('front.device_imei') }} {!! tooltipMarkImei(asset('assets/images/tracker-imei.jpg'), trans('front.tracker_imei_info')) !!}
            /
            {{ trans('front.tracker_id') }} {!! tooltipMarkImei(asset('assets/images/tracker-id.jpg'), trans('front.tracker_id_info')) !!}
            :
        </label>
        {!!Form::text('imei', $item->imei, ['class' => 'form-control', 'placeholder' => trans('front.imei_placeholder')] + ( ! Auth::user()->can('edit', $item, 'imei') ? ['disabled' => 'disabled'] : []) )!!}
    </div>
@endif

@if (Auth::user()->can('view', $item, 'model_id'))
    <div class="form-group">
        @php $disabled = !Auth::user()->can('edit', $item, 'model_id') @endphp

        {!! Form::label('model_id', trans('validation.attributes.model_id')) !!}
        {!! Form::select('model_id', $models, $item->model_id, ['class' => 'form-control'] + ($disabled ? ['disabled' => 'disabled'] : []))!!}
    </div>
@endif

@if (isAdmin() && Auth::user()->can('view', $item, 'expiration_date'))
    <div class="form-group">
        {!! Form::label('expiration_date', trans('validation.attributes.expiration_date').':') !!}
        <div class="input-group">
            @if ($item->exists)
                <div class="checkbox input-group-btn">
                    {!! Form::hidden('enable_expiration_date', 0) !!}
                    {!! Form::checkbox('enable_expiration_date', 1, $item->hasExpireDate(), Auth::user()->can('edit', $item, 'expiration_date') ? [] : ['disabled' => 'disabled']) !!}
                    {!! Form::label(null) !!}
                </div>
                {!! Form::text(
                    'expiration_date',
                    $item->hasExpireDate() ? Formatter::time()->convert($item->expiration_date) : null,
                    ['class' => 'form-control datetimepicker', 'disabled' => 'disabled'])
                !!}
            @else
                <div class="checkbox input-group-btn">
                    {!! Form::hidden('enable_expiration_date', 0) !!}
                    {!! Form::checkbox('enable_expiration_date', 1, false, Auth::user()->can('edit', $item, 'expiration_date') ? [] : ['disabled' => 'disabled']) !!}
                    {!! Form::label(null) !!}
                </div>
                {!! Form::text(
                    'expiration_date',
                    \Tobuli\Services\DeviceService::getExpirationDateOffset(),
                    ['class' => 'form-control datetimepicker', 'disabled' => 'disabled'])
                !!}
            @endif
        </div>
    </div>
@endif

@if(!$item->exists && Auth::user()->able('configure_device'))
    <div class="form-group">
        <div class="checkbox-inline">
            {!! Form::checkbox('configure_device', 1, false, ['data-disabler' => '#device-form-configurator;hide-disable']) !!}
            {!! Form::label(null, trans('front.device_configuration')) !!}
        </div>
    </div>
    <div class="form-group" id="device-form-configurator">
        @include('Frontend.DeviceConfig.form', ['showDeviceSelect' => false])
    </div>
@endif