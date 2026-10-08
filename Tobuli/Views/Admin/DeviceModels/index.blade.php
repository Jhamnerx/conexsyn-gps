@extends('admin::Layouts.default')

@section('content')
    @if (Session::has('messages'))
        <div class="alert alert-success">
            <ul>
                @foreach (Session::get('messages') as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel panel-default" id="table_device_models">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="panel-heading">
            <div class="panel-title"><i class="icon user"></i> {{ trans('front.device_models') }}</div>

            <div class="panel-form">
                <div class="form-group search">
                    {!! Form::text('search_phrase', null, ['class' => 'form-control', 'placeholder' => trans('admin.search_it'), 'data-filter' => 'true']) !!}
                </div>
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.DeviceModels.table')
        </div>
    </div>
@stop

@section('javascript')
<script>
    tables.set_config('table_device_models', {
        url: '{{ route("admin.device_models.table") }}',
    });

    function device_models_edit_modal_callback() {
        tables.get('table_device_models');
    }
</script>
@stop