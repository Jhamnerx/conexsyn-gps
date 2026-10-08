@extends('Frontend.Layouts.modal')

@section('title')
    {!!trans('global.edit')!!}
@stop

@section('body')
    {!!Form::open(['route' => 'user_drivers.update', 'method' => 'PUT'])!!}
    {!!Form::hidden('id', $item->id)!!}
    <div class="form-group">
        {!!Form::label('name', trans('validation.attributes.name').'*:')!!}
        {!!Form::text('name', $item->name, ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!! Form::label('devices', trans('front.devices').':') !!}
        {!! Form::select('devices[]', [], null, [
            'class' => 'form-control',
            'multiple' => 'multiple',
            'data-live-search' => 'true',
            'data-ajax' => route('user_drivers.devices', $item->id)
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
                'data-ajax' => route('devices.index', ['request_key' => 'device_id'] + ($item->device_id ? ['selected_device_id' => ["id;true;$item->device_id"]] : []))
            ]) !!}
        </div>
    </div>
    <div class="form-group">
        {!!Form::label('rfid', trans('validation.attributes.rfid').':')!!}
        {!!Form::text('rfid', $item->rfid, ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('phone', trans('validation.attributes.phone').':')!!}
        {!!Form::text('phone', $item->phone, ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('email', trans('validation.attributes.email').':')!!}
        {!!Form::text('email', $item->email, ['class' => 'form-control'])!!}
    </div>
    <div class="form-group">
        {!!Form::label('description', trans('validation.attributes.description').':')!!}
        {!!Form::textarea('description', $item->description, ['class' => 'form-control', 'rows' => 2])!!}
    </div>
    {!!Form::close()!!}
@stop