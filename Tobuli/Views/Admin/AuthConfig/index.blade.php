@extends('Admin.Layouts.default')

@section('content')
    <div class="row">
        <div class="col-sm-6">
            @if (Session::has('errors'))
                <div class="alert alert-danger">
                    <ul>
                        @foreach (Session::get('errors')->all() as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {!! Form::open(['route' => 'admin.auth_config.store', 'method' => 'POST', 'class' => 'form form-horizontal']) !!}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="panel-title">{{ trans('front.auth_config') }}</div>
                    </div>

                    <div class="panel-body">
                        <label>{{ trans('front.login_methods') }}:</label>

                        @foreach($generalSettings['login_methods'] as $method => $enabled)
                            <div class="form-group">
                                <div class="col-xs-12 col-sm-8">
                                    <div class="checkbox">
                                        {!! Form::hidden("login_methods[$method]", 0) !!}
                                        {!! Form::checkbox("login_methods[$method]", 1, $enabled) !!}
                                        {!! Form::label("login_methods[$method]", trans("validation.login_methods.$method") ) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <hr>

                        <div class="form-group">
                            <div class="col-xs-12 col-sm-8">
                                <div class="checkbox">
                                    {!! Form::hidden('user_individual_config', 0) !!}
                                    {!! Form::checkbox('user_individual_config', 1, $generalSettings['user_individual_config']) !!}
                                    {!! Form::label('user_individual_config', trans('front.user_individual_config') ) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-footer">
                        <button type="submit" class="btn btn-action">
                            {{ trans('global.save') }}
                        </button>
                    </div>
                </div>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="row">
        @foreach($auths as $auth)
            @if($auth instanceof \Tobuli\Services\Auth\ConfigurableInterface)
                <div class="col-sm-6">
                    {!! $auth->renderConfigForm() !!}
                </div>
            @endif
        @endforeach
    </div>
@stop

@section('javascript')
    <script>
        let endpoint = '{{ route('admin.auth_config.check', 'authKey') }}';

        $('.config-test').click(function (e) {
            let authKey = e.target.id.replace('_test', '');
            let data = $('#' + authKey + ' input').map(function() {
                if ($(this).attr('id'))
                return $(this).val();
            }).get()

            $.ajax({
                method: 'POST',
                data: $('#' + authKey).serializeArray().reduce(function(obj, item) {
                    if (item.name.startsWith('__') === false) {
                        obj[item.name] = item.value;
                    }

                    return obj;
                }, {}),
                url: endpoint.replace('authKey', authKey),
                beforeSend: function () {
                    loader.add('div.content');
                },
                success: function (response) {
                    if (response.errors.length) {
                        toastr.error('Config is invalid. ' + response.errors);
                    } else {
                        toastr.success('Config is valid.');
                    }
                },
                complete: function () {
                    loader.remove('div.content');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                }
            });
        });
    </script>
@stop