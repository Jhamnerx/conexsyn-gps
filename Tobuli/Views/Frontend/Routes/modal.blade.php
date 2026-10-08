@extends('front::Layouts.modal')
@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon routes"></i> {!! trans('front.routes') !!}
@stop

@section('body')
    <div class="row" id="table_routes">
        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="pull-right">
            <ul class="nav nav-tabs nav-icons">
                <li>
                    <a href="javascript:" data-url="{{ route('routes.export') }}" data-modal="routes_export">
                        <i class="icon download" title="{{ trans('front.export') }}"></i>
                    </a>
                </li>
                <li>
                    <a href="javascript:" data-url="{{ route('routes.import_modal') }}" data-modal="routes_import">
                        <i class="icon upload" title="{{ trans('front.import') }}"></i>
                    </a>
                </li>
                <li>
                    <a href='javascript:' data-dismiss="modal" onclick="app.routes.create();">
                        <i class="icon add" title="{{ trans('front.add_new') }}"></i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="col-xs-4">
            <div class="form-group search">
                {!! Form::text('search_phrase', null, ['class' => 'form-control', 'placeholder' => trans('admin.search_it'), 'data-filter' => 'true']) !!}
            </div>
        </div>

        <div class="col-xs-12" data-table>
            @include('front::Routes.table')
        </div>
    </div>
@stop

<script>
    tables.set_config('table_routes', {
        url: '{{ route("routes.table") }}',
        destroy: '{{ route("routes.destroy") }}',
        set_active: {
            url: '{{ route("routes.change_active", ['active' => 1]) }}',
            method: 'POST'
        },
        set_inactive: {
            url: '{{ route("routes.change_active", ['active' => 0]) }}',
            method: 'POST'
        }
    });

    function routes_import_modal_callback(res) {
        app.notice.success(res.message);

        tables.get('table_routes');
        app.routes.list();
        app.routes.load();
    }
</script>

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{!! trans('global.cancel') !!}</button>
@stop