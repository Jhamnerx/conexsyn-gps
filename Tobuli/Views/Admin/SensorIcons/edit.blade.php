@extends('admin::Layouts.modal')

@section('title')
    {{ trans('global.edit') }} (#{{ $item->id }})
@stop

@section('body')
    {!! Form::open(['route' => ['admin.sensor_icons.update', $item->id], 'method' => 'PUT']) !!}
    {!! Form::hidden('id') !!}

    <div class="form-group">
        <img src="{{ asset($item->path) }}" width="{{ $item->width ?? 100 }}px">
    </div>

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