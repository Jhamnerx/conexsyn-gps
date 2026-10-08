@extends('Frontend.Layouts.modal')

@section('title')
    {{ trans('front.import') }}
@stop

@section('body')
    <div class="alert alert-info small">
        <a href="{{ asset('examples/import_sensor_calibrations.csv') }}">example.csv</a>
    </div>

    {!! Form::open(['route' => 'sensor_calibrations.import', 'method' => 'POST']) !!}

    <div class="form-group">
        {!! Form::label('file', trans('validation.attributes.file').'*:') !!}
        {!! Form::file('file', ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        <div class="checkbox">
            {!! Form::checkbox('append', 1, 0) !!}
            {!! Form::label('append', trans('front.append')) !!}
        </div>
    </div>

    {!! Form::close() !!}
@stop

@section('buttons')
    <button type="button" class="btn btn-action" data-submit="modal">{{ trans('front.import') }}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@stop