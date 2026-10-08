@extends('admin::Layouts.default')

@section('content')
    <div class="panel panel-default" id="table_sensor_icons">
        <div class="panel-heading">
            <ul class="nav nav-tabs nav-icons pull-right">
                <li role="presentation" class="">
                    <a href="javascript:" type="button" class="" data-modal="sensor_icons_create" data-url="{{ route("admin.sensor_icons.create") }}">
                        <i class="icon add" title="{{ trans('global.add') }}"></i>
                    </a>
                </li>

            </ul>

            <div class="panel-title"><i class="icon user"></i> {{ trans('admin.sensor_icons') }}</div>
        </div>

        <div class="panel-body">
            <div data-table>
                @include('admin::SensorIcons.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_sensor_icons', {
            url: '{{ route("admin.sensor_icons.index") }}',
            delete_url:'{{ route("admin.sensor_icons.destroy") }}'
        });

        function sensor_icons_edit_modal_callback() {
            tables.get('table_sensor_icons');
        }

        function sensor_icons_create_modal_callback() {
            tables.get('table_sensor_icons');
        }

        $(document).ready(function() {
            $(document).on('click', '.table-icon .controls a', function () {
                $.ajax({
                    type: 'POST',
                    url: '{{ route("admin.sensor_icons.destroy") }}',
                    data: {
                        _method: 'DELETE',
                        id: {0:$(this).data('id')}
                    },
                    success: function () {
                        tables.get('table_sensor_icons');
                    }
                });
            });
        });
    </script>
@stop