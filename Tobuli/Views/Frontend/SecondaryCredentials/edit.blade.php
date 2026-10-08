@extends('front::Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    {!! Form::open(['route' => ['secondary_credentials.update', $item->id], 'method' => 'PUT']) !!}

    <div class="row">
        <div class="col-sm-12">
            <div class="checkbox-inline">
                {!! Form::checkbox('readonly', 1, $item->readonly) !!}
                {!! Form::label(null, trans('validation.attributes.readonly')) !!}
            </div>
        </div>
    </div>

    <br>

    <div class="form-group">
        {!! Form::label('email', trans('validation.attributes.email') . ':') !!}
        {!! Form::text('email', $item->email, ['class' => 'form-control']) !!}
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="checkbox-inline">
                {!! Form::checkbox('password_generate', 1, false) !!}
                {!! Form::label(null, trans('admin.autogenerate')) !!}
            </div>
            <div class="checkbox-inline">
                {!! Form::checkbox('password_generate', 0, false, ['data-disabler' => '#password-fields;hide-disable']) !!}
                {!! Form::label(null, trans('admin.manual')) !!}
            </div>
        </div>
    </div>

    <div class="row" id="password-fields">
        <br>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('password', trans('validation.attributes.password').':') !!}
                {!! Form::password('password', ['class' => 'form-control']) !!}
                {!! error_for('password', $errors) !!}
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('password_confirmation', trans('validation.attributes.password_confirmation').':') !!}
                {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
                {!! error_for('password_confirmation', $errors) !!}
            </div>
        </div>
    </div>

    {!! Form::close() !!}
@stop