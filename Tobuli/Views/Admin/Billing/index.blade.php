@extends('Admin.Layouts.default')

@section('content')

    <div class="row">
        <div class="col-sm-6">

            @if (Session::has('user_defaults_errors'))
                <div class="alert alert-danger">
                    <ul>
                        @foreach (Session::get('user_defaults_errors')->all() as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel panel-default">

                <div class="panel-heading">
                    <div class="panel-title">{{ trans('front.registration') }}</div>
                </div>

                <div class="panel-body">
                    {!! Form::open(array('route' => 'admin.main_server_settings.new_user_defaults_save', 'method' => 'POST', 'class' => 'form form-horizontal', 'id' => 'new-user-defaults-form')) !!}

                    <div class="form-group">
                        {!! Form::label('email_verification', trans('validation.attributes.email_verification'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                        <div class="col-xs-12 col-sm-8">
                            {!! Form::select('email_verification', ['0' => trans('global.no'), '1' => trans('global.yes')], $settings['email_verification'], ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('phone_verification', trans('validation.attributes.phone_verification'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                        <div class="col-xs-12 col-sm-8">
                            {!! Form::select('phone_verification', ['0' => trans('global.no'), '1' => trans('global.yes')], $settings['phone_verification'], ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('allow_users_registration', trans('validation.attributes.allow_users_registration'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                        <div class="col-xs-12 col-sm-8">
                            {!! Form::select('allow_users_registration', ['0' => trans('global.no'), '1' => trans('global.yes')], $settings['allow_users_registration'], ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group" data-disablable="#allow_users_registration;hide-disable;1">
                        <div class="col-xs-12 col-sm-4">{{ trans('admin.registration_fields') }}</div>
                        <div class="col-xs-12 col-sm-8">
                            <div class="checkbox">
                                {!! Form::checkbox('custom_registration_fields[enabled]', 1, $settings['custom_registration_fields']['enabled'], ['id' => 'custom_registration_fields']) !!}
                                {!! Form::label('custom_registration_fields[enabled]', trans('front.enabled') ) !!}
                            </div>
                        </div>

                        <div class="col-xs-12">

                            <div data-disablable="#custom_registration_fields;hide">
                                <div>
                                    <table class="table">
                                        <thead>
                                        <th style="text-align: left">{{ trans('admin.field') }}</th>
                                        <th style="text-align: center">{{ trans('validation.attributes.present') }}</th>
                                        <th style="text-align: center">{{ trans('validation.attributes.required') }}</th>
                                        </thead>

                                        <tbody>
                                        @foreach($customRegistrationFields as $key => $conf)
                                            <tr>
                                                <td>{{ trans('validation.attributes.' . $conf['attribute']) }}</td>
                                                <td style="text-align: center">
                                                    <div class="checkbox">
                                                        {!! Form::checkbox("{$key}[present]", 1, $conf['present'], ['class' => 'perm_checkbox perm_view']) !!}
                                                        {!! Form::label(null) !!}
                                                    </div>
                                                </td>
                                                <td style="text-align: center">
                                                    <div class="checkbox">
                                                        {!! Form::checkbox("{$key}[required]", 1, $conf['required'], ['class' => 'perm_checkbox perm_edit']) !!}
                                                        {!! Form::label(null) !!}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-xs-12 col-sm-4"></div>
                        <div class="col-xs-12 col-sm-8">
                            <div class="checkbox">
                                {!! Form::checkbox('enable_plans', 1, settings('main_settings.enable_plans'), ['id' => 'enable_plans']) !!}
                                {!! Form::label('enable_plans', trans('validation.attributes.enable_plans') ) !!}
                            </div>
                        </div>
                    </div>

                    <div data-disablable="#enable_plans;show-enable">
                        <div class="form-group">
                            {!! Form::label(null, trans('validation.attributes.devices_limit'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                            <div class="col-xs-12 col-sm-8">
                                <div class="input-group">
                                    <div class="checkbox input-group-btn">
                                        {!! Form::checkbox('enable_devices_limit', 1, !is_null(settings('main_settings.devices_limit'))) !!}
                                        {!! Form::label(null) !!}
                                    </div>
                                    {!! Form::text('devices_limit', settings('main_settings.devices_limit'), ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label(null, trans('validation.attributes.subscription_expiration_after_days'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                            <div class="col-xs-12 col-sm-8">
                                <div class="input-group">
                                    <div class="checkbox input-group-btn">
                                        {!! Form::checkbox('enable_subscription_expiration_after_days', 1, !is_null(settings('main_settings.subscription_expiration_after_days'))) !!}
                                        {!! Form::label(null) !!}
                                    </div>
                                    {!! Form::text('subscription_expiration_after_days', settings('main_settings.subscription_expiration_after_days'), ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div data-disablable="#enable_plans;hide-disable">
                        <div class="form-group">
                            <div class="col-xs-12 col-sm-4"></div>
                            <div class="col-xs-12 col-sm-8">
                                <div class="checkbox">
                                    {!! Form::checkbox('allow_user_change_plan', 1, settings('main_settings.allow_user_change_plan')) !!}
                                    {!! Form::label('allow_user_change_plan', trans('validation.attributes.allow_user_change_plan')) !!}
                                </div>
                            </div>
                        </div>
                        <div class="form-group" id="default_billing_plan">
                            {!! Form::label('default_billing_plan', trans('validation.attributes.default_billing_plan'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                            <div class="col-xs-12 col-sm-8">
                                {!! Form::select('default_billing_plan', $items->pluck('title','id')->all(), settings('main_settings.default_billing_plan'), ['class' => 'form-control']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('default_timezone', trans('validation.attributes.default_timezone'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                        <div class="col-xs-12 col-sm-8">
                            {!! Form::select('default_timezone', $timezones, $settings['default_timezone'], ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.daylight_saving_time'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                        <div class="col-xs-12 col-sm-8">
                            {!! Form::label('default_dst_type', trans('validation.attributes.dst_type').':') !!}
                            {!! Form::select('default_dst_type', $dst_types, $settings['default_dst_type'] ?? null, ['class' => 'form-control']) !!}

                            <div class="row" data-disablable="#default_dst_type;hide-disable;exact">
                                <div class="col-xs-6">
                                    {!! Form::label('default_dst_date_from', trans('validation.attributes.date_from').':') !!}
                                    {!! Form::text('default_dst_date_from', $settings['default_dst_date_from'] ?? null, ['class' => 'form-control']) !!}
                                </div>
                                <div class="col-xs-6">
                                    {!! Form::label('default_dst_date_to', trans('validation.attributes.date_to').':') !!}
                                    {!! Form::text('default_dst_date_to', $settings['default_dst_date_to'] ?? null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div data-disablable="#default_dst_type;hide-disable;other">
                                {!! Form::label('date_from', trans('front.from').':') !!}
                                <div class="row">
                                    <div class="col-xs-4">
                                        {!! Form::select('default_dst_month_from', $months, $settings['default_dst_month_from'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-2">
                                        {!! Form::select('default_dst_week_pos_from', $week_pos, $settings['default_dst_week_pos_from'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-4">
                                        {!! Form::select('default_dst_week_day_from', $weekdays, $settings['default_dst_week_day_from'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-2">
                                        {!! Form::text('default_dst_time_from', $settings['default_dst_time_from'] ?? null, ['class' => 'form-control', 'placeholder' => trans('front.time')]) !!}
                                    </div>
                                </div>

                                {!! Form::label('date_to', trans('front.to').':') !!}
                                <div class="row">
                                    <div class="col-xs-4">
                                        {!! Form::select('default_dst_month_to', $months, $settings['default_dst_month_to'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-2">
                                        {!! Form::select('default_dst_week_pos_to', $week_pos, $settings['default_dst_week_pos_to'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-4">
                                        {!! Form::select('default_dst_week_day_to', $weekdays, $settings['default_dst_week_day_to'] ?? null, ['class' => 'form-control']) !!}
                                    </div>
                                    <div class="col-xs-2">
                                        {!! Form::text('default_dst_time_to', $settings['default_dst_time_to'] ?? null, ['class' => 'form-control', 'placeholder' => trans('front.time')]) !!}
                                    </div>
                                </div>
                            </div>
                            <div data-disablable="#default_dst_type;hide-disable;automatic">
                                {!! Form::label('default_dst_country_id', trans('front.country').':') !!}
                                {!! Form::select('default_dst_country_id', $dst_countries, $settings['default_dst_country_id'] ?? null, ['class' => 'form-control', 'data-live-search' => 'true']) !!}
                            </div>
                        </div>
                    </div>

                    <div data-disablable="#enable_plans;show-enable">
                        <div>
                            <h3>{{ trans('validation.attributes.permissions') }}</h3>
                            <table class="table">
                                <thead>
                                <th style="text-align: left">{{ trans('front.permission') }}</th>
                                <th style="text-align: center">{{ trans('front.view') }}</th>
                                <th style="text-align: center">{{ trans('global.edit') }}</th>
                                <th style="text-align: center">{{ trans('global.delete') }}</th>
                                </thead>
                                <tbody>
                                @foreach($grouped_permissions as $group => $permissions)
                                    @if($group !== 'main')
                                        <tr class="table">
                                            <th colspan="4">
                                                <a href="javascript:" data-toggle="collapse" data-target="{{ ".group-$group" }}">
                                                    {{ ucfirst($group) }}
                                                    <i class="fa fa-angle-down"></i>
                                                </a>
                                            </th>
                                        </tr>
                                    @endif
                                    @foreach($permissions as $permission => $modes)
                                        <tr class="{{ "group-$group" }} {{ ($group !== 'main') ? 'collapse' : '' }}">
                                            <td>
                                                @if($group !== 'main')
                                                    {{ trans('validation.attributes.' . explode('.', $permission)[1]) }}
                                                @else
                                                    {{ trans('front.' . $permission) }}
                                                @endif
                                            </td>
                                            <td style="text-align: center">
                                                <div class="checkbox">
                                                    @if ($modes['view'])
                                                        {!! Form::checkbox("perms[$permission][view]", 1, getMainPermission($permission, 'view'), ['class' => 'perm_checkbox perm_view']) !!}
                                                    @else
                                                        {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                    @endif
                                                    {!! Form::label(null, null) !!}
                                                </div>
                                            </td>
                                            <td style="text-align: center">
                                                <div class="checkbox">
                                                    @if ($modes['edit'])
                                                        {!! Form::checkbox("perms[$permission][edit]", 1, getMainPermission($permission, 'edit'), ['class' => 'perm_checkbox perm_edit']) !!}
                                                    @else
                                                        {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                    @endif
                                                    {!! Form::label(null, null) !!}
                                                </div>
                                            </td>
                                            <td style="text-align: center">
                                                <div class="checkbox">
                                                    @if ($modes['remove'])
                                                        {!! Form::checkbox("perms[$permission][remove]", 1, getMainPermission($permission, 'remove'), ['class' => 'perm_checkbox perm_remove']) !!}
                                                    @else
                                                        {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                    @endif
                                                    {!! Form::label(null, null) !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {!! Form::close() !!}
                </div>

                <div class="panel-footer">
                    <button type="submit" class="btn btn-action" onClick="$('#new-user-defaults-form').submit();">{{ trans('global.save') }}</button>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="panel panel-default" id="table_billing_plans">
                <div class="panel-heading">
                    <ul class="nav nav-tabs nav-icons pull-right">
                        <li role="presentation" class="">
                            <a href="javascript:" type="button" data-modal="billing_plans_create" data-url="{{ route("admin.billing.create") }}">
                                <i class="icon add" title="{{ trans('admin.add_new_plan') }}"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="panel-title">{!! trans('front.plans') !!}</div>
                </div>

                <div class="panel-body" data-table>
                    @include('Admin.Billing.table')
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @if (Session::has('errors'))
                <div class="alert alert-danger">
                    <ul>
                        @foreach (Session::get('errors')->all() as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel panel-default" id="setup-form-sms-gateway">
                <div class="panel-heading">
                    <div class="panel-title">{{ trans('front.users_default_sms_gateway') }}</div>
                </div>

                {!! Form::open(array('route' => 'admin.billing.users_default_sms_gateway', 'method' => 'POST', 'id' => 'users-default-sms-gateway')) !!}

                <div class="panel-body">
                    <div class="form-group">
                        <div class="checkbox">
                            {!! Form::checkbox('sms_gateway', 1, $userDefaultSms['sms_gateway']) !!}
                            {!! Form::label('sms_gateway', trans('front.enable_sms_gateway')) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        {!!Form::label('request_method', trans('validation.attributes.request_method').':')!!}
                        {!!Form::select('request_method', $requestMethods, $userDefaultSms['request_method'], ['class' => 'form-control'])!!}
                    </div>
                    <div class="request-method request-method-post">
                        <div class="form-group">
                            {!!Form::label('encoding', trans('validation.attributes.encoding').':')!!}
                            {!!Form::select('encoding', $encodings, $userDefaultSms['encoding'], ['class' => 'form-control'])!!}
                        </div>
                    </div>
                    <div class="request-method request-method-get request-method-post">
                        <div class="form-group">
                            {!!Form::label('authentication', trans('validation.attributes.authentication').':')!!}
                            {!!Form::select('authentication', $authentications, $userDefaultSms['authentication'], ['class' => 'form-control'])!!}
                        </div>
                        <div class="form-group" data-disablable="#authentication;hide-disable;1">
                            {!!Form::label('username', trans('validation.attributes.username').':')!!}
                            {!!Form::text('username', $userDefaultSms['username'], ['class' => 'form-control'])!!}
                        </div>
                        <div class="form-group" data-disablable="#authentication;hide-disable;1">
                            {!!Form::label('password', trans('validation.attributes.password').':')!!}
                            {!!Form::password('password', ['class' => 'form-control'])!!}
                        </div>
                        <div class="form-group">
                            {!!Form::label('custom_headers', trans('validation.attributes.sms_gateway_headers').':')!!}
                            {!!Form::textarea('custom_headers', $userDefaultSms['custom_headers'], ['class' => 'form-control', 'rows' => 2])!!}
                            <small>Colon seperated list ( e.g. Accept: text/plain; Accept-Language: en-US; ) </small>
                        </div>
                        <div class="form-group">
                            {!!Form::label('sms_gateway_url', trans('validation.attributes.sms_gateway_url').':')!!}
                            {!!Form::textarea('sms_gateway_url', $userDefaultSms['sms_gateway_url'], ['class' => 'form-control', 'rows' => 3])!!}
                        </div>
                        <div class="alert alert-info">
                            {!!trans('front.sms_gateway_text')!!}
                        </div>

                        <button type="button" class="btn btn-info btn-xs" data-url="{!!route('sms_gateway.test_sms')!!}" data-modal="send_test_sms">{!!trans('front.send_test_sms')!!}</button>
                    </div>
                    <div class="request-method request-method-app">
                        <div class="form-group">
                            <button type="button" class="btn btn-danger btn-xs" onClick="app.clearQueue();">{!!trans('front.clear_queue')!!}</button>
                            <button type="button" class="btn btn-info btn-xs" data-url="{!!route('sms_gateway.test_sms')!!}" data-modal="send_test_sms">{!!trans('front.send_test_sms')!!}</button>
                        </div>
                        <div class="form-group">
                            <small>{!!trans('front.sms_in_queue')!!}: <span class="sms_queue_count">{!! $smsQueueCount !!}</span></small><br>
                            <small>{!!trans('front.app_last_connection')!!}: {!! Formatter::time()->human(Auth::User()->sms_gateway_app_date) !!}</small><br>
                            <small>{!!trans('front.sms_deletion_after')!!}</small>
                        </div>
                    </div>
                    <div class="request-method request-method-plivo">
                        <div class="form-group">
                            {!! Form::label('auth_id', trans('validation.attributes.auth_id').':') !!}
                            {!! Form::text('auth_id', $userDefaultSms['auth_id'] ?? null, ['class' => 'form-control']) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('auth_token', trans('validation.attributes.auth_token').':') !!}
                            {!! Form::text('auth_token', $userDefaultSms['auth_token'] ?? null, ['class' => 'form-control']) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('senders_phone', trans('validation.attributes.senders_phone').':') !!}
                            {!! Form::text('senders_phone', $userDefaultSms['senders_phone'] ?? null, ['class' => 'form-control']) !!}
                        </div>

                        <button type="button" class="btn btn-info btn-xs" data-url="{{ route('sms_gateway.test_sms') }}" data-modal="send_test_sms">{{ trans('front.send_test_sms') }}</button>
                    </div>
                    <div class="request-method request-method-server">
                        <button type="button" class="btn btn-info btn-xs" data-url="{{ route('sms_gateway.test_sms') }}" data-modal="send_test_sms">{{ trans('front.send_test_sms') }}</button>
                    </div>
                </div>

                {!! Form::close() !!}

                <div class="panel-footer">
                    <button type="submit" class="btn btn-action" onClick="$('#users-default-sms-gateway').submit();">{{ trans('global.save') }}</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
    var $sms_gateway_container = $('#setup-form-sms-gateway');

    $sms_gateway_container.on('change', 'select[name="request_method"]', function () {
        dd('select[name="request_method"]');
        $('.request-method', $sms_gateway_container).hide();
        $('.request-method-' + $(this).val(), $sms_gateway_container).show();
    });

    tables.set_config('table_billing_plans', {
        url:'{{ route("admin.billing.plans") }}',
        delete_url:'{{ route("admin.billing.destroy") }}'
    });

    function billing_plans_edit_modal_callback() {
        tables.get('table_billing_plans');
        updateBillingPlans();
    }

    function billing_plans_create_modal_callback() {
        tables.get('table_billing_plans');
        updateBillingPlans();
    }

    function updateBillingPlans() {
        $.ajax({
            type: 'GET',
            dataType: "html",
            url: '{{ route('admin.billing.billing_plans_form') }}',
            success: function(res){
                $('#default_billing_plan div').html(res);
            }
        });
    }

    $(document).ready(function() {
        $(document).on('change', 'select[name="payment_type"]', function() {
            $("div[class*='payment-']").hide();
            $(".payment-" + $(this).val()).show();
        });
        $('select[name="payment_type"]').trigger('change');

        $(document).on('click', '.multi_delete', function() {
            setTimeout(function() {
                updateBillingPlans();
            }, 2000);
        });

        $('input[name="enable_plans"]').trigger('change');
        $('input[name="custom_registration_fields[enabled]"]').trigger('change');

        checkPerms();

        $(document).ready(function () {
            $('input[name="dst_date_from"]').datetimepicker({
                changeYear: false,
                format: 'mm-dd hh:ii',
                closeOnDateSelect: true
            });
            $('input[name="dst_date_to"]').datetimepicker({
                changeYear: false,
                format: 'mm-dd hh:ii',
                closeOnDateSelect: true
            });
        });

        $('select[name="request_method"]', $sms_gateway_container).trigger('change');
        $('select[name="authentication"]', $sms_gateway_container).trigger('change');
    });

    $(document).on('change', 'input.perm_checkbox', function () {
        checkPerm($(this));
    });

    $('input[name^="default_dst_date_"]').datetimepicker({
        changeYear: false,
        format: 'mm-dd hh:ii',
        closeOnDateSelect: true,
        weekStart: app.settings.weekStart
    }).on('monthUpdate', titleRemoveYear);
</script>
@stop