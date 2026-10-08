@extends('Frontend.Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    @php /** @var \Tobuli\Entities\Task $item */ @endphp

    {!!Form::open(['route' => 'tasks.update', 'method' => 'PUT', 'class' => 'task-form'])!!}
    {!!Form::hidden('id', $item->id)!!}

    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                {!!Form::label('device_id', trans('validation.attributes.device_id').':')!!}
                {!!Form::select('device_id', $devices, $item->device_id, ['class' => 'form-control',  'data-live-search' => 'true'])!!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!!Form::label('title', trans('validation.attributes.title').':')!!}
                {!!Form::text('title',  $item->title, ['class' => 'form-control'])!!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!!Form::label('status', trans('validation.attributes.status').':')!!}
                {!!Form::select('status', $statuses, $item->status, ['class' => 'form-control'])!!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!!Form::label('priority', trans('validation.attributes.priority').':')!!}
                {!!Form::select('priority', $priorities, $item->priority, ['class' => 'form-control'])!!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!!Form::label('invoice_number', trans('validation.attributes.invoice_number').':')!!}
                {!!Form::text('invoice_number', $item->invoice_number, ['class' => 'form-control'])!!}
            </div>
        </div>

        @if (Auth::user()->perm('task_sets', 'view'))
            <div class="col-sm-6">
                <div class="form-group">
                    {!!Form::label('task_set_id', trans('validation.attributes.task_set_id').':')!!}
                    {!!Form::select('task_set_id', $taskSets, $item->task_set_id, ['class' => 'form-control',  'data-live-search' => 'true'])!!}
                </div>
            </div>
        @endif
    </div>

    <hr>

    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('pickup_address', trans('validation.attributes.pickup_address').':')!!}
                {!! Form::hidden('pickup_address_id') !!}
                {!! Form::hidden('pickup_address_lat', $item->pickup_address_lat) !!}
                {!! Form::hidden('pickup_address_lng', $item->pickup_address_lng) !!}
                @include('Frontend.Addresses.partials.map_button',
                    [
                        'type' => 'pickup',
                        'parent' => '#tasks_edit',
                        'address' => $item->pickup_address,
                        'lat' => $item->pickup_address_lat,
                        'lng' => $item->pickup_address_lng,
                    ]
                )
            </div>
            <div class=" form-horizontal">
                <div class="form-group">
                    {!! Form::label('pickup_time_from', trans('global.from'), ['class' => 'col-xs-3 control-label'])!!}
                    <div class="col-xs-9">
                        <div class="input-group">
                            <div class="has-feedback">
                                <i class="icon calendar form-control-feedback"></i>
                                {!!Form::text('pickup_time_from', $item->pickup_time_from, ['class' => 'datetimepicker form-control'])!!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::label('pickup_time_to', trans('global.to'), ['class' => 'col-xs-3 control-label'])!!}
                    <div class="col-xs-9">
                        <div class="input-group">
                            <div class="has-feedback">
                                <i class="icon calendar form-control-feedback"></i>
                                {!!Form::text('pickup_time_to', $item->pickup_time_to, ['class' => 'datetimepicker form-control'])!!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($acEnabled)
                <div class="form-group">
                    <div class="checkbox">
                        {!! Form::hidden('pickup_ac', 0) !!}
                        {!! Form::checkbox('pickup_ac', 1, $item->pickup_ac) !!}
                        {!! Form::label('pickup_ac', trans('validation.attributes.auto_status_change')) !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('pickup_ac_radius', trans('front.radius') . ' (' . trans('front.mt') . ')')!!}
                            <div class="input-group">
                                <input class="form-control" name="pickup_ac_radius" type="text" value="{{ $item->pickup_ac_radius }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('pickup_ac_duration', trans('front.duration') . ' (' . trans('front.second_short') . ')')!!}
                            <div class="input-group">
                                <input class="form-control" name="pickup_ac_duration" type="text" value="{{ $item->pickup_ac_duration }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('pickup_ac_status', trans('front.status'))!!}
                            <div class="input-group">
                                {!! Form::select('pickup_ac_status', $statuses, $item->pickup_ac_status, ['class' => 'form-control']) !!}
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
                {!! Form::hidden('delivery_address_lat', $item->delivery_address_lat) !!}
                {!! Form::hidden('delivery_address_lng', $item->delivery_address_lng) !!}
                @include('Frontend.Addresses.partials.map_button',
                    [
                        'type' => 'delivery',
                        'parent' => '#tasks_edit',
                        'address' => $item->delivery_address,
                        'lat' => $item->delivery_address_lat,
                        'lng' => $item->delivery_address_lng,
                    ]
                )
            </div>
            <div class=" form-horizontal">
                <div class="form-group">
                    {!! Form::label('delivery_time_from', trans('global.from'), ['class' => 'col-xs-3 control-label'])!!}
                    <div class="col-xs-9">
                        <div class="input-group">
                            <div class="has-feedback">
                                <i class="icon calendar form-control-feedback"></i>
                                {!!Form::text('delivery_time_from', $item->delivery_time_from, ['class' => 'datetimepicker form-control'])!!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('delivery_time_to', trans('global.to'), ['class' => 'col-xs-3 control-label'])!!}
                    <div class="col-xs-9">
                        <div class="input-group">
                            <div class="has-feedback">
                                <i class="icon calendar form-control-feedback"></i>
                                {!!Form::text('delivery_time_to', $item->delivery_time_to, ['class' => 'datetimepicker form-control'])!!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($acEnabled)
                <div class="form-group">
                    <div class="checkbox">
                        {!! Form::hidden('delivery_ac', 0) !!}
                        {!! Form::checkbox('delivery_ac', 1, $item->delivery_ac) !!}
                        {!! Form::label('delivery_ac', trans('validation.attributes.auto_status_change')) !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('delivery_ac_radius', trans('front.radius') . ' (' . trans('front.mt') . ')')!!}
                            <div class="input-group">
                                <input class="form-control" name="delivery_ac_radius" type="text" value="{{ $item->delivery_ac_radius }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('delivery_ac_duration', trans('front.duration') . ' (' . trans('front.second_short') . ')')!!}
                            <div class="input-group">
                                <input class="form-control" name="delivery_ac_duration" type="text" value="{{ $item->delivery_ac_duration }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('delivery_ac_status', trans('front.status'))!!}
                            <div class="input-group">
                                {!! Form::select('delivery_ac_status', $statuses, $item->delivery_ac_status, ['class' => 'form-control']) !!}
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
        {!!Form::textarea('comment',  $item->comment, ['class' => 'form-control'])!!}
    </div>


    @if (config('addon.custom_fields_task'))
        <hr>

        <div id="task-custom-fields">
            @include('Frontend.CustomFields.panel')
        </div>
    @endif

    {!!Form::close()!!}
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!!trans('global.save')!!}</button>
    <button class="btn btn-default" data-target="#deleteTask" data-toggle="modal">{!!trans('global.delete')!!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>
@stop