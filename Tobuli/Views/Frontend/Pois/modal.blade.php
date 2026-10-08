@extends('front::Layouts.modal')
@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon pois"></i> {!! trans('front.poi') !!}
@stop

@section('body')
    <div class="row" id="table_pois">
        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="pull-right">
            <ul class="nav nav-tabs nav-icons">
                <li>
                    <a href="javascript:" data-url="{{ route('pois.export') }}" data-modal="pois_export">
                        <i class="icon download" title="{{ trans('front.export') }}"></i>
                    </a>
                </li>
                <li>
                    <a href="javascript:" data-url="{{ route('pois.import.index') }}" data-modal="pois_import">
                        <i class="icon upload" title="{{ trans('front.import') }}"></i>
                    </a>
                </li>
                <li>
                    <a href='javascript:' data-dismiss="modal" onclick="app.pois.create();">
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
            @include('front::Pois.table')
        </div>
    </div>
@stop

<script>
    tables.set_config('table_pois', {
        url: '{{ route("pois.table") }}',
        destroy: '{{ route("pois.destroy") }}',
        set_active: {
            url: '{{ route("pois.change_active", ['active' => 1]) }}',
            method: 'POST'
        },
        set_inactive: {
            url: '{{ route("pois.change_active", ['active' => 0]) }}',
            method: 'POST'
        }
    });

    function pois_import_modal_callback(res) {
        app.notice.success(res.message);

        tables.get('table_pois');
        app.pois.list();
        app.pois.load();
    }
</script>

@section('buttons')
    <button type="button" class="btn btn-default" data-dismiss="modal">{!! trans('global.cancel') !!}</button>
@stop