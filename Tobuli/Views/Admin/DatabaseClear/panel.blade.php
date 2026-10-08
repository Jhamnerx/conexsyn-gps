<div class="panel panel-default">

    <div class="panel-heading">
        <div class="panel-title">{{ trans('admin.database_clear') }}</div>
    </div>

    <div class="panel-body">
        {!! Form::open(array('route' => 'admin.db_clear.save', 'method' => 'POST', 'class' => 'form form-horizontal', 'id' => 'database-clear-form')) !!}

        <div class="form-group">
            <div class="col-xs-12">
                <div class="checkbox">
                    {!! Form::checkbox('status', 1, !empty($settings['status'])) !!}
                    {!! Form::label('status', trans('validation.attributes.database_clear_status') ) !!}
                </div>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label(null, trans('front.from'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
            <div class="col-xs-8">
                <div class="radio-inline">
                    {!! Form::radio('from', 'server_time', 'server_time' == \Illuminate\Support\Arr::get($settings,'from')) !!}
                    {!! Form::label('from', trans('front.server_time') ) !!}
                </div>
                <div class="radio-inline">
                    {!! Form::radio('from', 'last_connection', 'last_connection' == \Illuminate\Support\Arr::get($settings,'from')) !!}
                    {!! Form::label('from', trans('admin.last_connection') ) !!}
                </div>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('days', trans('validation.attributes.database_clear_days'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::text('days', isset($settings['days']) ? $settings['days'] : 90, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label(null, trans('front.database_size'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
            <div class="col-xs-12 col-sm-8" id="db-size-field">
                <div class="input-group">
                    <span class="input-group-addon" id="db-size-total"></span>
                </div>
                <div class="input-group">
                    <div class="progress" style="">
                        <div class="progress-bar progress-bar-warning" id="db-size-used"></div>
                        <div class="progress-bar progress-bar-success" id="db-size-reserved"></div>
                    </div>
                </div>
            </div>
        </div>

        {!! Form::close() !!}
    </div>

    <div class="panel-footer">
        <button type="submit" class="btn btn-action" onClick="$('#database-clear-form').submit();">{{ trans('global.save') }}</button>
    </div>
</div>

@push('javascript')
    <script>
        $(document).ready(function() {
            let container = $('#db-size-field');
            $.ajax({
                type: 'GET',
                url: '{{ route('admin.db_clear.size') }}',
                beforeSend: function() {
                    $('.progress', container).hide();
                    loader.add(container);
                },
                success: function(response) {
                    $('.progress', container).show();
                    $('#db-size-total').html(response.total.size);
                    $('#db-size-used').css('width', response.used.percentage + '%').html(response.used.size);
                    $('#db-size-reserved').css('width', response.reserved.percentage + '%').html(response.reserved.size);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handlerFailTarget(jqXHR, textStatus, errorThrown, container);
                },
                complete: function () {
                    loader.remove(container);
                }
            });
        });
    </script>
@endpush