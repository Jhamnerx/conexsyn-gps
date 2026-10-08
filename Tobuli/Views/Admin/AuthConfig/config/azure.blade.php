@extends('admin::AuthConfig.config.layout')

@section('auth_config_input')
    <div class="form-group">
        <div class="col-xs-12 col-sm-8">
            {!! Form::label('client_id', trans('validation.attributes.client_id') ) !!}
            {!! Form::text('client_id', $config['client']['id'] ?? null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-8">
            {!! Form::label('client_secret', trans('validation.attributes.client_secret') ) !!}
            {!! Form::text('client_secret', $config['client']['secret'] ?? null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-8">
            {!! Form::label('tenant_id', trans('validation.attributes.tenant_id') ) !!}
            {!! Form::text('tenant_id', $config['tenant_id'] ?? null, ['class' => 'form-control']) !!}
        </div>
    </div>
@stop