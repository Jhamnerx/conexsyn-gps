@extends('Frontend.Layouts.modal')

@section('modal_class', 'modal-sm')

@section('title')
    {{ trans('front.login_as') }}
@stop

@section('body')
    {{ strtr(trans('front.do_login_as'), [':email' => $item->email]) }}

    {!! Form::open(['route' => ['admin.clients.login_as_agree', $item->id], 'method' => 'POST']) !!}
    {!! Form::close() !!}
@stop

@section('buttons')
    <button type="button" class="btn btn-action" onClick="$('#clients_login_as form').submit();">{{ trans('global.yes') }}</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.no') }}</button>
@stop