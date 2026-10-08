@extends('Frontend.Layouts.modal')

@section('title')
    {{ trans('global.edit') }}
@stop

@section('body')
    {!! Form::open(['route' => 'my_account.update', 'method' => 'PUT', 'class' => 'form']) !!}
        {!! Form::hidden('id', $item->id) !!}

        @if (settings('password.change_required_current'))
        <div class="form-group">
            {!! Form::label('current_password', trans('validation.attributes.current_password').'*:') !!}
            {!! Form::password('current_password', ['class' => 'form-control']) !!}
        </div>
        @endif

        <div class="form-group">
            {!! Form::label('password', trans('validation.attributes.password').'*:') !!}
            {!! Form::password('password', ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('password_confirmation', trans('validation.attributes.password_confirmation').'*:') !!}
            {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
        </div>
    {!! Form::close() !!}
@stop