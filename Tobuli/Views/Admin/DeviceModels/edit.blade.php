@extends('front::Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    {!! Form::open(['route' => ['admin.device_models.update', $item->id], 'method' => 'PUT']) !!}

    <div class="row">
        <div class="col-sm-12">
            <div class="checkbox-inline">
                {!! Form::hidden('active', 0) !!}
                {!! Form::checkbox('active', 1, $item->active) !!}
                {!! Form::label(null, trans('validation.attributes.active')) !!}
            </div>
        </div>
    </div>

    <br>

    <div class="form-group">
        {!! Form::label('title', trans('validation.attributes.title') . ':') !!}
        {!! Form::text('title', $item->title, ['class' => 'form-control']) !!}
    </div>

    {!! Form::close() !!}
@stop