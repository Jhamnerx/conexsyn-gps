@extends('front::Layouts.modal')

@section('title')
    <i class="icon filter"></i> {{ trans('validation.attributes.filter') }}
@stop

@section('body')
    <div class="tab-content">
        {!! Form::open(['id' => 'filterForm', 'method' => 'GET']) !!}
        <div class="tab-pane active">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="checkbox-inline">
                            {!! Form::checkbox('filter_sidebar', 1, $input['filter_sidebar'] ?? null) !!}
                            {!! Form::label(null, trans('front.filter_sidebar')) !!}
                        </div>
                        <div class="checkbox-inline">
                            {!! Form::checkbox('filter_map', 1, $input['filter_map'] ?? null) !!}
                            {!! Form::label(null, trans('front.filter_map')) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('status', trans('validation.attributes.status') . ':') !!}
                        {!! Form::select('status[]', $statuses, $input['status'] ?? [], ['class' => 'form-control multiexpand half', 'multiple' => 'multiple']) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('online', trans('admin.last_connection') . ' (' . trans('front.minutes') . ')') !!}
                        {!! Form::number('online', $input['online'] ?? null, ['class' => 'form-control', 'placeholder' => trans('global.from')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('offline', '&nbsp;') !!}
                        {!! Form::number('offline', $input['offline'] ?? null, ['class' => 'form-control', 'placeholder' => trans('global.to')]) !!}
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
@stop

@section('scripts')
    <script>
        function applySidebarFilters() {
            $form = $('#filterForm');
            $button = $('#objects_tab a[data-modal="device_sidebar_filter"]');

            let filters = {},
                sidebar = false,
                map = false,
                $online = $('input[name="online"]', $form),
                $offline = $('input[name="offline"]', $form);

            if ($online.val() !== '' && $offline.val() !== '' && parseInt($online.val()) < parseInt($offline.val())) {
                let tmp = $online.val();
                $online.val($offline.val());
                $offline.val(tmp);

            }

            $form.serializeArray().map(function(item) {
                if (item.value === '')
                    return;


                if (item.name === 'filter_sidebar') {
                    sidebar = true;
                    return;
                }

                if (item.name === 'filter_map') {
                    map = true;
                    return;
                }

                let _name = item.name.replace(/\[\]$/, '');

                if (filters[_name]) {
                    if (typeof (filters[_name]) === "string") {
                        filters[_name] = [filters[_name]];
                    }
                    filters[_name].push(item.value);
                } else {
                    filters[_name] = item.value;
                }
            });

            $button
                .data('url', "{{ route('objects.sidebar.filters') }}?" + $form.serialize())
                .addClass('btn-primary')

            if ($.isEmptyObject(filters)) {
                $button.removeClass('btn-primary');
            }

            app.devices.setFiltersList(sidebar ? filters : null);
            app.devices.setFiltersMap(map ? filters : null);
        }

        function clearSidebarFilters() {
            let $form = $('#filterForm');

            $('input[name="online"]', $form).val('');
            $('input[name="offline"]', $form).val('');
            $('select[name="status[]"]', $form).val('').trigger('change');

            applySidebarFilters();
        }
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action" onclick="applySidebarFilters();" data-dismiss="modal">
        {!! trans('global.apply') !!}
    </button>

    <button type="button" class="btn btn-secondary" onclick="clearSidebarFilters();" data-dismiss="modal">
        {!! trans('global.clear') !!}
    </button>

    <div class="pull-right">
        <button type="button" class="btn btn-default" data-dismiss="modal">
            {!! trans('global.cancel') !!}
        </button>
    </div>
@stop