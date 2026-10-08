@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon sensors"></i> {{ trans('front.add_sensor') }}
@stop

@section('body')
    {!! Form::open(['route' => $route, 'method' => 'POST']) !!}
        {!! Form::hidden('id', $id ?? null) !!}
        {!! Form::hidden('sensor_group_id', $sensor_group_id ?? null) !!}
        {!! Form::hidden('device_id', $device_id) !!}

        @include('Frontend.Sensors.form')
    {!! Form::close() !!}
@stop