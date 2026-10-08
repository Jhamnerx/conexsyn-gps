@if (Session::has('errors-' . $authKey))
    <div class="alert alert-danger">
        <ul>
            @foreach (Session::get('errors-' . $authKey)->all() as $error)
                <li>{!! $error !!}</li>
            @endforeach
        </ul>
    </div>
@endif

{!! Form::open([
    'route' => ['admin.auth_config.store.auth', $authKey],
    'method' => 'POST',
    'class' => 'form form-horizontal',
    'id' => $authKey,
]) !!}

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">{{ trans("validation.login_methods.$authKey") }}</div>
    </div>

    <div class="panel-body">
        @yield('auth_config_input')
    </div>

    <div class="panel-footer">
        <button type="submit" class="btn btn-action">
            {{ trans('global.save') }}
        </button>

        <button type="button" class="btn btn-default pull-right config-test" id="{{ $authKey . '_test' }}">
            {{ trans('validation.attributes.test_config') }}
        </button>
    </div>
</div>

{!! Form::close() !!}
