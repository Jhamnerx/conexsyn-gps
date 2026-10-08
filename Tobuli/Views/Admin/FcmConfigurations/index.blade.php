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

    <div class="panel panel-default" id="table_fcm_configurations">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="panel-heading">
            <ul class="nav nav-tabs nav-icons pull-right">
                <li role="presentation" class="">
                    <a href="javascript:"
                       type="button"
                       data-modal="fcm_configurations_create"
                       data-url="{{ route('admin.fcm_configurations.create') }}"
                    >
                        <i class="icon add" title="{{ trans('admin.add_new') }}"></i>
                    </a>
                </li>
                <li role="presentation" class="">
                    <a href="javascript:"
                       type="button"
                       data-modal="fcm_test_create"
                       data-url="{{ route('admin.fcm_test.create') }}"
                    >
                        <i class="icon send" title="{{ trans('front.send_test_notification') }}"></i>
                    </a>
                </li>
            </ul>

            <div class="panel-title"><i class="icon user"></i> {{ trans('admin.fcm_configurations') }}</div>

            <div class="panel-form">
                <div class="form-group search">
                    {!! Form::text('search_phrase', null, ['class' => 'form-control', 'placeholder' => trans('admin.search_it'), 'data-filter' => 'true']) !!}
                </div>
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.FcmConfigurations.table')
        </div>
    </div>
@stop

@section('javascript')
<script>
    tables.set_config('table_fcm_configurations', {
        url: '{{ route("admin.fcm_configurations.table") }}',
    });

    function fcm_configurations_create_modal_callback() {
        tables.get('table_fcm_configurations');
    }

    function fcm_configurations_edit_modal_callback() {
        tables.get('table_fcm_configurations');
    }

    function fcm_configurations_set_default_modal_callback() {
        tables.get('table_fcm_configurations');
    }

    function fcm_configurations_destroy_modal_callback() {
        tables.get('table_fcm_configurations');
    }

    function fcm_test_create_modal_callback(data) {
        $('#fcm_test_create #results').html(data.html);
    }
</script>
@stop