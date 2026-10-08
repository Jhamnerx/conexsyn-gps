@extends('Admin.Layouts.default')

@section('content')
    <div class="panel panel-default" id="table_unregistered_devices_log">

        <div class="panel-heading">
            <div class="panel-title"><i class="icon logs"></i> {{ trans('admin.unregistered_devices_log') }}</div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.UnregisteredDevicesLog.table')
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_unregistered_devices_log', {
            url:'{{ route("admin.unregistered_devices_log.index") }}',
            delete_url:'{{ route("admin.unregistered_devices_log.destroy") }}',
            create_url:{
                url: '{{ route("admin.unregistered_devices_log.create") }}',
                method: 'GET',
                modal: 'devices_create'
            }
        });

        function devices_create_modal_callback() {
            tables.get('table_unregistered_devices_log');
        }
    </script>
@stop