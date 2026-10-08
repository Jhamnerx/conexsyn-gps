@extends('Frontend.Google2fa.layout')

@section('form')
    {!! Form::open(['route' => 'google_2fa_setup.store_confirm', 'method' => 'POST']) !!}
        <div class="text-center">
            <p>{{ trans('auth.google_2fa_scan_for_psw') }}</p>

            @inject('google2fa', 'PragmaRX\Google2FALaravel\Support\Authenticator')

            {!! QrCode::size(200)->generate($google2fa->getQRCodeUrl(settings('main_settings.server_name'), Auth::user()->email, $qrSecret)) !!}
        </div>

        <div class="form-group">
            {!! Form::text('one_time_password', null, ['class' => 'form-control', 'placeholder' => trans('auth.google_2fa_enter_psw'), 'autocomplete' => 'off']) !!}
        </div>

        <button class="btn btn-lg btn-info btn-block" type="Submit">{!! trans('global.confirm') !!}</button>
    {!! Form::close() !!}
@stop