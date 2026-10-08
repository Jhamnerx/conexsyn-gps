<div class="form-group">
    <div class="form-group">
        <div class="checkbox">
            {!! Form::hidden('default_login_methods', 0, ['id' => 'default_login_methods_hidden']) !!}
            {!! Form::checkbox('default_login_methods', 1, $defaultLoginMethod, ['id' => 'default_login_methods']) !!}
            {!! Form::label('default_login_methods', trans('validation.attributes.default_login_methods')) !!}
        </div>
    </div>

    <div class="form-group" id="login_methods">
        <div class="form-group">
            @foreach($loginMethods as $method => $enabled)
                <div class="checkbox">
                    {!! Form::hidden("login_methods[$method]", 0) !!}
                    {!! Form::checkbox("login_methods[$method]", 1, $loginMethodsChoices[$method] ?? $enabled) !!}
                    {!! Form::label("login_methods[$method]", trans("validation.login_methods.$method") ) !!}
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    function loginMethodsHide(hide) {
        if (hide) {
            $("#login_methods").hide();
        } else {
            $("#login_methods").show();
        }
    }

    $(document).ready(function() {
        let defaultLoginMethods = $("#default_login_methods");

        defaultLoginMethods.click(function() {
            loginMethodsHide($(this).is(":checked"));
        });

        loginMethodsHide(defaultLoginMethods.is(":checked"));
    });
</script>