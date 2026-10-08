@extends('front::Layouts.modal')

@section('title', trans('front.add_new'))

@section('body')
    {!! Form::open(['route' => 'task_sets.store', 'method' => 'POST']) !!}

    <div class="form-group">
        {!! Form::label('title', trans('validation.attributes.title') . ':') !!}
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
    </div>

    {!! Form::close() !!}
@stop