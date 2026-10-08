@extends('Frontend.Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    {!! Form::open(['route' => 'admin.command_templates.update', 'method' => 'PUT']) !!}
    {!! Form::hidden('id', $item->id) !!}

    @include('Admin.CommandTemplates.form')

    {!! Form::close() !!}
@stop