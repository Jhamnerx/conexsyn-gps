@extends('Frontend.Google2fa.layout')

@section('form')
    {!! Form::open(['route' => 'google_2fa', 'method' => 'POST']) !!}
        <div class="form-group">
            {!! Form::text('one_time_password', null, ['class' => 'form-control', 'placeholder' => trans('auth.google_2fa_enter_psw'), 'autocomplete' => 'off']) !!}
        </div>

        <button class="btn btn-lg btn-info btn-block" type="Submit">{!! trans('global.confirm') !!}</button>
    {!! Form::close() !!}
@stop