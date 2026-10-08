<?php /** @var \Tobuli\Entities\Device $item */ ?>
@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon device"></i> {!!trans('global.edit')!!}
@stop

@section('body')
    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#device-form-main" role="tab" data-toggle="tab">{!!trans('front.main')!!}</a></li>
        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <li><a href="#device-form-users" role="tab" data-toggle="tab">{!!trans('front.users')!!}</a></li>
        @endif
        <li><a href="#device-form-icons" role="tab" data-toggle="tab">{!!trans('front.icons')!!}</a></li>
        <li><a href="#device-form-advanced" role="tab" data-toggle="tab">{!!trans('front.advanced')!!}</a></li>
        <li><a href="#device-form-sensors" role="tab" data-toggle="tab">{!!trans('front.sensors')!!}</a></li>
        <li><a href="#device-form-tail" role="tab" data-toggle="tab">{!!trans('front.tail')!!}</a></li>
        @if(expensesTypesExist())
            <li><a href="#device-form-expenses" role="tab" data-toggle="tab" data-url="{{ route('device_expenses.index', $item->id) }}">{!!trans('front.expenses')!!}</a></li>
        @endif
    </ul>

    {!!Form::open(['route' => ['beacons.update', $item->id], 'method' => 'PUT'])!!}
    {!!Form::hidden('id', $item->id)!!}
    <?php
    $additional_fields_on = settings('plugins.additional_installation_fields.status');
    ?>
    <div class="tab-content">
        <div id="device-form-main" class="tab-pane active">
            @if (isAdmin())
                <div class="form-group">
                    <div class="checkbox-inline">
                        {!! Form::hidden('active', 0) !!}
                        {!! Form::checkbox('active', 1, $item->active) !!}
                        {!! Form::label(null, trans('validation.attributes.active')) !!}
                    </div>
                </div>
            @endif

            <div class="form-group">
                {!!Form::label('name', trans('validation.attributes.name').'*:')!!}
                {!!Form::text('name', $item->name, ['class' => 'form-control'])!!}
            </div>


            @if(Auth::user()->can('view', $item, 'imei'))
                <div class="form-group">
                    <label for="imei">{{ trans('front.tracker_id') }}:</label>
                    {!!Form::text('imei', $item->imei, ['class' => 'form-control', 'placeholder' => trans('front.imei_placeholder')] + ( ! Auth::user()->can('edit', $item, 'imei') ? ['disabled' => 'disabled'] : []) )!!}
                </div>
            @endif
        </div>

        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <div id="device-form-users" class="tab-pane">
                @include('Frontend.Devices.partials.users')
            </div>
        @endif

        <div id="device-form-icons" class="tab-pane">
            @include('Frontend.Devices.partials.icons')
        </div>

        <div id="device-form-advanced" class="tab-pane">
            <div class="form-group">
                {!!Form::label('group_id', trans('validation.attributes.group_id').':')!!}
                {!!Form::select('group_id', $device_groups, $group_id, ['class' => 'form-control', 'data-live-search' => 'true'])!!}
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!!Form::label('vin', trans('validation.attributes.vin').':')!!}
                        {!!Form::text('vin', $item->vin, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('device_model', trans('validation.attributes.device_model').':')!!}
                        {!!Form::text('device_model', $item->device_model, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('object_owner', trans('validation.attributes.object_owner').':')!!}
                        {!!Form::text('object_owner', $item->object_owner, ['class' => 'form-control'])!!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!!Form::label('plate_number', trans('validation.attributes.plate_number').':')!!}
                        {!!Form::text('plate_number', $item->plate_number, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('registration_number', trans('validation.attributes.registration_number').':')!!}
                        {!!Form::text('registration_number', $item->registration_number, ['class' => 'form-control'])!!}
                    </div>

                </div>
            </div>

            <div class="form-group">
                {!!Form::label('additional_notes', trans('validation.attributes.additional_notes').':')!!}
                {!!Form::text('additional_notes', $item->additional_notes, ['class' => 'form-control'])!!}
            </div>
            <div class="form-group">
                {!!Form::label('comment', trans('validation.attributes.comment').':')!!}
                {!!Form::text('comment', $item->comment, ['class' => 'form-control'])!!}
            </div>
        </div>

        <div id="device-form-sensors" class="tab-pane">
            @include('Frontend.Devices.partials.sensors')
            @includeWhen(isAdmin(), 'Frontend.Devices.partials.sensor_group')
        </div>

        <div id="device-form-tail" class="tab-pane">
            @include('Frontend.Devices.partials.tail')
        </div>

        @if(expensesTypesExist())
            <div id="device-form-expenses" class="tab-pane"></div>
        @endif
    </div>
    {!!Form::close()!!}
    <script>
        $(document).ready(function () {
            $('select[name="device_icons_type"]').trigger('change');
        });

        tables.set_config('device-form-sensors', {
            url: '{!!route('sensors.index', $item->id)!!}'
        });

        function sensors_create_modal_callback() {
            tables.get('device-form-sensors');
        }

        function sensors_edit_modal_callback() {
            tables.get('device-form-sensors');
        }

        function sensors_destroy_modal_callback() {
            tables.get('device-form-sensors');
        }
    </script>
@stop

@section('buttons')
    <button type="button" class="btn btn-action update">{!!trans('global.save')!!}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{!!trans('global.cancel')!!}</button>
    @if (Auth::User()->perm('devices', 'remove'))
        <a href="javascript:" data-modal="objects_delete" class="btn btn-danger"
           data-url="{{ route("devices.do_destroy", ['id' => $item->id]) }}">
            {{ trans('global.delete') }}
        </a>
    @endif
@stop