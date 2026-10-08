@extends('front::Layouts.modal')

@section('title', trans('front.send_test_notification'))

@section('body')
    {!! Form::open(['route' => ['admin.fcm_test.store'], 'method' => 'POST']) !!}

    <div class="form-group">
        {!! Form::label('user_id', trans('validation.attributes.user').':') !!}
        {!! Form::select('user_id', [], null, [
            'class' => 'form-control',
            'data-live-search' => 'true',
            'data-ajax' => route('devices.users.index')
            ]) !!}
    </div>

    {!! Form::close() !!}

    <div id="results"></div>
@stop