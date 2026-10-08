@extends('Frontend.Layouts.frontend')

@section('content')
    @if ( Appearance::getSetting('welcome_text') )
    <h1 class="sign-in-text text-center">
        {!! Appearance::getSetting('welcome_text') !!}
    </h1>
    @endif

    <div class="panel">
        <div class="panel-background"></div>
        <div class="panel-body">

            @if ( Appearance::assetFileExists('logo-main') )
            <a href="{{ route('home') }}">
                <img class="img-responsive center-block" src="{{ Appearance::getAssetFileUrl('logo-main') }}" alt="Logo">
            </a>
            @endif

            <hr>

            @if (Session::has('success'))
                <div class="alert alert-success alert-dismissible">
                    {!! Session::get('success') !!}
                </div>
            @endif

            @if (Session::has('message'))
                <div class="alert alert-danger alert-dismissible">
                    {!! Session::get('message') !!}
                </div>
            @endif

            @includeWhen(count($internalAuths), 'front::Login.partials.internal', $internalAuths)

            @if(count($externalAuths))
                <div class="row justify-content-center">
                    <h3 class="text-center">{{ trans('front.external_login') }}</h3>

                    @foreach($externalAuths as $auth)
                        <div class="col-md-4">
                            <div class="form-group">
                                @include("front::Login.partials.{$auth->getKey()}")
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ( Appearance::getSetting('google_play_link') || Appearance::getSetting('apple_store_link') )
        <div class="app-links">
            @if ( Appearance::getSetting('google_play_link') )
                <div class="col-xs-6">
                    <a href="{{ Appearance::getSetting('google_play_link') }}" target="_blank"><img src="{{ asset('assets/images/google-play.png') }}" class="img-responsive" /></a>
                </div>
            @endif

            @if ( Appearance::getSetting('apple_store_link') )
                <div class="col-xs-6">
                    <a href="{{ Appearance::getSetting('apple_store_link') }}" target="_blank"><img src="{{ asset('assets/images/apple-store.png') }}" class="img-responsive" /></a>
                </div>
            @endif
            <div class="clearfix"></div>
        </div>
    @endif

    @if ( Appearance::getSetting('bottom_text') )
        <p class="sign-in-text">{!! Appearance::getSetting('bottom_text') !!}</p>
    @endif
@stop