@extends('admin::Layouts.modal')

@section('title')
    {{ trans('global.add') }}
@stop

@section('body')
    {!! Form::open(['route' => 'admin.sensor_icons.store', 'method' => 'POST', 'id' => 'create_icon_form']) !!}

    <div class="form-group">
        {!! Form::label('file', trans('validation.attributes.file').'*:') !!}
        {!! Form::file('file', ['class' => 'form-control']) !!}
    </div>

    {!! Form::close() !!}
@stop

@section('footer')
    <button type="button" class="btn btn-action update_with_files">{{ trans('global.save') }}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.cancel') }}</button>
@stop