@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-md')

@section('title')
    {{ $title }}
@stop

@section('body')
    {!! $content !!}
@stop

@section('buttons')

@stop