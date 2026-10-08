{!! Form::open(array('route' => 'authentication.store', 'class' => 'form')) !!}

@php /** @var \Tobuli\Services\Auth\InternalInterface[] $internalAuths */@endphp

<div class="form-group">
    {!! Form::text(
        'identifier',
        null,
        [
            'class' => 'form-control',
            'placeholder' => implode(' / ', array_map(fn ($auth) => $auth->getInputTitle(), $internalAuths)),
            'id' => 'sign-in-form-email',
        ]
    ) !!}
</div>

<div class="form-group">
    <div class="password-toggle-wrap">
        {!! Form::password('password', ['class' => 'form-control', 'placeholder' => trans('validation.attributes.password'), 'id' => 'sign-in-form-password']) !!}
        <button type="button" class="password-toggle" tabindex="-1"
                aria-label="{{ trans('validation.attributes.password') }}"
                onclick="var p=document.getElementById('sign-in-form-password');var s=p.type==='password';p.type=s?'text':'password';this.classList.toggle('showing',s);p.focus();">
            <svg class="eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
        </button>
    </div>
</div>

@include('Frontend.Captcha.form')

@if (config('session.remember_me'))
    <div class="form-group">
        <div class="checkbox">
            {!! Form::checkbox('remember_me', 1, ['id' => 'sign-in-form-remember']) !!}
            <label>{!! trans('validation.attributes.remember_me') !!}</label>
        </div>
    </div>
@endif

<button class="btn btn-lg btn-primary btn-block" name="Submit" value="Login" type="Submit">{!! trans('front.sign_in') !!}</button>

<hr>

<div class="form-group">
    <div class="row">
        <div class="col-sm-12">
            <a href="{!! route('password_reminder.create') !!}"
               class="btn btn-block btn-lg btn-default">{!! trans('front.cant_sign_in') !!}</a>
        </div>
        <div class="col-sm-12">
            @if (settings('main_settings.allow_users_registration'))
                <a href="{!! route('registration.create') !!}"
                   class="btn btn-block btn-lg btn-default">{!! trans('front.not_a_member') !!}</a>
            @endif
        </div>
    </div>
</div>

{!! Form::close() !!}
