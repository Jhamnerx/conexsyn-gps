@extends('Admin.Layouts.default')

@section('content')
    <div class="panel panel-default" id="table_backup">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="panel-heading">
            <div class="panel-title">{{ trans('front.backups') }}</div>

            <div class="panel-form">
                <div class="form-group search">
                    {!! Form::text('search_phrase', null, ['class' => 'form-control', 'placeholder' => trans('admin.search_it'), 'data-filter' => 'true']) !!}
                </div>
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.Backup.table')
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_backup', {
            url:'{{ route("admin.backup.table") }}',
        });
    </script>
@stop