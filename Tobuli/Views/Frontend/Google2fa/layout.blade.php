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

            @yield('form')
        </div>
    </div>

    @if ( Appearance::getSetting('bottom_text') )
        <p class="sign-in-text">{!! Appearance::getSetting('bottom_text') !!}</p>
    @endif
@stop