@extends('Frontend.Layouts.modal')

@section('title', trans('global.add'))

@section('body')
    {!! Form::open(['route' => 'admin.command_templates.store', 'method' => 'POST']) !!}

    @include('Admin.CommandTemplates.form')

    {!! Form::close() !!}
@stop