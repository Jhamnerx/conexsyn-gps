@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon sensors"></i> {{ trans('global.edit') }}
@stop

@section('body')
    {!! Form::open(['route' => $route, 'method' => 'PUT']) !!}
        {!! Form::hidden('id', $item->id) !!}
        {!! Form::hidden('sensor_group_id', $item->group_id) !!}
        {!! Form::hidden('device_id', $item->device_id) !!}
        @include('Frontend.Sensors.form')
    {!! Form::close() !!}
@stop