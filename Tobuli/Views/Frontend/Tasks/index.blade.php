@extends('Frontend.Layouts.modal')
@section('modal_class', 'modal-lg')

@section('title')
    <i class="icon task"></i> {!!trans('front.tasks')!!}
@stop

@section('body')
    <?php $item = new \Tobuli\Entities\Task(); ?>

    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#new_task" role="tab" data-toggle="tab">{!!trans('front.new_task')!!}</a></li>

        @if (Auth::user()->perm('task_sets', 'view'))
            <li>
                <a href="#setup-form-task-set-list"
                   role="tab"
                   data-toggle="tab"
                   data-url="{{ route('task_sets.index') }}"
                >
                    {{ trans('front.task_sets') }}
                </a>
            </li>
        @endif

        <li><a href="#setup-form-task-list" role="tab" data-toggle="tab" data-url="{{ route('lookup.table', 'tasks') }}">{!! trans('front.all_tasks') !!}</a></li>
    </ul>

    <div id="tasks-modal">
        <div class="tab-content">
            <div id="new_task" class="tab-pane active">
                {!!Form::open(['route' => 'tasks.store', 'method' => 'POST'])!!}
                {!!Form::hidden('id')!!}

                <div class="alert alert-success" role="alert" style="display: none;">{!!trans('front.task_created')!!}</div>
                <div class="alert alert-danger main-alert" role="alert" style="display: none;"></div>

                <div class="row">
                    <div class="col-sm-9">
                        <div class="form-group">
                            {!!Form::label('device_id', trans('validation.attributes.device_id').':')!!}
                            {!!Form::select('device_id', $devices, null, ['class' => 'form-control', 'data-live-search' => 'true'])!!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!!Form::label('invoice_number', trans('validation.attributes.invoice_number').':')!!}
                            {!!Form::text('invoice_number', null, ['class' => 'form-control'])!!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-9">
                        <div class="form-group">
                            {!!Form::label('title', trans('validation.attributes.title').':')!!}
                            {!!Form::text('title',  null, ['class' => 'form-control'])!!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!!Form::label('priority', trans('validation.attributes.priority').':')!!}
                            {!!Form::select('priority', $priorities, null, ['class' => 'form-control'])!!}
                        </div>
                    </div>
                </div>

                @if (Auth::user()->perm('task_sets', 'view'))
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group">
                                {!!Form::label('task_set_id', trans('validation.attributes.task_set_id').':')!!}
                                {!!Form::select('task_set_id', $taskSets, null, ['class' => 'form-control',  'data-live-search' => 'true'])!!}
                            </div>
                        </div>
                    </div>
                @endif

                <hr>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('pickup_address', trans('validation.attributes.pickup_address').':')!!}
                            {!! Form::hidden('pickup_address_id') !!}
                            {!! Form::hidden('pickup_address_lat') !!}
                            {!! Form::hidden('pickup_address_lng') !!}
                            @include('Frontend.Addresses.partials.map_button',
                                [
                                    'type' => 'pickup',
                                    'parent' => '#new_task',
                                    'address' => '',
                                    'lat' => '',
                                    'lng' => '',
                                ]
                            )
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! Form::label('pickup_time_from', trans('global.from'))!!}
                                    <div class="input-group">
                                        <div class="has-feedback">
                                            <i class="icon calendar form-control-feedback"></i>
                                            <input class="datetimepicker form-control" name="pickup_time_from" type="text" value="{{ date('Y-m-d') . ' 08:00:00' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! Form::label('pickup_time_to', trans('global.to'))!!}
                                    <div class="input-group">
                                        <div class="has-feedback">
                                            <i class="icon calendar form-control-feedback"></i>
                                            <input class="datetimepicker form-control" name="pickup_time_to" type="text" value="{{ date('Y-m-d') . ' 12:00:00' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($acEnabled)
                            <div class="form-group">
                                <div class="checkbox">
                                    {!! Form::hidden('pickup_ac', 0) !!}
                                    {!! Form::checkbox('pickup_ac', 1) !!}
                                    {!! Form::label('pickup_ac', trans('validation.attributes.auto_status_change')) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('pickup_ac_radius', trans('front.radius') . ' (' . trans('front.mt') . ')')!!}
                                        <div class="input-group">
                                            <input class="form-control" name="pickup_ac_radius" type="text" value="{{ $acValues['pickup_radius'] }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('pickup_ac_duration', trans('front.duration') . ' (' . trans('front.second_short') . ')')!!}
                                        <div class="input-group">
                                            <input class="form-control" name="pickup_ac_duration" type="text" value="{{ $acValues['pickup_duration'] }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('pickup_ac_status', trans('front.status'))!!}
                                        <div class="input-group">
                                            {!! Form::select('pickup_ac_status', $statuses, $acValues['pickup_status'], ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('delivery_address', trans('validation.attributes.delivery_address').':')!!}
                            {!! Form::hidden('delivery_address_id') !!}
                            {!! Form::hidden('delivery_address_lat') !!}
                            {!! Form::hidden('delivery_address_lng') !!}
                            @include('Frontend.Addresses.partials.map_button',
                                [
                                    'type' => 'delivery',
                                    'parent' => '#new_task',
                                    'address' => '',
                                    'lat' => '',
                                    'lng' => '',
                                ]
                            )
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="delivery_time_from" class="control-label">{{ trans('global.from') }}</label>
                                    <div class="input-group">
                                        <div class="has-feedback">
                                            <i class="icon calendar form-control-feedback"></i>
                                            <input class="datetimepicker form-control" name="delivery_time_from" type="text" value="{{ date('Y-m-d') . ' 12:00:00' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="delivery_time_to" class="control-label">{{ trans('global.to') }}</label>
                                    <div class="input-group">
                                        <div class="has-feedback">
                                            <i class="icon calendar form-control-feedback"></i>
                                            <input class="datetimepicker form-control" name="delivery_time_to" type="text" value="{{ date('Y-m-d')  . ' 17:00:00' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($acEnabled)
                            <div class="form-group">
                                <div class="checkbox">
                                    {!! Form::hidden('delivery_ac', 0) !!}
                                    {!! Form::checkbox('delivery_ac', 1) !!}
                                    {!! Form::label('delivery_ac', trans('validation.attributes.auto_status_change')) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('delivery_ac_radius', trans('front.radius') . ' (' . trans('front.mt') . ')')!!}
                                        <div class="input-group">
                                            <input class="form-control" name="delivery_ac_radius" type="text" value="{{ $acValues['delivery_radius'] }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('delivery_ac_duration', trans('front.duration') . ' (' . trans('front.second_short') . ')')!!}
                                        <div class="input-group">
                                            <input class="form-control" name="delivery_ac_duration" type="text" value="{{ $acValues['delivery_duration'] }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('delivery_ac_status', trans('front.status'))!!}
                                        <div class="input-group">
                                            {!! Form::select('delivery_ac_status', $statuses, $acValues['delivery_status'], ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    {!!Form::label('comment', trans('front.comment').':')!!}
                    {!!Form::textarea('comment',  null, ['class' => 'form-control'])!!}
                </div>

                @if (config('addon.custom_fields_task'))
                    <div id="task-custom-fields">
                        @include('Frontend.CustomFields.panel')
                    </div>
                @endif

                {!!Form::close()!!}
            </div>

            <div class="tab-pane" id="setup-form-task-list"></div>

            @if (Auth::user()->perm('task_sets', 'view'))
                <div class="tab-pane" id="setup-form-task-set-list"></div>
            @endif
        </div>
    </div>
    <script>
            tables.set_config('taskList', {
                url:'{{ route('tasks.list') }}',
                do_destroy: {
                    url: '{{ route("tasks.do_destroy") }}',
                    modal: 'tasks_destroy',
                    method: 'GET'
                },
                assign: {
                    url: '{{ route("tasks.assign_form") }}',
                    modal: 'tasks_assign',
                    method: 'GET'
                }
            });

            function tasks_edit_modal_callback(res) {
                if (res.status == 1) {
                    reloadTaskDatatable();
                }
            }

            function tasks_destroy_modal_callback(res) {
                if (res.status == 1)
                    reloadTaskDatatable();
            }

            function tasks_import_modal_callback() {
                reloadTaskDatatable();
            }

            function tasks_assign_modal_callback(res) {
                if (res.status == 1)
                    reloadTaskDatatable();
            }

            function reloadTaskDatatable() {
                $('#setup-form-task-list .dataTable').DataTable().draw();
            }

            $('#searchTasks').on('click', function () {
                var $deviceId = $('#search_device_id').find("option:selected").val();
                var $status = $('#search_task_status').find("option:selected").val();
                var $time_from = $('#search_time_from').val();
                var $time_to = $('#search_time_to').val();
                var $invoice_number = $('#search_invoice_number').val();

                $.ajax({
                    type: "Get",
                    dataType: "html",
                    url: "{{route('tasks.list')}}",
                    data: {
                        search_device_id: $deviceId,
                        search_task_status: $status,
                        search_time_from: $time_from,
                        search_time_to: $time_to,
                        search_invoice_number: $invoice_number
                    },
                    beforeSend: function() {
                        loader.add( $('body') );
                    },
                    success: function(res){
                        $table = $('[data-table]', $('#tasks'));
                        $table.html(res)


                    },
                    complete: function() {
                        loader.remove( $('body') );
                    }
                });
            });
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!!trans('global.save')!!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>
    <button type="button" class="btn btn-default" data-modal="tasks_import" data-url="{{ route('tasks.import') }}">{!!trans('front.import')!!}</button>
@stop
