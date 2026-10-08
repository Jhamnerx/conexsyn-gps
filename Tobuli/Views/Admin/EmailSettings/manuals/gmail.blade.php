@extends('Admin.Layouts.modal')

@section('title')
    {{ trans('global.info') }}
@stop

@section('body')
    <p>
        Go to <a href="https://console.cloud.google.com" target="_blank">https://console.cloud.google.com</a>.
    </p>

    <br>

    <p>
        Create new or Select existing project.
    </p>

    <br>

    <p>
        At the same page click
        <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">APIs & Services</a>.
    </p>

    <br>

    <p>
        In the sidebar menu click
        <a href="https://console.cloud.google.com/apis/library" target="_blank">Library</a>
        and search for "gmail api".
    </p>
    <p>
        In the results click on "Gmail API" and then "Enable".
    </p>

    <br>

    <p>
        Now you should be returned to
        <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">APIs & Services</a>.
    </p>
    <p>
        In the sidebar menu click "OAuth consent screen" and click button "Get started"
        (if the button is missing and you can see metrics then you already completed this step).
    </p>
    <p>
        Fill out the form.
    </p>

    <br>

    <p>
        Now you should be at <a href="https://console.cloud.google.com/auth/overview" target="_blank">Overview</a>.
    </p>
    <p>
        In the sidebar menu click
        <a href="https://console.cloud.google.com/auth/clients" target="_blank">Clients</a>
        and click button "Create client".
    </p>
    <p>
        In the "Application type" choose "Web application".
    </p>
    <p>
        Fill out the form. There should be "Authorized redirect URIs" section where you have to add
        <b>{!! url()->route('gmail.oauth2.callback') !!}</b> (use HTTPS URI!).
    </p>

    <br>

    <p>
        A pop-up window should come up.
        There are <b>Client ID</b> and <b>Client secret</b> which are needed for your server.
    </p>

    <br>

    <p>
        In the sidebar menu click
        <a href="https://console.cloud.google.com/auth/audience" target="_blank">Audience</a>
        and click button "Publish app".
    </p>
@stop

@section('footer')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.close') }}</button>
@stop