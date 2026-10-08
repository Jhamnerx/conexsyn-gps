@extends('Frontend.Google2fa.layout')

@section('form')
    <div class="justify-content-center">
        <p>{{ trans('auth.google_2fa_prompt') }}</p>
    </div>

    <div class="buttons">
        <div class="form-group col-md-6">
            {!! Form::open(['route' => 'google_2fa_setup.store', 'method' => 'POST']) !!}
            {!! Form::hidden('enable', 1) !!}

            <button class="btn btn-lg btn-primary btn-block" type="Submit">{!! trans('global.yes') !!}</button>
            {!! Form::close() !!}
        </div>

        <div class="form-group col-md-6">
            {!! Form::open(['route' => 'google_2fa_setup.store', 'method' => 'POST']) !!}
            {!! Form::hidden('enable', 0) !!}

            <button class="btn btn-lg btn-default btn-block" type="Submit">{!! trans('global.no') !!}</button>
            {!! Form::close() !!}
        </div>
    </div>
@stop