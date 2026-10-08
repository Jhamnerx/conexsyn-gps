@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon device"></i> {!!trans('global.add_new')!!}
@stop

@section('body')
    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#device-add-form-main" role="tab" data-toggle="tab">{!!trans('front.main')!!}</a></li>
        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <li><a href="#device-add-form-users" role="tab" data-toggle="tab">{!!trans('front.users')!!}</a></li>
        @endif
        <li><a href="#device-add-form-icons" role="tab" data-toggle="tab">{!!trans('front.icons')!!}</a></li>
        <li><a href="#device-add-form-advanced" role="tab" data-toggle="tab">{!!trans('front.advanced')!!}</a></li>
        @if (isAdmin())
            <li><a href="#device-add-form-sensors" role="tab" data-toggle="tab">{{ trans('front.sensors') }}</a></li>
        @endif
        <li><a href="#device-add-form-accuracy" role="tab" data-toggle="tab">{!!trans('front.accuracy')!!}</a></li>
        <li><a href="#device-add-form-tail" role="tab" data-toggle="tab">{!!trans('front.tail')!!}</a></li>
        <li><a href="javascript:" role="tab" class="disabled">{!!trans('front.services')!!}</a></li>
        @if (Auth::user()->can('view', $item, 'custom_fields') && $item->hasCustomFields())
            <li><a href="#device-custom-fields" role="tab" data-toggle="tab">{!!trans('admin.custom_fields')!!}</a></li>
        @endif
    </ul>

    {!!Form::open(['route' => 'admin.unregistered_devices_log.store', 'method' => 'POST'])!!}
    {!!Form::hidden('id')!!}

    @if(isset($imeis))
        @foreach($imeis as $_imei)
            {!! Form::hidden('imeis[]', $_imei) !!}
        @endforeach
    @endif

    <div class="tab-content">
        <div id="device-add-form-main" class="tab-pane active">
            @include('Frontend.Devices.partials.main')
        </div>

        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <div id="device-add-form-users" class="tab-pane">
                @include('Frontend.Devices.partials.users')
            </div>
        @endif

        <div id="device-add-form-icons" class="tab-pane">
            @include('Frontend.Devices.partials.icons')
        </div>

        <div id="device-add-form-advanced" class="tab-pane">
            @include('Frontend.Devices.partials.advanced')
        </div>

        <div id="device-add-form-sensors" class="tab-pane">
            @include('Frontend.Devices.partials.sensor_group')
        </div>

        <div id="device-add-form-accuracy" class="tab-pane">
            @include('Frontend.Devices.partials.accuracy')
        </div>

        <div id="device-add-form-tail" class="tab-pane">
            @include('Frontend.Devices.partials.tail')
        </div>

        @if (Auth::user()->can('view', $item, 'custom_fields') && $item->hasCustomFields())
            <div id="device-custom-fields" class="tab-pane">
                @include('Frontend.CustomFields.panel')
            </div>
        @endif
    </div>
    {!!Form::close()!!}

    <script>
        $(document).ready(function() {
            var measurements = {!!json_encode($device_fuel_measurements)!!};

            $(document).on('change', '#devices_create select[name="fuel_measurement_id"]', function () {
                var val = $(this).val();

                $.each(measurements, function (index, value) {
                    if (value.id == val) {
                        $('.distance_title').html(value.distance_title);
                        $('.fuel_title').html(value.fuel_title);
                        $('.cost_title').html(value.cost_title);

                    }
                });
            });

            $(document).on('change', '#devices_create input[name="enable_expiration_date"]', function () {
                if ($(this).prop('checked'))
                    $('input[name="expiration_date"]').removeAttr('disabled');
                else
                    $('input[name="expiration_date"]').attr('disabled', 'disabled');
            });

            $(document).on('change', '#devices_create input[name="forward[active]"]', function () {
                if ($(this).prop('checked'))
                    $('input[name^="forward["]:not([name="forward[active]"])').removeAttr('disabled');
                else
                    $('input[name^="forward["]:not([name="forward[active]"])').attr('disabled', 'disabled');
            });

            $('select[name="device_icons_type"]').trigger('change');

            $('#devices_create input[name="forward[active]"]').trigger('change');

            $('#devices_create select[name="fuel_measurement_id"]').trigger('change');

            $('#devices_create input[name="enable_expiration_date"]').trigger('change');
        });
    </script>
@stop