@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon device"></i> {!! trans('global.edit') !!}
@stop

@section('body')
    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#device-form-main" role="tab" data-toggle="tab">{!! trans('front.main') !!}</a></li>
        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <li><a href="#device-form-users" role="tab" data-toggle="tab">{!! trans('front.users') !!}</a></li>
        @endif
        <li><a href="#device-form-icons" role="tab" data-toggle="tab">{!! trans('front.icons') !!}</a></li>
        <li><a href="#device-form-advanced" role="tab" data-toggle="tab">{!! trans('front.advanced') !!}</a></li>
        <li><a href="#device-form-sensors" role="tab" data-toggle="tab">{!! trans('front.sensors') !!}</a></li>
        <li><a href="#device-form-services" role="tab" data-toggle="tab">{!! trans('front.services') !!}</a></li>
        <li><a href="#device-form-accuracy" role="tab" data-toggle="tab">{!! trans('front.accuracy') !!}</a></li>
        <li><a href="#device-form-tail" role="tab" data-toggle="tab">{!! trans('front.tail') !!}</a></li>

        @if (expensesTypesExist())
            <li><a href="#device-form-expenses" role="tab" data-toggle="tab"
                    data-url="{{ route('device_expenses.index', $item->id) }}">{!! trans('front.expenses') !!}</a></li>
        @endif
        @if (Auth::User()->perm('device_camera', 'view'))
            <li><a href="#device-form-cameras" role="tab" data-toggle="tab">{!! trans('front.cameras') !!}</a></li>
        @endif
        @if (Auth::user()->can('view', $item, 'custom_fields') && $item->hasCustomFields())
            <li><a href="#device-custom-fields" role="tab" data-toggle="tab">{!! trans('admin.custom_fields') !!}</a></li>
        @endif
        @if (Auth::user()->can('edit', $item, 'tags'))
            <li><a href="#device-form-tags" role="tab" data-toggle="tab">{!! trans('front.tags') !!}</a></li>
        @endif
    </ul>

    {!! Form::open(['route' => 'devices.update', 'method' => 'PUT']) !!}
    {!! Form::hidden('id', $item->id) !!}

    <div class="tab-content">
        <div id="device-form-main" class="tab-pane active">
            @include('Frontend.Devices.partials.main')
        </div>

        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <div id="device-form-users" class="tab-pane">
                @include('Frontend.Devices.partials.users')
            </div>
        @endif

        <div id="device-form-icons" class="tab-pane">
            @include('Frontend.Devices.partials.icons')
        </div>

        <div id="device-form-advanced" class="tab-pane">
            @include('Frontend.Devices.partials.advanced')
        </div>

        <div id="device-form-sensors" class="tab-pane">
            @include('Frontend.Devices.partials.sensors')
            @includeWhen(isAdmin(), 'Frontend.Devices.partials.sensor_group')
        </div>

        <div id="device-form-services" class="tab-pane">
            @include('Frontend.Devices.partials.services')
        </div>

        <div id="device-form-accuracy" class="tab-pane">
            @include('Frontend.Devices.partials.accuracy', ['detectionFields' => true])
        </div>

        <div id="device-form-tail" class="tab-pane">
            @include('Frontend.Devices.partials.tail')
        </div>


        @if (expensesTypesExist())
            <div id="device-form-expenses" class="tab-pane"></div>
        @endif

        @if (Auth::User()->perm('device_camera', 'view'))
            <div id="device-form-cameras" class="tab-pane">
                @include('Frontend.Devices.partials.cameras')
            </div>
        @endif

        @if (Auth::user()->can('view', $item, 'custom_fields') && $item->hasCustomFields())
            <div id="device-custom-fields" class="tab-pane">
                @include('Frontend.CustomFields.panel')
            </div>
        @endif

        @if (Auth::user()->can('edit', $item, 'tags'))
            <div id="device-form-tags" class="tab-pane">
                @include('Frontend.Devices.partials.tags')
            </div>
        @endif
    </div>

    {!! Form::close() !!}

    <script>
        $(document).ready(function() {

            var measurements = {!! json_encode($device_fuel_measurements) !!};

            $(document).on('change', '#devices_edit select[name="fuel_measurement_id"]', function() {
                var val = $(this).val();

                $.each(measurements, function(index, value) {
                    if (value.id == val) {
                        $('.distance_title').html(value.distance_title);
                        $('.fuel_title').html(value.fuel_title);
                        $('.cost_title').html(value.cost_title);
                    }
                });
            });

            $(document).on('change', '#devices_edit input[name="enable_expiration_date"]', function() {
                if ($(this).prop('checked'))
                    $('input[name="expiration_date"]').removeAttr('disabled');
                else
                    $('input[name="expiration_date"]').attr('disabled', 'disabled');
            });

            $(document).on('change', '#devices_edit input[name="forward[active]"]', function() {
                if ($(this).prop('checked'))
                    $('input[name^="forward["]:not([name="forward[active]"])').removeAttr('disabled');
                else
                    $('input[name^="forward["]:not([name="forward[active]"])').attr('disabled', 'disabled');
            });


            $('select[name="device_icons_type"]').trigger('change');

            $('#devices_edit input[name="forward[active]"]').trigger('change');

            $(document).on('change', '#devices_edit input[name="mininter"]', function() {
                $('#devices_edit select[name="mininter_type"]').prop('disabled', !$(this).prop('checked'));
            });

            $('#devices_edit input[name="mininter"]').trigger('change');

            $('#devices_edit select[name="engine_hours"]').trigger('change');

            $('#devices_edit input[name="enable_expiration_date"]').trigger('change');

            $('#devices_edit select[name="fuel_measurement_id"]').trigger('change');
        });

        tables.set_config('device-form-services', {
            url: '{!! route('services.table', $item->id) !!}'
        });

        function services_create_modal_callback() {
            tables.get('device-form-services');
        }

        function services_edit_modal_callback() {
            tables.get('device-form-services');
        }

        function services_destroy_modal_callback() {
            tables.get('device-form-services');
        }

        tables.set_config('device-form-sensors', {
            url: '{!! route('sensors.index', $item->id) !!}'
        });

        function sensors_create_modal_callback() {
            tables.get('device-form-sensors');
        }

        function sensors_edit_modal_callback() {
            tables.get('device-form-sensors');
        }

        function sensors_destroy_modal_callback() {
            tables.get('device-form-sensors');
        }

        tables.set_config('device-form-cameras', {
            url: '{!! route('device_camera.index', $item->id) !!}'
        });

        function device_camera_create_modal_callback() {
            tables.get('device-form-cameras');
        }

        function device_camera_edit_modal_callback() {
            tables.get('device-form-cameras');
        }

        function device_camera_destroy_modal_callback() {
            tables.get('device-form-cameras');
        }

        function set_engine_hours_modal_callback() {
            app.devices.loadData({{ $item->id }});
        }
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!! trans('global.save') !!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!! trans('global.cancel') !!}</button>
    @if (Auth::User()->perm('devices', 'remove'))
        <a href="javascript:" data-modal="objects_delete" class="btn btn-danger"
            data-url="{{ route('devices.do_destroy', ['id' => $item->id]) }}">
            {{ trans('global.delete') }}
        </a>
    @endif
@stop
