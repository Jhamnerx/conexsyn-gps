@extends('Frontend.Layouts.modal')

@section('title')
    {!!trans('front.add_driver')!!}
@stop

@section('body')
    {!!Form::open(['route' => 'user_drivers.store', 'method' => 'POST'])!!}
        {!!Form::hidden('id')!!}

        <div class="form-group">
            {!!Form::label('name', trans('validation.attributes.name').'*:')!!}
            {!!Form::text('name', null, ['class' => 'form-control'])!!}
        </div>
        <div class="form-group">
            {!!Form::label('devices', trans('front.devices').':')!!}
            {!! Form::select('devices[]', [], null, [
                'class' => 'form-control',
                'multiple' => 'multiple',
                'data-live-search' => 'true',
                'data-ajax' => route('user_drivers.devices')
            ]) !!}
        </div>
        <div class="form-group">
            {!! Form::label('device_id', trans('front.set_as_current').':') !!}
            <div class="input-group">
                <div class="checkbox input-group-btn">
                    {!! Form::hidden('current', 0) !!}
                    {!! Form::checkbox('current', 1, 0, ['data-disabler' => '#current_device_id;hide-disable']) !!}
                    {!! Form::label(null) !!}
                </div>
                {!! Form::select('device_id', [], null, [
                    'id' => 'current_device_id',
                    'class' => 'form-control',
                    'data-live-search' => 'true',
                    'data-ajax' => route('devices.index', ['request_key' => 'device_id'])
                ]) !!}
            </div>
        </div>
        <div class="form-group">
            {!!Form::label('rfid', trans('validation.attributes.rfid').':')!!}
            {!!Form::text('rfid', null, ['class' => 'form-control'])!!}
        </div>
        <div class="form-group">
            {!!Form::label('phone', trans('validation.attributes.phone').':')!!}
            {!!Form::text('phone', null, ['class' => 'form-control'])!!}
        </div>
        <div class="form-group">
            {!!Form::label('email', trans('validation.attributes.email').':')!!}
            {!!Form::text('email', null, ['class' => 'form-control'])!!}
        </div>
        <div class="form-group">
            {!!Form::label('description', trans('validation.attributes.description').':')!!}
            {!!Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2])!!}
        </div>
    {!!Form::close()!!}
@stop