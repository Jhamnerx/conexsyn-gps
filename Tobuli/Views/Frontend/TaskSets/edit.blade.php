@extends('front::Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    {!! Form::open(['route' => ['task_sets.update', $item->id], 'method' => 'PUT']) !!}

    <div class="form-group">
        {!! Form::label('title', trans('validation.attributes.title') . ':') !!}
        {!! Form::text('title', $item->title, ['class' => 'form-control']) !!}
    </div>

    {!! Form::close() !!}
@stop