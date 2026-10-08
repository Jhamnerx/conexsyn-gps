@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div id="asaas">
        <div class="form-group">
            {!! Form::label('environment', trans('validation.attributes.environment'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::select('environment', config('payments.asaas.environments'), settings('payments.asaas.environment'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('api_key', 'API key', ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}

            <div class="col-xs-12 col-sm-8">
                {!! Form::text('api_key', settings('payments.asaas.api_key'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('access_token', 'Access token (webhook)', ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}

            <div class="col-xs-12 col-sm-8">
                {!! Form::text('access_token', settings('payments.asaas.access_token'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('currency', 'Currency', ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}

            <div class="col-xs-12 col-sm-8">
                {!! Form::text('currency', 'R$', ['class' => 'form-control', 'disabled' => 'disabled']) !!}
            </div>
        </div>
    </div>
@overwrite