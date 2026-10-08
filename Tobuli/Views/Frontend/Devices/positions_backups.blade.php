@extends('Frontend.Layouts.modal')

@section('title', trans('front.positions_backups'))

@section('body')
    @if (Session::has('success'))
        <div class="alert alert-success alert-dismissible">
            {!! Session::get('success') !!}
        </div>
    @endif

    <div id="positions_backups_table">
        <div data-table>
            @include('Frontend.Devices.positions_backups_table')
        </div>
    </div>
@stop

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.close') }}</button>
@stop

@section('scripts')
    <script>
        tables.set_config('positions_backups_table', {
            url:'{{ route('admin.objects.positions_backups.table', $id) }}'
        });
    </script>
@stop